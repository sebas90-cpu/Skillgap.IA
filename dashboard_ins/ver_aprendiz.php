<?php
session_start();
require_once __DIR__ . '/../conexion.php';

// 1. Validar sesión de Instructor / Evaluador (Rol 3 o Admin)
if (!isset($_SESSION['persona_id']) && !isset($_SESSION['id_usuario']) && !isset($_SESSION['id'])) {
    header("Location: ../registro_login/login.php");
    exit();
}

if (isset($_SESSION['rol_id']) && intval($_SESSION['rol_id']) !== 3 && intval($_SESSION['rol_id']) !== 1) {
    header("Location: ../dashboard_aprendiz/dashboard.php");
    exit();
}

$nombre_instructor = $_SESSION['nombre'] ?? $_SESSION['usuario'] ?? 'Instructor';

// 2. Obtener y validar el ID del aprendiz
$persona_id = isset($_GET['persona_id']) ? intval($_GET['persona_id']) : 0;

if ($persona_id <= 0) {
    header("Location: dashboard_ins.php");
    exit();
}

// 3. Consultar datos del Aprendiz
$stmtAprendiz = $conexion->prepare("SELECT id, nombre, apellido, correo, programa, documento FROM personas WHERE id = ?");
if (!$stmtAprendiz) {
    die("Error en consulta de aprendiz: " . $conexion->error);
}
$stmtAprendiz->bind_param("i", $persona_id);
$stmtAprendiz->execute();
$aprendiz = $stmtAprendiz->get_result()->fetch_assoc();

if (!$aprendiz) {
    header("Location: dashboard_ins.php");
    exit();
}

// 4. Consultar Métricas del Aprendiz
$stmtMetrics = $conexion->prepare("SELECT COUNT(*) AS total_evals, AVG(puntaje) AS promedio FROM evaluaciones WHERE persona_id = ?");
if (!$stmtMetrics) {
    die("Error en métricas: " . $conexion->error);
}
$stmtMetrics->bind_param("i", $persona_id);
$stmtMetrics->execute();
$metrics = $stmtMetrics->get_result()->fetch_assoc();

$total_evals = $metrics['total_evals'] ?? 0;
$promedio_aprendiz = $metrics['promedio'] ?? 0;

// 5. Consultar Historial de Evaluaciones y Diagnóstico de IA
$queryHistorial = "
    SELECT 
        e.id AS evaluacion_id,
        c.nombre AS competencia_nombre,
        c.descripcion AS competencia_desc,
        e.puntaje,
        e.retroalimentacion AS retro_eval,
        e.fecha,
        MAX(a.nivel) AS nivel,
        MAX(a.fortalezas) AS fortalezas,
        MAX(a.oportunidades) AS oportunidades,
        MAX(a.recomendaciones) AS recomendaciones
    FROM evaluaciones e
    INNER JOIN casos cs ON e.caso_id = cs.id
    INNER JOIN competencias c ON cs.competencia_id = c.id
    LEFT JOIN analisis_ia a ON a.persona_id = e.persona_id AND a.competencia_id = c.id
    WHERE e.persona_id = ?
    GROUP BY e.id
    ORDER BY e.fecha DESC
";

$stmtHistorial = $conexion->prepare($queryHistorial);

if (!$stmtHistorial) {
    die("<div style='background: #fee2e2; border: 1px solid #ef4444; color: #b91c1c; padding: 20px; border-radius: 8px; font-family: sans-serif;'>
            <h2>Error SQL en prepare():</h2>
            <p>" . htmlspecialchars($conexion->error) . "</p>
         </div>");
}

$stmtHistorial->bind_param("i", $persona_id);
$stmtHistorial->execute();
$resHistorial = $stmtHistorial->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Aprendiz - SkillGap AI</title>
    
    <!-- RemixIcon & Google Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Hoja de Estilos Exclusiva del Módulo Instructor -->
    <link rel="stylesheet" type="text/css" href="dashboard_ins.css?v=<?= time() ?>">
</head>
<body>

<div class="dashboard">
    <!-- BARRA LATERAL (INSTRUCTOR) -->
    <aside class="sidebar">
        <div class="sidebar-top">
            <div class="logo">
                <i class="ri-user-star-fill"></i>
                <h2>Portal<br>Instructor</h2>
            </div>
            <span class="titulo-menu">MENÚ GENERAL</span>
            <nav class="menu">
                <a href="dashboard_ins.php"><i class="ri-dashboard-line"></i><span>Panel Principal</span></a>
                <a href="aprendices.php" class="active"><i class="ri-group-line"></i><span>Aprendices</span></a>
                <a href="competencias.php"><i class="ri-award-line"></i><span>Competencias</span></a>
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
        <!-- BOTÓN VOLVER -->
        <div style="margin-bottom: 20px;">
            <a href="dashboard_ins.php" class="btn-volver">
                <i class="ri-arrow-left-line"></i> Volver al Panel
            </a>
        </div>

        <!-- TARJETA CABECERA APRENDIZ -->
        <div class="perfil-card">
            <div class="perfil-info">
                <div class="perfil-avatar">
                    <?= strtoupper(substr($aprendiz['nombre'], 0, 1) . substr($aprendiz['apellido'], 0, 1)) ?>
                </div>
                <div class="perfil-datos">
                    <h2><?= htmlspecialchars($aprendiz['nombre'] . ' ' . $aprendiz['apellido']) ?></h2>
                    <p><i class="ri-book-read-line"></i> Programa: <strong><?= htmlspecialchars($aprendiz['programa'] ?: 'No especificado') ?></strong></p>
                    <p><i class="ri-mail-line"></i> Correo: <?= htmlspecialchars($aprendiz['correo']) ?></p>
                    <p><i class="ri-id-card-line"></i> Documento: <?= htmlspecialchars($aprendiz['documento'] ?: 'N/A') ?></p>
                </div>
            </div>
            <div class="perfil-kpis">
                <div class="mini-kpi">
                    <span>Evaluaciones</span>
                    <h3><?= $total_evals ?></h3>
                </div>
                <div class="mini-kpi">
                    <span>Promedio General</span>
                    <h3><?= number_format($promedio_aprendiz, 1) ?>/100</h3>
                </div>
            </div>
        </div>

        <!-- HISTORIAL Y DIAGNÓSTICO IA -->
        <h3 class="seccion-titulo"><i class="ri-brain-line"></i> Diagnóstico de Competencias (IA)</h3>

        <?php if ($resHistorial && $resHistorial->num_rows > 0): ?>
            <div class="evaluaciones-grid">
                <?php while ($eval = $resHistorial->fetch_assoc()): ?>
                    <?php 
                        $nivel_class = 'badge-intermedio';
                        $nivel_txt = $eval['nivel'] ?? 'Intermedio';
                        if (strpos(strtolower($nivel_txt), 'inicial') !== false) $nivel_class = 'badge-inicial';
                        if (strpos(strtolower($nivel_txt), 'avanzado') !== false) $nivel_class = 'badge-avanzado';
                    ?>
                    <div class="evaluacion-card">
                        <div class="eval-header">
                            <div>
                                <h4><?= htmlspecialchars($eval['competencia_nombre']) ?></h4>
                                <span class="eval-fecha"><i class="ri-calendar-line"></i> <?= date('d/m/Y H:i', strtotime($eval['fecha'])) ?></span>
                            </div>
                            <span class="badge-status <?= $nivel_class ?>"><?= htmlspecialchars($nivel_txt) ?></span>
                        </div>

                        <div class="eval-puntaje-box">
                            <span class="puntaje-label">Puntaje Obtenido</span>
                            <span class="puntaje-valor"><?= round($eval['puntaje'], 1) ?> pts</span>
                        </div>

                        <?php if (!empty($eval['retro_eval'])): ?>
                            <div class="ia-bloque">
                                <h5><i class="ri-robot-line"></i> Retroalimentación General</h5>
                                <p><?= nl2br(htmlspecialchars($eval['retro_eval'])) ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($eval['fortalezas'])): ?>
                            <div class="ia-bloque bloque-fortalezas">
                                <h5><i class="ri-checkbox-circle-line"></i> Fortalezas</h5>
                                <p><?= nl2br(htmlspecialchars($eval['fortalezas'])) ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($eval['oportunidades'])): ?>
                            <div class="ia-bloque bloque-mejora">
                                <h5><i class="ri-line-chart-line"></i> Oportunidades de Mejora</h5>
                                <p><?= nl2br(htmlspecialchars($eval['oportunidades'])) ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($eval['recomendaciones'])): ?>
                            <div class="ia-bloque" style="border-left-color: #8b5cf6;">
                                <h5><i class="ri-lightbulb-line"></i> Recomendaciones</h5>
                                <p><?= nl2br(htmlspecialchars($eval['recomendaciones'])) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="tabla-contenedor" style="text-align: center; padding: 40px; color: #64748b;">
                <i class="ri-file-search-line" style="font-size: 48px; color: #94a3b8; display: block; margin-bottom: 12px;"></i>
                Este aprendiz aún no tiene evaluaciones registradas.
            </div>
        <?php endif; ?>
    </main>
</div>

</body>
</html>