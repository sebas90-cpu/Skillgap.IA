<?php
session_start();

// 1. Cargar la conexión desde la raíz
require_once __DIR__ . '/../conexion.php';

// 2. Validar sesión activa (usando la constante BASE_URL)
if (!isset($_SESSION['id']) && !isset($_SESSION['usuario']) && !isset($_SESSION['persona_id']) && !isset($_SESSION['id_usuario'])) {
    header("Location: " . BASE_URL . "registro_login/login.php");
    exit();
}

// Activar reporte temporal de errores de SQL para diagnóstico si algo falla
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Variables de usuario robustas
$persona_id = $_SESSION['persona_id'] ?? $_SESSION['id_usuario'] ?? $_SESSION['id'] ?? 1;
$nombre_aprendiz = $_SESSION['nombre'] ?? $_SESSION['usuario'] ?? 'Aprendiz';
$fichas_programa = $_SESSION['programa'] ?? 'Programa Formativo';

$certificados_disponibles = [];

try {
    // Consulta corregida usando 'ultima_actualizacion' tal como está en tu base de datos
    $sql_cert = "SELECT c.nombre as competencia_nombre, p.nivel_actual as porcentaje, p.ultima_actualizacion as fecha 
                 FROM progreso p 
                 INNER JOIN competencias c ON p.competencia_id = c.id 
                 WHERE p.persona_id = ? AND p.nivel_actual >= 70 
                 ORDER BY p.nivel_actual DESC";
                 
    $stmt = $conexion->prepare($sql_cert);
    $stmt->bind_param("i", $persona_id);
    $stmt->execute();
    $certificados_disponibles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $certificados_disponibles = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Certificados - Portal Aprendiz</title>

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
                <a href="mi_progreso.php">
                    <i class="ri-bar-chart-box-line"></i>
                    <span>Mi Progreso</span>
                </a>
                <a href="mis_certificados.php" class="activo">
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

            <!-- CONTENIDO DE LA VISTA MIS CERTIFICADOS -->
            <div class="quiz-container">
                <div class="quiz-header">
                    <div>
                        <h2><i class="ri-award-line" style="color: #2563EB;"></i> Certificados de Competencias</h2>
                        <small style="color: #64748B;">Descarga tus constancias de aprobación obtenidas al superar el puntaje mínimo requerido (70%).</small>
                    </div>
                </div>

                <?php if (!empty($certificados_disponibles)): ?>
                    <div style="margin-top: 25px; display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                        <?php foreach ($certificados_disponibles as $cert): ?>
                            <div style="background: #FFFFFF; border: 1px solid #E2E8F0; padding: 25px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); display: flex; flex-direction: column; justify-content: space-between;">
                                <div>
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                                        <div style="background: #F0FDF4; color: #166534; padding: 8px 12px; border-radius: 8px; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                                            <i class="ri-checkbox-circle-fill"></i> Aprobado (<?php echo intval($cert['porcentaje']); ?>%)
                                        </div>
                                        <i class="ri-award-line" style="font-size: 28px; color: #2563EB;"></i>
                                    </div>
                                    <h3 style="color: #1E293B; font-size: 16px; margin-bottom: 8px;"><?php echo htmlspecialchars($cert['competencia_nombre']); ?></h3>
                                    <p style="color: #64748B; font-size: 13px; margin-bottom: 20px;">
                                        Certificado oficial emitido por superación de competencia mediante evaluación de Inteligencia Artificial.
                                    </p>
                                </div>
                                <a href="generar_pdf.php?competencia=<?php echo urlencode($cert['competencia_nombre']); ?>&score=<?php echo $cert['porcentaje']; ?>" target="_blank" class="btn-principal" style="text-decoration: none; text-align: center; display: block; padding: 10px;">
                                    <i class="ri-download-line"></i> Descargar Certificado
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <!-- ESTADO VACÍO SI NINGUNA COMPETENCIA SUPERA EL 70% -->
                    <div style="text-align: center; padding: 60px 20px; background: #F8FAFC; border: 2px dashed #E2E8F0; border-radius: 16px; margin-top: 25px;">
                        <div style="font-size: 48px; color: #94A3B8; margin-bottom: 15px;">
                            <i class="ri-lock-line"></i>
                        </div>
                        <h3 style="color: #1E293B; font-size: 18px; margin-bottom: 8px;">Aún no tienes certificados disponibles</h3>
                        <p style="color: #64748B; font-size: 14px; max-width: 420px; margin: 0 auto 20px auto;">
                            Para obtener y descargar un certificado, debes completar las pruebas de tus módulos de competencias y alcanzar un puntaje mínimo del <strong>70%</strong>.
                        </p>
                        <a href="evaluacion_ia.php" class="btn-principal" style="display: inline-block; text-decoration: none;">
                            Presentar Evaluación
                        </a>
                    </div>
                <?php endif; ?>

            </div>

            <footer class="footer-dashboard" style="margin-top: 40px;">
                <p>&copy; <?php echo date('Y'); ?> SkillGap AI - Todos los derechos reservados.</p>
            </footer>

        </main>
    </div>
</body>
</html>