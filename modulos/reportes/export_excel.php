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

$meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

// Generar CSV (formato Excel compatible)
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="reporte_' . $meses[$mes] . '_' . $ano . '.csv"');

// Agregar BOM para Excel
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// Encabezados
fputcsv($output, ['Reporte de Asistencias - ' . $meses[$mes] . ' ' . $ano], ';');
fputcsv($output, [], ';');
fputcsv($output, ['Nombre', 'Apellido', 'Código de Juramento', 'Grupo', 'Tipo', 'Total Guardias', 'Reemplazos', 'Apoyos'], ';');

// Datos
foreach ($reporte as $fila) {
    fputcsv($output, [
        $fila['nombre'],
        $fila['apellido'],
        $fila['codigo_juramento'],
        $fila['grupo_nombre'],
        ucfirst($fila['tipo']),
        $fila['total_guardias'],
        $fila['total_reemplazos'],
        $fila['total_apoyos']
    ], ';');
}

// Totales
$total_guardias = array_sum(array_column($reporte, 'total_guardias'));
$total_reemplazos = array_sum(array_column($reporte, 'total_reemplazos'));
$total_apoyos = array_sum(array_column($reporte, 'total_apoyos'));

fputcsv($output, [], ';');
fputcsv($output, ['TOTALES', '', '', '', '', $total_guardias, $total_reemplazos, $total_apoyos], ';');

fclose($output);
exit();
?>

