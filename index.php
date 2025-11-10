<?php
date_default_timezone_set('America/Asuncion');
if (!function_exists('getBasePath')) {
    include __DIR__ . '/includes/config.php';
}
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();

// Obtener estadísticas
include $base_path . 'includes/conexion.php';

// Total de bomberos activos
$stmt = $pdo->query("SELECT COUNT(*) as total FROM bomberos WHERE activo = 1");
$total_bomberos = $stmt->fetch()['total'];

// Total de guardias este mes
$stmt = $pdo->query("SELECT COUNT(*) as total FROM guardias WHERE MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE())");
$total_guardias_mes = $stmt->fetch()['total'];

// Total de asistencias este mes
$stmt = $pdo->query("
    SELECT COUNT(*) as total 
    FROM asistencias a
    INNER JOIN guardias g ON a.guardia_id = g.id
    WHERE MONTH(g.fecha) = MONTH(CURDATE()) AND YEAR(g.fecha) = YEAR(CURDATE())
");
$total_asistencias_mes = $stmt->fetch()['total'];

// Próximas guardias (próximos 7 días)
$stmt = $pdo->query("
    SELECT g.*, gr.nombre as grupo_nombre, gr.dia_guardia
    FROM guardias g
    INNER JOIN grupos gr ON g.grupo_id = gr.id
    WHERE g.fecha >= CURDATE() AND g.fecha <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY g.fecha ASC
    LIMIT 5
");
$proximas_guardias = $stmt->fetchAll();

// Guardias por grupo este mes
$stmt = $pdo->query("
    SELECT gr.nombre as grupo_nombre, COUNT(*) as total
    FROM guardias g
    INNER JOIN grupos gr ON g.grupo_id = gr.id
    WHERE MONTH(g.fecha) = MONTH(CURDATE()) AND YEAR(g.fecha) = YEAR(CURDATE())
    GROUP BY gr.id, gr.nombre
    ORDER BY gr.nombre
");
$guardias_por_grupo = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - Sistema de Control de Asistencias de Bomberos</title>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-fire-extinguisher"></i> Bienvenido al Sistema de Control de Asistencias</h1>
            <p>Gestión y control de guardias del Cuerpo de Bomberos Voluntarios</p>
        </div>

        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Bomberos Activos</h3>
                <div class="stat-value"><?php echo $total_bomberos; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <h3>Guardias este Mes</h3>
                <div class="stat-value"><?php echo $total_guardias_mes; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3>Asistencias este Mes</h3>
                <div class="stat-value"><?php echo $total_asistencias_mes; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Grupos Activos</h3>
                <div class="stat-value">5</div>
            </div>
        </div>

        <!-- Próximas guardias -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-calendar-week"></i>
                Próximas Guardias (7 días)
            </div>
            <?php if (count($proximas_guardias) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Grupo</th>
                                <th>Tipo</th>
                                <th>Turno</th>
                                <th>Día</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($proximas_guardias as $guardia): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($guardia['fecha'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($guardia['grupo_nombre']); ?></strong></td>
                                    <td>
                                        <span class="badge <?php echo $guardia['tipo'] == 'diurna' ? 'badge-warning' : 'badge-info'; ?>">
                                            <?php echo ucfirst($guardia['tipo']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($guardia['tipo'] == 'diurna' && !empty($guardia['turno'])) {
                                            echo 'Turno ' . $guardia['turno'];
                                        } else {
                                            echo 'Nocturno';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($guardia['dia_guardia']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No hay guardias programadas</h3>
                    <p>No se encontraron guardias para los próximos 7 días</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Guardias por grupo -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-pie"></i>
                Guardias por Grupo (Este Mes)
            </div>
            <?php if (count($guardias_por_grupo) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Grupo</th>
                                <th>Total de Guardias</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($guardias_por_grupo as $grupo): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($grupo['grupo_nombre']); ?></strong></td>
                                    <td><?php echo $grupo['total']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-chart-bar"></i>
                    <h3>No hay datos disponibles</h3>
                    <p>No se registraron guardias este mes</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Accesos rápidos -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-bolt"></i>
                Accesos Rápidos
            </div>
            <div class="d-flex gap-20" style="flex-wrap: wrap;">
                <a href="<?php echo $base_url; ?>modulos/guardias/registrar.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Registrar Guardia
                </a>
                <a href="<?php echo $base_url; ?>modulos/guardias/calendario.php" class="btn btn-info">
                    <i class="fas fa-calendar-alt"></i> Ver Calendario
                </a>
                <a href="<?php echo $base_url; ?>modulos/reportes/mensual.php" class="btn btn-success">
                    <i class="fas fa-file-alt"></i> Ver Reportes
                </a>
                <?php if (tieneRol('administrador') || tieneRol('encargado')): ?>
                <a href="<?php echo $base_url; ?>modulos/bomberos/listar.php" class="btn btn-warning">
                    <i class="fas fa-users"></i> Gestionar Bomberos
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
