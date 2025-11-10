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

$grupo_id = isset($_GET['grupo_id']) ? (int)$_GET['grupo_id'] : 0;

if ($grupo_id <= 0) {
    echo json_encode([]);
    exit();
}

// Obtener bomberos del grupo
$stmt = $pdo->prepare("
    SELECT id, nombre, apellido, codigo_juramento, tipo 
    FROM bomberos 
    WHERE grupo_id = ? AND activo = 1 
    ORDER BY apellido, nombre
");
$stmt->execute(array($grupo_id));
$bomberos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($bomberos);
?>

