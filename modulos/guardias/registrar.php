<?php
date_default_timezone_set('America/Asuncion');
if (!function_exists('getBasePath')) {
    include __DIR__ . '/../../includes/config.php';
}
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';
include $base_path . 'includes/auth.php';
requerirLogin();

$mensaje = '';
$tipo_mensaje = '';

if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    $tipo_mensaje = isset($_SESSION['tipo_mensaje']) ? $_SESSION['tipo_mensaje'] : 'success';
    unset($_SESSION['mensaje']);
    unset($_SESSION['tipo_mensaje']);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha = isset($_POST['fecha']) ? $_POST['fecha'] : '';
    $grupo_id = (int)(isset($_POST['grupo_id']) ? $_POST['grupo_id'] : 0);
    $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';
    
    // Para guardias nocturnas, el turno debe ser NULL
    // Para guardias diurnas, el turno debe ser '1' o '2'
    if ($tipo == 'nocturna') {
        $turno = null;
    } else {
        $turno = isset($_POST['turno']) && !empty($_POST['turno']) ? $_POST['turno'] : null;
    }
    
    if (empty($fecha) || $grupo_id == 0 || empty($tipo)) {
        $error = 'Por favor, complete todos los campos obligatorios.';
    } else if ($tipo == 'diurna' && empty($turno)) {
        $error = 'Por favor, seleccione un turno para la guardia diurna.';
    } else {
        try {
            // Para la verificación de duplicados, necesitamos manejar NULL correctamente
            if ($turno === null) {
                $stmt = $pdo->prepare("
                    SELECT id FROM guardias 
                    WHERE fecha = ? AND grupo_id = ? AND tipo = ? AND turno IS NULL
                ");
                $stmt->execute(array($fecha, $grupo_id, $tipo));
            } else {
                $stmt = $pdo->prepare("
                    SELECT id FROM guardias 
                    WHERE fecha = ? AND grupo_id = ? AND tipo = ? AND turno = ?
                ");
                $stmt->execute(array($fecha, $grupo_id, $tipo, $turno));
            }
            
            if ($stmt->fetch()) {
                $error = 'Esta guardia ya está registrada.';
            } else {
                // Crear la guardia
                $stmt = $pdo->prepare("
                    INSERT INTO guardias (fecha, grupo_id, tipo, turno) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute(array($fecha, $grupo_id, $tipo, $turno));
                $guardia_id = $pdo->lastInsertId();
                
                // Registrar asistencias
                if (isset($_POST['bomberos']) && is_array($_POST['bomberos'])) {
                    foreach ($_POST['bomberos'] as $bombero_data) {
                        if (!isset($bombero_data['id']) || empty($bombero_data['id'])) {
                            continue;
                        }
                        $bombero_id = (int)$bombero_data['id'];
                        $es_reemplazo = isset($bombero_data['es_reemplazo']) ? 1 : 0;
                        $bombero_reemplazado_id = isset($bombero_data['reemplazado_id']) && $bombero_data['reemplazado_id'] > 0 
                            ? (int)$bombero_data['reemplazado_id'] : null;
                        $es_apoyo = isset($bombero_data['es_apoyo']) ? 1 : 0;
                        $observaciones = isset($bombero_data['observaciones']) ? $bombero_data['observaciones'] : '';
                        
                        $stmt_asis = $pdo->prepare("
                            INSERT INTO asistencias 
                            (guardia_id, bombero_id, es_reemplazo, bombero_reemplazado_id, es_apoyo, observaciones) 
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $stmt_asis->execute(array(
                            $guardia_id, 
                            $bombero_id, 
                            $es_reemplazo, 
                            $bombero_reemplazado_id, 
                            $es_apoyo, 
                            $observaciones
                        ));
                    }
                }
                
                // Registrar observaciones generales
                if (!empty($_POST['observaciones_generales'])) {
                    $stmt_obs = $pdo->prepare("
                        INSERT INTO observaciones (fecha, grupo_id, observacion, created_by) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt_obs->execute(array(
                        $fecha, 
                        $grupo_id, 
                        $_POST['observaciones_generales'], 
                        obtenerUsuarioId()
                    ));
                }
                
                $_SESSION['mensaje'] = 'Guardia registrada correctamente.';
                $_SESSION['tipo_mensaje'] = 'success';
                header('Location: ' . $base_url . 'modulos/guardias/listar.php');
                exit();
            }
        } catch (PDOException $e) {
            $error = 'Error al registrar la guardia: ' . $e->getMessage();
        }
    }
}

// Obtener grupos
$stmt_grupos = $pdo->query("SELECT * FROM grupos WHERE activo = 1 ORDER BY nombre");
$grupos = $stmt_grupos->fetchAll();

// Obtener todos los bomberos activos (para agregar bomberos adicionales)
$stmt_bomberos = $pdo->query("SELECT * FROM bomberos WHERE activo = 1 ORDER BY apellido, nombre");
$bomberos_todos = $stmt_bomberos->fetchAll();

// Fecha por defecto: hoy
$fecha_default = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Guardia - Sistema de Control de Asistencias</title>
    <style>
        .guardia-section {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .bomberos-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 15px;
        }
        .bomberos-table th {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        .bomberos-table td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        .bomberos-table tr:hover {
            background: #f8fafc;
        }
        .bombero-checkbox {
            width: 20px;
            height: 20px;
        }
        .bombero-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .bombero-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .bombero-actions input[type="checkbox"] {
            margin-right: 5px;
        }
        .bombero-actions label {
            font-weight: normal;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .reemplazo-select {
            width: 200px;
            padding: 5px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            display: none;
        }
        .observaciones-input {
            width: 100%;
            padding: 5px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            margin-top: 5px;
        }
        .agregar-bombero-section {
            margin-top: 15px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border: 2px dashed #cbd5e1;
        }
    </style>
</head>
<body>
    <?php include $base_path . 'includes/header.php'; ?>

    <div class="form-container">
        <div class="page-header">
            <h1><i class="fas fa-calendar-plus"></i> Registrar Guardia</h1>
            <p>Registrar asistencias para guardias diurnas o nocturnas</p>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <i class="fas fa-<?php echo $tipo_mensaje == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="guardiaForm">
            <div class="form-row">
                <div class="form-group">
                    <label for="fecha">Fecha *</label>
                    <input type="date" id="fecha" name="fecha" required 
                           value="<?php echo htmlspecialchars(isset($_POST['fecha']) ? $_POST['fecha'] : $fecha_default); ?>">
                </div>
                <div class="form-group">
                    <label for="grupo_id">Grupo *</label>
                    <select id="grupo_id" name="grupo_id" required onchange="cargarBomberosGrupo()">
                        <option value="">Seleccione un grupo</option>
                        <?php foreach ($grupos as $grupo): ?>
                            <option value="<?php echo $grupo['id']; ?>" 
                                    <?php echo (isset($_POST['grupo_id']) && $_POST['grupo_id'] == $grupo['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($grupo['nombre'] . ' - ' . $grupo['dia_guardia']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="tipo">Tipo de Guardia *</label>
                    <select id="tipo" name="tipo" required onchange="toggleTurno()">
                        <option value="">Seleccione el tipo</option>
                        <option value="diurna" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'diurna') ? 'selected' : ''; ?>>
                            Diurna
                        </option>
                        <option value="nocturna" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'nocturna') ? 'selected' : ''; ?>>
                            Nocturna
                        </option>
                    </select>
                </div>
                <div class="form-group" id="turnoGroup" style="display: none;">
                    <label for="turno">Turno *</label>
                    <select id="turno" name="turno">
                        <option value="1">Turno 1 (07:00 - 13:00)</option>
                        <option value="2">Turno 2 (13:00 - 19:00)</option>
                    </select>
                </div>
            </div>

            <div class="guardia-section">
                <h3 style="margin-bottom: 15px;">
                    <i class="fas fa-users"></i> Bomberos del Grupo
                    <small style="font-size: 14px; color: #64748b; font-weight: normal;">
                        (Seleccione un grupo para cargar los bomberos)
                    </small>
                </h3>
                <div id="bomberosGrupoContainer" style="display: none;">
                    <table class="bomberos-table" id="bomberosTable">
                        <thead>
                            <tr>
                                <th style="width: 30px;">
                                    <input type="checkbox" id="selectAll" onchange="toggleAllBomberos()">
                                </th>
                                <th>Bombero</th>
                                <th>Código</th>
                                <th>Tipo</th>
                                <th>Es Reemplazo</th>
                                <th>Reemplaza a</th>
                                <th>Es Apoyo</th>
                                <th>Observaciones</th>
                            </tr>
                        </thead>
                        <tbody id="bomberosTableBody">
                            <!-- Los bomberos se cargarán aquí dinámicamente -->
                        </tbody>
                    </table>
                </div>
                <div id="noGrupoSelected" style="text-align: center; padding: 40px; color: #64748b;">
                    <i class="fas fa-info-circle" style="font-size: 48px; margin-bottom: 10px;"></i>
                    <p>Seleccione un grupo para cargar los bomberos automáticamente</p>
                </div>

                <div class="agregar-bombero-section" id="agregarBomberoSection" style="display: none;">
                    <h4 style="margin-bottom: 10px;">
                        <i class="fas fa-user-plus"></i> Agregar Bombero Adicional
                    </h4>
                    <div class="form-row">
                        <div class="form-group" style="flex: 1;">
                            <select id="bomberoAdicional" style="width: 100%;">
                                <option value="">Seleccione un bombero adicional</option>
                                <?php foreach ($bomberos_todos as $bombero): ?>
                                    <option value="<?php echo $bombero['id']; ?>" 
                                            data-nombre="<?php echo htmlspecialchars($bombero['apellido'] . ', ' . $bombero['nombre']); ?>"
                                            data-codigo="<?php echo htmlspecialchars($bombero['codigo_juramento']); ?>"
                                            data-tipo="<?php echo htmlspecialchars($bombero['tipo']); ?>">
                                        <?php echo htmlspecialchars($bombero['apellido'] . ', ' . $bombero['nombre'] . ' (' . $bombero['codigo_juramento'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="button" class="btn btn-info btn-sm" onclick="agregarBomberoAdicional()">
                                <i class="fas fa-plus"></i> Agregar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="observaciones_generales">Observaciones Generales (opcional)</label>
                <textarea id="observaciones_generales" name="observaciones_generales" rows="4"
                          placeholder="Observaciones generales de la guardia..."><?php echo htmlspecialchars(isset($_POST['observaciones_generales']) ? $_POST['observaciones_generales'] : ''); ?></textarea>
            </div>

            <div class="form-group" style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Registrar Guardia
                </button>
                <a href="<?php echo $base_url; ?>modulos/guardias/listar.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>

    <script>
        let bomberoCount = 0;
        let bomberosGrupo = [];
        let bomberosAgregados = [];

        function toggleTurno() {
            const tipo = document.getElementById('tipo').value;
            const turnoGroup = document.getElementById('turnoGroup');
            const turnoSelect = document.getElementById('turno');
            
            if (tipo === 'diurna') {
                turnoGroup.style.display = 'block';
                turnoSelect.required = true;
                // Asegurar que tenga un valor por defecto
                if (!turnoSelect.value) {
                    turnoSelect.value = '1';
                }
            } else if (tipo === 'nocturna') {
                turnoGroup.style.display = 'none';
                turnoSelect.required = false;
                // Limpiar el valor del turno para guardias nocturnas
                turnoSelect.value = '';
                // Ocultar columna de apoyo si es nocturna
                const apoyoHeaders = document.querySelectorAll('th:nth-child(7), td:nth-child(7)');
                apoyoHeaders.forEach(el => {
                    el.style.display = 'none';
                });
            } else {
                turnoGroup.style.display = 'none';
                turnoSelect.required = false;
                turnoSelect.value = '';
            }
        }

        function cargarBomberosGrupo() {
            const grupoId = document.getElementById('grupo_id').value;
            
            if (!grupoId) {
                document.getElementById('bomberosGrupoContainer').style.display = 'none';
                document.getElementById('noGrupoSelected').style.display = 'block';
                document.getElementById('agregarBomberoSection').style.display = 'none';
                return;
            }

            // Mostrar sección de bomberos
            document.getElementById('bomberosGrupoContainer').style.display = 'block';
            document.getElementById('noGrupoSelected').style.display = 'none';
            document.getElementById('agregarBomberoSection').style.display = 'block';

            // Realizar petición AJAX para cargar bomberos del grupo
            const xhr = new XMLHttpRequest();
            xhr.open('GET', '<?php echo $base_url; ?>modulos/guardias/get_bomberos_grupo.php?grupo_id=' + grupoId, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        bomberosGrupo = JSON.parse(xhr.responseText);
                        renderizarTablaBomberos();
                    } catch (e) {
                        console.error('Error al parsear respuesta:', e);
                    }
                }
            };
            xhr.send();
        }

        function renderizarTablaBomberos() {
            const tbody = document.getElementById('bomberosTableBody');
            tbody.innerHTML = '';

            bomberosGrupo.forEach((bombero, index) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>
                        <input type="checkbox" class="bombero-checkbox" 
                               name="bomberos[${bomberoCount}][id]" 
                               value="${bombero.id}" 
                               onchange="actualizarBomberoSeleccionado(${index})">
                    </td>
                    <td>
                        <div class="bombero-info">
                            <strong>${bombero.apellido}, ${bombero.nombre}</strong>
                        </div>
                    </td>
                    <td>${bombero.codigo_juramento}</td>
                    <td>
                        <span class="badge ${bombero.tipo.includes('rentado') ? 'badge-warning' : 'badge-info'}">
                            ${bombero.tipo.replace(',', ' / ')}
                        </span>
                    </td>
                    <td>
                        <input type="checkbox" 
                               name="bomberos[${bomberoCount}][es_reemplazo]" 
                               value="1" 
                               onchange="toggleReemplazoSelect(${bomberoCount})"
                               id="reemplazo_${bomberoCount}">
                    </td>
                    <td>
                        <select name="bomberos[${bomberoCount}][reemplazado_id]" 
                                class="reemplazo-select" 
                                id="reemplazo_select_${bomberoCount}">
                            <option value="">Seleccione...</option>
                            ${bomberosGrupo.map(b => 
                                b.id !== bombero.id ? 
                                `<option value="${b.id}">${b.apellido}, ${b.nombre}</option>` : 
                                ''
                            ).join('')}
                        </select>
                    </td>
                    <td>
                        <input type="checkbox" 
                               name="bomberos[${bomberoCount}][es_apoyo]" 
                               value="1">
                    </td>
                    <td>
                        <input type="text" 
                               name="bomberos[${bomberoCount}][observaciones]" 
                               class="observaciones-input" 
                               placeholder="Observaciones...">
                    </td>
                `;
                tbody.appendChild(tr);
                bomberoCount++;
            });
        }

        function toggleAllBomberos() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.bombero-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = selectAll.checked;
                const index = Array.from(checkboxes).indexOf(cb);
                actualizarBomberoSeleccionado(index);
            });
        }

        function actualizarBomberoSeleccionado(index) {
            // Esta función se puede usar para actualizar el estado
        }

        function toggleReemplazoSelect(index) {
            const checkbox = document.getElementById('reemplazo_' + index);
            const select = document.getElementById('reemplazo_select_' + index);
            if (checkbox.checked) {
                select.style.display = 'block';
                select.required = true;
            } else {
                select.style.display = 'none';
                select.required = false;
                select.value = '';
            }
        }

        function agregarBomberoAdicional() {
            const select = document.getElementById('bomberoAdicional');
            const option = select.options[select.selectedIndex];
            
            if (!option.value) {
                alert('Por favor seleccione un bombero');
                return;
            }

            const bomberoId = option.value;
            
            // Verificar si ya está agregado
            if (bomberosAgregados.includes(bomberoId)) {
                alert('Este bombero ya está en la lista');
                return;
            }

            // Agregar a la lista de agregados
            bomberosAgregados.push(bomberoId);

            // Agregar fila a la tabla
            const tbody = document.getElementById('bomberosTableBody');
            const tr = document.createElement('tr');
            tr.style.background = '#fef3c7';
            tr.innerHTML = `
                <td>
                    <input type="checkbox" class="bombero-checkbox" 
                           name="bomberos[${bomberoCount}][id]" 
                           value="${bomberoId}" 
                           checked>
                </td>
                <td>
                    <div class="bombero-info">
                        <strong>${option.getAttribute('data-nombre')}</strong>
                        <span style="color: #f59e0b; font-size: 12px;">(Adicional)</span>
                    </div>
                </td>
                <td>${option.getAttribute('data-codigo')}</td>
                <td>
                    <span class="badge badge-info">
                        ${option.getAttribute('data-tipo').replace(',', ' / ')}
                    </span>
                </td>
                <td>
                    <input type="checkbox" 
                           name="bomberos[${bomberoCount}][es_reemplazo]" 
                           value="1" 
                           onchange="toggleReemplazoSelect(${bomberoCount})"
                           id="reemplazo_${bomberoCount}">
                </td>
                <td>
                    <select name="bomberos[${bomberoCount}][reemplazado_id]" 
                            class="reemplazo-select" 
                            id="reemplazo_select_${bomberoCount}">
                        <option value="">Seleccione...</option>
                        ${bomberosGrupo.map(b => 
                            `<option value="${b.id}">${b.apellido}, ${b.nombre}</option>`
                        ).join('')}
                    </select>
                </td>
                <td>
                    <input type="checkbox" 
                           name="bomberos[${bomberoCount}][es_apoyo]" 
                           value="1">
                </td>
                <td>
                    <input type="text" 
                           name="bomberos[${bomberoCount}][observaciones]" 
                           class="observaciones-input" 
                           placeholder="Observaciones...">
                </td>
            `;
            tbody.appendChild(tr);
            bomberoCount++;

            // Limpiar select
            select.value = '';
        }

        // Inicializar
        toggleTurno();
        if (document.getElementById('grupo_id').value) {
            cargarBomberosGrupo();
        }
    </script>

    <?php include $base_path . 'includes/footer.php'; ?>
</body>
</html>
