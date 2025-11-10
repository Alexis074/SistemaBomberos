<?php
date_default_timezone_set('America/Asuncion');
if (!function_exists('getBasePath')) {
    include __DIR__ . '/../../includes/config.php';
}
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirAdministrador();

$mensaje = '';
$tipo_mensaje = '';

if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    $tipo_mensaje = isset($_SESSION['tipo_mensaje']) ? $_SESSION['tipo_mensaje'] : 'success';
    unset($_SESSION['mensaje']);
    unset($_SESSION['tipo_mensaje']);
}

// Consulta de usuarios
$stmt = $pdo->query("
    SELECT u.*, b.nombre as bombero_nombre, b.apellido as bombero_apellido
    FROM usuarios u
    LEFT JOIN bomberos b ON u.bombero_id = b.id
    ORDER BY u.nombre
");
$usuarios = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios - Sistema de Control de Asistencias</title>
</head>
<body>
    <?php include $base_path . 'includes/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-user-cog"></i> Gestión de Usuarios</h1>
            <p>Administración de usuarios del sistema</p>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <i class="fas fa-<?php echo $tipo_mensaje == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="d-flex gap-20" style="justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap;">
            <div>
                <a href="<?php echo $base_url; ?>modulos/usuarios/agregar.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Agregar Usuario
                </a>
            </div>
        </div>

        <!-- Tabla de usuarios -->
        <?php if (count($usuarios) > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Nombre</th>
                            <th>Código de Juramento</th>
                            <th>Bombero Asociado</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars(isset($usuario['usuario']) ? $usuario['usuario'] : '-'); ?></strong></td>
                                <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                                <td><?php echo htmlspecialchars(isset($usuario['codigo_juramento']) ? $usuario['codigo_juramento'] : '-'); ?></td>
                                <td>
                                    <?php 
                                    if ($usuario['bombero_nombre']) {
                                        echo htmlspecialchars($usuario['bombero_apellido'] . ', ' . $usuario['bombero_nombre']);
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo ucfirst($usuario['rol']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $usuario['activo'] ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <a href="<?php echo $base_url; ?>modulos/usuarios/editar.php?id=<?php echo $usuario['id']; ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <?php if ($usuario['id'] != obtenerUsuarioId()): ?>
                                        <a href="<?php echo $base_url; ?>modulos/usuarios/eliminar.php?id=<?php echo $usuario['id']; ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('¿Está seguro de eliminar este usuario?');">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-users-slash"></i>
                <h3>No se encontraron usuarios</h3>
                <p>No hay usuarios registrados en el sistema</p>
                <a href="<?php echo $base_url; ?>modulos/usuarios/agregar.php" class="btn btn-primary mt-20">
                    <i class="fas fa-plus"></i> Agregar Primer Usuario
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php include $base_path . 'includes/footer.php'; ?>
</body>
</html>

