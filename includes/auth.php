<?php
if (!function_exists('getBasePath')) {
    include __DIR__ . '/config.php';
}
if (!isset($pdo)) {
    include $base_path . 'includes/conexion.php';
}
if (!function_exists('estaLogueado')) {
    include $base_path . 'includes/session.php';
}

// Verificar si el usuario tiene un rol específico
if (!function_exists('tieneRol')) {
    function tieneRol($rol) {
        return obtenerRol() === $rol;
    }
}

// Verificar si el usuario es administrador
if (!function_exists('esAdministrador')) {
    function esAdministrador() {
        return tieneRol('administrador');
    }
}

// Verificar si el usuario es encargado o administrador
if (!function_exists('esEncargado')) {
    function esEncargado() {
        $rol = obtenerRol();
        return $rol === 'administrador' || $rol === 'encargado';
    }
}

// Requerir que el usuario sea administrador
if (!function_exists('requerirAdministrador')) {
    function requerirAdministrador() {
        requerirLogin();
        if (!esAdministrador()) {
            // Detectar ruta base automáticamente
            if (!function_exists('getBaseUrl')) {
                if (file_exists(__DIR__ . '/config.php')) {
                    include __DIR__ . '/config.php';
                } else {
                    $base_url = '/bomberos/';
                }
            }
            if (!isset($base_url)) {
                $base_url = getBaseUrl();
            }
            header('Location: ' . $base_url . 'index.php');
            $_SESSION['error'] = 'No tienes permiso para acceder a esta sección. Se requiere rol de administrador.';
            exit();
        }
    }
}

// Requerir que el usuario sea encargado o administrador
if (!function_exists('requerirEncargado')) {
    function requerirEncargado() {
        requerirLogin();
        if (!esEncargado()) {
            // Detectar ruta base automáticamente
            if (!function_exists('getBaseUrl')) {
                if (file_exists(__DIR__ . '/config.php')) {
                    include __DIR__ . '/config.php';
                } else {
                    $base_url = '/bomberos/';
                }
            }
            if (!isset($base_url)) {
                $base_url = getBaseUrl();
            }
            header('Location: ' . $base_url . 'index.php');
            $_SESSION['error'] = 'No tienes permiso para acceder a esta sección.';
            exit();
        }
    }
}
?>
