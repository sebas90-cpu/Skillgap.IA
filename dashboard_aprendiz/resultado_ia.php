<?php
session_start();

// 1. Cargar conexión desde la raíz del proyecto
require_once __DIR__ . '/../conexion.php';

// Validar sesión con soporte para múltiples nombres de llaves de usuario
if (!isset($_SESSION['id']) && !isset($_SESSION['usuario']) && !isset($_SESSION['persona_id']) && !isset($_SESSION['id_usuario'])) {
    $redirect_url = defined('BASE_URL') ? BASE_URL . "login.php" : "login.php";
    header("Location: " . $redirect_url);
    exit();
}

$persona_id = $_SESSION['persona_id'] ?? $_SESSION['id_usuario'] ?? $_SESSION['id'] ?? 0;
$competencia_id = isset($_GET['competencia_id']) ? intval($_GET['competencia_id']) : 0;

if ($competencia_id === 0) {
    header("Location: dashboard.php?error=sin_competencia");
    exit();
}

// 2. Obtener el último análisis de IA para esta competencia y usuario
$stmtAnalisis = $conexion->prepare("
    SELECT a.*, c.nombre as competencia_nombre, c.descripcion as competencia_descripcion 
    FROM analisis_ia a 
    JOIN competencias c ON a.competencia_id = c.id 
    WHERE a.persona_id = ? AND a.competencia_id = ? 
    ORDER BY a.id DESC LIMIT 1
");
$stmtAnalisis->bind_param("ii", $persona_id, $competencia_id);
$stmtAnalisis->execute();
$resultado = $stmtAnalisis->get_result();

if ($resultado->num_rows === 0) {
    header("Location: dashboard.php?error=sin_analisis");
    exit();
}

$evaluacion = $resultado->fetch_assoc();

// Datos del usuario para la barra superior
$nombre_aprendiz = $_SESSION['nombre'] ?? $_SESSION['usuario'] ?? 'Aprendiz';
$fichas_programa = $_SESSION['programa'] ?? 'Programa Formativo';

// 3. Obtener el puntaje actualizado desde 'persona_competencia' o 'progreso'
$stmtPC = $conexion->prepare("SELECT despues FROM persona_competencia WHERE persona = ? AND competencia = ?");
$stmtPC->bind_param("ii", $persona_id, $competencia_id);
$stmtPC->execute();
$resPC = $stmtPC->get_result();

if ($resPC->num_rows > 0) {
    $puntaje = $resPC->fetch_assoc()['despues'];
} else {
    // Intentar buscar en la tabla progreso
    $stmtProg = $conexion->prepare("SELECT nivel_actual FROM progreso WHERE persona_id = ? AND competencia_id = ?");
    $stmtProg->bind_param("ii", $persona_id, $competencia_id);
    $stmtProg->execute();
    $resProg = $stmtProg->get_result();
    $puntaje = ($resProg->num_rows > 0) ? $resProg->fetch_assoc()['nivel_actual'] : 75;
}

// Función helper para renderizar texto plano o con viñetas
function renderizarListadoTexto($texto) {
    if (empty(trim($texto))) {
        return '<p style="color: #94A3B8; font-style: italic;">Sin observaciones registradas.</p>';
    }
    
    // Si contiene viñetas, convertirlo en lista HTML limpia
    if (strpos($texto, '•') !== false) {
        $lineas = explode("\n", $texto);
        $html = '<ul style="margin: 0; padding-left: 20px; color: #475569;">';
        foreach ($lineas as $linea) {
            $limpia = trim(str_replace('•', '', $linea));
            if (!empty($limpia)) {
                $html .= '<li style="margin-bottom: 6px;">' . htmlspecialchars($limpia) . '</li>';
            }
        }
        $html .= '</ul>';
        return $html;
    }

    return '<p>' . nl2br(htmlspecialchars($texto)) . '</p>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado de Evaluación IA</title>

    <!-- RemixIcon & Google Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS Unificado del Dashboard -->
    <link rel="stylesheet" type="text/css" href="dashboard.css?v=<?php echo time(); ?>">
    
    <style>
        .resultado-container {
            max-width: 900px;
            margin: 0 auto;
            background: #FFFFFF;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
        }
        .resultado-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #F1F5F9;
            padding-bottom: 25px;
            margin-bottom: 30px;
        }
        .badge-nivel {
            background: #EFF6FF;
            color: #2563EB;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }
        .grid-resultados {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .card-caja {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            padding: 20px;
        }
        .card-caja h4 {
            font-size: 16px;
            color: #0F172A;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-caja p, .card-caja ul {
            font-size: 14px;
            color: #475569;
            line-height: 1.6;
            margin: 0;
        }
        .analisis-general-box {
            background: #F0FDF4;
            border: 1px solid #BBF7D0;
            border-radius: 14px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .analisis-general-box h4 {
            color: #166534;
            margin-bottom: 10px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .analisis-general-box p {
            color: #15803D;
            font-size: 15px;
            line-height: 1.6;
            margin: 0;
        }
    </style>
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
                <a href="evaluacion_ia.php">
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

            <div class="resultado-container">

                <div class="resultado-header">
                    <div>
                        <span style="font-size: 13px; color: #2563EB; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Diagnóstico de Inteligencia Artificial</span>
                        <h2 style="color: #0F172A; margin-top: 4px; font-size: 24px;"><?php echo htmlspecialchars($evaluacion['competencia_nombre']); ?></h2>
                    </div>
                    <div style="text-align: right;">
                        <span class="badge-nivel">Nivel: <?php echo htmlspecialchars($evaluacion['nivel']); ?></span>
                        <div style="font-size: 13px; color: #64748B; margin-top: 6px;">Puntaje: <strong><?php echo round($puntaje, 1); ?>/100</strong></div>
                    </div>
                </div>

                <!-- ANÁLISIS GENERAL DESTACADO -->
                <div class="analisis-general-box">
                    <h4><i class="ri-brain-line"></i> Análisis General del Evaluador</h4>
                    <p><?php echo nl2br(htmlspecialchars($evaluacion['analisis_completo'])); ?></p>
                </div>

                <!-- CUADRÍCULA DE FORTALEZAS, OPORTUNIDADES Y RECOMENDACIONES -->
                <div class="grid-resultados">
                    
                    <div class="card-caja">
                        <h4 style="color: #059669;"><i class="ri-checkbox-circle-line"></i> Fortalezas Detectadas</h4>
                        <?php echo renderizarListadoTexto($evaluacion['fortalezas']); ?>
                    </div>

                    <div class="card-caja">
                        <h4 style="color: #D97706;"><i class="ri-error-warning-line"></i> Oportunidades de Mejora</h4>
                        <?php echo renderizarListadoTexto($evaluacion['oportunidades']); ?>
                    </div>

                    <div class="card-caja">
                        <h4 style="color: #2563EB;"><i class="ri-lightbulb-line"></i> Recomendaciones</h4>
                        <?php echo renderizarListadoTexto($evaluacion['recomendaciones']); ?>
                    </div>

                </div>

                <!-- ACCIONES DE RETORNO -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 40px; padding-top: 20px; border-top: 2px solid #F1F5F9;">
                    <a href="evaluacion_ia.php" class="btn-secundario">Evaluar otro módulo</a>
                    <a href="dashboard.php" class="btn-principal" style="text-decoration: none;">Volver al Panel Principal</a>
                </div>

            </div>

            <footer class="footer-dashboard" style="margin-top: 40px;">
                <p>&copy; <?php echo date('Y'); ?> Sistema de Evaluación con IA - Todos los derechos reservados.</p>
            </footer>

        </main>
    </div>

</body>
</html>