<?php
date_default_timezone_set('America/Asuncion');
if (!function_exists('getBasePath')) {
    include __DIR__ . '/../../includes/config.php';
}
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();

if (!tieneRol('administrador') && !tieneRol('encargado')) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta página';
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: ' . $base_url . 'index.php');
    exit;
}

// Obtener móviles
$stmt_moviles = $pdo->query("SELECT * FROM moviles WHERE activo = 1 ORDER BY codigo");
$moviles = $stmt_moviles->fetchAll();

// Obtener tipos de servicio
$stmt_tipos = $pdo->query("SELECT * FROM tipos_servicio WHERE activo = 1 ORDER BY codigo");
$tipos_servicio = $stmt_tipos->fetchAll();

// Obtener usuario actual
$usuario_id = obtenerUsuarioId();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $movil_id = isset($_POST['movil_id']) ? (int)$_POST['movil_id'] : 0;
    $tipo_servicio_id = isset($_POST['tipo_servicio_id']) ? (int)$_POST['tipo_servicio_id'] : 0;
    $fecha_salida = isset($_POST['fecha_salida']) ? trim($_POST['fecha_salida']) : '';
    $hora_salida = isset($_POST['hora_salida']) ? trim($_POST['hora_salida']) : '';
    $hora_regreso = isset($_POST['hora_regreso']) ? trim($_POST['hora_regreso']) : '';
    $kilometraje_regreso = isset($_POST['kilometraje_regreso']) ? (trim($_POST['kilometraje_regreso']) != '' ? (int)$_POST['kilometraje_regreso'] : null) : null;
    $conductor = isset($_POST['conductor']) ? trim($_POST['conductor']) : '';
    $observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
    
    if (empty($movil_id) || empty($tipo_servicio_id) || empty($fecha_salida) || empty($hora_salida)) {
        $error = 'Por favor complete todos los campos obligatorios';
    } else {
        // Calcular automáticamente el kilometraje de salida (último kilometraje de regreso del móvil)
        $kilometraje_salida = 0;
        $stmt_km = $pdo->prepare("
            SELECT kilometraje_regreso 
            FROM salidas 
            WHERE movil_id = ? AND kilometraje_regreso IS NOT NULL 
            ORDER BY fecha_regreso DESC, id DESC 
            LIMIT 1
        ");
        $stmt_km->execute(array($movil_id));
        $ultimo_km = $stmt_km->fetch();
        if ($ultimo_km && $ultimo_km['kilometraje_regreso']) {
            $kilometraje_salida = (int)$ultimo_km['kilometraje_regreso'];
        } else {
            // Si no hay registros previos, buscar si hay algún kilometraje inicial configurado
            // Por ahora, si no hay registro previo, el kilometraje de salida será 0
            // y se deberá ingresar manualmente el kilometraje de regreso
        }
        
        $fecha_salida_completa = $fecha_salida . ' ' . $hora_salida . ':00';
        $fecha_regreso_completa = null;
        // Si hay hora de regreso, la fecha de regreso es la misma que la de salida
        if ($hora_regreso) {
            $fecha_regreso_completa = $fecha_salida . ' ' . $hora_regreso . ':00';
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO salidas (movil_id, tipo_servicio_id, fecha_salida, fecha_regreso, 
                                kilometraje_salida, kilometraje_regreso, conductor, observaciones, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if ($stmt->execute(array($movil_id, $tipo_servicio_id, $fecha_salida_completa, $fecha_regreso_completa, 
                                 $kilometraje_salida, $kilometraje_regreso, $conductor, $observaciones, $usuario_id))) {
            $_SESSION['mensaje'] = 'Salida registrada correctamente';
            $_SESSION['tipo_mensaje'] = 'success';
            header('Location: listar.php');
            exit;
        } else {
            $error = 'Error al registrar la salida';
        }
    }
}

include $base_path . 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-plus"></i> Registrar Salida</h1>
        <p>Registrar una nueva salida de servicio</p>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label for="movil_id">Móvil *</label>
                        <select id="movil_id" name="movil_id" class="form-control" required>
                            <option value="">Seleccione un móvil</option>
                            <?php foreach ($moviles as $movil): ?>
                                <option value="<?php echo $movil['id']; ?>" <?php echo (isset($_POST['movil_id']) && $_POST['movil_id'] == $movil['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($movil['codigo'] . ' - ' . $movil['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label for="tipo_servicio_id">Tipo de Servicio *</label>
                        <select id="tipo_servicio_id" name="tipo_servicio_id" class="form-control" required>
                            <option value="">Seleccione un tipo de servicio</option>
                            <?php foreach ($tipos_servicio as $tipo): ?>
                                <option value="<?php echo $tipo['id']; ?>" <?php echo (isset($_POST['tipo_servicio_id']) && $_POST['tipo_servicio_id'] == $tipo['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($tipo['codigo'] . ($tipo['descripcion'] ? ' - ' . $tipo['descripcion'] : '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label for="fecha_salida">Fecha de Salida *</label>
                        <input type="date" id="fecha_salida" name="fecha_salida" class="form-control" 
                               value="<?php echo isset($_POST['fecha_salida']) ? htmlspecialchars($_POST['fecha_salida']) : date('Y-m-d'); ?>" 
                               required>
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label for="hora_salida">Hora de Salida *</label>
                        <input type="time" id="hora_salida" name="hora_salida" class="form-control" 
                               value="<?php echo isset($_POST['hora_salida']) ? htmlspecialchars($_POST['hora_salida']) : date('H:i'); ?>" 
                               required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="hora_regreso">Hora de Regreso</label>
                    <input type="time" id="hora_regreso" name="hora_regreso" class="form-control" 
                           value="<?php echo isset($_POST['hora_regreso']) ? htmlspecialchars($_POST['hora_regreso']) : ''; ?>">
                    <small>La fecha de regreso será la misma que la fecha de salida</small>
                </div>

                <div class="form-group">
                    <label for="kilometraje_regreso">Kilometraje de Regreso</label>
                    <input type="number" id="kilometraje_regreso" name="kilometraje_regreso" class="form-control" 
                           value="<?php echo isset($_POST['kilometraje_regreso']) && $_POST['kilometraje_regreso'] != '' ? (int)$_POST['kilometraje_regreso'] : ''; ?>" 
                           min="0">
                    <small>El kilometraje de salida se calculará automáticamente con el último kilometraje de regreso del móvil</small>
                </div>

                <div class="alert alert-info" id="km_info" style="display: none; margin-top: 10px;">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Kilometraje de salida calculado:</strong> <span id="km_salida_calc">0</span> km
                    <small>(Basado en el último kilometraje de regreso de este móvil)</small>
                </div>

                <div class="form-group">
                    <label for="conductor">Conductor</label>
                    <input type="text" id="conductor" name="conductor" class="form-control" 
                           value="<?php echo isset($_POST['conductor']) ? htmlspecialchars($_POST['conductor']) : ''; ?>" 
                           placeholder="Nombre del conductor">
                </div>

                <div class="form-group">
                    <label for="observaciones">Observaciones</label>
                    <textarea id="observaciones" name="observaciones" class="form-control" rows="4"><?php echo isset($_POST['observaciones']) ? htmlspecialchars($_POST['observaciones']) : ''; ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <a href="listar.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const movilSelect = document.getElementById('movil_id');
    const kmInfo = document.getElementById('km_info');
    const kmSalidaCalc = document.getElementById('km_salida_calc');
    
    function cargarKilometraje() {
        const movilId = movilSelect.value;
        if (movilId) {
            fetch('get_ultimo_kilometraje.php?movil_id=' + movilId)
                .then(response => response.json())
                .then(data => {
                    if (data.kilometraje > 0) {
                        kmSalidaCalc.textContent = data.kilometraje.toLocaleString('es-PY');
                        kmInfo.style.display = 'block';
                    } else {
                        kmInfo.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    kmInfo.style.display = 'none';
                });
        } else {
            kmInfo.style.display = 'none';
        }
    }
    
    movilSelect.addEventListener('change', cargarKilometraje);
    
    // Cargar kilometraje si ya hay un móvil seleccionado
    if (movilSelect.value) {
        cargarKilometraje();
    }
});
</script>

<?php include $base_path . 'includes/footer.php'; ?>

