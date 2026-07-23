<?php
session_start();

// 1. Validar sesión activa
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

require_once("conexion.php");

$persona_id = $_SESSION['id'];
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

// Obtener datos del usuario para la barra superior
$nombre_aprendiz = $_SESSION['nombre'] ?? $_SESSION['usuario'] ?? 'Aprendiz';
$fichas_programa = $_SESSION['programa'] ?? 'Programa Formativo';

// Buscar puntaje en la tabla de progreso/competencias si lo guardaste ahí
$stmtPC = $conexion->prepare("SELECT despues FROM persona_competencia WHERE persona = ? AND competencia = ?");
$stmtPC->bind_param("ii", $persona_id, $competencia_id);
$stmtPC->execute();
$resPC = $stmtPC->get_result();
$puntaje = ($resPC->num_rows > 0) ? $resPC->fetch_assoc()['despues'] : 80;
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
            padding-left: 15px;
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
                        <div style="font-size: 13px; color: #64748B; margin-top: 6px;">Puntaje: <strong><?php echo $puntaje; ?>/100</strong></div>
                    </div>
                </div>

                <!-- ANÁLISIS GENERAL DESTACADO -->
                <div class="analisis-general-box">
                    <h4><i class="ri-brain-line"></i> Análisis General del Evaluador</h4>
                    <p><?php echo nl2br(htmlspecialchars($evaluacion['analisis_completo'])); ?></p>
                </div>

                <!-- CUADRICULA DE FORTALEZAS, OPORTUNIDADES Y RECOMENDACIONES -->
                <div class="grid-resultados">
                    
                    <div class="card-caja">
                        <h4 style="color: #059669;"><i class="ri-checkbox-circle-line"></i> Fortalezas Detectadas</h4>
                        <p><?php echo nl2br(htmlspecialchars($evaluacion['fortalezas'])); ?></p>
                    </div>

                    <div class="card-caja">
                        <h4 style="color: #D97706;"><i class="ri-error-warning-line"></i> Oportunidades de Mejora</h4>
                        <p><?php echo nl2br(htmlspecialchars($evaluacion['oportunidades'])); ?></p>
                    </div>

                    <div class="card-caja">
                        <h4 style="color: #2563EB;"><i class="ri-lightbulb-line"></i> Recomendaciones</h4>
                        <p><?php echo nl2br(htmlspecialchars($evaluacion['recomendaciones'])); ?></p>
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