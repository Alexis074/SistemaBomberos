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

if ($id == 0 || $id == obtenerUsuarioId()) {
    $_SESSION['mensaje'] = 'No se puede eliminar este usuario.';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: ' . $base_url . 'modulos/usuarios/listar.php');
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute(array($id));
    $_SESSION['mensaje'] = 'Usuario eliminado correctamente.';
    $_SESSION['tipo_mensaje'] = 'success';
} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error al eliminar el usuario: ' . $e->getMessage();
    $_SESSION['tipo_mensaje'] = 'danger';
}

header('Location: ' . $base_url . 'modulos/usuarios/listar.php');
exit();
?>

