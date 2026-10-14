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

// 2. Parámetro de búsqueda
$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

// 3. Consultar Competencias junto con la cantidad de casos asociados
$sql = "
    SELECT 
        c.id,
        c.nombre,
        c.descripcion,
        COUNT(cs.id) AS total_casos
    FROM competencias c
    LEFT JOIN casos cs ON cs.competencia_id = c.id
";

if (!empty($buscar)) {
    $sql .= " WHERE c.nombre LIKE ? OR c.descripcion LIKE ?";
}

$sql .= " GROUP BY c.id ORDER BY c.nombre ASC";

$stmt = $conexion->prepare($sql);

if (!empty($buscar)) {
    $term = "%" . $buscar . "%";
    $stmt->bind_param("ss", $term, $term);
}

$stmt->execute();
$resCompetencias = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Competencias Evaluables - SkillGap AI</title>
    
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
        <div class="barra-superior">
            <div>
                <h2 style="font-size: 22px; color: #0F172A; font-weight: 700;">Competencias Evaluables</h2>
                <p style="font-size: 13px; color: #64748B;">Catálogo de habilidades y casos prácticos configurados en la plataforma.</p>
            </div>
        </div>

        <!-- FILTRO DE BÚSQUEDA -->
        <form method="GET" action="competencias.php" class="filtros-bar">
            <div class="input-search">
                <i class="ri-search-line"></i>
                <input type="text" name="buscar" placeholder="Buscar por nombre o descripción..." value="<?= htmlspecialchars($buscar) ?>">
            </div>

            <button type="submit" class="btn-filtrar"><i class="ri-filter-3-line"></i> Buscar</button>
            
            <?php if (!empty($buscar)): ?>
                <a href="competencias.php" class="btn-limpiar"><i class="ri-refresh-line"></i> Limpiar</a>
            <?php endif; ?>
        </form>

        <!-- TARJETAS DE COMPETENCIAS -->
        <?php if ($resCompetencias && $resCompetencias->num_rows > 0): ?>
            <div class="competencias-grid">
                <?php while ($comp = $resCompetencias->fetch_assoc()): ?>
                    <div class="competencia-card">
                        <div class="comp-header">
                            <div class="comp-icon">
                                <i class="ri-award-line"></i>
                            </div>
                            <div>
                                <h3><?= htmlspecialchars($comp['nombre']) ?></h3>
                                <span class="badge-status badge-intermedio"><?= $comp['total_casos'] ?> Casos Configurados</span>
                            </div>
                        </div>

                        <p class="comp-desc">
                            <?= htmlspecialchars($comp['descripcion'] ?: 'Sin descripción registrada.') ?>
                        </p>

                        <div class="comp-footer">
                            <a href="ver_casos.php?competencia_id=<?= $comp['id'] ?>" class="btn-tabla-action" style="width: 100%; text-align: center;">
                                <i class="ri-folders-line"></i> Ver Casos Prácticos
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="tabla-contenedor" style="text-align: center; padding: 40px; color: #64748b;">
                <i class="ri-award-line" style="font-size: 48px; color: #94a3b8; display: block; margin-bottom: 12px;"></i>
                No se encontraron competencias registradas en el sistema.
            </div>
        <?php endif; ?>
    </main>
</div>

</body>
</html>