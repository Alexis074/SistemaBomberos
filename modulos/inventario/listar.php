<?php
date_default_timezone_set('America/Asuncion');
if (!function_exists('getBasePath')) {
    include __DIR__ . '/../../includes/config.php';
}
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();

// Obtener móvil seleccionado
$movil_id = isset($_GET['movil_id']) ? (int)$_GET['movil_id'] : 0;

// Obtener todos los móviles
$stmt_moviles = $pdo->query("SELECT * FROM moviles WHERE activo = 1 ORDER BY codigo");
$moviles = $stmt_moviles->fetchAll();

// Obtener inventario del móvil seleccionado
$inventario = [];
$movil_seleccionado = null;
if ($movil_id > 0) {
    $stmt_movil = $pdo->prepare("SELECT * FROM moviles WHERE id = ?");
    $stmt_movil->execute(array($movil_id));
    $movil_seleccionado = $stmt_movil->fetch();
    
    if ($movil_seleccionado) {
        $stmt = $pdo->prepare("
            SELECT * FROM inventario 
            WHERE movil_id = ? 
            ORDER BY tipo, nombre
        ");
        $stmt->execute(array($movil_id));
        $inventario = $stmt->fetchAll();
    }
}

// Procesar eliminación
if (isset($_POST['eliminar']) && isset($_POST['id'])) {
    if (tieneRol('administrador') || tieneRol('encargado')) {
        $id_eliminar = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM inventario WHERE id = ?");
        if ($stmt->execute(array($id_eliminar))) {
            $_SESSION['mensaje'] = 'Item de inventario eliminado correctamente';
            $_SESSION['tipo_mensaje'] = 'success';
            header('Location: ' . $_SERVER['PHP_SELF'] . '?movil_id=' . $movil_id);
            exit;
        } else {
            $_SESSION['mensaje'] = 'Error al eliminar el item de inventario';
            $_SESSION['tipo_mensaje'] = 'error';
        }
    }
}

include $base_path . 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-boxes"></i> Inventario de Móviles</h1>
        <p>Gestión de equipamientos e insumos por móvil</p>
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

    <!-- Selector de móvil -->
    <div class="card" style="margin-bottom: 20px;">
        <div class="card-header">
            <h3>Seleccionar Móvil</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <div class="form-group">
                    <label for="movil_id">Móvil:</label>
                    <select name="movil_id" id="movil_id" class="form-control" onchange="this.form.submit()">
                        <option value="0">Seleccione un móvil</option>
                        <?php foreach ($moviles as $movil): ?>
                            <option value="<?php echo $movil['id']; ?>" <?php echo ($movil_id == $movil['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($movil['codigo'] . ' - ' . $movil['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <?php if ($movil_seleccionado): ?>
        <div class="card">
            <div class="card-header">
                <h3>
                    <i class="fas fa-truck"></i> 
                    Inventario: <?php echo htmlspecialchars($movil_seleccionado['codigo'] . ' - ' . $movil_seleccionado['nombre']); ?>
                </h3>
                <?php if (tieneRol('administrador') || tieneRol('encargado')): ?>
                    <a href="agregar.php?movil_id=<?php echo $movil_id; ?>" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Agregar Item
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (count($inventario) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th>Cantidad</th>
                                <th>Unidad</th>
                                <th>Estado</th>
                                <th>Ubicación</th>
                                <th>Descripción</th>
                                <?php if (tieneRol('administrador') || tieneRol('encargado')): ?>
                                    <th>Acciones</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventario as $item): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($item['nombre']); ?></strong></td>
                                    <td>
                                        <span class="badge <?php echo $item['tipo'] == 'equipamiento' ? 'badge-info' : 'badge-warning'; ?>">
                                            <?php echo ucfirst($item['tipo']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $item['cantidad']; ?></td>
                                    <td><?php echo htmlspecialchars($item['unidad'] ? $item['unidad'] : '-'); ?></td>
                                    <td>
                                        <span class="badge badge-secondary">
                                            <?php echo htmlspecialchars($item['estado']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['ubicacion'] ? $item['ubicacion'] : '-'); ?></td>
                                    <td><?php echo htmlspecialchars($item['descripcion'] ? substr($item['descripcion'], 0, 50) : '-'); ?></td>
                                    <?php if (tieneRol('administrador') || tieneRol('encargado')): ?>
                                        <td>
                                            <a href="editar.php?id=<?php echo $item['id']; ?>&movil_id=<?php echo $movil_id; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('¿Está seguro de eliminar este item?');">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <input type="hidden" name="eliminar" value="1">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i> Eliminar
                                                </button>
                                            </form>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <h3>No hay items en el inventario</h3>
                        <p>Agregue items al inventario de este móvil</p>
                        <?php if (tieneRol('administrador') || tieneRol('encargado')): ?>
                            <a href="agregar.php?movil_id=<?php echo $movil_id; ?>" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Agregar Primer Item
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body">
                <div class="empty-state">
                    <i class="fas fa-truck"></i>
                    <h3>Seleccione un móvil</h3>
                    <p>Por favor, seleccione un móvil para ver su inventario</p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include $base_path . 'includes/footer.php'; ?>

