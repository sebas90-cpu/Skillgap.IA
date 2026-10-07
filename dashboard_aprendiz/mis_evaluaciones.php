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

// Consultar las evaluaciones o análisis realizados por este aprendiz
$mis_evaluaciones = [];
try {
    $sql = "SELECT a.id, a.competencia_id, c.nombre as competencia_nombre, a.nivel, a.fecha 
            FROM analisis_ia a 
            JOIN competencias c ON a.competencia_id = c.id 
            WHERE a.persona_id = ? 
            ORDER BY a.fecha DESC";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $persona_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $mis_evaluaciones = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $mis_evaluaciones = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Evaluaciones - Portal Aprendiz</title>

    <!-- RemixIcon & Google Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS Unificado del Dashboard -->
    <link rel="stylesheet" type="text/css" href="dashboard.css?v=<?php echo time(); ?>">
</head>
<body>

    <div class="dashboard">

        <!-- BARRA LATERAL (NAVEGACIÓN UNIFICADA) -->
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
                <a href="mis_evaluaciones.php" class="activo">
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

            <!-- CONTENIDO PRINCIPAL DE LA VISTA -->
            <div class="quiz-container">
                <div class="quiz-header">
                    <div>
                        <h2><i class="ri-book-read-line" style="color: #2563EB;"></i> Historial de Evaluaciones</h2>
                        <small style="color: #64748B;">Consulta el registro de tus pruebas presentadas y análisis de la IA.</small>
                    </div>
                    <a href="evaluacion_ia.php" class="btn-principal" style="text-decoration: none;">Nueva Evaluación</a>
                </div>

                <?php if (empty($mis_evaluaciones)): ?>
                    <!-- ESTADO VACÍO: SI NO HAY EVALUACIONES -->
                    <div style="text-align: center; padding: 60px 20px; background: #F8FAFC; border: 2px dashed #E2E8F0; border-radius: 16px; margin-top: 25px;">
                        <div style="font-size: 48px; color: #94A3B8; margin-bottom: 15px;">
                            <i class="ri-folder-open-line"></i>
                        </div>
                        <h3 style="color: #1E293B; font-size: 18px; margin-bottom: 8px;">Aún no tienes evaluaciones</h3>
                        <p style="color: #64748B; font-size: 14px; max-width: 400px; margin: 0 auto 20px auto;">
                            Todavía no has completado ningún módulo de práctica analizado por la IA. Selecciona una competencia para empezar.
                        </p>
                        <a href="evaluacion_ia.php" class="btn-principal" style="display: inline-block; text-decoration: none;">
                            Realizar Prueba Ahora
                        </a>
                    </div>
                <?php else: ?>
                    <!-- LISTADO DE EVALUACIONES SI YA EXISTEN -->
                    <div style="margin-top: 25px; display: flex; flex-direction: column; gap: 15px;">
                        <?php foreach ($mis_evaluaciones as $eval): ?>
                            <div style="background: #FFFFFF; border: 1px solid #E2E8F0; padding: 20px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                                <div>
                                    <h3 style="color: #1E293B; font-size: 16px; margin-bottom: 5px;"><?php echo htmlspecialchars($eval['competencia_nombre']); ?></h3>
                                    <p style="color: #64748B; font-size: 13px;">
                                        Nivel: <strong style="color: #2563EB;"><?php echo htmlspecialchars($eval['nivel']); ?></strong> &bull; Fecha: <?php echo htmlspecialchars($eval['fecha']); ?>
                                    </p>
                                </div>
                                <a href="resultado_ia.php?competencia_id=<?php echo $eval['competencia_id']; ?>" class="btn-secundario" style="text-decoration: none; padding: 8px 16px; font-size: 13px;">
                                    Ver Detalle
                                </a>
                            </div>
                        <?php endforeach; ?>
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