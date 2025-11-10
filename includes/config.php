<?php
/**
 * Archivo de configuración con rutas base
 * Detecta automáticamente la ruta del proyecto
 */

// Detectar ruta base automáticamente
function getBasePath() {
    $possible_paths = [
        $_SERVER['DOCUMENT_ROOT'] . '/bomberos/',
        $_SERVER['DOCUMENT_ROOT'] . '/repuestos/',
        dirname(__DIR__) . '/',
    ];
    
    foreach ($possible_paths as $path) {
        if (file_exists($path . 'index.php')) {
            return $path;
        }
    }
    
    // Si no se encuentra, usar bomberos por defecto
    return $_SERVER['DOCUMENT_ROOT'] . '/bomberos/';
}

function getBaseUrl() {
    $base_path = getBasePath();
    $doc_root = $_SERVER['DOCUMENT_ROOT'];
    
    // Convertir ruta del sistema a URL
    $base_url = str_replace($doc_root, '', $base_path);
    $base_url = str_replace('\\', '/', $base_url);
    
    // Asegurar que termine con /
    if (substr($base_url, -1) !== '/') {
        $base_url .= '/';
    }
    
    return $base_url;
}

// Variables globales para usar en los archivos
$base_path = getBasePath();
$base_url = getBaseUrl();

?>

