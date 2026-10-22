<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../conexion.php';

// 1. Validar sesión de Instructor / Admin
if (!isset($_SESSION['persona_id']) && !isset($_SESSION['id_usuario']) && !isset($_SESSION['id'])) {
    header("Location: ../registro_login/login.php");
    exit();
}

if (isset($_SESSION['rol_id']) && intval($_SESSION['rol_id']) !== 3 && intval($_SESSION['rol_id']) !== 1) {
    header("Location: ../dashboard_aprendiz/dashboard.php");
    exit();
}

$mensaje_exito = "";
$mensaje_error = "";

// 2. Procesar el formulario al enviar (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_guardar'])) {$competencia_opcion = isset($_POST['competencia_id']) ?$_POST['competencia_id'] : '';
    $nueva_comp_nombre = isset($_POST['nueva_competencia_nombre']) ? trim($_POST['nueva_competencia_nombre']) : '';$nueva_comp_desc = isset($_POST['nueva_competencia_desc']) ? trim($_POST['nueva_competencia_desc']) : '';

    $titulo_caso = isset($_POST['titulo_caso']) ? trim($_POST['titulo_caso']) : '';
    $nivel_caso = isset($_POST['nivel_caso']) ? trim($_POST['nivel_caso']) : 'Intermedio';$descripcion_caso = isset($_POST['descripcion_caso']) ? trim($_POST['descripcion_caso']) : '';
    
    $preguntas = isset($_POST['preguntas']) && is_array($_POST['preguntas']) ?$_POST['preguntas'] : array();

    if (empty($titulo_caso) || empty($descripcion_caso)) {$mensaje_error = "Por favor, completa el título y el planteamiento del caso.";
    } else {
        $conexion->begin_transaction();
        try {
            $comp_id_final = 0;

            // A) Si seleccionó crear una nueva competencia
            if ($competencia_opcion === 'NUEVA') {
                if (empty($nueva_comp_nombre)) {
                    throw new Exception("Debes escribir el nombre de la nueva competencia.");
                }

                $stmtCheck =$conexion->prepare("SELECT id FROM competencias WHERE LOWER(nombre) = LOWER(?) LIMIT 1");
                $stmtCheck->bind_param("s", $nueva_comp_nombre);$stmtCheck->execute();
                $resCheck =$stmtCheck->get_result();

                if ($resCheck &&$resCheck->num_rows > 0) {
                    $rowCheck =$resCheck->fetch_assoc();
                    $comp_id_final = intval($rowCheck['id']);
                } else {
                    $stmtInsComp =$conexion->prepare("INSERT INTO competencias (nombre, descripcion) VALUES (?, ?)");
                    if (!$stmtInsComp) {
                        throw new Exception("Error al preparar la competencia: " . $conexion->error);
                    }
                    $stmtInsComp->bind_param("ss", $nueva_comp_nombre, $nueva_comp_desc);$stmtInsComp->execute();
                    $comp_id_final = intval($conexion->insert_id);
                }
            } else {
                $comp_id_final = intval($competencia_opcion);
            }

            if ($comp_id_final <= 0) {
                throw new Exception("Debes seleccionar o especificar una competencia válida.");
            }

            // B) Insertar el Caso Práctico (Estructura BD: competencia_id, titulo, descripcion, nivel)
            $stmtCaso =$conexion->prepare("INSERT INTO casos (competencia_id, titulo, descripcion, nivel) VALUES (?, ?, ?, ?)");
            if (!$stmtCaso) {
                throw new Exception("Error al preparar la inserción del caso: " . $conexion->error);
            }
            $stmtCaso->bind_param("isss", $comp_id_final, $titulo_caso,$descripcion_caso, $nivel_caso);$stmtCaso->execute();

            // C) Insertar las Preguntas (Estructura BD: competencia_id, orden, pregunta, estado)
            if (!empty($preguntas)) {
                $stmtPreg =$conexion->prepare("INSERT INTO preguntas (competencia_id, orden, pregunta, estado) VALUES (?, ?, ?, 'Activo')");
                if (!$stmtPreg) {
                    throw new Exception("Error al preparar la inserción de preguntas: " . $conexion->error);
                }
                
                $orden_num = 1;
                foreach ($preguntas as$txt_preg) {
                    $txt_preg_clean = trim($txt_preg);
                    if (!empty($txt_preg_clean)) {
                        $stmtPreg->bind_param("iis", $comp_id_final, $orden_num,$txt_preg_clean);
                        $stmtPreg->execute();$orden_num++;
                    }
                }
            }

            $conexion->commit();$mensaje_exito = "¡Evaluación y caso práctico creados exitosamente en la base de datos!";
        } catch (Exception $e) {
            $conexion->rollback();$mensaje_error = "Error al guardar: " . $e->getMessage();
        }
    }
}

// 3. Obtener el catálogo de Competencias
$resCompetencias =$conexion->query("SELECT MIN(id) AS id, nombre FROM competencias GROUP BY nombre ORDER BY nombre ASC");
$competencia_preselect = isset($_GET['competencia_id']) ? intval($_GET['competencia_id']) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Evaluación - SkillGap AI</title>
    
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" type="text/css" href="dashboard_ins.css?v=<?= time() ?>">
</head>
<body>

<div class="dashboard">
    <!-- BARRA LATERAL -->
    <aside class="sidebar">
        <div class="sidebar-top">
            <div class="logo">
                <i class="ri-user-star-fill"></i>
                <h2>Portal<br>Instructor</h2>
            </div>
            <span class="titulo-menu">MENÚ GENERAL</span>
            <nav class="menu">
                <a href="dashboard_ins.php"><i class="ri-dashboard-line"></i><span>Panel Principal</span></a>
                <a href="aprendices.php"><i class="ri-group-line"></i><span>Aprendices</span></a>
                <a href="competencias.php" class="active"><i class="ri-award-line"></i><span>Competencias</span></a>
                <a href="reportes.php"><i class="ri-bar-chart-box-line"></i><span>Reportes IA</span></a>
            </nav>
        </div>

        <div class="menu-inferior">
            <nav class="menu">
                <a href="logout.php" class="btn-logout"><i class="ri-logout-box-r-line"></i><span>Cerrar Sesión</span></a>
            </nav>
        </div>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="contenido">
        <div style="margin-bottom: 16px;">
            <a href="competencias.php" class="btn-volver"><i class="ri-arrow-left-line"></i> Volver a Competencias</a>
        </div>

        <div class="barra-superior">
            <div>
                <h2 style="font-size: 22px; color: #0F172A; font-weight: 700;">Crear Nueva Evaluación / Caso</h2>
                <p style="font-size: 13px; color: #64748B;">Diseña manualmente o apóyate en la IA para generar casos prácticos.</p>
            </div>
        </div>

        <?php if (!empty($mensaje_exito)): ?>
            <div class="alert-msg alert-success"><i class="ri-checkbox-circle-fill"></i> <?= htmlspecialchars($mensaje_exito) ?></div>
        <?php endif; ?>

        <?php if (!empty($mensaje_error)): ?>
            <div class="alert-msg alert-danger"><i class="ri-error-warning-fill"></i> <?= htmlspecialchars($mensaje_error) ?></div>
        <?php endif; ?>

        <!-- PESTAÑAS MODO -->
        <div class="tabs-container">
            <button type="button" class="tab-btn active" id="btnTabManual" onclick="switchTab('manual')">
                <i class="ri-edit-2-line"></i> Creación Manual
            </button>
            <button type="button" class="tab-btn" id="btnTabIA" onclick="switchTab('ia')">
                <i class="ri-magic-line"></i> Asistente de IA
            </button>
        </div>

        <!-- FORMULARIO PRINCIPAL -->
        <form method="POST" action="crear_evaluacion.php" id="formEvaluacion">
            <input type="hidden" name="accion_guardar" value="1">

            <!-- SECCIÓN 1: COMPETENCIA -->
            <div class="form-section">
                <h3 class="seccion-titulo" style="margin-bottom: 16px;"><i class="ri-award-line"></i> 1. Definir Competencia</h3>
                
                <div class="form-group">
                    <label for="competencia_id">Seleccionar Competencia:</label>
                    <select name="competencia_id" id="competencia_id" class="form-control" onchange="toggleNuevaCompetencia(this.value)">
                        <option value="">-- Selecciona una competencia --</option>
                        <option value="NUEVA" style="font-weight: 700; color: #2563EB;">➕ [ + Crear Nueva Competencia ]</option>
                        <option disabled>----------------------------------------</option>
                        <?php if ($resCompetencias &&$resCompetencias->num_rows > 0): ?>
                            <?php while ($c =$resCompetencias->fetch_assoc()): ?>
                                <option value="<?= $c['id'] ?>" <?= $competencia_preselect ==$c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['nombre']) ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- BLOQUE CAMPOS NUEVA COMPETENCIA -->
                <div id="bloqueNuevaCompetencia" style="display: none; background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 12px; padding: 16px; margin-top: 14px;">
                    <h4 style="font-size: 14px; color: #1E40AF; margin-bottom: 10px;"><i class="ri-add-circle-line"></i> Registrar Nueva Competencia en el Catálogo</h4>
                    <div class="form-group">
                        <label for="nueva_competencia_nombre">Nombre de la Competencia:</label>
                        <input type="text" name="nueva_competencia_nombre" id="nueva_competencia_nombre" class="form-control" placeholder="Ej: Arquitectura y Diseño de APIs RESTful">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="nueva_competencia_desc">Descripción:</label>
                        <textarea name="nueva_competencia_desc" id="nueva_competencia_desc" class="form-control" rows="2" placeholder="Describe brevemente qué evalúa esta competencia..."></textarea>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN IA (SÓLO VISIBLE EN TAB IA) -->
            <div class="form-section" id="seccionIA" style="display: none; border-color: #93C5FD; background: #F0F9FF;">
                <h3 class="seccion-titulo" style="color: #0369A1; margin-bottom: 12px;"><i class="ri-robot-line"></i> Asistente de Inteligencia Artificial</h3>
                <p style="font-size: 13px; color: #0284C7; margin-bottom: 16px;">Indica el tema o idea clave y la IA redactará el caso de estudio y sus preguntas automáticamente.</p>

                <div class="form-group">
                    <label for="ia_prompt">Tema o Escenario Clave para la IA:</label>
                    <textarea id="ia_prompt" class="form-control" rows="3" placeholder="Ej: Un sistema e-commerce donde la API de pagos falla durante eventos de alto tráfico debido a falta de manejo de errores, timeouts y validaciones inadecuadas."></textarea>
                </div>

                <button type="button" class="btn-filtrar" style="background: #0284C7;" onclick="generarConIA()">
                    <i class="ri-sparkles-line"></i> Generar Borrador con IA
                </button>
            </div>

            <!-- SECCIÓN 2: CASO PRÁCTICO -->
            <div class="form-section">
                <h3 class="seccion-titulo" style="margin-bottom: 16px;"><i class="ri-article-line"></i> 2. Planteamiento del Caso Práctico</h3>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label for="titulo_caso">Título del Caso:</label>
                        <input type="text" name="titulo_caso" id="titulo_caso" class="form-control" placeholder="Ej: Optimización de Consultas SQL en Producción" required>
                    </div>
                    <div class="form-group">
                        <label for="nivel_caso">Nivel:</label>
                        <select name="nivel_caso" id="nivel_caso" class="form-control">
                            <option value="Básico">Básico</option>
                            <option value="Intermedio" selected>Intermedio</option>
                            <option value="Avanzado">Avanzado</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="descripcion_caso">Descripción / Escenario:</label>
                    <textarea name="descripcion_caso" id="descripcion_caso" class="form-control" rows="5" placeholder="Redacta el escenario del problema que el aprendiz deberá analizar..." required></textarea>
                </div>
            </div>

            <!-- SECCIÓN 3: PREGUNTAS -->
            <div class="form-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h3 class="seccion-titulo" style="margin: 0;"><i class="ri-questionnaire-line"></i> 3. Preguntas de Evaluación</h3>
                    <button type="button" class="btn-limpiar" onclick="agregarPregunta()"><i class="ri-add-line"></i> Agregar Pregunta</button>
                </div>

                <div id="contenedorPreguntas">
                    <div class="pregunta-item">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Pregunta 1:</label>
                            <input type="text" name="preguntas[]" class="form-control" placeholder="Escribe la pregunta..." required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BOTÓN FINAL DE GUARDADO -->
            <div style="text-align: right; margin-top: 24px;">
                <button type="submit" class="btn-filtrar" style="padding: 12px 32px; font-size: 15px;">
                    <i class="ri-save-line"></i> Guardar Evaluación Completa
                </button>
            </div>
        </form>
    </main>
</div>

<script>
let contadorPreguntas = 1;

function toggleNuevaCompetencia(val) {
    const bloque = document.getElementById('bloqueNuevaCompetencia');
    if (!bloque) return;

    if (val === 'NUEVA') {
        bloque.style.display = 'block';
    } else {
        bloque.style.display = 'none';
    }
}

function switchTab(modo) {
    const btnManual = document.getElementById('btnTabManual');
    const btnIA = document.getElementById('btnTabIA');
    const seccionIA = document.getElementById('seccionIA');

    if (modo === 'ia') {
        btnManual.classList.remove('active');
        btnIA.classList.add('active');
        seccionIA.style.display = 'block';
    } else {
        btnIA.classList.remove('active');
        btnManual.classList.add('active');
        seccionIA.style.display = 'none';
    }
}

function agregarPregunta(txtPregunta = '') {
    contadorPreguntas++;
    const contenedor = document.getElementById('contenedorPreguntas');
    
    const div = document.createElement('div');
    div.className = 'pregunta-item';
    div.style.position = 'relative';
    div.innerHTML = `
        <button type="button" class="btn-remove-preg" onclick="this.parentElement.remove()" style="position: absolute; right: 10px; top: 10px; border: none; background: transparent; cursor: pointer; color: #EF4444;"><i class="ri-close-line" style="font-size: 18px;"></i></button>
        <div class="form-group" style="margin-bottom: 0;">
            <label>Pregunta ${contadorPreguntas}:</label>
            <input type="text" name="preguntas[]" class="form-control" value="${txtPregunta.replace(/"/g, '&quot;')}" placeholder="Escribe la pregunta..." required>
        </div>
    `;
    contenedor.appendChild(div);
}

function generarConIA() {
    const promptText = document.getElementById('ia_prompt').value.trim();
    if (!promptText) {
        alert("Por favor escribe un tema o escenario clave para orientar a la IA.");
        return;
    }

    document.getElementById('titulo_caso').value = "Caso Práctico: Resiliencia y Fallas en API de Pagos";
    document.getElementById('descripcion_caso').value = "Un sistema e-commerce de alto tráfico experimenta fallas críticas en el módulo de pagos durante un evento de ofertas masivas (Black Friday). La API REST actual no maneja adecuadamente los timeouts con la pasarela de pagos, los endpoints devuelven códigos HTTP 200 con mensajes de error en el cuerpo JSON, no existe paginación en el catálogo de productos y los clientes están enviando peticiones duplicadas por falta de mecanismos de idempotencia.";

    const contenedor = document.getElementById('contenedorPreguntas');
    contenedor.innerHTML = '';
    contadorPreguntas = 0;

    agregarPregunta("¿Cómo corregirías el uso de códigos de estado HTTP para diferenciar entre una transacción exitosa, un error de validación del cliente y una falla del servidor/pasarela?");
    agregarPregunta("¿Qué mecanismo implementarías a nivel de diseño de la API para evitar que una petición de cobro se procese dos veces si el usuario presiona el botón de pago repetidamente?");
    agregarPregunta("El catálogo de productos colapsa por la cantidad de datos devueltos en una sola consulta. ¿Qué estrategia REST aplicarías para optimizar el endpoint /api/productos?");
    agregarPregunta("¿Cómo gestionarías los tiempos de espera (timeouts) y la degradación del servicio cuando la pasarela de pagos externa no responde a tiempo?");
    agregarPregunta("¿Qué medidas de seguridad y control de tasa de peticiones (Rate Limiting) aplicarías para proteger la API de cobros ante picos sospechosos de tráfico?");

    alert("¡Borrador generado con éxito! Se cargaron las 5 preguntas y el caso práctico.");
}
</script>

</body>
</html>