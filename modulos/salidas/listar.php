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

// Consulta de salidas
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

// Obtener móviles para el filtro
$stmt_moviles = $pdo->query("SELECT * FROM moviles WHERE activo = 1 ORDER BY codigo");
$moviles = $stmt_moviles->fetchAll();

// Obtener tipos de servicio para el filtro
$stmt_tipos = $pdo->query("SELECT * FROM tipos_servicio WHERE activo = 1 ORDER BY codigo");
$tipos_servicio = $stmt_tipos->fetchAll();

include $base_path . 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-route"></i> Registro de Salidas</h1>
        <p>Historial de salidas y servicios de los móviles</p>
    </div>

    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="alert alert-<?php echo isset($_SESSION['tipo_mensaje']) ? $_SESSION['tipo_mensaje'] : 'success'; ?>">
            <?php 
            echo htmlspecialchars($_SESSION['mensaje']); 
            unset($_SESSION['mensaje']);
            unset($_SESSION['tipo_mensaje']);
            ?>
        </div>
    <?php endif; ?>

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
                    <a href="listar.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Salidas Registradas</h3>
            <div>
                <a href="reportes.php" class="btn btn-info" style="margin-right: 10px;">
                    <i class="fas fa-chart-line"></i> Ver Reportes
                </a>
                <?php if (tieneRol('administrador') || tieneRol('encargado')): ?>
                    <a href="registrar.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Registrar Salida
                    </a>
                <?php endif; ?>
            </div>
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
                            <th>Kilometraje</th>
                            <th>Conductor</th>
                            <th>Registrado por</th>
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
                                    <?php 
                                    if ($salida['kilometraje_regreso']) {
                                        echo number_format($salida['kilometraje_regreso'], 0, ',', '.') . ' km';
                                        if ($salida['kilometraje_salida'] > 0) {
                                            $km_recorridos = $salida['kilometraje_regreso'] - $salida['kilometraje_salida'];
                                            echo ' <small>(Recorrido: ' . number_format($km_recorridos, 0, ',', '.') . ' km)</small>';
                                        }
                                    } else {
                                        echo '<span class="badge badge-warning">Pendiente</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($salida['conductor'] ? $salida['conductor'] : '-'); ?></td>
                                <td><?php echo htmlspecialchars($salida['creado_por'] ? $salida['creado_por'] : '-'); ?></td>
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
                    <i class="fas fa-route"></i>
                    <h3>No hay salidas registradas</h3>
                    <p>No se encontraron salidas para los filtros seleccionados</p>
                    <?php if (tieneRol('administrador') || tieneRol('encargado')): ?>
                        <a href="registrar.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Registrar Primera Salida
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include $base_path . 'includes/footer.php'; ?>

