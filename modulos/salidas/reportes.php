<?php
date_default_timezone_set('America/Asuncion');
if (!function_exists('getBasePath')) {
    include __DIR__ . '/../../includes/config.php';
}
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();

// Filtros
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : date('Y-m-01');
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : date('Y-m-t');
$movil_filtro = isset($_GET['movil_id']) ? (int)$_GET['movil_id'] : 0;
$tipo_servicio_filtro = isset($_GET['tipo_servicio_id']) ? (int)$_GET['tipo_servicio_id'] : 0;

// Consulta de salidas con kilometraje de salida calculado
$sql = "SELECT s.*, m.codigo as movil_codigo, m.nombre as movil_nombre, 
               ts.codigo as servicio_codigo, ts.descripcion as servicio_descripcion,
               u.nombre as creado_por
        FROM salidas s
        INNER JOIN moviles m ON s.movil_id = m.id
        INNER JOIN tipos_servicio ts ON s.tipo_servicio_id = ts.id
        LEFT JOIN usuarios u ON s.created_by = u.id
        WHERE DATE(s.fecha_salida) BETWEEN ? AND ?";

$params = [$fecha_desde, $fecha_hasta];

if ($movil_filtro > 0) {
    $sql .= " AND s.movil_id = ?";
    $params[] = $movil_filtro;
}

if ($tipo_servicio_filtro > 0) {
    $sql .= " AND s.tipo_servicio_id = ?";
    $params[] = $tipo_servicio_filtro;
}

$sql .= " ORDER BY s.fecha_salida DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$salidas = $stmt->fetchAll();

// Calcular el kilometraje de salida para cada salida basado en el último regreso anterior
foreach ($salidas as $index => &$salida) {
    // Si el kilometraje de salida es 0 o no está definido, calcularlo
    if ($salida['kilometraje_salida'] == 0 || !$salida['kilometraje_salida']) {
        $stmt_km = $pdo->prepare("
            SELECT kilometraje_regreso 
            FROM salidas 
            WHERE movil_id = ? 
            AND kilometraje_regreso IS NOT NULL 
            AND (fecha_regreso < ? OR (fecha_regreso = ? AND id < ?))
            ORDER BY fecha_regreso DESC, id DESC 
            LIMIT 1
        ");
        $stmt_km->execute(array(
            $salida['movil_id'], 
            $salida['fecha_salida'], 
            $salida['fecha_salida'], 
            $salida['id']
        ));
        $ultimo_km = $stmt_km->fetch();
        if ($ultimo_km && $ultimo_km['kilometraje_regreso']) {
            $salida['kilometraje_salida_calculado'] = (int)$ultimo_km['kilometraje_regreso'];
        } else {
            $salida['kilometraje_salida_calculado'] = $salida['kilometraje_salida'];
        }
    } else {
        $salida['kilometraje_salida_calculado'] = $salida['kilometraje_salida'];
    }
}
unset($salida); // Liberar referencia

// Obtener móviles para el filtro
$stmt_moviles = $pdo->query("SELECT * FROM moviles WHERE activo = 1 ORDER BY codigo");
$moviles = $stmt_moviles->fetchAll();

// Obtener tipos de servicio para el filtro
$stmt_tipos = $pdo->query("SELECT * FROM tipos_servicio WHERE activo = 1 ORDER BY codigo");
$tipos_servicio = $stmt_tipos->fetchAll();

// Calcular estadísticas
$total_salidas = count($salidas);
$total_km_recorridos = 0;
foreach ($salidas as $salida) {
    if ($salida['kilometraje_regreso'] && $salida['kilometraje_salida_calculado']) {
        $total_km_recorridos += ($salida['kilometraje_regreso'] - $salida['kilometraje_salida_calculado']);
    }
}

include $base_path . 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-chart-line"></i> Reportes de Salidas</h1>
        <p>Reporte detallado de salidas con kilometraje calculado</p>
    </div>

    <div class="card" style="margin-bottom: 20px;">
        <div class="card-header">
            <h3>Filtros</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label for="fecha_desde">Fecha Desde:</label>
                        <input type="date" id="fecha_desde" name="fecha_desde" class="form-control" 
                               value="<?php echo htmlspecialchars($fecha_desde); ?>">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="fecha_hasta">Fecha Hasta:</label>
                        <input type="date" id="fecha_hasta" name="fecha_hasta" class="form-control" 
                               value="<?php echo htmlspecialchars($fecha_hasta); ?>">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="movil_id">Móvil:</label>
                        <select id="movil_id" name="movil_id" class="form-control">
                            <option value="0">Todos los móviles</option>
                            <?php foreach ($moviles as $movil): ?>
                                <option value="<?php echo $movil['id']; ?>" <?php echo ($movil_filtro == $movil['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($movil['codigo'] . ' - ' . $movil['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="tipo_servicio_id">Tipo de Servicio:</label>
                        <select id="tipo_servicio_id" name="tipo_servicio_id" class="form-control">
                            <option value="0">Todos los servicios</option>
                            <?php foreach ($tipos_servicio as $tipo): ?>
                                <option value="<?php echo $tipo['id']; ?>" <?php echo ($tipo_servicio_filtro == $tipo['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($tipo['codigo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <a href="reportes.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="card" style="margin-bottom: 20px;">
        <div class="card-header">
            <h3>Estadísticas</h3>
        </div>
        <div class="card-body">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-label">Total de Salidas</div>
                    <div class="stat-value"><?php echo $total_salidas; ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Total de Kilómetros Recorridos</div>
                    <div class="stat-value"><?php echo number_format($total_km_recorridos, 0, ',', '.'); ?> km</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Reporte de Salidas</h3>
        </div>
        <div class="card-body">
            <?php if (count($salidas) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Fecha/Hora Salida</th>
                            <th>Fecha/Hora Regreso</th>
                            <th>Móvil</th>
                            <th>Tipo de Servicio</th>
                            <th>Km. Salida (Calculado)</th>
                            <th>Km. Regreso</th>
                            <th>Km. Recorridos</th>
                            <th>Conductor</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salidas as $salida): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($salida['fecha_salida'])); ?></td>
                                <td>
                                    <?php 
                                    if ($salida['fecha_regreso']) {
                                        echo date('d/m/Y H:i', strtotime($salida['fecha_regreso'])); 
                                    } else {
                                        echo '<span class="badge badge-warning">En servicio</span>';
                                    }
                                    ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($salida['movil_codigo']); ?></strong></td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo htmlspecialchars($salida['servicio_codigo']); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo number_format($salida['kilometraje_salida_calculado'], 0, ',', '.'); ?> km</strong>
                                    <?php if ($salida['kilometraje_salida_calculado'] != $salida['kilometraje_salida']): ?>
                                        <small class="text-muted">(calculado)</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($salida['kilometraje_regreso']) {
                                        echo number_format($salida['kilometraje_regreso'], 0, ',', '.') . ' km';
                                    } else {
                                        echo '<span class="badge badge-warning">Pendiente</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($salida['kilometraje_regreso'] && $salida['kilometraje_salida_calculado']) {
                                        $km_recorridos = $salida['kilometraje_regreso'] - $salida['kilometraje_salida_calculado'];
                                        echo '<strong>' . number_format($km_recorridos, 0, ',', '.') . ' km</strong>';
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($salida['conductor'] ? $salida['conductor'] : '-'); ?></td>
                                <td>
                                    <a href="ver.php?id=<?php echo $salida['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-chart-line"></i>
                    <h3>No hay salidas registradas</h3>
                    <p>No se encontraron salidas para los filtros seleccionados</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}
.stat-item {
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}
.stat-label {
    font-size: 14px;
    color: #666;
    margin-bottom: 10px;
}
.stat-value {
    font-size: 32px;
    font-weight: bold;
    color: #333;
}
</style>

<?php include $base_path . 'includes/footer.php'; ?>

