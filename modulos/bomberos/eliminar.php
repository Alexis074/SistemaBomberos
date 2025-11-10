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

// Verificar si el bombero tiene asistencias registradas
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM asistencias 
    WHERE bombero_id = ?
");
$stmt->execute(array($id));
$asistencias = $stmt->fetch();

if ($asistencias['total'] > 0) {
    // Si tiene asistencias, solo desactivar
    try {
        $stmt = $pdo->prepare("UPDATE bomberos SET activo = 0 WHERE id = ?");
        $stmt->execute(array($id));
        $_SESSION['mensaje'] = 'El bombero fue desactivado porque tiene asistencias registradas.';
        $_SESSION['tipo_mensaje'] = 'warning';
    } catch (PDOException $e) {
        $_SESSION['mensaje'] = 'Error al desactivar el bombero: ' . $e->getMessage();
        $_SESSION['tipo_mensaje'] = 'danger';
    }
} else {
    // Si no tiene asistencias, eliminar
    try {
        $stmt = $pdo->prepare("DELETE FROM bomberos WHERE id = ?");
        $stmt->execute(array($id));
        $_SESSION['mensaje'] = 'Bombero eliminado correctamente.';
        $_SESSION['tipo_mensaje'] = 'success';
    } catch (PDOException $e) {
        $_SESSION['mensaje'] = 'Error al eliminar el bombero: ' . $e->getMessage();
        $_SESSION['tipo_mensaje'] = 'danger';
    }
}

header('Location: ' . $base_url . 'modulos/bomberos/listar.php');
exit();
?>

