<?php
session_start();

// 1. Cargar la conexión desde la raíz
require_once __DIR__ . '/../conexion.php';

// 2. Validar sesión activa (usando la constante BASE_URL)
if (!isset($_SESSION['id']) && !isset($_SESSION['usuario']) && !isset($_SESSION['persona_id']) && !isset($_SESSION['id_usuario'])) {
    header("Location: " . BASE_URL . "registro_login/login.php");
    exit();
}

// Activar reporte de errores de MySQLi
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Variables de usuario robustas
$persona_id = $_SESSION['persona_id'] ?? $_SESSION['id_usuario'] ?? $_SESSION['id'] ?? 1;
$nombre_aprendiz = $_SESSION['nombre'] ?? $_SESSION['usuario'] ?? 'Aprendiz';
$fichas_programa = $_SESSION['programa'] ?? 'Programa Formativo';

// Variables de progreso por defecto
$progreso_competencias = [];
$promedio_total = 0;

try {
    // Consultar el progreso de las competencias del aprendiz uniendo la tabla progreso con competencias
    $sql_progreso = "SELECT c.nombre as tema, p.nivel_actual as porcentaje 
                     FROM progreso p 
                     INNER JOIN competencias c ON p.competencia_id = c.id 
                     WHERE p.persona_id = ?";
    $stmt = $conexion->prepare($sql_progreso);
    $stmt->bind_param("i", $persona_id);
    $stmt->execute();
    $progreso_competencias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Calcular un promedio general si existen registros
    if (!empty($progreso_competencias)) {
        $suma = array_sum(array_column($progreso_competencias, 'porcentaje'));
        $promedio_total = round($suma / count($progreso_competencias), 1);
    }
} catch (Exception $e) {
    $progreso_competencias = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Progreso - Portal Aprendiz</title>

    <!-- RemixIcon & Google Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS Unificado del Dashboard -->
    <link rel="stylesheet" type="text/css" href="dashboard.css?v=<?php echo time(); ?>">
</head>
<body>

    <div class="dashboard">

        <!-- BARRA LATERAL (IDÉNTICA A TODO EL PORTAL) -->
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
                <a href="mi_progreso.php" class="activo">
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
                    <a href="<?php echo BASE_URL; ?>registro_login/logout.php" style="color: #ef4444;">
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

            <!-- CONTENIDO DE LA VISTA MI PROGRESO -->
            <div class="quiz-container">
                <div class="quiz-header">
                    <div>
                        <h2><i class="ri-bar-chart-box-line" style="color: #2563EB;"></i> Métricas de Progreso Formativo</h2>
                        <small style="color: #64748B;">Visualiza tu nivel de desarrollo global y la evolución por cada competencia analizada por la IA.</small>
                    </div>
                </div>

                <!-- 1. BARRA DE PROGRESO GENERAL DEL PROGRAMA -->
                <div style="background: #FFFFFF; border: 1px solid #E2E8F0; padding: 25px; border-radius: 12px; margin-top: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <div>
                            <h3 style="color: #1E293B; font-size: 18px; margin-bottom: 4px;">Progreso General del Programa</h3>
                            <p style="color: #64748B; font-size: 13px;">Avance total basado en el promedio acumulado de tus competencias evaluadas.</p>
                        </div>
                        <div style="font-size: 24px; font-weight: 700; color: #2563EB; background: #EFF6FF; padding: 8px 16px; border-radius: 8px; border: 1px solid #BFDBFE;">
                            <?php echo $promedio_total; ?>%
                        </div>
                    </div>
                    <!-- Barra general grande -->
                    <div class="barra" style="background: #F1F5F9; height: 14px; border-radius: 7px; overflow: hidden;">
                        <div class="progreso" style="width: <?php echo min(100, intval($promedio_total)); ?>%; background: #2563EB; height: 100%; border-radius: 7px; transition: width 0.6s ease;"></div>
                    </div>
                </div>

                <!-- 2. DESGLOSE POR MÓDULOS Y COMPETENCIAS INDIVIDUALES -->
                <div style="margin-top: 35px;">
                    <h3 style="color: #1E293B; font-size: 16px; margin-bottom: 20px;">Desglose por Módulos Individuales</h3>

                    <?php if (!empty($progreso_competencias)): ?>
                        <div style="display: flex; flex-direction: column; gap: 20px;">
                            <?php foreach ($progreso_competencias as $comp): ?>
                                <div style="background: #FFFFFF; border: 1px solid #E2E8F0; padding: 20px; border-radius: 12px;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                        <strong style="color: #1E293B; font-size: 15px;"><?php echo htmlspecialchars($comp['tema']); ?></strong>
                                        <span style="color: #2563EB; font-weight: 600; font-size: 14px;"><?php echo intval($comp['porcentaje']); ?>%</span>
                                    </div>
                                    <div class="barra" style="background: #F1F5F9; height: 10px; border-radius: 5px; overflow: hidden;">
                                        <div class="progreso" style="width: <?php echo min(100, intval($comp['porcentaje'])); ?>%; background: #2563EB; height: 100%; border-radius: 5px;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <!-- ESTADO VACÍO SI NO HAY PROGRESO REGISTRADO -->
                        <div style="text-align: center; padding: 50px 20px; background: #F8FAFC; border: 2px dashed #E2E8F0; border-radius: 16px;">
                            <div style="font-size: 42px; color: #94A3B8; margin-bottom: 12px;">
                                <i class="ri-pie-chart-line"></i>
                            </div>
                            <h4 style="color: #1E293B; font-size: 16px; margin-bottom: 6px;">Sin métricas registradas</h4>
                            <p style="color: #64748B; font-size: 13px; max-width: 380px; margin: 0 auto 20px auto;">
                                Aún no cuentas con datos de avance. Completa tu primera evaluación con IA para generar tus estadísticas de progreso general y por módulo.
                            </p>
                            <a href="evaluacion_ia.php" class="btn-principal" style="display: inline-block; text-decoration: none; padding: 10px 20px;">
                                Iniciar una Prueba
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <footer class="footer-dashboard" style="margin-top: 40px;">
                <p>&copy; <?php echo date('Y'); ?> SkillGap AI - Todos los derechos reservados.</p>
            </footer>

        </main>
    </div>
</body>
</html>