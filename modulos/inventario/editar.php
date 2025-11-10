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

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$movil_id = isset($_GET['movil_id']) ? (int)$_GET['movil_id'] : 0;

// Obtener item
$stmt = $pdo->prepare("SELECT i.*, m.codigo as movil_codigo, m.nombre as movil_nombre 
                       FROM inventario i 
                       INNER JOIN moviles m ON i.movil_id = m.id 
                       WHERE i.id = ?");
$stmt->execute(array($id));
$item = $stmt->fetch();

if (!$item) {
    $_SESSION['mensaje'] = 'Item no encontrado';
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: listar.php?movil_id=' . $movil_id);
    exit;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';
    $cantidad = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 1;
    $unidad = isset($_POST['unidad']) ? trim($_POST['unidad']) : '';
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    $estado = isset($_POST['estado']) ? trim($_POST['estado']) : 'disponible';
    $ubicacion = isset($_POST['ubicacion']) ? trim($_POST['ubicacion']) : '';
    $observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
    
    if (empty($nombre)) {
        $error = 'El nombre es obligatorio';
    } else {
        $stmt = $pdo->prepare("
            UPDATE inventario 
            SET nombre = ?, tipo = ?, cantidad = ?, unidad = ?, descripcion = ?, 
                estado = ?, ubicacion = ?, observaciones = ?
            WHERE id = ?
        ");
        if ($stmt->execute(array($nombre, $tipo, $cantidad, $unidad, $descripcion, $estado, $ubicacion, $observaciones, $id))) {
            $_SESSION['mensaje'] = 'Item actualizado correctamente';
            $_SESSION['tipo_mensaje'] = 'success';
            header('Location: listar.php?movil_id=' . $item['movil_id']);
            exit;
        } else {
            $error = 'Error al actualizar el item';
        }
    }
}

include $base_path . 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-edit"></i> Editar Item del Inventario</h1>
        <p>Móvil: <?php echo htmlspecialchars($item['movil_codigo'] . ' - ' . $item['movil_nombre']); ?></p>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="nombre">Nombre del Item *</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" 
                           value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : htmlspecialchars($item['nombre']); ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="tipo">Tipo *</label>
                    <select id="tipo" name="tipo" class="form-control" required>
                        <option value="">Seleccione un tipo</option>
                        <option value="equipamiento" <?php echo ((isset($_POST['tipo']) && $_POST['tipo'] == 'equipamiento') || (!isset($_POST['tipo']) && $item['tipo'] == 'equipamiento')) ? 'selected' : ''; ?>>
                            Equipamiento
                        </option>
                        <option value="insumo" <?php echo ((isset($_POST['tipo']) && $_POST['tipo'] == 'insumo') || (!isset($_POST['tipo']) && $item['tipo'] == 'insumo')) ? 'selected' : ''; ?>>
                            Insumo
                        </option>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label for="cantidad">Cantidad *</label>
                        <input type="number" id="cantidad" name="cantidad" class="form-control" 
                               value="<?php echo isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : $item['cantidad']; ?>" 
                               min="1" required>
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label for="unidad">Unidad</label>
                        <input type="text" id="unidad" name="unidad" class="form-control" 
                               value="<?php echo isset($_POST['unidad']) ? htmlspecialchars($_POST['unidad']) : htmlspecialchars($item['unidad']); ?>" 
                               placeholder="Ej: unidades, litros, kg, etc.">
                    </div>
                </div>

                <div class="form-group">
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado" class="form-control">
                        <option value="disponible" <?php echo ((!isset($_POST['estado']) && $item['estado'] == 'disponible') || (isset($_POST['estado']) && $_POST['estado'] == 'disponible')) ? 'selected' : ''; ?>>
                            Disponible
                        </option>
                        <option value="en_uso" <?php echo ((!isset($_POST['estado']) && $item['estado'] == 'en_uso') || (isset($_POST['estado']) && $_POST['estado'] == 'en_uso')) ? 'selected' : ''; ?>>
                            En Uso
                        </option>
                        <option value="mantenimiento" <?php echo ((!isset($_POST['estado']) && $item['estado'] == 'mantenimiento') || (isset($_POST['estado']) && $_POST['estado'] == 'mantenimiento')) ? 'selected' : ''; ?>>
                            Mantenimiento
                        </option>
                        <option value="no_disponible" <?php echo ((!isset($_POST['estado']) && $item['estado'] == 'no_disponible') || (isset($_POST['estado']) && $_POST['estado'] == 'no_disponible')) ? 'selected' : ''; ?>>
                            No Disponible
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="ubicacion">Ubicación</label>
                    <input type="text" id="ubicacion" name="ubicacion" class="form-control" 
                           value="<?php echo isset($_POST['ubicacion']) ? htmlspecialchars($_POST['ubicacion']) : htmlspecialchars($item['ubicacion']); ?>" 
                           placeholder="Ej: Compartimento frontal, Estante 2, etc.">
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" class="form-control" rows="3"><?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : htmlspecialchars($item['descripcion']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="observaciones">Observaciones</label>
                    <textarea id="observaciones" name="observaciones" class="form-control" rows="3"><?php echo isset($_POST['observaciones']) ? htmlspecialchars($_POST['observaciones']) : htmlspecialchars($item['observaciones']); ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <a href="listar.php?movil_id=<?php echo $item['movil_id']; ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include $base_path . 'includes/footer.php'; ?>

