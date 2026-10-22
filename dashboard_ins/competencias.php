<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../conexion.php';

// 1. Validar sesión de Instructor / Admin
if (!isset($_SESSION['persona_id']) && !isset($_SESSION['id_usuario']) && !isset($_SESSION['id'])) {
    header("Location: ../registro_login/login.php");
    exit();
}

if (isset($_SESSION['rol_id']) && intval($_SESSION['rol_id']) !== 3 && intval($_SESSION['rol_id']) !== 1) {
    header("Location: ../dashboard_aprendiz/dashboard.php");
    exit();
}

$nombre_instructor = $_SESSION['nombre'] ?? $_SESSION['usuario'] ?? 'Instructor';

// 2. Consultar Competencias y Casos Prácticos
$queryCompetencias = "
    SELECT 
        c.id AS competencia_id,
        c.nombre AS competencia_nombre,
        c.descripcion AS competencia_desc,
        cs.id AS caso_id,
        cs.titulo AS caso_titulo,
        cs.nivel AS caso_nivel,
        cs.intentos_permitidos,
        (SELECT COUNT(*) FROM preguntas p WHERE p.competencia_id = c.id AND p.estado = 'Activo') AS total_preguntas
    FROM competencias c
    LEFT JOIN casos cs ON cs.competencia_id = c.id
    ORDER BY c.nombre ASC, cs.id DESC
";

$resCompetencias = $conexion->query($queryCompetencias);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Competencias - SkillGap AI</title>
    
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
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
        <!-- BARRA SUPERIOR -->
        <div class="barra-superior" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div>
                <h2 style="font-size: 22px; color: #0F172A; font-weight: 700;">Gestión de Competencias y Evaluaciones</h2>
                <p style="font-size: 13px; color: #64748B;">Administra los casos prácticos, ajusta los intentos permitidos y edita las preguntas de evaluación.</p>
            </div>

            <div>
                <a href="crear_evaluacion.php" class="btn-filtrar" style="padding: 10px 18px; text-decoration: none; font-size: 13px; background: #2563EB; color: #FFFFFF; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="ri-add-circle-line" style="font-size: 16px;"></i> Crear Nueva Evaluación
                </a>
            </div>
        </div>

        <!-- TABLA DE COMPETENCIAS Y CASOS -->
        <div class="tabla-contenedor">
            <h3 class="tabla-titulo">Catálogo de Evaluaciones Disponibles</h3>
            <table class="tabla-custom">
                <thead>
                    <tr>
                        <th>Competencia</th>
                        <th>Caso Práctico / Evaluación</th>
                        <th>Nivel</th>
                        <th>Preguntas</th>
                        <th>Intentos</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resCompetencias && $resCompetencias->num_rows > 0): ?>
                        <?php while ($row = $resCompetencias->fetch_assoc()): ?>
                            <?php 
                                $nivel_class = 'badge-intermedio';
                                $nivel_txt = $row['caso_nivel'] ?? 'Intermedio';
                                if (strpos(strtolower($nivel_txt), 'básico') !== false || strpos(strtolower($nivel_txt), 'inicial') !== false) $nivel_class = 'badge-inicial';
                                if (strpos(strtolower($nivel_txt), 'avanzado') !== false) $nivel_class = 'badge-avanzado';
                            ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($row['competencia_nombre']) ?></strong>
                                </td>
                                <td>
                                    <?php if (!empty($row['caso_titulo'])): ?>
                                        <span style="font-weight: 500; color: #1E293B;"><?= htmlspecialchars($row['caso_titulo']) ?></span>
                                    <?php else: ?>
                                        <em style="color: #94A3B8;">Sin caso práctico asignado</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['caso_nivel'])): ?>
                                        <span class="badge-status <?= $nivel_class ?>"><?= htmlspecialchars($nivel_txt) ?></span>
                                    <?php else: ?>
                                        <span style="color: #94A3B8;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= intval($row['total_preguntas']) ?></strong> preg.
                                </td>
                                <td>
                                    <strong><?= isset($row['intentos_permitidos']) ? intval($row['intentos_permitidos']) : 1 ?></strong> máx.
                                </td>
                                <td>
                                    <?php if (!empty($row['caso_id'])): ?>
                                        <a href="editar_evaluacion.php?id=<?= $row['caso_id'] ?>" class="btn-tabla-action" style="background: #EFF6FF; color: #2563EB; border: 1px solid #BFDBFE;">
                                            <i class="ri-edit-box-line"></i> Editar
                                        </a>
                                    <?php else: ?>
                                        <a href="crear_evaluacion.php?competencia_id=<?= $row['competencia_id'] ?>" class="btn-tabla-action" style="background: #F0FDF4; color: #16A34A; border: 1px solid #BBF7D0;">
                                            <i class="ri-add-line"></i> Crear Caso
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="tabla-vacia">No hay competencias registradas en el catálogo.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

</body>
</html>