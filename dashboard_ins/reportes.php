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

// 2. Métrica Totales
$total_aprendices = $conexion->query("SELECT COUNT(*) AS total FROM personas WHERE rol_id = (SELECT id FROM roles WHERE nombre LIKE '%aprendiz%' LIMIT 1)")->fetch_assoc()['total'] ?? 0;
$total_evals = $conexion->query("SELECT COUNT(*) AS total FROM evaluaciones")->fetch_assoc()['total'] ?? 0;
$promedio_general = $conexion->query("SELECT AVG(puntaje) AS promedio FROM evaluaciones")->fetch_assoc()['promedio'] ?? 0;

// 3. Distribución por Nivel de IA (Análisis IA)
$qNiveles = "
    SELECT 
        LOWER(nivel) AS nivel_limpio, 
        COUNT(*) AS cantidad 
    FROM analisis_ia 
    GROUP BY LOWER(nivel)
";
$resNiveles = $conexion->query($qNiveles);

$cnt_inicial = 0;
$cnt_intermedio = 0;
$cnt_avanzado = 0;

if ($resNiveles && $resNiveles->num_rows > 0) {
    while ($row = $resNiveles->fetch_assoc()) {
        $n = $row['nivel_limpio'];
        if (strpos($n, 'inicial') !== false || strpos($n, 'básico') !== false) {
            $cnt_inicial += $row['cantidad'];
        } elseif (strpos($n, 'avanzado') !== false) {
            $cnt_avanzado += $row['cantidad'];
        } else {
            $cnt_intermedio += $row['cantidad'];
        }
    }
}

// 4. Promedio de Puntaje por Competencia
$qCompProm = "
    SELECT 
        c.nombre AS competencia, 
        ROUND(AVG(e.puntaje), 1) AS promedio_puntaje,
        COUNT(e.id) AS total_evaluaciones
    FROM competencias c
    INNER JOIN casos cs ON cs.competencia_id = c.id
    INNER JOIN evaluaciones e ON e.caso_id = cs.id
    GROUP BY c.id
    ORDER BY promedio_puntaje DESC
";
$resCompProm = $conexion->query($qCompProm);

$labelsComp = [];
$scoresComp = [];

if ($resCompProm && $resCompProm->num_rows > 0) {
    while ($r = $resCompProm->fetch_assoc()) {
        $labelsComp[] = $r['competencia'];
        $scoresComp[] = floatval($r['promedio_puntaje']);
    }
    // Reiniciar puntero para volver a listar en tabla
    $resCompProm->data_seek(0);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes IA & Analítica - SkillGap AI</title>
    
    <!-- RemixIcon & Google Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Hoja de Estilos del Módulo Instructor -->
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
                <a href="competencias.php"><i class="ri-award-line"></i><span>Competencias</span></a>
                <a href="reportes.php" class="active"><i class="ri-bar-chart-box-line"></i><span>Reportes IA</span></a>
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
        <div class="barra-superior">
            <div>
                <h2 style="font-size: 22px; color: #0F172A; font-weight: 700;">Reportes y Diagnósticos IA</h2>
                <p style="font-size: 13px; color: #64748B;">Consolidado general sobre el rendimiento y brechas de competencias de los aprendices.</p>
            </div>
        </div>

        <!-- TARJETAS METRICAS (KPIs) -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon icon-blue"><i class="ri-group-line"></i></div>
                <div>
                    <span class="kpi-label">Aprendices</span>
                    <h3 class="kpi-value"><?= $total_aprendices ?></h3>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon icon-green"><i class="ri-file-list-3-line"></i></div>
                <div>
                    <span class="kpi-label">Total Evaluaciones</span>
                    <h3 class="kpi-value"><?= $total_evals ?></h3>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon icon-amber"><i class="ri-bar-chart-line"></i></div>
                <div>
                    <span class="kpi-label">Promedio Institucional</span>
                    <h3 class="kpi-value"><?= number_format($promedio_general, 1) ?>/100</h3>
                </div>
            </div>
        </div>

        <!-- SECCIÓN DE GRÁFICOS ANALÍTICOS -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 24px;">
            <!-- GRÁFICO 1: NIVELES ALCANZADOS -->
            <div class="tabla-contenedor" style="margin: 0; padding: 20px;">
                <h3 class="tabla-titulo"><i class="ri-pie-chart-line"></i> Distribución de Niveles (IA)</h3>
                <div style="max-width: 280px; margin: 0 auto; padding: 10px 0;">
                    <canvas id="chartNiveles"></canvas>
                </div>
            </div>

            <!-- GRÁFICO 2: PROMEDIO POR COMPETENCIA -->
            <div class="tabla-contenedor" style="margin: 0; padding: 20px;">
                <h3 class="tabla-titulo"><i class="ri-bar-chart-grouped-line"></i> Promedio por Competencia</h3>
                <div style="padding: 10px 0; height: 260px;">
                    <canvas id="chartCompetencias"></canvas>
                </div>
            </div>
        </div>

        <!-- TABLA DESGLOSADA POR COMPETENCIA -->
        <div class="tabla-contenedor">
            <h3 class="tabla-titulo"><i class="ri-list-check-2"></i> Desglose Detallado por Competencia</h3>
            <table class="tabla-custom">
                <thead>
                    <tr>
                        <th>Competencia</th>
                        <th>Evaluaciones Realizadas</th>
                        <th>Promedio Obtenido</th>
                        <th>Estado General</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resCompProm && $resCompProm->num_rows > 0): ?>
                        <?php while ($row = $resCompProm->fetch_assoc()): ?>
                            <?php 
                                $prom = floatval($row['promedio_puntaje']);
                                $estado_class = 'badge-intermedio';
                                $estado_txt = 'En Desarrollo';
                                if ($prom >= 85) {
                                    $estado_class = 'badge-avanzado';
                                    $estado_txt = 'Dominio Alto';
                                } elseif ($prom < 60) {
                                    $estado_class = 'badge-inicial';
                                    $estado_txt = 'Requiere Refuerzo';
                                }
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['competencia']) ?></strong></td>
                                <td><?= $row['total_evaluaciones'] ?> evaluadas</td>
                                <td><strong><?= number_format($prom, 1) ?> pts</strong></td>
                                <td><span class="badge-status <?= $estado_class ?>"><?= $estado_txt ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="tabla-vacia">Aún no hay datos de evaluaciones registradas para generar reportes.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- SCRIPT GENERADOR DE GRÁFICOS -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Gráfico de Dona: Niveles alcanzados
    const ctxNiveles = document.getElementById('chartNiveles').getContext('2d');
    new Chart(ctxNiveles, {
        type: 'doughnut',
        data: {
            labels: ['Inicial', 'Intermedio', 'Avanzado'],
            datasets: [{
                data: [<?= $cnt_inicial ?>, <?= $cnt_intermedio ?>, <?= $cnt_avanzado ?>],
                backgroundColor: ['#F59E0B', '#2563EB', '#10B981'],
                borderWidth: 2,
                borderColor: '#FFFFFF'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // 2. Gráfico de Barras: Promedio por Competencia
    const ctxComp = document.getElementById('chartCompetencias').getContext('2d');
    new Chart(ctxComp, {
        type: 'bar',
        data: {
            labels: <?= json_encode($labelsComp) ?>,
            datasets: [{
                label: 'Promedio (0 - 100)',
                data: <?= json_encode($scoresComp) ?>,
                backgroundColor: '#2563EB',
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, max: 100 }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
});
</script>

</body>
</html>