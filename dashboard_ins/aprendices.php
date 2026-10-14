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

// 2. Parámetros de Búsqueda y Filtro
$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$programa = isset($_GET['programa']) ? trim($_GET['programa']) : '';

// 3. Obtener lista de programas para el filtro desplegable
$resProgramas = $conexion->query("SELECT DISTINCT programa FROM personas WHERE programa IS NOT NULL AND programa != '' ORDER BY programa ASC");

// 4. Construir la Consulta de Aprendices con sus Métricas
$sql = "
    SELECT 
        p.id,
        p.nombre,
        p.apellido,
        p.documento,
        p.correo,
        p.programa,
        COUNT(e.id) AS total_evaluaciones,
        COALESCE(AVG(e.puntaje), 0) AS promedio_puntaje
    FROM personas p
    INNER JOIN roles r ON p.rol_id = r.id
    LEFT JOIN evaluaciones e ON e.persona_id = p.id
    WHERE r.nombre LIKE '%aprendiz%'
";

$params = [];
$types = "";

if (!empty($buscar)) {
    $sql .= " AND (p.nombre LIKE ? OR p.apellido LIKE ? OR p.documento LIKE ? OR p.correo LIKE ?)";
    $term = "%" . $buscar . "%";
    $params[] = $term; $params[] = $term; $params[] = $term; $params[] = $term;
    $types .= "ssss";
}

if (!empty($programa)) {
    $sql .= " AND p.programa = ?";
    $params[] = $programa;
    $types .= "s";
}

$sql .= " GROUP BY p.id ORDER BY p.nombre ASC, p.apellido ASC";

$stmt = $conexion->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$resAprendices = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Aprendices - SkillGap AI</title>
    
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
        <div class="barra-superior">
            <div>
                <h2 style="font-size: 22px; color: #0F172A; font-weight: 700;">Directorio de Aprendices</h2>
                <p style="font-size: 13px; color: #64748B;">Consulta el progreso e historial evaluativo de los estudiantes.</p>
            </div>
        </div>

        <!-- FILTROS Y BÚSQUEDA -->
        <form method="GET" action="aprendices.php" class="filtros-bar">
            <div class="input-search">
                <i class="ri-search-line"></i>
                <input type="text" name="buscar" placeholder="Buscar por nombre, documento o correo..." value="<?= htmlspecialchars($buscar) ?>">
            </div>

            <select name="programa" class="select-filtro">
                <option value="">Todos los Programas</option>
                <?php if ($resProgramas && $resProgramas->num_rows > 0): ?>
                    <?php while ($p = $resProgramas->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($p['programa']) ?>" <?= $programa === $p['programa'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['programa']) ?>
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>

            <button type="submit" class="btn-filtrar"><i class="ri-filter-3-line"></i> Filtrar</button>
            
            <?php if (!empty($buscar) || !empty($programa)): ?>
                <a href="aprendices.php" class="btn-limpiar"><i class="ri-refresh-line"></i> Limpiar</a>
            <?php endif; ?>
        </form>

        <!-- TABLA DE APRENDICES -->
        <div class="tabla-contenedor">
            <table class="tabla-custom">
                <thead>
                    <tr>
                        <th>Aprendiz</th>
                        <th>Documento</th>
                        <th>Programa</th>
                        <th>Evaluaciones</th>
                        <th>Promedio</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resAprendices && $resAprendices->num_rows > 0): ?>
                        <?php while ($ap = $resAprendices->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div class="usuario-avatar" style="width: 36px; height: 36px; font-size: 14px;">
                                            <?= strtoupper(substr($ap['nombre'], 0, 1) . substr($ap['apellido'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <strong><?= htmlspecialchars($ap['nombre'] . ' ' . $ap['apellido']) ?></strong>
                                            <div style="font-size: 12px; color: #64748B;"><?= htmlspecialchars($ap['correo']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($ap['documento'] ?: 'N/A') ?></td>
                                <td><?= htmlspecialchars($ap['programa'] ?: 'No asignado') ?></td>
                                <td><span class="badge-status badge-intermedio"><?= $ap['total_evaluaciones'] ?> realizadas</span></td>
                                <td><strong><?= number_format($ap['promedio_puntaje'], 1) ?> pts</strong></td>
                                <td>
                                    <a href="ver_aprendiz.php?persona_id=<?= $ap['id'] ?>" class="btn-tabla-action">Ver Detalle</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="tabla-vacia">No se encontraron aprendices registrados con los criterios seleccionados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

</body>
</html>