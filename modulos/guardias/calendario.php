<?php
date_default_timezone_set('America/Asuncion');
if (!function_exists('getBasePath')) {
    include __DIR__ . '/../../includes/config.php';
}
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();

// Mes y año actuales
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('n');
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : date('Y');

// Obtener grupos con sus días de guardia
$stmt_grupos = $pdo->query("SELECT * FROM grupos WHERE activo = 1 ORDER BY id");
$grupos = $stmt_grupos->fetchAll();

// Colores únicos para cada grupo
$colores_grupos = [
    1 => ['bg' => '#fee2e2', 'border' => '#dc2626', 'text' => '#991b1b', 'name' => 'Grupo 1'],
    2 => ['bg' => '#f3e8ff', 'border' => '#a855f7', 'text' => '#6b21a8', 'name' => 'Grupo 2'], // Violeta (Miércoles)
    3 => ['bg' => '#fef3c7', 'border' => '#f59e0b', 'text' => '#92400e', 'name' => 'Grupo 3'],
    4 => ['bg' => '#d1fae5', 'border' => '#10b981', 'text' => '#065f46', 'name' => 'Grupo 4'],
    5 => ['bg' => '#e5e7eb', 'border' => '#374151', 'text' => '#111827', 'name' => 'Grupo 5'], // Gris oscuro
];

// Mapeo de días de la semana en español a números
$dias_semana_map = [
    'Lunes' => 1,
    'Martes' => 2,
    'Miércoles' => 3,
    'Jueves' => 4,
    'Viernes' => 5,
    'Sábado' => 6,
    'Domingo' => 7
];

// Obtener guardias registradas del mes
$fecha_inicio = "$ano-$mes-01";
$fecha_fin = date('Y-m-t', strtotime($fecha_inicio));

$stmt = $pdo->prepare("
    SELECT g.*, gr.id as grupo_id, gr.nombre as grupo_nombre, gr.dia_guardia
    FROM guardias g
    INNER JOIN grupos gr ON g.grupo_id = gr.id
    WHERE g.fecha BETWEEN ? AND ?
    ORDER BY g.fecha ASC
");
$stmt->execute(array($fecha_inicio, $fecha_fin));
$guardias_registradas = $stmt->fetchAll();

// Organizar guardias registradas por fecha
$guardias_por_fecha = [];
foreach ($guardias_registradas as $guardia) {
    $fecha = $guardia['fecha'];
    if (!isset($guardias_por_fecha[$fecha])) {
        $guardias_por_fecha[$fecha] = [];
    }
    $guardias_por_fecha[$fecha][] = $guardia;
}

// Función para calcular las guardias programadas del mes
function calcularGuardiasProgramadas($ano, $mes, $grupos, $dias_semana_map) {
    $guardias_programadas = [];
    $fecha_inicio = "$ano-$mes-01";
    $dias_mes = date('t', strtotime($fecha_inicio));
    
    // Calcular guardias fijas (cada grupo en su día de la semana)
    // NOTA: Los sábados y domingos NO se incluyen aquí, solo se asignan por rotación
    foreach ($grupos as $grupo) {
        $dia_guardia_num = $dias_semana_map[$grupo['dia_guardia']];
        
        // Solo asignar guardias fijas a días de lunes a viernes (1-5)
        if ($dia_guardia_num >= 6) {
            continue; // Saltar sábados y domingos
        }
        
        // Encontrar todos los días del mes que corresponden a ese día de la semana
        for ($dia = 1; $dia <= $dias_mes; $dia++) {
            $fecha = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
            $dia_semana = date('N', strtotime($fecha)); // 1=Lunes, 7=Domingo
            
            // Solo asignar si es el día de guardia fija y NO es sábado ni domingo
            if ($dia_semana == $dia_guardia_num && $dia_semana < 6) {
                if (!isset($guardias_programadas[$fecha])) {
                    $guardias_programadas[$fecha] = [];
                }
                $guardias_programadas[$fecha][] = [
                    'grupo_id' => $grupo['id'],
                    'grupo_nombre' => $grupo['nombre'],
                    'dia_guardia' => $grupo['dia_guardia'],
                    'tipo' => 'fija'
                ];
            }
        }
    }
    
    // Calcular guardias de fin de semana (sábados y domingos rotativos)
    // Rotación según el usuario:
    // - Sábado anterior (ayer) = Grupo 4
    // - Domingo hoy = Grupo 5
    // - Próximo sábado = Grupo 1
    // - Próximo domingo = Grupo 2
    // - Siguiente sábado = Grupo 3
    // - Siguiente domingo = Grupo 4
    // - Y así continúa...
    // 
    // Rotación sábados: 4, 1, 3, 5, 2, 4, 1, 3, 5, 2...
    // Rotación domingos: 5, 2, 4, 1, 3, 5, 2, 4, 1, 3...
    // 
    // Fecha base de referencia para la rotación de fin de semana
    // CONFIGURACIÓN: Ajustar la fecha base conocida aquí
    // 
    // Fecha conocida donde sabemos qué grupo tiene guardia:
    // Si sabes que un domingo específico es Grupo 5, pon esa fecha aquí
    // Si sabes que un sábado específico es Grupo 4, pon esa fecha aquí
    // 
    // Fecha base conocida para la rotación
    // CONFIGURACIÓN: Esta fecha debe ser un DOMINGO donde el Grupo 5 tiene guardia
    // 
    // IMPORTANTE: Si conoces una fecha específica donde un domingo es Grupo 5,
    // actualiza la variable $fecha_referencia_domingo_manual con esa fecha.
    // 
    // Fecha actual: Hoy es domingo y es Grupo 5, ayer sábado fue Grupo 4
    // Calcular automáticamente usando la fecha de hoy si es domingo
    $hoy = date('Y-m-d');
    $dia_semana_hoy = date('N', strtotime($hoy));
    
    if ($dia_semana_hoy == 7) {
        // Hoy es domingo, usar como referencia
        $fecha_referencia_domingo = $hoy;
        $fecha_base_domingo = $hoy;
        $fecha_base_sabado = date('Y-m-d', strtotime($hoy . ' -1 day'));
    } else {
        // No es domingo, usar fecha manual configurada
        $fecha_referencia_domingo_manual = '2024-12-15'; // Domingo del Grupo 5 (AJUSTAR ESTA FECHA)
        $fecha_referencia_domingo = $fecha_referencia_domingo_manual;
        $fecha_base_domingo = $fecha_referencia_domingo;
        $fecha_base_sabado = date('Y-m-d', strtotime($fecha_referencia_domingo . ' -1 day'));
    }
    
    // Rotaciones (el orden en el array define la secuencia de rotación)
    // Estos arrays definen qué grupo le toca a cada sábado/domingo en orden
    $rotacion_sabados = [4, 1, 3, 5, 2]; // Orden de rotación de sábados: 4, 1, 3, 5, 2, 4, 1, 3...
    $rotacion_domingos = [5, 2, 4, 1, 3]; // Orden de rotación de domingos: 5, 2, 4, 1, 3, 5, 2, 4...
    
    // Encontrar TODOS los sábados y domingos del mes
    $sabados = [];
    $domingos = [];
    
    for ($dia = 1; $dia <= $dias_mes; $dia++) {
        $fecha = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
        $dia_semana = date('N', strtotime($fecha));
        
        if ($dia_semana == 6) { // Sábado
            $sabados[] = $fecha;
        } elseif ($dia_semana == 7) { // Domingo
            $domingos[] = $fecha;
        }
    }
    
    // Calcular grupo para TODOS los sábados del mes
    foreach ($sabados as $fecha_sabado) {
        $timestamp_sabado = strtotime($fecha_sabado);
        $timestamp_base_sabado = strtotime($fecha_base_sabado);
        $dias_diferencia = $timestamp_sabado - $timestamp_base_sabado;
        $semanas_diferencia = floor($dias_diferencia / (60 * 60 * 24 * 7));
        
        // Calcular índice en la rotación (puede ser negativo, así que usar módulo correctamente)
        $indice = ($semanas_diferencia % 5 + 5) % 5;
        $grupo_sabado = $rotacion_sabados[$indice];
        
        foreach ($grupos as $g) {
            if ($g['id'] == $grupo_sabado) {
                if (!isset($guardias_programadas[$fecha_sabado])) {
                    $guardias_programadas[$fecha_sabado] = [];
                }
                $guardias_programadas[$fecha_sabado][] = [
                    'grupo_id' => $g['id'],
                    'grupo_nombre' => $g['nombre'],
                    'dia_guardia' => 'Sábado',
                    'tipo' => 'fin_semana'
                ];
                break;
            }
        }
    }
    
    // Calcular grupo para TODOS los domingos del mes
    foreach ($domingos as $fecha_domingo) {
        $timestamp_domingo = strtotime($fecha_domingo);
        $timestamp_base_domingo = strtotime($fecha_base_domingo);
        $dias_diferencia = $timestamp_domingo - $timestamp_base_domingo;
        $semanas_diferencia = floor($dias_diferencia / (60 * 60 * 24 * 7));
        
        // Calcular índice en la rotación (puede ser negativo, así que usar módulo correctamente)
        $indice = ($semanas_diferencia % 5 + 5) % 5;
        $grupo_domingo = $rotacion_domingos[$indice];
        
        foreach ($grupos as $g) {
            if ($g['id'] == $grupo_domingo) {
                if (!isset($guardias_programadas[$fecha_domingo])) {
                    $guardias_programadas[$fecha_domingo] = [];
                }
                $guardias_programadas[$fecha_domingo][] = [
                    'grupo_id' => $g['id'],
                    'grupo_nombre' => $g['nombre'],
                    'dia_guardia' => 'Domingo',
                    'tipo' => 'fin_semana'
                ];
                break;
            }
        }
    }
    
    return $guardias_programadas;
}

// Calcular todas las guardias programadas
$guardias_programadas = calcularGuardiasProgramadas($ano, $mes, $grupos, $dias_semana_map);

// Combinar guardias programadas con guardias registradas
// Las guardias registradas reemplazan las programadas para esa fecha y grupo
$todas_las_guardias = [];

// Primero agregar todas las guardias programadas
foreach ($guardias_programadas as $fecha => $guardias_prog) {
    $todas_las_guardias[$fecha] = $guardias_prog;
}

// Luego, agregar/reemplazar con guardias registradas
foreach ($guardias_por_fecha as $fecha => $guardias_reg) {
    if (!isset($todas_las_guardias[$fecha])) {
        $todas_las_guardias[$fecha] = [];
    }
    
    // Para cada guardia registrada, buscar si hay una programada del mismo grupo y reemplazarla
    foreach ($guardias_reg as $guardia_reg) {
        $grupo_id_reg = $guardia_reg['grupo_id'];
        $reemplazado = false;
        
        // Buscar si hay una guardia programada del mismo grupo y reemplazarla
        foreach ($todas_las_guardias[$fecha] as $index => $guardia_prog) {
            if (isset($guardia_prog['grupo_id']) && $guardia_prog['grupo_id'] == $grupo_id_reg) {
                // Reemplazar la guardia programada con la registrada
                $todas_las_guardias[$fecha][$index] = [
                    'grupo_id' => $guardia_reg['grupo_id'],
                    'grupo_nombre' => $guardia_reg['grupo_nombre'],
                    'dia_guardia' => $guardia_reg['dia_guardia'],
                    'tipo' => 'registrada',
                    'tipo_guardia' => isset($guardia_reg['tipo']) ? $guardia_reg['tipo'] : null,
                    'turno' => isset($guardia_reg['turno']) ? $guardia_reg['turno'] : null
                ];
                $reemplazado = true;
                break;
            }
        }
        
        // Si no se reemplazó ninguna, agregar la guardia registrada
        if (!$reemplazado) {
            $todas_las_guardias[$fecha][] = [
                'grupo_id' => $guardia_reg['grupo_id'],
                'grupo_nombre' => $guardia_reg['grupo_nombre'],
                'dia_guardia' => $guardia_reg['dia_guardia'],
                'tipo' => 'registrada',
                'tipo_guardia' => isset($guardia_reg['tipo']) ? $guardia_reg['tipo'] : null,
                'turno' => isset($guardia_reg['turno']) ? $guardia_reg['turno'] : null
            ];
        }
    }
}

// Obtener días del mes
$dias_mes = date('t', strtotime($fecha_inicio));
$primer_dia = date('N', strtotime($fecha_inicio)); // 1 (Lunes) a 7 (Domingo)

// Nombres de días y meses
$dias_semana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
$meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

// Navegación de mes
$mes_anterior = $mes - 1;
$ano_anterior = $ano;
if ($mes_anterior < 1) {
    $mes_anterior = 12;
    $ano_anterior--;
}

$mes_siguiente = $mes + 1;
$ano_siguiente = $ano;
if ($mes_siguiente > 12) {
    $mes_siguiente = 1;
    $ano_siguiente++;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario de Guardias - Sistema de Control de Asistencias</title>
    <style>
        .calendar-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .calendar-nav h2 {
            margin: 0;
            font-size: 24px;
            color: #1e293b;
        }
        .calendar-nav .nav-buttons {
            display: flex;
            gap: 10px;
        }
        .calendar-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .calendar-table th {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            padding: 12px;
            text-align: center;
            font-weight: 600;
            font-size: 14px;
        }
        .calendar-table td {
            border: 1px solid #e2e8f0;
            padding: 10px;
            vertical-align: top;
            min-height: 100px;
            width: 14.28%;
            position: relative;
        }
        .calendar-day {
            min-height: 100px;
        }
        .day-number {
            font-weight: 700;
            font-size: 16px;
            color: #1e293b;
            margin-bottom: 8px;
            display: inline-block;
        }
        .day-number.today {
            background: #22c55e;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(34, 197, 94, 0.3);
        }
        .guardia-item {
            padding: 4px 8px;
            margin-bottom: 4px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
            border-left: 3px solid;
            cursor: pointer;
        }
        .guardia-item.registrada {
            opacity: 1;
            font-weight: 600;
        }
        .guardia-item.programada {
            opacity: 0.8;
        }
        .empty-day {
            color: #cbd5e1;
            background: #f8fafc;
        }
        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 20px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 8px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            border-left: 3px solid;
        }
    </style>
</head>
<body>
    <?php include $base_path . 'includes/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-calendar-alt"></i> Calendario de Guardias</h1>
            <p>Visualización mensual de guardias programadas y registradas</p>
        </div>

        <div class="calendar-nav">
            <a href="?mes=<?php echo $mes_anterior; ?>&ano=<?php echo $ano_anterior; ?>" class="btn btn-secondary">
                <i class="fas fa-chevron-left"></i> Mes Anterior
            </a>
            <h2><?php echo $meses[$mes] . ' ' . $ano; ?></h2>
            <a href="?mes=<?php echo $mes_siguiente; ?>&ano=<?php echo $ano_siguiente; ?>" class="btn btn-secondary">
                Mes Siguiente <i class="fas fa-chevron-right"></i>
            </a>
        </div>

        <table class="calendar-table">
            <thead>
                <tr>
                    <?php foreach ($dias_semana as $dia): ?>
                        <th><?php echo $dia; ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $dia_actual = 1;
                $dias_en_semana = 0;
                $fecha_hoy = date('Y-m-d');
                
                // Crear celdas vacías para los días antes del primer día del mes
                echo '<tr>';
                for ($i = 1; $i < $primer_dia; $i++) {
                    echo '<td class="empty-day"></td>';
                    $dias_en_semana++;
                }
                
                // Crear celdas para cada día del mes
                while ($dia_actual <= $dias_mes) {
                    if ($dias_en_semana == 7) {
                        echo '</tr><tr>';
                        $dias_en_semana = 0;
                    }
                    
                    $fecha_actual = sprintf('%04d-%02d-%02d', $ano, $mes, $dia_actual);
                    $es_hoy = ($fecha_actual == $fecha_hoy);
                    
                    echo '<td class="calendar-day">';
                    echo '<div class="day-number' . ($es_hoy ? ' today' : '') . '">' . $dia_actual . '</div>';
                    
                    // Mostrar guardias para este día
                    if (isset($todas_las_guardias[$fecha_actual])) {
                        foreach ($todas_las_guardias[$fecha_actual] as $guardia) {
                            $grupo_id = $guardia['grupo_id'];
                            $color = isset($colores_grupos[$grupo_id]) ? $colores_grupos[$grupo_id] : $colores_grupos[1];
                            
                            $clase = 'guardia-item';
                            if (isset($guardia['tipo_guardia'])) {
                                $clase .= ' registrada';
                            } else {
                                $clase .= ' programada';
                            }
                            
                            $texto = $guardia['grupo_nombre'];
                            if (isset($guardia['tipo_guardia'])) {
                                if ($guardia['tipo_guardia'] == 'diurna' && !empty($guardia['turno'])) {
                                    $texto .= ' - T' . $guardia['turno'];
                                } else {
                                    $texto .= ' - N';
                                }
                            } else {
                                $texto .= ' - ' . $guardia['dia_guardia'];
                            }
                            
                            echo '<div class="' . $clase . '" style="background: ' . $color['bg'] . '; border-left-color: ' . $color['border'] . '; color: ' . $color['text'] . ';">';
                            echo htmlspecialchars($texto);
                            echo '</div>';
                        }
                    }
                    
                    echo '</td>';
                    $dia_actual++;
                    $dias_en_semana++;
                }
                
                // Completar la última fila con celdas vacías
                while ($dias_en_semana < 7) {
                    echo '<td class="empty-day"></td>';
                    $dias_en_semana++;
                }
                echo '</tr>';
                ?>
            </tbody>
        </table>

        <div class="legend">
            <h3 style="width: 100%; margin-bottom: 10px;">Leyenda de Grupos:</h3>
            <?php foreach ($colores_grupos as $grupo_id => $color): 
                $grupo_nombre = 'Grupo ' . $grupo_id;
                foreach ($grupos as $g) {
                    if ($g['id'] == $grupo_id) {
                        $grupo_nombre = $g['nombre'];
                        break;
                    }
                }
            ?>
                <div class="legend-item">
                    <div class="legend-color" style="background: <?php echo $color['bg']; ?>; border-left-color: <?php echo $color['border']; ?>;"></div>
                    <span><?php echo htmlspecialchars($grupo_nombre); ?></span>
                </div>
            <?php endforeach; ?>
            <div class="legend-item" style="margin-left: 20px;">
                <div class="legend-color" style="background: #f8fafc; border-left-color: #94a3b8;"></div>
                <span>Programada (sin registrar)</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #fef3c7; border-left-color: #f59e0b;"></div>
                <span>Registrada</span>
            </div>
        </div>

        <div style="margin-top: 20px;">
            <a href="<?php echo $base_url; ?>modulos/guardias/registrar.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Registrar Nueva Guardia
            </a>
            <a href="<?php echo $base_url; ?>modulos/guardias/listar.php" class="btn btn-secondary">
                <i class="fas fa-list"></i> Ver Lista de Guardias
            </a>
        </div>
    </div>

    <?php include $base_path . 'includes/footer.php'; ?>
</body>
</html>
