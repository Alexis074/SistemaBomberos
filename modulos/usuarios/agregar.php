<?php
date_default_timezone_set('America/Asuncion');
if (!function_exists('getBasePath')) {
    include __DIR__ . '/../../includes/config.php';
}
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirAdministrador();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim(isset($_POST['usuario']) ? $_POST['usuario'] : '');
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $nombre = trim(isset($_POST['nombre']) ? $_POST['nombre'] : '');
    $codigo_juramento = trim(isset($_POST['codigo_juramento']) ? $_POST['codigo_juramento'] : '');
    $bombero_id = !empty($_POST['bombero_id']) ? (int)$_POST['bombero_id'] : null;
    $rol = isset($_POST['rol']) ? $_POST['rol'] : 'bombero';
    $activo = isset($_POST['activo']) ? 1 : 1;

    if (empty($nombre) || empty($password)) {
        $error = 'Por favor, complete todos los campos obligatorios.';
    } else {
        // Verificar que el usuario no exista
        if (!empty($usuario)) {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
            $stmt->execute(array($usuario));
            if ($stmt->fetch()) {
                $error = 'El usuario ya existe.';
            }
        }

        // Verificar que el código de juramento no exista
        if (empty($error) && !empty($codigo_juramento)) {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE codigo_juramento = ?");
            $stmt->execute(array($codigo_juramento));
            if ($stmt->fetch()) {
                $error = 'El código de juramento ya existe.';
            }
        }

        if (empty($error)) {
            try {
                // Guardar contraseña en texto plano
                $stmt = $pdo->prepare("
                    INSERT INTO usuarios (usuario, password, nombre, codigo_juramento, bombero_id, rol, activo) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute(array(
                    $usuario ?: null, 
                    $password, 
                    $nombre, 
                    $codigo_juramento ?: null, 
                    $bombero_id, 
                    $rol, 
                    $activo
                ));
                
                $_SESSION['mensaje'] = 'Usuario agregado correctamente.';
                $_SESSION['tipo_mensaje'] = 'success';
                header('Location: ' . $base_url . 'modulos/usuarios/listar.php');
                exit();
            } catch (PDOException $e) {
                $error = 'Error al agregar el usuario: ' . $e->getMessage();
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
    <title>Agregar Usuario - Sistema de Control de Asistencias</title>
</head>
<body>
    <?php include $base_path . 'includes/header.php'; ?>

    <div class="form-container">
        <div class="page-header">
            <h1><i class="fas fa-user-plus"></i> Agregar Usuario</h1>
            <p>Registrar un nuevo usuario en el sistema</p>
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
                           value="<?php echo htmlspecialchars(isset($_POST['nombre']) ? $_POST['nombre'] : ''); ?>">
                </div>
                <div class="form-group">
                    <label for="usuario">Usuario (opcional)</label>
                    <input type="text" id="usuario" name="usuario" 
                           value="<?php echo htmlspecialchars(isset($_POST['usuario']) ? $_POST['usuario'] : ''); ?>"
                           placeholder="Dejar vacío si solo usa código de juramento">
                    <small>Opcional: si se deja vacío, solo podrá usar código de juramento</small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Contraseña *</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="codigo_juramento">Código de Juramento (opcional)</label>
                    <input type="text" id="codigo_juramento" name="codigo_juramento" 
                           value="<?php echo htmlspecialchars(isset($_POST['codigo_juramento']) ? $_POST['codigo_juramento'] : ''); ?>"
                           placeholder="Permite login con código de juramento">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="bombero_id">Bombero Asociado (opcional)</label>
                    <select id="bombero_id" name="bombero_id">
                        <option value="">Ninguno</option>
                        <?php foreach ($bomberos as $bombero): ?>
                            <option value="<?php echo $bombero['id']; ?>">
                                <?php echo htmlspecialchars($bombero['apellido'] . ', ' . $bombero['nombre'] . ' (' . $bombero['codigo_juramento'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="rol">Rol *</label>
                    <select id="rol" name="rol" required>
                        <option value="bombero" <?php echo (isset($_POST['rol']) && $_POST['rol'] == 'bombero') ? 'selected' : ''; ?>>
                            Bombero
                        </option>
                        <option value="encargado" <?php echo (isset($_POST['rol']) && $_POST['rol'] == 'encargado') ? 'selected' : ''; ?>>
                            Encargado
                        </option>
                        <option value="administrador" <?php echo (isset($_POST['rol']) && $_POST['rol'] == 'administrador') ? 'selected' : ''; ?>>
                            Administrador
                        </option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Usuario
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

