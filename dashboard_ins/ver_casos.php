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

// 2. Obtener y validar el ID de la competencia
$competencia_id = isset($_GET['competencia_id']) ? intval($_GET['competencia_id']) : 0;

if ($competencia_id <= 0) {
    header("Location: competencias.php");
    exit();
}

// 3. Consultar la Competencia
$stmtComp = $conexion->prepare("SELECT id, nombre, descripcion FROM competencias WHERE id = ?");
$stmtComp->bind_param("i", $competencia_id);
$stmtComp->execute();
$competencia = $stmtComp->get_result()->fetch_assoc();

if (!$competencia) {
    header("Location: competencias.php");
    exit();
}

// 4. Consultar Casos asociados a la Competencia y cantidad de preguntas
$queryCasos = "
    SELECT 
        cs.id AS caso_id,
        cs.titulo,
        cs.descripcion,
        cs.nivel,
        COUNT(p.id) AS total_preguntas
    FROM casos cs
    LEFT JOIN preguntas p ON p.competencia_id = cs.competencia_id
    WHERE cs.competencia_id = ?
    GROUP BY cs.id
    ORDER BY cs.id ASC
";

$stmtCasos = $conexion->prepare($queryCasos);
$stmtCasos->bind_param("i", $competencia_id);
$stmtCasos->execute();
$resCasos = $stmtCasos->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Casos Prácticos - SkillGap AI</title>
    
    <!-- RemixIcon & Google Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
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
                <a href="competencias.php" class="active"><i class="ri-award-line"></i><span>Competencias</span></a>
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
            <a href="competencias.php" class="btn-volver">
                <i class="ri-arrow-left-line"></i> Volver a Competencias
            </a>
        </div>

        <!-- TARJETA CABECERA COMPETENCIA -->
        <div class="perfil-card">
            <div class="perfil-info">
                <div class="perfil-avatar" style="background: #EFF6FF; color: #2563EB;">
                    <i class="ri-award-line" style="font-size: 24px;"></i>
                </div>
                <div class="perfil-datos">
                    <h2><?= htmlspecialchars($competencia['nombre']) ?></h2>
                    <p><?= htmlspecialchars($competencia['descripcion'] ?: 'Sin descripción asignada.') ?></p>
                </div>
            </div>
            <div class="perfil-kpis">
                <div class="mini-kpi">
                    <span>Casos Prácticos</span>
                    <h3><?= $resCasos ? $resCasos->num_rows : 0 ?></h3>
                </div>
            </div>
        </div>

        <h3 class="seccion-titulo"><i class="ri-folders-line"></i> Casos Configurados</h3>

        <!-- LISTA DE CASOS -->
        <?php if ($resCasos && $resCasos->num_rows > 0): ?>
            <div class="evaluaciones-grid">
                <?php while ($caso = $resCasos->fetch_assoc()): ?>
                    <?php 
                        $nivel_class = 'badge-intermedio';
                        $nivel_txt = $caso['nivel'] ?? 'Intermedio';
                        if (strpos(strtolower($nivel_txt), 'inicial') !== false || strpos(strtolower($nivel_txt), 'básico') !== false) {
                            $nivel_class = 'badge-inicial';
                        }
                        if (strpos(strtolower($nivel_txt), 'avanzado') !== false) {
                            $nivel_class = 'badge-avanzado';
                        }
                    ?>
                    <div class="evaluacion-card">
                        <div class="eval-header">
                            <div>
                                <h4><?= htmlspecialchars($caso['titulo']) ?></h4>
                                <span class="eval-fecha"><i class="ri-question-line"></i> <?= $caso['total_preguntas'] ?> preguntas asociadas</span>
                            </div>
                            <span class="badge-status <?= $nivel_class ?>"><?= htmlspecialchars($nivel_txt) ?></span>
                        </div>

                        <div class="ia-bloque" style="margin-top: 15px;">
                            <h5><i class="ri-file-text-line"></i> Planteamiento del Caso</h5>
                            <p><?= nl2br(htmlspecialchars($caso['descripcion'])) ?></p>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="tabla-contenedor" style="text-align: center; padding: 40px; color: #64748b;">
                <i class="ri-folder-unknow-line" style="font-size: 48px; color: #94a3b8; display: block; margin-bottom: 12px;"></i>
                Esta competencia aún no tiene casos prácticos registrados.
            </div>
        <?php endif; ?>
    </main>
</div>

</body>
</html>