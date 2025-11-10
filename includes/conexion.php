<?php
// Configurar zona horaria
date_default_timezone_set('America/Asuncion');

// conexion.php - Sistema de Control de Asistencias de Bomberos
$host = '127.0.0.1';
$db   = 'bomberos_db';
$user = 'root';
$pass = ''; // si tu MySQL tiene contraseña, ponla aquí
$charset = 'utf8mb4';

// La ruta base se detecta automáticamente en config.php si está disponible
// Si no, se usará la ruta por defecto

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
    exit;
}