<?php
date_default_timezone_set('America/Asuncion');
if (!function_exists('getBasePath')) {
    include __DIR__ . '/../../includes/config.php';
}
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();

// Filtros
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('n');
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : date('Y');
$grupo_filtro = isset($_GET['grupo']) ? (int)$_GET['grupo'] : 0;
$bombero_filtro = isset($_GET['bombero']) ? (int)$_GET['bombero'] : 0;

$fecha_inicio = "$ano-$mes-01";
$fecha_fin = date('Y-m-t', strtotime($fecha_inicio));

// Consulta de reporte
$sql = "
    SELECT 
        b.id,
        b.nombre,
        b.apellido,
        b.codigo_juramento,
        b.tipo,
        gr.nombre as grupo_nombre,
        COUNT(a.id) as total_guardias,
        SUM(CASE WHEN a.es_reemplazo = 1 THEN 1 ELSE 0 END) as total_reemplazos,
        SUM(CASE WHEN a.es_apoyo = 1 THEN 1 ELSE 0 END) as total_apoyos
    FROM bomberos b
    INNER JOIN grupos gr ON b.grupo_id = gr.id
    LEFT JOIN asistencias a ON b.id = a.bombero_id
    LEFT JOIN guardias g ON a.guardia_id = g.id AND g.fecha BETWEEN ? AND ?
    WHERE b.activo = 1
";

$params = [$fecha_inicio, $fecha_fin];

if ($grupo_filtro > 0) {
    $sql .= " AND b.grupo_id = ?";
    $params[] = $grupo_filtro;
}

if ($bombero_filtro > 0) {
    $sql .= " AND b.id = ?";
    $params[] = $bombero_filtro;
}

$sql .= " GROUP BY b.id, b.nombre, b.apellido, b.codigo_juramento, b.tipo, gr.nombre
          ORDER BY b.apellido, b.nombre";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reporte = $stmt->fetchAll();

// Obtener grupos para el filtro
$stmt_grupos = $pdo->query("SELECT * FROM grupos ORDER BY nombre");
$grupos = $stmt_grupos->fetchAll();

// Obtener bomberos para el filtro
$stmt_bomberos = $pdo->query("SELECT * FROM bomberos WHERE activo = 1 ORDER BY apellido, nombre");
$bomberos = $stmt_bomberos->fetchAll();

$meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Mensual - Sistema de Control de Asistencias</title>
</head>
<body>
    <?php include $base_path . 'includes/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-chart-bar"></i> Reporte Mensual</h1>
            <p>Reporte de asistencias por bombero</p>
        </div>

        <!-- Filtros -->
        <div class="filters">
            <div class="filter-group">
                <label>Mes:</label>
                <select name="mes" onchange="document.getElementById('filterForm').submit()" form="filterForm">
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $mes == $i ? 'selected' : ''; ?>>
                            <?php echo $meses[$i]; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Año:</label>
                <select name="ano" onchange="document.getElementById('filterForm').submit()" form="filterForm">
                    <?php for ($i = date('Y') - 2; $i <= date('Y') + 1; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $ano == $i ? 'selected' : ''; ?>>
                            <?php echo $i; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Grupo:</label>
                <select name="grupo" onchange="document.getElementById('filterForm').submit()" form="filterForm">
                    <option value="0">Todos los grupos</option>
                    <?php foreach ($grupos as $grupo): ?>
                        <option value="<?php echo $grupo['id']; ?>" <?php echo $grupo_filtro == $grupo['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($grupo['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Bombero:</label>
                <select name="bombero" onchange="document.getElementById('filterForm').submit()" form="filterForm">
                    <option value="0">Todos los bomberos</option>
                    <?php foreach ($bomberos as $bombero): ?>
                        <option value="<?php echo $bombero['id']; ?>" <?php echo $bombero_filtro == $bombero['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($bombero['apellido'] . ', ' . $bombero['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <form method="GET" id="filterForm" style="display: none;"></form>

        <!-- Botones de exportación -->
        <div style="margin-bottom: 20px;">
            <a href="<?php echo $base_url; ?>modulos/reportes/export_excel.php?mes=<?php echo $mes; ?>&ano=<?php echo $ano; ?>&grupo=<?php echo $grupo_filtro; ?>&bombero=<?php echo $bombero_filtro; ?>" 
               class="btn btn-success">
                <i class="fas fa-file-excel"></i> Exportar a Excel
            </a>
            <a href="<?php echo $base_url; ?>modulos/reportes/export_pdf.php?mes=<?php echo $mes; ?>&ano=<?php echo $ano; ?>&grupo=<?php echo $grupo_filtro; ?>&bombero=<?php echo $bombero_filtro; ?>" 
               class="btn btn-danger">
                <i class="fas fa-file-pdf"></i> Exportar a PDF
            </a>
        </div>

        <!-- Tabla de reporte -->
        <?php if (count($reporte) > 0): ?>
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-list"></i>
                    Reporte de Asistencias - <?php echo $meses[$mes] . ' ' . $ano; ?>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre Completo</th>
                                <th>Código de Juramento</th>
                                <th>Grupo</th>
                                <th>Tipo</th>
                                <th>Total Guardias</th>
                                <th>Reemplazos</th>
                                <th>Apoyos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_guardias = 0;
                            $total_reemplazos = 0;
                            $total_apoyos = 0;
                            foreach ($reporte as $fila): 
                                $total_guardias += $fila['total_guardias'];
                                $total_reemplazos += $fila['total_reemplazos'];
                                $total_apoyos += $fila['total_apoyos'];
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($fila['apellido'] . ', ' . $fila['nombre']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($fila['codigo_juramento']); ?></td>
                                    <td><?php echo htmlspecialchars($fila['grupo_nombre']); ?></td>
                                    <td>
                                        <?php 
                                        $tipos = explode(',', $fila['tipo']);
                                        foreach ($tipos as $tipo_item) {
                                            $tipo_item = trim($tipo_item);
                                            $badge_class = ($tipo_item == 'voluntario') ? 'badge-info' : 'badge-warning';
                                            echo '<span class="badge ' . $badge_class . '" style="margin-right: 5px;">';
                                            echo ucfirst($tipo_item);
                                            echo '</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><strong><?php echo $fila['total_guardias']; ?></strong></td>
                                    <td><?php echo $fila['total_reemplazos']; ?></td>
                                    <td><?php echo $fila['total_apoyos']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background: #f8fafc; font-weight: 700;">
                                <td colspan="4"><strong>TOTALES</strong></td>
                                <td><strong><?php echo $total_guardias; ?></strong></td>
                                <td><strong><?php echo $total_reemplazos; ?></strong></td>
                                <td><strong><?php echo $total_apoyos; ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-chart-bar"></i>
                <h3>No se encontraron datos</h3>
                <p>No hay asistencias registradas para el período seleccionado</p>
            </div>
        <?php endif; ?>
    </div>

    <?php include $base_path . 'includes/footer.php'; ?>
</body>
</html>

