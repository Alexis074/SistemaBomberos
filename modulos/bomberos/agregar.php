<?php
date_default_timezone_set('America/Asuncion');
if (!function_exists('getBasePath')) {
    include __DIR__ . '/../../includes/config.php';
}
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirEncargado();

$error = '';
$success = '';

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
    
    $activo = isset($_POST['activo']) ? 1 : 1;

    if (empty($nombre) || empty($apellido) || empty($codigo_juramento) || $grupo_id == 0 || empty($tipos)) {
        $error = 'Por favor, complete todos los campos obligatorios y seleccione al menos un tipo.';
    } else {
        // Verificar que el código de juramento no exista
        $stmt = $pdo->prepare("SELECT id FROM bomberos WHERE codigo_juramento = ?");
        $stmt->execute(array($codigo_juramento));
        if ($stmt->fetch()) {
            $error = 'El código de juramento ya existe.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO bomberos (nombre, apellido, codigo_juramento, grupo_id, tipo, activo) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute(array($nombre, $apellido, $codigo_juramento, $grupo_id, $tipo, $activo));
                
                $_SESSION['mensaje'] = 'Bombero agregado correctamente.';
                $_SESSION['tipo_mensaje'] = 'success';
                header('Location: ' . $base_url . 'modulos/bomberos/listar.php');
                exit();
            } catch (PDOException $e) {
                $error = 'Error al agregar el bombero: ' . $e->getMessage();
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
    <title>Agregar Bombero - Sistema de Control de Asistencias</title>
</head>
<body>
    <?php include $base_path . 'includes/header.php'; ?>

    <div class="form-container">
        <div class="page-header">
            <h1><i class="fas fa-user-plus"></i> Agregar Bombero</h1>
            <p>Registrar un nuevo bombero en el sistema</p>
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
                           value="<?php echo htmlspecialchars(isset($_POST['nombre']) ? $_POST['nombre'] : ''); ?>">
                </div>
                <div class="form-group">
                    <label for="apellido">Apellido *</label>
                    <input type="text" id="apellido" name="apellido" required 
                           value="<?php echo htmlspecialchars(isset($_POST['apellido']) ? $_POST['apellido'] : ''); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="codigo_juramento">Código de Juramento *</label>
                    <input type="text" id="codigo_juramento" name="codigo_juramento" required 
                           value="<?php echo htmlspecialchars(isset($_POST['codigo_juramento']) ? $_POST['codigo_juramento'] : ''); ?>"
                           placeholder="Ej: BOM001">
                    <small>Este código será único para cada bombero</small>
                </div>
                <div class="form-group">
                    <label for="grupo_id">Grupo *</label>
                    <select id="grupo_id" name="grupo_id" required>
                        <option value="">Seleccione un grupo</option>
                        <?php foreach ($grupos as $grupo): ?>
                            <option value="<?php echo $grupo['id']; ?>" 
                                    <?php echo (isset($_POST['grupo_id']) && $_POST['grupo_id'] == $grupo['id']) ? 'selected' : ''; ?>>
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
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                            <input type="checkbox" name="tipo_voluntario" value="1" 
                                   <?php echo (isset($_POST['tipo_voluntario']) || !isset($_POST['nombre'])) ? 'checked' : ''; ?>>
                            <span>Voluntario (Guardia nocturna normal)</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                            <input type="checkbox" name="tipo_rentado" value="1" 
                                   <?php echo (isset($_POST['tipo_rentado'])) ? 'checked' : ''; ?>>
                            <span>Rentado (Cumple horario de día)</span>
                        </label>
                    </div>
                    <small style="color: #64748b; margin-top: 5px; display: block;">
                        Puede seleccionar ambos tipos si el bombero cumple horario de día y también tiene guardia nocturna normal
                    </small>
                </div>
            </div>

            <div class="form-group" style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Bombero
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

