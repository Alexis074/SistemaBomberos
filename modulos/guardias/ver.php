<?php
date_default_timezone_set('America/Asuncion');
if (!function_exists('getBasePath')) {
    include __DIR__ . '/../../includes/config.php';
}
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id == 0) {
    header('Location: ' . $base_url . 'modulos/guardias/listar.php');
    exit();
}

// Obtener datos de la guardia
$stmt = $pdo->prepare("
    SELECT g.*, gr.nombre as grupo_nombre, gr.dia_guardia
    FROM guardias g
    INNER JOIN grupos gr ON g.grupo_id = gr.id
    WHERE g.id = ?
");
$stmt->execute(array($id));
$guardia = $stmt->fetch();

if (!$guardia) {
    header('Location: ' . $base_url . 'modulos/guardias/listar.php');
    exit();
}

// Obtener asistencias
$stmt = $pdo->prepare("
    SELECT a.*, b.nombre, b.apellido, b.codigo_juramento,
           br.nombre as reemplazado_nombre, br.apellido as reemplazado_apellido
    FROM asistencias a
    INNER JOIN bomberos b ON a.bombero_id = b.id
    LEFT JOIN bomberos br ON a.bombero_reemplazado_id = br.id
    WHERE a.guardia_id = ?
    ORDER BY b.apellido, b.nombre
");
$stmt->execute(array($id));
$asistencias = $stmt->fetchAll();

// Obtener observaciones
$stmt = $pdo->prepare("
    SELECT o.*, u.nombre as usuario_nombre
    FROM observaciones o
    LEFT JOIN usuarios u ON o.created_by = u.id
    WHERE o.fecha = ? AND o.grupo_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute(array($guardia['fecha'], $guardia['grupo_id']));
$observaciones = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Guardia - Sistema de Control de Asistencias</title>
</head>
<body>
    <?php include $base_path . 'includes/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-eye"></i> Detalle de Guardia</h1>
            <p>Información completa de la guardia registrada</p>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-info-circle"></i>
                Información de la Guardia
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div>
                    <strong>Fecha:</strong><br>
                    <?php echo date('d/m/Y', strtotime($guardia['fecha'])); ?>
                </div>
                <div>
                    <strong>Grupo:</strong><br>
                    <?php echo htmlspecialchars($guardia['grupo_nombre']); ?>
                </div>
                <div>
                    <strong>Día de Guardia:</strong><br>
                    <?php echo htmlspecialchars($guardia['dia_guardia']); ?>
                </div>
                <div>
                    <strong>Tipo:</strong><br>
                    <span class="badge <?php echo $guardia['tipo'] == 'diurna' ? 'badge-warning' : 'badge-info'; ?>">
                        <?php echo ucfirst($guardia['tipo']); ?>
                    </span>
                </div>
                <div>
                    <strong>Turno:</strong><br>
                    <?php 
                    if ($guardia['tipo'] == 'diurna' && !empty($guardia['turno'])) {
                        echo 'Turno ' . $guardia['turno'];
                    } else {
                        echo 'Nocturno (19:00 - 07:00)';
                    }
                    ?>
                </div>
                <div>
                    <strong>Total de Asistencias:</strong><br>
                    <?php echo count($asistencias); ?> bomberos
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-users"></i>
                Bomberos Asistentes
            </div>
            <?php if (count($asistencias) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre Completo</th>
                                <th>Código de Juramento</th>
                                <th>Tipo</th>
                                <th>Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($asistencias as $asistencia): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($asistencia['apellido'] . ', ' . $asistencia['nombre']); ?></strong>
                                        <?php if ($asistencia['es_reemplazo']): ?>
                                            <br><small class="badge badge-warning">
                                                Reemplaza a: <?php echo htmlspecialchars($asistencia['reemplazado_apellido'] . ', ' . $asistencia['reemplazado_nombre']); ?>
                                            </small>
                                        <?php endif; ?>
                                        <?php if ($asistencia['es_apoyo']): ?>
                                            <br><small class="badge badge-info">Apoyo</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($asistencia['codigo_juramento']); ?></td>
                                    <td>
                                        <?php if ($asistencia['es_reemplazo']): ?>
                                            <span class="badge badge-warning">Reemplazo</span>
                                        <?php elseif ($asistencia['es_apoyo']): ?>
                                            <span class="badge badge-info">Apoyo</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Regular</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($asistencia['observaciones'] ?: '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-users-slash"></i>
                    <h3>No hay asistencias registradas</h3>
                </div>
            <?php endif; ?>
        </div>

        <?php if (count($observaciones) > 0): ?>
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-comment-alt"></i>
                    Observaciones
                </div>
                <?php foreach ($observaciones as $obs): ?>
                    <div style="padding: 15px; background: #f8fafc; border-radius: 8px; margin-bottom: 10px;">
                        <strong><?php echo htmlspecialchars($obs['observacion']); ?></strong>
                        <br><small style="color: #64748b;">
                            Por: <?php echo htmlspecialchars(isset($obs['usuario_nombre']) ? $obs['usuario_nombre'] : 'Sistema'); ?> 
                            - <?php echo date('d/m/Y H:i', strtotime($obs['created_at'])); ?>
                        </small>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div style="margin-top: 20px;">
            <a href="<?php echo $base_url; ?>modulos/guardias/listar.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <?php include $base_path . 'includes/footer.php'; ?>
</body>
</html>

