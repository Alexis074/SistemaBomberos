<?php 
if (!function_exists('getBasePath')) {
    include __DIR__ . '/config.php';
}
$current_page = $_SERVER['REQUEST_URI'];
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
?>
<link rel="stylesheet" href="<?php echo $base_url; ?>style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<div class="navbar">
    <div class="logo">
        <i class="fas fa-fire-extinguisher"></i>
        Sistema de Bomberos
    </div>
    <a href="<?php echo $base_url; ?>index.php" <?php echo (strpos($current_page, 'index.php') !== false || ($current_page == $base_url || $current_page == $base_url . 'index.php')) ? 'class="active"' : ''; ?>>
        <i class="fas fa-home"></i> Inicio
    </a>
    <?php if (tieneRol('administrador') || tieneRol('encargado')): ?>
    <a href="<?php echo $base_url; ?>modulos/bomberos/listar.php" <?php echo (strpos($current_page, 'bomberos') !== false) ? 'class="active"' : ''; ?>>
        <i class="fas fa-users"></i> Bomberos
    </a>
    <?php endif; ?>
    <a href="<?php echo $base_url; ?>modulos/guardias/registrar.php" <?php echo (strpos($current_page, 'guardias') !== false) ? 'class="active"' : ''; ?>>
        <i class="fas fa-calendar-check"></i> Guardias
    </a>
    <a href="<?php echo $base_url; ?>modulos/guardias/calendario.php" <?php echo (strpos($current_page, 'calendario') !== false) ? 'class="active"' : ''; ?>>
        <i class="fas fa-calendar-alt"></i> Calendario
    </a>
    <a href="<?php echo $base_url; ?>modulos/reemplazos/listar.php" <?php echo (strpos($current_page, 'reemplazos') !== false) ? 'class="active"' : ''; ?>>
        <i class="fas fa-exchange-alt"></i> Reemplazos
    </a>
    <a href="<?php echo $base_url; ?>modulos/reportes/mensual.php" <?php echo (strpos($current_page, 'reportes') !== false) ? 'class="active"' : ''; ?>>
        <i class="fas fa-chart-bar"></i> Reportes
    </a>
    <?php if (tieneRol('administrador') || tieneRol('encargado')): ?>
    <a href="<?php echo $base_url; ?>modulos/inventario/listar.php" <?php echo (strpos($current_page, 'inventario') !== false) ? 'class="active"' : ''; ?>>
        <i class="fas fa-boxes"></i> Inventario
    </a>
    <?php endif; ?>
    <a href="<?php echo $base_url; ?>modulos/salidas/listar.php" <?php echo (strpos($current_page, 'salidas') !== false) ? 'class="active"' : ''; ?>>
        <i class="fas fa-route"></i> Salidas
    </a>
    <?php if (tieneRol('administrador')): ?>
    <a href="<?php echo $base_url; ?>modulos/usuarios/listar.php" <?php echo (strpos($current_page, 'usuarios') !== false && strpos($current_page, 'bomberos') === false) ? 'class="active"' : ''; ?>>
        <i class="fas fa-user-cog"></i> Usuarios
    </a>
    <?php endif; ?>
    <a href="<?php echo $base_url; ?>logout.php" style="margin-left: auto;">
        <i class="fas fa-sign-out-alt"></i> Salir
    </a>
</div>
