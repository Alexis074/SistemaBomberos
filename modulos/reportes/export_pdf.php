<?php
date_default_timezone_set('America/Asuncion');
if (!function_exists('getBasePath')) {
    include __DIR__ . '/../../includes/config.php';
}
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();

require_once($base_path . 'fpdf/fpdf.php');

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

// Crear PDF
class PDF extends FPDF
{
    function Header()
    {
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'Sistema de Control de Asistencias de Bomberos', 0, 1, 'C');
        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 8, 'Reporte de Asistencias', 0, 1, 'C');
        $this->Ln(5);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    function TableHeader()
    {
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(220, 38, 38);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(50, 8, 'Nombre', 1, 0, 'L', true);
        $this->Cell(30, 8, 'Codigo', 1, 0, 'L', true);
        $this->Cell(30, 8, 'Grupo', 1, 0, 'L', true);
        $this->Cell(25, 8, 'Tipo', 1, 0, 'L', true);
        $this->Cell(25, 8, 'Guardias', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Reemplazos', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Apoyos', 1, 1, 'C', true);
        $this->SetTextColor(0, 0, 0);
    }
}

$pdf = new PDF('L'); // Landscape orientation
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

// Título del reporte
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Reporte de Asistencias - ' . $meses[$mes] . ' ' . $ano, 0, 1, 'L');
$pdf->Ln(5);

// Tabla
$pdf->TableHeader();

$pdf->SetFont('Arial', '', 9);
$total_guardias = 0;
$total_reemplazos = 0;
$total_apoyos = 0;

foreach ($reporte as $fila) {
    $nombre_completo = $fila['apellido'] . ', ' . $fila['nombre'];
    $tipo = ucfirst($fila['tipo']);
    
    $total_guardias += $fila['total_guardias'];
    $total_reemplazos += $fila['total_reemplazos'];
    $total_apoyos += $fila['total_apoyos'];
    
    $pdf->Cell(50, 7, $nombre_completo, 1, 0, 'L');
    $pdf->Cell(30, 7, $fila['codigo_juramento'], 1, 0, 'L');
    $pdf->Cell(30, 7, $fila['grupo_nombre'], 1, 0, 'L');
    $pdf->Cell(25, 7, $tipo, 1, 0, 'L');
    $pdf->Cell(25, 7, $fila['total_guardias'], 1, 0, 'C');
    $pdf->Cell(25, 7, $fila['total_reemplazos'], 1, 0, 'C');
    $pdf->Cell(25, 7, $fila['total_apoyos'], 1, 1, 'C');
}

// Totales
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(248, 250, 252);
$pdf->Cell(135, 7, 'TOTALES', 1, 0, 'R', true);
$pdf->Cell(25, 7, $total_guardias, 1, 0, 'C', true);
$pdf->Cell(25, 7, $total_reemplazos, 1, 0, 'C', true);
$pdf->Cell(25, 7, $total_apoyos, 1, 1, 'C', true);

// Fecha de generación
$pdf->Ln(10);
$pdf->SetFont('Arial', 'I', 8);
$pdf->Cell(0, 5, 'Generado el: ' . date('d/m/Y H:i:s'), 0, 1, 'L');

$pdf->Output('D', 'reporte_' . $meses[$mes] . '_' . $ano . '.pdf');
exit();
?>

