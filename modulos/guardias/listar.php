<?php
date_default_timezone_set('America/Asuncion');
if (!function_exists('getBasePath')) {
    include __DIR__ . '/../../includes/config.php';
}
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();

$mensaje = '';
$tipo_mensaje = '';

if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    $tipo_mensaje = isset($_SESSION['tipo_mensaje']) ? $_SESSION['tipo_mensaje'] : 'success';
    unset($_SESSION['mensaje']);
    unset($_SESSION['tipo_mensaje']);
}

// Filtros
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : date('Y-m-01');
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : date('Y-m-t');
$grupo_filtro = isset($_GET['grupo']) ? (int)$_GET['grupo'] : 0;
$tipo_filtro = isset($_GET['tipo']) ? $_GET['tipo'] : '';

// Consulta de guardias
$sql = "SELECT g.*, gr.nombre as grupo_nombre, gr.dia_guardia,
        (SELECT COUNT(*) FROM asistencias WHERE guardia_id = g.id) as total_asistencias
        FROM guardias g
        INNER JOIN grupos gr ON g.grupo_id = gr.id
        WHERE g.fecha BETWEEN ? AND ?";

$params = [$fecha_desde, $fecha_hasta];

if ($grupo_filtro > 0) {
    $sql .= " AND g.grupo_id = ?";
    $params[] = $grupo_filtro;
}

if ($tipo_filtro != '') {
    $sql .= " AND g.tipo = ?";
    $params[] = $tipo_filtro;
}

$sql .= " ORDER BY g.fecha DESC, gr.nombre";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$guardias = $stmt->fetchAll();

// Obtener grupos para el filtro
$stmt_grupos = $pdo->query("SELECT * FROM grupos ORDER BY nombre");
$grupos = $stmt_grupos->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listar Guardias - Sistema de Control de Asistencias</title>
</head>
<body>
    <?php include $base_path . 'includes/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-calendar-check"></i> Listar Guardias</h1>
            <p>Consulta y gestión de guardias registradas</p>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <i class="fas fa-<?php echo $tipo_mensaje == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="d-flex gap-20" style="justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap;">
            <div>
                <a href="<?php echo $base_url; ?>modulos/guardias/registrar.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Registrar Guardia
                </a>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters">
            <div class="filter-group">
                <label>Fecha Desde:</label>
                <input type="date" name="fecha_desde" value="<?php echo $fecha_desde; ?>" 
                       onchange="this.form.submit()" form="filterForm">
            </div>
            <div class="filter-group">
                <label>Fecha Hasta:</label>
                <input type="date" name="fecha_hasta" value="<?php echo $fecha_hasta; ?>" 
                       onchange="this.form.submit()" form="filterForm">
            </div>
            <div class="filter-group">
                <label>Grupo:</label>
                <select name="grupo" onchange="document.getElementById('filterForm').submit()" form="filterForm">
                    <option value="0">Todos los grupos</option>
                    <?php foreach ($grupos as $grupo): ?>
                        <option value="<?php echo $grupo['id']; ?>" <?php echo $grupo_filtro == $grupo['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($grupo['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Tipo:</label>
                <select name="tipo" onchange="document.getElementById('filterForm').submit()" form="filterForm">
                    <option value="">Todos</option>
                    <option value="diurna" <?php echo $tipo_filtro == 'diurna' ? 'selected' : ''; ?>>Diurna</option>
                    <option value="nocturna" <?php echo $tipo_filtro == 'nocturna' ? 'selected' : ''; ?>>Nocturna</option>
                </select>
            </div>
        </div>

        <form method="GET" id="filterForm" style="display: none;"></form>

        <!-- Tabla de guardias -->
        <?php if (count($guardias) > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Grupo</th>
                            <th>Día</th>
                            <th>Tipo</th>
                            <th>Turno</th>
                            <th>Asistencias</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($guardias as $guardia): ?>
                            <tr>
                                <td><strong><?php echo date('d/m/Y', strtotime($guardia['fecha'])); ?></strong></td>
                                <td><?php echo htmlspecialchars($guardia['grupo_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($guardia['dia_guardia']); ?></td>
                                <td>
                                    <span class="badge <?php echo $guardia['tipo'] == 'diurna' ? 'badge-warning' : 'badge-info'; ?>">
                                        <?php echo ucfirst($guardia['tipo']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if ($guardia['tipo'] == 'diurna' && !empty($guardia['turno'])) {
                                        echo 'Turno ' . $guardia['turno'];
                                    } else {
                                        echo 'Nocturno';
                                    }
                                    ?>
                                </td>
                                <td><?php echo $guardia['total_asistencias']; ?> bomberos</td>
                                <td class="actions">
                                    <a href="<?php echo $base_url; ?>modulos/guardias/ver.php?id=<?php echo $guardia['id']; ?>" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h3>No se encontraron guardias</h3>
                <p>No hay guardias registradas con los filtros seleccionados</p>
                <a href="<?php echo $base_url; ?>modulos/guardias/registrar.php" class="btn btn-primary mt-20">
                    <i class="fas fa-plus"></i> Registrar Primera Guardia
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php include $base_path . 'includes/footer.php'; ?>
</body>
</html>

