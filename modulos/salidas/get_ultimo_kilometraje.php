<?php
date_default_timezone_set('America/Asuncion');
if (!function_exists('getBasePath')) {
    include __DIR__ . '/../../includes/config.php';
}
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();

header('Content-Type: application/json');

$movil_id = isset($_GET['movil_id']) ? (int)$_GET['movil_id'] : 0;

if ($movil_id > 0) {
    // Obtener el último kilometraje de regreso del móvil
    $stmt = $pdo->prepare("
        SELECT kilometraje_regreso 
        FROM salidas 
        WHERE movil_id = ? AND kilometraje_regreso IS NOT NULL 
        ORDER BY fecha_regreso DESC, id DESC 
        LIMIT 1
    ");
    $stmt->execute(array($movil_id));
    $ultimo_km = $stmt->fetch();
    
    if ($ultimo_km && $ultimo_km['kilometraje_regreso']) {
        echo json_encode(array('kilometraje' => (int)$ultimo_km['kilometraje_regreso']));
    } else {
        echo json_encode(array('kilometraje' => 0));
    }
} else {
    echo json_encode(array('kilometraje' => 0));
}

