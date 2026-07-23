<?php
session_start();

// 1. Validar sesión activa
if (!isset($_SESSION['id_usuario']) && !isset($_SESSION['usuario']) && !isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

// 2. Incluir conexión a la base de datos
require_once 'conexion.php'; 

// Activar reporte de errores
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Variables de sesión (Usando la tabla 'personas')
$persona_id = $_SESSION['persona_id'] ?? $_SESSION['id_usuario'] ?? $_SESSION['id'] ?? 0;
$nombre_aprendiz = $_SESSION['nombre'] ?? $_SESSION['usuario'] ?? 'Aprendiz';
$fichas_programa = $_SESSION['programa'] ?? 'Programa Formativo';

// Variables por defecto
$evaluaciones_listas = 0;
$evaluaciones_proceso = 0;
$promedio_general = '0.0';
$result_historial = [];
$result_competencias = [];

// -----------------------------------------------------------------
// 3. CONSULTAS SQL BASADAS EN TU DIAGRAMA DE BASE DE DATOS
// -----------------------------------------------------------------
try {
    // A. Evaluaciones Completadas (basado en tabla 'evaluaciones')
    $sql_listas = "SELECT COUNT(*) as total FROM evaluaciones WHERE persona_id = ? AND fecha_fin IS NOT NULL";
    $stmt = $conexion->prepare($sql_listas);
    $stmt->bind_param("i", $persona_id);
    $stmt->execute();
    $evaluaciones_listas = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    // B. Evaluaciones En Proceso
    $sql_proceso = "SELECT COUNT(*) as total FROM evaluaciones WHERE persona_id = ? AND fecha_fin IS NULL";
    $stmt = $conexion->prepare($sql_proceso);
    $stmt->bind_param("i", $persona_id);
    $stmt->execute();
    $evaluaciones_proceso = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    // C. Nivel Promedio / Nivel Actual (basado en tabla 'progreso')
    $sql_promedio = "SELECT AVG(nivel_actual) as promedio FROM progreso WHERE persona_id = ?";
    $stmt = $conexion->prepare($sql_promedio);
    $stmt->bind_param("i", $persona_id);
    $stmt->execute();
    $prom_res = $stmt->get_result()->fetch_assoc()['promedio'];
    $promedio_general = $prom_res ? number_format($prom_res, 1) : '0.0';

    // D. Últimas Evaluaciones Realizadas (Uniendo 'evaluaciones' y 'casos')
    $sql_historial = "SELECT c.titulo as titulo_materia, e.fecha_inicio, e.fecha_fin 
                      FROM evaluaciones e 
                      LEFT JOIN casos c ON e.caso_id = c.id 
                      WHERE e.persona_id = ? 
                      ORDER BY e.fecha_inicio DESC LIMIT 5";
    $stmt = $conexion->prepare($sql_historial);
    $stmt->bind_param("i", $persona_id);
    $stmt->execute();
    $result_historial = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // E. Progreso de Competencias (Uniendo 'progreso' y 'competencias')
    $sql_competencias = "SELECT c.nombre as tema, p.nivel_actual as porcentaje 
                          FROM progreso p 
                          INNER JOIN competencias c ON p.competencia_id = c.id 
                          WHERE p.persona_id = ?";
    $stmt = $conexion->prepare($sql_competencias);
    $stmt->bind_param("i", $persona_id);
    $stmt->execute();
    $result_competencias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    // Si la BD aún está vacía o hay algún campo nulo, cargará en 0 limpio.
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Aprendiz</title>
    
    <!-- RemixIcon & Google Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS del Dashboard -->
    <link rel="stylesheet" type="text/css" href="dashboard.css?v=<?php echo time(); ?>">
</head>
<body>

    <div class="dashboard">

        <!-- BARRA LATERAL CON RUTAS DE REDIRECCIÓN -->
        <aside class="sidebar">
            <div class="logo">
                <i class="ri-graduation-cap-fill"></i>
                <h2>Portal<br>Aprendiz</h2>
            </div>

            <span class="titulo-menu">MENÚ PRINCIPAL</span>

            <nav class="menu">
                <a href="dashboard.php" class="activo">
                    <i class="ri-dashboard-line"></i>
                    <span>Mi Panel</span>
                </a>
                <a href="mis_evaluaciones.php">
                    <i class="ri-book-read-line"></i>
                    <span>Mis Evaluaciones</span>
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

            <!-- BARRA SUPERIOR -->
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

            <!-- ENCABEZADO -->
            <div class="encabezado">
                <h1>¡Hola de nuevo, <?php echo htmlspecialchars(explode(' ', $nombre_aprendiz)[0]); ?>! 👋</h1>
                <p>Aquí tienes el resumen en tiempo real de tu avance formativo.</p>
            </div>

            <!-- TARJETAS DE MÉTRICAS -->
            <div class="tarjetas">
                <div class="card">
                    <i class="ri-checkbox-circle-line"></i>
                    <h3>Evaluaciones Listas</h3>
                    <h2><?php echo $evaluaciones_listas; ?></h2>
                </div>

                <div class="card">
                    <i class="ri-time-line"></i>
                    <h3>En Proceso</h3>
                    <h2><?php echo $evaluaciones_proceso; ?></h2>
                </div>

                <div class="card">
                    <i class="ri-star-line"></i>
                    <h3>Nivel Promedio</h3>
                    <h2><?php echo $promedio_general; ?></h2>
                </div>
            </div>

            <!-- BOTÓN PRUEBA IA -->
            <div class="panel-ia">
                <i class="ri-robot-line"></i>
                <h2>Evaluación por Inteligencia Artificial</h2>
                <p>Inicia un nuevo test automatizado basado en un caso de estudio real para medir tus competencias.</p>
                <a href="evaluacion_ia.php" class="btn-principal" style="text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="ri-play-circle-line"></i> Iniciar Prueba IA
                </a>
            </div>

            <!-- PANELES DINÁMICOS -->
            <div class="paneles">
                
                <!-- Historial de Evaluaciones -->
                <div class="panel">
                    <h2>Últimas Evaluaciones</h2>
                    <div class="lista">
                        <?php if (!empty($result_historial)): ?>
                            <?php foreach ($result_historial as $row): ?>
                                <div class="lista-item">
                                    <div>
                                        <strong><?php echo htmlspecialchars($row['titulo_materia'] ?? 'Evaluación General'); ?></strong><br>
                                        <small style="color: #64748B;">
                                            <?php echo date('d/m/Y H:i', strtotime($row['fecha_inicio'])); ?>
                                        </small>
                                    </div>
                                    <span class="estado <?php echo $row['fecha_fin'] ? 'aprobado' : 'proceso'; ?>">
                                        <?php echo $row['fecha_fin'] ? 'Completado' : 'En Curso'; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: #64748B; font-size: 14px; margin-top: 10px;">Aún no has presentado evaluaciones.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Competencias del Aprendiz -->
                <div class="panel">
                    <h2>Mis Competencias</h2>
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
                        <p style="color: #64748B; font-size: 14px; margin-top: 10px;">Tus métricas de competencias se calcularán cuando completes evaluaciones.</p>
                    <?php endif; ?>
                </div>

            </div>

            <footer class="footer-dashboard">
                <p>&copy; <?php echo date('Y'); ?> Sistema de Evaluación con IA - Todos los derechos reservados.</p>
            </footer>

        </main>
    </div>

    <script src="dashboard.js"></script>
</body>
</html> 