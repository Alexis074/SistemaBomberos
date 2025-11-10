<?php
date_default_timezone_set('America/Asuncion');
if (!function_exists('getBasePath')) {
    include __DIR__ . '/includes/config.php';
}
include $base_path . 'includes/session.php';

// Destruir sesión
session_destroy();

// Redirigir a login
header('Location: ' . $base_url . 'login.php');
exit();
?>

