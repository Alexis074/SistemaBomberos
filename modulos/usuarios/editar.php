<?php
date_default_timezone_set('America/Asuncion');
if (!function_exists('getBasePath')) {
    include __DIR__ . '/../../includes/config.php';
}
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirAdministrador();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id == 0) {
    $_SESSION['mensaje'] = 'Usuario no encontrado.';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: ' . $base_url . 'modulos/usuarios/listar.php');
    exit();
}

// Obtener datos del usuario
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute(array($id));
$usuario = $stmt->fetch();

if (!$usuario) {
    $_SESSION['mensaje'] = 'Usuario no encontrado.';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: ' . $base_url . 'modulos/usuarios/listar.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_nuevo = trim(isset($_POST['usuario']) ? $_POST['usuario'] : '');
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $nombre = trim(isset($_POST['nombre']) ? $_POST['nombre'] : '');
    $codigo_juramento = trim(isset($_POST['codigo_juramento']) ? $_POST['codigo_juramento'] : '');
    $bombero_id = !empty($_POST['bombero_id']) ? (int)$_POST['bombero_id'] : null;
    $rol = isset($_POST['rol']) ? $_POST['rol'] : 'bombero';
    $activo = isset($_POST['activo']) ? 1 : 0;

    if (empty($nombre)) {
        $error = 'Por favor, complete todos los campos obligatorios.';
    } else {
        // Verificar que el usuario no exista en otro registro
        if (!empty($usuario_nuevo) && $usuario_nuevo != $usuario['usuario']) {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ? AND id != ?");
            $stmt->execute(array($usuario_nuevo, $id));
            if ($stmt->fetch()) {
                $error = 'El usuario ya existe.';
            }
        }

        // Verificar que el código de juramento no exista en otro registro
        if (empty($error) && !empty($codigo_juramento) && $codigo_juramento != $usuario['codigo_juramento']) {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE codigo_juramento = ? AND id != ?");
            $stmt->execute(array($codigo_juramento, $id));
            if ($stmt->fetch()) {
                $error = 'El código de juramento ya existe.';
            }
        }

        if (empty($error)) {
            try {
                if (!empty($password)) {
                    // Guardar contraseña en texto plano
                    $stmt = $pdo->prepare("
                        UPDATE usuarios 
                        SET usuario = ?, password = ?, nombre = ?, codigo_juramento = ?, bombero_id = ?, rol = ?, activo = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute(array(
                        $usuario_nuevo ?: null, 
                        $password, 
                        $nombre, 
                        $codigo_juramento ?: null, 
                        $bombero_id, 
                        $rol, 
                        $activo, 
                        $id
                    ));
                } else {
                    // Si no se proporciona nueva contraseña, mantener la actual
                    $stmt = $pdo->prepare("
                        UPDATE usuarios 
                        SET usuario = ?, nombre = ?, codigo_juramento = ?, bombero_id = ?, rol = ?, activo = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute(array(
                        $usuario_nuevo ?: null, 
                        $nombre, 
                        $codigo_juramento ?: null, 
                        $bombero_id, 
                        $rol, 
                        $activo, 
                        $id
                    ));
                }
                
                $_SESSION['mensaje'] = 'Usuario actualizado correctamente.';
                $_SESSION['tipo_mensaje'] = 'success';
                header('Location: ' . $base_url . 'modulos/usuarios/listar.php');
                exit();
            } catch (PDOException $e) {
                $error = 'Error al actualizar el usuario: ' . $e->getMessage();
            }
        }
    }
}

// Obtener bomberos
$stmt_bomberos = $pdo->query("SELECT * FROM bomberos WHERE activo = 1 ORDER BY apellido, nombre");
$bomberos = $stmt_bomberos->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario - Sistema de Control de Asistencias</title>
</head>
<body>
    <?php include $base_path . 'includes/header.php'; ?>

    <div class="form-container">
        <div class="page-header">
            <h1><i class="fas fa-user-edit"></i> Editar Usuario</h1>
            <p>Modificar información del usuario</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="nombre">Nombre Completo *</label>
                    <input type="text" id="nombre" name="nombre" required 
                           value="<?php echo htmlspecialchars($usuario['nombre']); ?>">
                </div>
                <div class="form-group">
                    <label for="usuario">Usuario</label>
                    <input type="text" id="usuario" name="usuario" 
                           value="<?php echo htmlspecialchars(isset($usuario['usuario']) ? $usuario['usuario'] : ''); ?>"
                           placeholder="Opcional">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Nueva Contraseña (dejar vacío para no cambiar)</label>
                    <input type="password" id="password" name="password" 
                           placeholder="Solo complete si desea cambiar la contraseña">
                </div>
                <div class="form-group">
                    <label for="codigo_juramento">Código de Juramento</label>
                    <input type="text" id="codigo_juramento" name="codigo_juramento" 
                           value="<?php echo htmlspecialchars(isset($usuario['codigo_juramento']) ? $usuario['codigo_juramento'] : ''); ?>"
                           placeholder="Opcional">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="bombero_id">Bombero Asociado</label>
                    <select id="bombero_id" name="bombero_id">
                        <option value="">Ninguno</option>
                        <?php foreach ($bomberos as $bombero): ?>
                            <option value="<?php echo $bombero['id']; ?>" 
                                    <?php echo $usuario['bombero_id'] == $bombero['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($bombero['apellido'] . ', ' . $bombero['nombre'] . ' (' . $bombero['codigo_juramento'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="rol">Rol *</label>
                    <select id="rol" name="rol" required>
                        <option value="bombero" <?php echo $usuario['rol'] == 'bombero' ? 'selected' : ''; ?>>
                            Bombero
                        </option>
                        <option value="encargado" <?php echo $usuario['rol'] == 'encargado' ? 'selected' : ''; ?>>
                            Encargado
                        </option>
                        <option value="administrador" <?php echo $usuario['rol'] == 'administrador' ? 'selected' : ''; ?>>
                            Administrador
                        </option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="activo" value="1" 
                           <?php echo $usuario['activo'] ? 'checked' : ''; ?>>
                    Activo
                </label>
            </div>

            <div class="form-group" style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
                <a href="<?php echo $base_url; ?>modulos/usuarios/listar.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>

    <?php include $base_path . 'includes/footer.php'; ?>
</body>
</html>

