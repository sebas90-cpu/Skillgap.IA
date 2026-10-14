<?php
session_start();
require_once __DIR__ . '/../conexion.php';

// 1. Validar sesión y permisos de acceso (Instructor / Evaluador = Rol 3)
if (!isset($_SESSION['persona_id']) && !isset($_SESSION['id_usuario']) && !isset($_SESSION['id'])) {
    header("Location: ../registro_login/login.php");
    exit();
}

// Redireccionar si el usuario conectado no es Instructor/Evaluador (rol_id != 3)
if (isset($_SESSION['rol_id']) && intval($_SESSION['rol_id']) !== 3 && intval($_SESSION['rol_id']) !== 1) {
    header("Location: ../dashboard_aprendiz/dashboard.php");
    exit();
}

$instructor_id = $_SESSION['persona_id'] ?? $_SESSION['id_usuario'] ?? $_SESSION['id'];
$nombre_instructor = $_SESSION['nombre'] ?? $_SESSION['usuario'] ?? 'Instructor';

// 2. Consultar Métricas Principales (KPIs)
$total_aprendices = $conexion->query("SELECT COUNT(*) AS total FROM personas WHERE rol_id = (SELECT id FROM roles WHERE nombre LIKE '%aprendiz%' LIMIT 1)")->fetch_assoc()['total'] ?? 0;
$total_evaluaciones = $conexion->query("SELECT COUNT(*) AS total FROM evaluaciones")->fetch_assoc()['total'] ?? 0;
$promedio_general = $conexion->query("SELECT AVG(puntaje) AS promedio FROM evaluaciones")->fetch_assoc()['promedio'] ?? 0;

// 3. Consultar Últimas Evaluaciones de Aprendices (Sin duplicados por GROUP BY e.id)
$queryEvaluaciones = "
    SELECT 
        e.id AS evaluacion_id,
        p.id AS persona_id,
        p.nombre,
        p.apellido,
        p.programa,
        c.nombre AS competencia_nombre,
        MAX(a.nivel) AS nivel,
        e.puntaje,
        e.fecha
    FROM evaluaciones e
    INNER JOIN personas p ON e.persona_id = p.id
    INNER JOIN casos cs ON e.caso_id = cs.id
    INNER JOIN competencias c ON cs.competencia_id = c.id
    LEFT JOIN analisis_ia a ON (a.persona_id = e.persona_id AND a.competencia_id = c.id)
    GROUP BY e.id
    ORDER BY e.fecha DESC
    LIMIT 10
";
$resEvaluaciones = $conexion->query($queryEvaluaciones);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Instructor - SkillGap AI</title>
    
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
                <a href="dashboard_ins.php" class="active"><i class="ri-dashboard-line"></i><span>Panel Principal</span></a>
                <a href="aprendices.php"><i class="ri-group-line"></i><span>Aprendices</span></a>
                <a href="competencias.php"><i class="ri-award-line"></i><span>Competencias</span></a>
                <a href="reportes.php"><i class="ri-bar-chart-box-line"></i><span>Reportes IA</span></a>
            </nav>
        </div>

        <div class="menu-inferior">
            <nav class="menu">
                <!-- APUNTA AL LOGOUT LOCAL DE LA MISMA CARPETA -->
                <a href="logout.php" class="btn-logout"><i class="ri-logout-box-r-line"></i><span>Cerrar Sesión</span></a>
            </nav>
        </div>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="contenido">
        <!-- BARRA SUPERIOR -->
        <div class="barra-superior">
            <div class="usuario-info">
                <div class="usuario-avatar"><?= strtoupper(substr($nombre_instructor, 0, 1)) ?></div>
                <div class="usuario-texto">
                    <h3><?= htmlspecialchars($nombre_instructor) ?></h3>
                    <span>Instructor / Evaluador</span>
                </div>
            </div>
        </div>

        <!-- TARJETAS METRICAS (KPIs) -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon icon-blue"><i class="ri-group-line"></i></div>
                <div>
                    <span class="kpi-label">Total Aprendices</span>
                    <h3 class="kpi-value"><?= $total_aprendices ?></h3>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon icon-green"><i class="ri-file-list-3-line"></i></div>
                <div>
                    <span class="kpi-label">Evaluaciones Realizadas</span>
                    <h3 class="kpi-value"><?= $total_evaluaciones ?></h3>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon icon-amber"><i class="ri-bar-chart-line"></i></div>
                <div>
                    <span class="kpi-label">Promedio General</span>
                    <h3 class="kpi-value"><?= number_format($promedio_general, 1) ?>/100</h3>
                </div>
            </div>
        </div>

        <!-- TABLA DE EVALUACIONES RECIENTES -->
        <div class="tabla-contenedor">
            <h3 class="tabla-titulo">Últimas Evaluaciones Realizadas</h3>
            <table class="tabla-custom">
                <thead>
                    <tr>
                        <th>Aprendiz</th>
                        <th>Programa</th>
                        <th>Competencia</th>
                        <th>Nivel IA</th>
                        <th>Puntaje</th>
                        <th>Fecha</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resEvaluaciones && $resEvaluaciones->num_rows > 0): ?>
                        <?php while ($row = $resEvaluaciones->fetch_assoc()): ?>
                            <?php 
                                $nivel_class = 'badge-intermedio';
                                $nivel_txt = $row['nivel'] ?? 'Intermedio';
                                if (strpos(strtolower($nivel_txt), 'inicial') !== false) $nivel_class = 'badge-inicial';
                                if (strpos(strtolower($nivel_txt), 'avanzado') !== false) $nivel_class = 'badge-avanzado';
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['nombre'] . ' ' . $row['apellido']) ?></strong></td>
                                <td><?= htmlspecialchars($row['programa'] ?: 'N/A') ?></td>
                                <td><?= htmlspecialchars($row['competencia_nombre']) ?></td>
                                <td><span class="badge-status <?= $nivel_class ?>"><?= htmlspecialchars($nivel_txt) ?></span></td>
                                <td><strong><?= round($row['puntaje'], 1) ?></strong></td>
                                <td><?= date('d/m/Y H:i', strtotime($row['fecha'])) ?></td>
                                <td>
                                    <a href="ver_aprendiz.php?persona_id=<?= $row['persona_id'] ?>" class="btn-tabla-action">Ver Detalle</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="tabla-vacia">No hay evaluaciones registradas aún.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

</body>
</html>