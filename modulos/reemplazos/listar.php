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

// Consulta de reemplazos
$stmt = $pdo->prepare("
    SELECT a.*, 
           b.nombre as bombero_nombre, b.apellido as bombero_apellido, b.codigo_juramento as bombero_codigo,
           br.nombre as reemplazado_nombre, br.apellido as reemplazado_apellido, br.codigo_juramento as reemplazado_codigo,
           g.fecha, g.tipo, g.turno,
           gr.nombre as grupo_nombre
    FROM asistencias a
    INNER JOIN bomberos b ON a.bombero_id = b.id
    INNER JOIN bomberos br ON a.bombero_reemplazado_id = br.id
    INNER JOIN guardias g ON a.guardia_id = g.id
    INNER JOIN grupos gr ON g.grupo_id = gr.id
    WHERE a.es_reemplazo = 1 
    AND g.fecha BETWEEN ? AND ?
    ORDER BY g.fecha DESC
");
$stmt->execute(array($fecha_desde, $fecha_hasta));
$reemplazos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reemplazos - Sistema de Control de Asistencias</title>
</head>
<body>
    <?php include $base_path . 'includes/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-exchange-alt"></i> Reemplazos</h1>
            <p>Registro de reemplazos realizados en las guardias</p>
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
        </div>

        <form method="GET" id="filterForm" style="display: none;"></form>

        <!-- Tabla de reemplazos -->
        <?php if (count($reemplazos) > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Grupo</th>
                            <th>Tipo</th>
                            <th>Bombero Reemplazante</th>
                            <th>Bombero Reemplazado</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reemplazos as $reemplazo): ?>
                            <tr>
                                <td><strong><?php echo date('d/m/Y', strtotime($reemplazo['fecha'])); ?></strong></td>
                                <td><?php echo htmlspecialchars($reemplazo['grupo_nombre']); ?></td>
                                <td>
                                    <span class="badge <?php echo $reemplazo['tipo'] == 'diurna' ? 'badge-warning' : 'badge-info'; ?>">
                                        <?php echo ucfirst($reemplazo['tipo']); ?>
                                    </span>
                                    <?php if ($reemplazo['tipo'] == 'diurna'): ?>
                                        <br><small>Turno <?php echo $reemplazo['turno']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($reemplazo['bombero_apellido'] . ', ' . $reemplazo['bombero_nombre']); ?></strong>
                                    <br><small><?php echo htmlspecialchars($reemplazo['bombero_codigo']); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($reemplazo['reemplazado_apellido'] . ', ' . $reemplazo['reemplazado_nombre']); ?></strong>
                                    <br><small><?php echo htmlspecialchars($reemplazo['reemplazado_codigo']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($reemplazo['observaciones'] ?: '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 20px; padding: 15px; background: #f8fafc; border-radius: 8px;">
                <strong>Total de reemplazos: <?php echo count($reemplazos); ?></strong>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-exchange-alt"></i>
                <h3>No se encontraron reemplazos</h3>
                <p>No hay reemplazos registrados en el período seleccionado</p>
            </div>
        <?php endif; ?>
    </div>

    <?php include $base_path . 'includes/footer.php'; ?>
</body>
</html>

