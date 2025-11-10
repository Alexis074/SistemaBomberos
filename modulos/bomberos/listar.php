<?php
date_default_timezone_set('America/Asuncion');
if (!function_exists('getBasePath')) {
    include __DIR__ . '/../../includes/config.php';
}
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirEncargado();

$mensaje = '';
$tipo_mensaje = '';

if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    $tipo_mensaje = isset($_SESSION['tipo_mensaje']) ? $_SESSION['tipo_mensaje'] : 'success';
    unset($_SESSION['mensaje']);
    unset($_SESSION['tipo_mensaje']);
}

// Filtrar por grupo
$grupo_filtro = isset($_GET['grupo']) ? (int)$_GET['grupo'] : 0;
$tipo_filtro = isset($_GET['tipo']) ? $_GET['tipo'] : '';

// Consulta de bomberos
$sql = "SELECT b.*, g.nombre as grupo_nombre, g.dia_guardia 
        FROM bomberos b 
        INNER JOIN grupos g ON b.grupo_id = g.id 
        WHERE 1=1";

$params = [];

if ($grupo_filtro > 0) {
    $sql .= " AND b.grupo_id = ?";
    $params[] = $grupo_filtro;
}

if ($tipo_filtro != '') {
    $sql .= " AND FIND_IN_SET(?, b.tipo) > 0";
    $params[] = $tipo_filtro;
}

$sql .= " ORDER BY b.apellido, b.nombre";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bomberos = $stmt->fetchAll();

// Obtener grupos para el filtro
$stmt_grupos = $pdo->query("SELECT * FROM grupos ORDER BY nombre");
$grupos = $stmt_grupos->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bomberos - Sistema de Control de Asistencias</title>
</head>
<body>
    <?php include $base_path . 'includes/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-users"></i> Gestión de Bomberos</h1>
            <p>Administración de bomberos voluntarios y rentados</p>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <i class="fas fa-<?php echo $tipo_mensaje == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="d-flex gap-20" style="justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap;">
            <div>
                <a href="<?php echo $base_url; ?>modulos/bomberos/agregar.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Agregar Bombero
                </a>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters">
            <div class="filter-group">
                <label>Filtrar por Grupo:</label>
                <select onchange="window.location.href='?grupo=' + this.value + '&tipo=<?php echo $tipo_filtro; ?>'">
                    <option value="0">Todos los grupos</option>
                    <?php foreach ($grupos as $grupo): ?>
                        <option value="<?php echo $grupo['id']; ?>" <?php echo $grupo_filtro == $grupo['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($grupo['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Filtrar por Tipo:</label>
                <select onchange="window.location.href='?grupo=<?php echo $grupo_filtro; ?>&tipo=' + this.value">
                    <option value="">Todos</option>
                    <option value="voluntario" <?php echo $tipo_filtro == 'voluntario' ? 'selected' : ''; ?>>Voluntario</option>
                    <option value="rentado" <?php echo $tipo_filtro == 'rentado' ? 'selected' : ''; ?>>Rentado</option>
                </select>
            </div>
        </div>

        <!-- Tabla de bomberos -->
        <?php if (count($bomberos) > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nombre Completo</th>
                            <th>Código de Juramento</th>
                            <th>Grupo</th>
                            <th>Día de Guardia</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bomberos as $bombero): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($bombero['nombre'] . ' ' . $bombero['apellido']); ?></strong></td>
                                <td><?php echo htmlspecialchars($bombero['codigo_juramento']); ?></td>
                                <td><?php echo htmlspecialchars($bombero['grupo_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($bombero['dia_guardia']); ?></td>
                                <td>
                                    <?php 
                                    $tipos = explode(',', $bombero['tipo']);
                                    foreach ($tipos as $tipo_item) {
                                        $tipo_item = trim($tipo_item);
                                        $badge_class = ($tipo_item == 'voluntario') ? 'badge-info' : 'badge-warning';
                                        echo '<span class="badge ' . $badge_class . '" style="margin-right: 5px;">';
                                        echo ucfirst($tipo_item);
                                        echo '</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $bombero['activo'] ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $bombero['activo'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <a href="<?php echo $base_url; ?>modulos/bomberos/editar.php?id=<?php echo $bombero['id']; ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <a href="<?php echo $base_url; ?>modulos/bomberos/eliminar.php?id=<?php echo $bombero['id']; ?>" 
                                       class="btn btn-danger btn-sm" 
                                       onclick="return confirm('¿Está seguro de eliminar este bombero?');">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-users-slash"></i>
                <h3>No se encontraron bomberos</h3>
                <p>No hay bomberos registrados con los filtros seleccionados</p>
                <a href="<?php echo $base_url; ?>modulos/bomberos/agregar.php" class="btn btn-primary mt-20">
                    <i class="fas fa-plus"></i> Agregar Primer Bombero
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php include $base_path . 'includes/footer.php'; ?>
</body>
</html>

