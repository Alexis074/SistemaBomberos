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

// Obtener salida
$stmt = $pdo->prepare("SELECT s.*, m.codigo as movil_codigo, m.nombre as movil_nombre, m.tipo as movil_tipo,
                               ts.codigo as servicio_codigo, ts.descripcion as servicio_descripcion,
                               u.nombre as creado_por
                       FROM salidas s
                       INNER JOIN moviles m ON s.movil_id = m.id
                       INNER JOIN tipos_servicio ts ON s.tipo_servicio_id = ts.id
                       LEFT JOIN usuarios u ON s.created_by = u.id
                       WHERE s.id = ?");
$stmt->execute(array($id));
$salida = $stmt->fetch();

if (!$salida) {
    $_SESSION['mensaje'] = 'Salida no encontrada';
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: listar.php');
    exit;
}

// Calcular kilómetros recorridos
$km_recorridos = null;
if ($salida['kilometraje_regreso']) {
    $km_recorridos = $salida['kilometraje_regreso'] - $salida['kilometraje_salida'];
}

include $base_path . 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-eye"></i> Detalle de Salida</h1>
        <p>Información completa de la salida</p>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Información de la Salida</h3>
            <a href="listar.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
        <div class="card-body">
            <div class="info-section">
                <h4>Móvil</h4>
                <p><strong>Código:</strong> <?php echo htmlspecialchars($salida['movil_codigo']); ?></p>
                <p><strong>Nombre:</strong> <?php echo htmlspecialchars($salida['movil_nombre']); ?></p>
                <p><strong>Tipo:</strong> <?php echo htmlspecialchars($salida['movil_tipo']); ?></p>
            </div>

            <div class="info-section">
                <h4>Tipo de Servicio</h4>
                <p><strong>Código:</strong> <span class="badge badge-info"><?php echo htmlspecialchars($salida['servicio_codigo']); ?></span></p>
                <?php if ($salida['servicio_descripcion']): ?>
                    <p><strong>Descripción:</strong> <?php echo htmlspecialchars($salida['servicio_descripcion']); ?></p>
                <?php endif; ?>
            </div>

            <div class="info-section">
                <h4>Fechas y Horarios</h4>
                <p><strong>Fecha/Hora de Salida:</strong> <?php echo date('d/m/Y H:i', strtotime($salida['fecha_salida'])); ?></p>
                <?php if ($salida['fecha_regreso']): ?>
                    <p><strong>Fecha/Hora de Regreso:</strong> <?php echo date('d/m/Y H:i', strtotime($salida['fecha_regreso'])); ?></p>
                    <?php
                    $timestamp_salida = strtotime($salida['fecha_salida']);
                    $timestamp_regreso = strtotime($salida['fecha_regreso']);
                    $duracion_segundos = $timestamp_regreso - $timestamp_salida;
                    $horas = floor($duracion_segundos / 3600);
                    $minutos = floor(($duracion_segundos % 3600) / 60);
                    ?>
                    <p><strong>Duración del Servicio:</strong> <?php echo $horas . ' horas y ' . $minutos . ' minutos'; ?></p>
                <?php else: ?>
                    <p><strong>Estado:</strong> <span class="badge badge-warning">En servicio</span></p>
                <?php endif; ?>
            </div>

            <div class="info-section">
                <h4>Kilometraje</h4>
                <?php
                // Calcular el kilometraje de salida si es 0 o no está definido
                $km_salida_calculado = $salida['kilometraje_salida'];
                if ($km_salida_calculado == 0 || !$km_salida_calculado) {
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
                        $km_salida_calculado = (int)$ultimo_km['kilometraje_regreso'];
                    }
                }
                ?>
                <p><strong>Kilometraje de Salida:</strong> 
                    <strong><?php echo number_format($km_salida_calculado, 0, ',', '.'); ?> km</strong>
                    <?php if ($km_salida_calculado != $salida['kilometraje_salida']): ?>
                        <small class="text-muted">(calculado automáticamente)</small>
                    <?php endif; ?>
                </p>
                <?php if ($salida['kilometraje_regreso']): ?>
                    <p><strong>Kilometraje de Regreso:</strong> <?php echo number_format($salida['kilometraje_regreso'], 0, ',', '.'); ?> km</p>
                    <?php
                    $km_recorridos = $salida['kilometraje_regreso'] - $km_salida_calculado;
                    ?>
                    <p><strong>Kilómetros Recorridos:</strong> <strong><?php echo number_format($km_recorridos, 0, ',', '.'); ?> km</strong></p>
                <?php else: ?>
                    <p><strong>Kilometraje de Regreso:</strong> <span class="badge badge-warning">Pendiente</span></p>
                <?php endif; ?>
            </div>

            <?php if ($salida['conductor']): ?>
                <div class="info-section">
                    <h4>Conductor</h4>
                    <p><?php echo htmlspecialchars($salida['conductor']); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($salida['observaciones']): ?>
                <div class="info-section">
                    <h4>Observaciones</h4>
                    <p><?php echo nl2br(htmlspecialchars($salida['observaciones'])); ?></p>
                </div>
            <?php endif; ?>

            <div class="info-section">
                <h4>Información del Registro</h4>
                <p><strong>Registrado por:</strong> <?php echo htmlspecialchars($salida['creado_por'] ? $salida['creado_por'] : 'Sistema'); ?></p>
                <p><strong>Fecha de Registro:</strong> <?php echo date('d/m/Y H:i', strtotime($salida['created_at'])); ?></p>
            </div>
        </div>
    </div>
</div>

<style>
.info-section {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e0e0e0;
}
.info-section:last-child {
    border-bottom: none;
}
.info-section h4 {
    margin-bottom: 15px;
    color: #333;
}
.info-section p {
    margin: 8px 0;
}
</style>

<?php include $base_path . 'includes/footer.php'; ?>

