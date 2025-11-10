<?php
date_default_timezone_set('America/Asuncion');
if (!function_exists('getBasePath')) {
    include __DIR__ . '/../../includes/config.php';
}
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirEncargado();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id == 0) {
    $_SESSION['mensaje'] = 'Bombero no encontrado.';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: ' . $base_url . 'modulos/bomberos/listar.php');
    exit();
}

// Obtener datos del bombero
$stmt = $pdo->prepare("SELECT * FROM bomberos WHERE id = ?");
$stmt->execute(array($id));
$bombero = $stmt->fetch();

if (!$bombero) {
    $_SESSION['mensaje'] = 'Bombero no encontrado.';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: ' . $base_url . 'modulos/bomberos/listar.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim(isset($_POST['nombre']) ? $_POST['nombre'] : '');
    $apellido = trim(isset($_POST['apellido']) ? $_POST['apellido'] : '');
    $codigo_juramento = trim(isset($_POST['codigo_juramento']) ? $_POST['codigo_juramento'] : '');
    $grupo_id = (int)(isset($_POST['grupo_id']) ? $_POST['grupo_id'] : 0);
    
    // Construir tipo SET desde checkboxes
    $tipos = [];
    if (isset($_POST['tipo_voluntario'])) {
        $tipos[] = 'voluntario';
    }
    if (isset($_POST['tipo_rentado'])) {
        $tipos[] = 'rentado';
    }
    $tipo = !empty($tipos) ? implode(',', $tipos) : 'voluntario';
    
    $activo = isset($_POST['activo']) ? 1 : 0;

    if (empty($nombre) || empty($apellido) || empty($codigo_juramento) || $grupo_id == 0 || empty($tipos)) {
        $error = 'Por favor, complete todos los campos obligatorios y seleccione al menos un tipo.';
    } else {
        // Verificar que el código de juramento no exista en otro bombero
        $stmt = $pdo->prepare("SELECT id FROM bomberos WHERE codigo_juramento = ? AND id != ?");
        $stmt->execute(array($codigo_juramento, $id));
        if ($stmt->fetch()) {
            $error = 'El código de juramento ya existe en otro bombero.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE bomberos 
                    SET nombre = ?, apellido = ?, codigo_juramento = ?, grupo_id = ?, tipo = ?, activo = ? 
                    WHERE id = ?
                ");
                $stmt->execute(array($nombre, $apellido, $codigo_juramento, $grupo_id, $tipo, $activo, $id));
                
                $_SESSION['mensaje'] = 'Bombero actualizado correctamente.';
                $_SESSION['tipo_mensaje'] = 'success';
                header('Location: ' . $base_url . 'modulos/bomberos/listar.php');
                exit();
            } catch (PDOException $e) {
                $error = 'Error al actualizar el bombero: ' . $e->getMessage();
            }
        }
    }
}

// Obtener grupos
$stmt_grupos = $pdo->query("SELECT * FROM grupos ORDER BY nombre");
$grupos = $stmt_grupos->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Bombero - Sistema de Control de Asistencias</title>
</head>
<body>
    <?php include $base_path . 'includes/header.php'; ?>

    <div class="form-container">
        <div class="page-header">
            <h1><i class="fas fa-user-edit"></i> Editar Bombero</h1>
            <p>Modificar información del bombero</p>
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
                    <label for="nombre">Nombre *</label>
                    <input type="text" id="nombre" name="nombre" required 
                           value="<?php echo htmlspecialchars($bombero['nombre']); ?>">
                </div>
                <div class="form-group">
                    <label for="apellido">Apellido *</label>
                    <input type="text" id="apellido" name="apellido" required 
                           value="<?php echo htmlspecialchars($bombero['apellido']); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="codigo_juramento">Código de Juramento *</label>
                    <input type="text" id="codigo_juramento" name="codigo_juramento" required 
                           value="<?php echo htmlspecialchars($bombero['codigo_juramento']); ?>">
                </div>
                <div class="form-group">
                    <label for="grupo_id">Grupo *</label>
                    <select id="grupo_id" name="grupo_id" required>
                        <option value="">Seleccione un grupo</option>
                        <?php foreach ($grupos as $grupo): ?>
                            <option value="<?php echo $grupo['id']; ?>" 
                                    <?php echo $bombero['grupo_id'] == $grupo['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($grupo['nombre'] . ' - ' . $grupo['dia_guardia']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Tipo *</label>
                    <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 10px;">
                        <?php 
                        $tipos_bombero = explode(',', $bombero['tipo']);
                        $es_voluntario = in_array('voluntario', $tipos_bombero);
                        $es_rentado = in_array('rentado', $tipos_bombero);
                        ?>
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                            <input type="checkbox" name="tipo_voluntario" value="1" 
                                   <?php echo $es_voluntario ? 'checked' : ''; ?>>
                            <span>Voluntario (Guardia nocturna normal)</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                            <input type="checkbox" name="tipo_rentado" value="1" 
                                   <?php echo $es_rentado ? 'checked' : ''; ?>>
                            <span>Rentado (Cumple horario de día)</span>
                        </label>
                    </div>
                    <small style="color: #64748b; margin-top: 5px; display: block;">
                        Puede seleccionar ambos tipos si el bombero cumple horario de día y también tiene guardia nocturna normal
                    </small>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="activo" value="1" 
                               <?php echo $bombero['activo'] ? 'checked' : ''; ?>>
                        Activo
                    </label>
                </div>
            </div>

            <div class="form-group" style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
                <a href="<?php echo $base_url; ?>modulos/bomberos/listar.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>

    <?php include $base_path . 'includes/footer.php'; ?>
</body>
</html>

