<?php
session_start();

// 1. Validar sesión activa (Asegurando la lectura correcta de la sesión del login)
if (!isset($_SESSION['id']) && !isset($_SESSION['usuario']) && !isset($_SESSION['persona_id']) && !isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

require_once 'conexion.php';

// Activar reporte de errores de MySQLi para control estricto
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Variables de usuario robustas según los estándares de sesión comunes
$persona_id = $_SESSION['persona_id'] ?? $_SESSION['id_usuario'] ?? $_SESSION['id'] ?? 1;
$nombre_aprendiz = $_SESSION['nombre'] ?? $_SESSION['usuario'] ?? 'Aprendiz';
$fichas_programa = $_SESSION['programa'] ?? 'Programa Formativo';

$competencia_id = isset($_GET['competencia_id']) ? intval($_GET['competencia_id']) : 0;

$competencias_disponibles = [];
$nombre_competencia = "";
$preguntas = [];
$result_competencias = [];

try {
    if ($competencia_id === 0) {
        // Cargar las 5 competencias registradas en la BD
        $sql_lista = "SELECT id, nombre, descripcion FROM competencias ORDER BY id ASC LIMIT 5";
        $res_lista = $conexion->query($sql_lista);
        $competencias_disponibles = $res_lista ? $res_lista->fetch_all(MYSQLI_ASSOC) : [];
    } else {
        // Cargar datos de la competencia seleccionada
        $sql_comp = "SELECT nombre FROM competencias WHERE id = ?";
        $stmt_comp = $conexion->prepare($sql_comp);
        $stmt_comp->bind_param("i", $competencia_id);
        $stmt_comp->execute();
        $res_comp = $stmt_comp->get_result()->fetch_assoc();
        $nombre_competencia = $res_comp['nombre'] ?? 'Módulo seleccionado';

        // Cargar las 5 preguntas exactas de esta competencia
        $sql_preg = "SELECT id, pregunta FROM preguntas WHERE competencia_id = ? AND estado = 'Activo' ORDER BY id ASC LIMIT 5";
        $stmt_preg = $conexion->prepare($sql_preg);
        $stmt_preg->bind_param("i", $competencia_id);
        $stmt_preg->execute();
        $preguntas = $stmt_preg->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Cargar avance de las competencias del aprendiz usando la tabla de control del dashboard
    $sql_progreso = "SELECT c.nombre as tema, COALESCE(pc.despues, 0) as porcentaje 
                     FROM competencias c 
                     LEFT JOIN persona_competencia pc ON c.id = pc.competencia AND pc.persona = ? 
                     LIMIT 5";
    $stmt_prog = $conexion->prepare($sql_progreso);
    $stmt_prog->bind_param("i", $persona_id);
    $stmt_prog->execute();
    $result_competencias = $stmt_prog->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    // Captura de errores visibles para evitar pantallas en blanco inesperadas
    echo "<div style='background: #fee2e2; color: #991b1b; padding: 20px; font-family: monospace; margin: 20px; border-radius: 8px;'>";
    echo "<strong>Error en la consulta o base de datos:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluación de Competencias con IA</title>

    <!-- RemixIcon & Google Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS Unificado del Dashboard -->
    <link rel="stylesheet" type="text/css" href="dashboard.css?v=<?php echo time(); ?>">
</head>
<body>

    <div class="dashboard">

        <!-- BARRA LATERAL (NAVEGACIÓN) -->
        <aside class="sidebar">
            <div class="logo">
                <i class="ri-graduation-cap-fill"></i>
                <h2>Portal<br>Aprendiz</h2>
            </div>

            <span class="titulo-menu">MENÚ PRINCIPAL</span>

            <nav class="menu">
                <a href="dashboard.php">
                    <i class="ri-dashboard-line"></i>
                    <span>Mi Panel</span>
                </a>
                <a href="mis_evaluaciones.php">
                    <i class="ri-book-read-line"></i>
                    <span>Mis Evaluaciones</span>
                </a>
                <a href="evaluacion_ia.php" class="activo">
                    <i class="ri-robot-line"></i>
                    <span>Prueba IA</span>
                </a>
                <a href="mi_progreso.php">
                    <i class="ri-bar-chart-box-line"></i>
                    <span>Mi Progreso</span>
                </a>
                <a href="mis_certificados.php">
                    <i class="ri-award-line"></i>
                    <span>Mis Certificados</span>
                </a>
                <a href="configuracion.php">
                    <i class="ri-settings-4-line"></i>
                    <span>Configuración</span>
                </a>
            </nav>

            <div class="menu-inferior">
                <nav class="menu">
                    <a href="logout.php" style="color: #ef4444;">
                        <i class="ri-logout-box-r-line"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </nav>
            </div>
        </aside>

        <!-- ÁREA DE CONTENIDO PRINCIPAL -->
        <main class="contenido">

            <!-- BARRA SUPERIOR DE USUARIO -->
            <div class="barra-superior">
                <div class="usuario-info">
                    <div class="usuario-avatar">
                        <?php echo strtoupper(substr($nombre_aprendiz, 0, 1)); ?>
                    </div>
                    <div class="usuario-texto">
                        <h3><?php echo htmlspecialchars($nombre_aprendiz); ?></h3>
                        <span>Aprendiz - <?php echo htmlspecialchars($fichas_programa); ?></span>
                    </div>
                </div>

                <a href="notificaciones.php" class="notificaciones" style="text-decoration: none; color: inherit;">
                    <i class="ri-notification-3-line" title="Notificaciones"></i>
                </a>
            </div>

            <div class="quiz-container">

                <!-- SI NO HA SELECCIONADO COMPETENCIA, MOSTRAR LA LISTA DE LAS 5 -->
                <?php if ($competencia_id === 0): ?>

                    <h2>Selecciona la Competencia a Evaluar</h2>
                    <p style="color: #64748B; margin-bottom: 25px;">Elige uno de tus módulos de formación para iniciar el cuestionario de casos prácticos.</p>

                    <div class="grid-competencias">
                        <?php foreach ($competencias_disponibles as $comp): ?>
                            <div class="card-competencia">
                                <div>
                                    <h3><?php echo htmlspecialchars($comp['nombre']); ?></h3>
                                    <p><?php echo htmlspecialchars($comp['descripcion'] ?? 'Evaluación de conocimientos y lógica aplicada.'); ?></p>
                                </div>
                                <a href="evaluacion_ia.php?competencia_id=<?php echo $comp['id']; ?>" class="btn-principal" style="text-decoration: none; text-align: center; display: block;">
                                    Iniciar Módulo (5 preguntas)
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <!-- SI YA SELECCIONÓ UNA COMPETENCIA, DIBUJAR SUS 5 PREGUNTAS -->
                <?php else: ?>

                    <div class="quiz-header">
                        <div>
                            <h2><i class="ri-robot-line" style="color: #2563EB;"></i> Módulo: <?php echo htmlspecialchars($nombre_competencia); ?></h2>
                            <small style="color: #64748B;">Responde detalladamente las preguntas del caso práctico.</small>
                        </div>
                    </div>

                    <!-- Barra de avance de la prueba (Gestionada por evaluacion.js) -->
                    <div class="barra-progreso-contenedor">
                        <div class="barra-progreso-info">
                            <span>Avance del módulo</span>
                            <strong id="textoProgresoQuiz" style="color: #2563EB;">0 de 5 respondidas (0%)</strong>
                        </div>
                        <div class="barra-progreso-fondo">
                            <div id="barraProgresoQuiz" class="barra-progreso-relleno"></div>
                        </div>
                    </div>

                    <form id="formEvaluacion" action="procesar_resultado.php" method="POST">
                        <input type="hidden" name="competencia_id" value="<?php echo $competencia_id; ?>">

                        <?php if (!empty($preguntas)): ?>
                            <?php foreach ($preguntas as $index => $preg): ?>
                                <div class="pregunta-card">
                                    <label class="pregunta-titulo">
                                        <?php echo ($index + 1) . '. ' . htmlspecialchars($preg['pregunta']); ?>
                                    </label>
                                    <textarea name="respuestas[<?php echo $preg['id']; ?>]" rows="4" placeholder="Escribe tu análisis o respuesta técnica aquí..."></textarea>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: #64748B; padding: 20px 0;">No se encontraron preguntas activas para esta competencia en la base de datos.</p>
                        <?php endif; ?>

                        <div class="quiz-acciones">
                            <a href="evaluacion_ia.php" class="btn-secundario">Cambiar Módulo</a>
                            <?php if (!empty($preguntas)): ?>
                                <button type="submit" class="btn-principal">
                                    <i class="ri-send-plane-fill"></i> Enviar a la IA para Análisis
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>

                <?php endif; ?>

            </div>

            <!-- SECCIÓN INFERIOR: RESUMEN DE COMPETENCIAS -->
            <div class="panel" style="margin-top: 35px;">
                <h2>Progreso en tus Competencias</h2>
                <?php if (!empty($result_competencias)): ?>
                    <?php foreach ($result_competencias as $comp): ?>
                        <div class="competencia">
                            <h4><?php echo htmlspecialchars($comp['tema']); ?></h4>
                            <div class="barra">
                                <div class="progreso" style="width: <?php echo min(100, intval($comp['porcentaje'])); ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #64748B; font-size: 14px; margin-top: 10px;">
                        Tus avances se actualizarán cuando completes tus respuestas.
                    </p>
                <?php endif; ?>
            </div>

            <footer class="footer-dashboard" style="margin-top: 40px;">
                <p>&copy; <?php echo date('Y'); ?> Sistema de Evaluación con IA - Todos los derechos reservados.</p>
            </footer>

        </main>
    </div>

    <!-- Enlace al JS interactivo de detección de texto -->
    <script src="evaluacion.js?v=<?php echo time(); ?>"></script>
</body>
</html>