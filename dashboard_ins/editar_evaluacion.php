<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../conexion.php';

// Validar sesión de Instructor / Admin
if (!isset($_SESSION['persona_id']) && !isset($_SESSION['id_usuario']) && !isset($_SESSION['id'])) {
    header("Location: ../registro_login/login.php");
    exit();
}

$caso_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($caso_id <= 0) {
    header("Location: competencias.php");
    exit();
}

$mensaje_exito = "";
$mensaje_error = "";

// 1. PROCESAR ACTUALIZACIÓN (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_actualizar'])) {
    $titulo_caso = trim($_POST['titulo_caso']);
    $nivel_caso =$_POST['nivel_caso'];
    $intentos_permitidos = intval($_POST['intentos_permitidos']);
    $descripcion_caso = trim($_POST['descripcion_caso']);
    
    // Arrays de preguntas
    $preguntas_existentes = isset($_POST['preguntas_existentes']) ?$_POST['preguntas_existentes'] : array(); // [id => texto]
    $preguntas_nuevas = isset($_POST['preguntas_nuevas']) ? $_POST['preguntas_nuevas'] : array();$preguntas_eliminar = isset($_POST['eliminar_preguntas']) ?$_POST['eliminar_preguntas'] : array(); // IDs a borrar

    if (empty($titulo_caso) || empty($descripcion_caso)) {$mensaje_error = "El título y la descripción del caso no pueden estar vacíos.";
    } else {
        $conexion->begin_transaction();
        try {
            // A) Actualizar el Caso Práctico
            $stmtCaso =$conexion->prepare("UPDATE casos SET titulo = ?, descripcion = ?, nivel = ?, intentos_permitidos = ? WHERE id = ?");
            if (!$stmtCaso) {
                throw new Exception("Error al preparar actualización del caso: " . $conexion->error);
            }
            $stmtCaso->bind_param("sssii", $titulo_caso,$descripcion_caso, $nivel_caso,$intentos_permitidos, $caso_id);$stmtCaso->execute();

            // Obtener competencia_id del caso
            $stmtComp =$conexion->prepare("SELECT competencia_id FROM casos WHERE id = ?");
            $stmtComp->bind_param("i", $caso_id);$stmtComp->execute();
            $comp_id =$stmtComp->get_result()->fetch_assoc()['competencia_id'];

            // B) Eliminar preguntas marcadas para borrar
            if (!empty($preguntas_eliminar)) {$ids_borrar = implode(',', array_map('intval', $preguntas_eliminar));$conexion->query("UPDATE preguntas SET estado = 'Inactivo' WHERE id IN ($ids_borrar)");
            }

            // C) Actualizar preguntas existentes
            if (!empty($preguntas_existentes)) {
                $stmtUpdPreg =$conexion->prepare("UPDATE preguntas SET pregunta = ? WHERE id = ?");
                foreach ($preguntas_existentes as $p_id =>$p_texto) {
                    $p_texto_clean = trim($p_texto);
                    if (!empty($p_texto_clean)) {
                        $p_id_int = intval($p_id);
                        $stmtUpdPreg->bind_param("si", $p_texto_clean, $p_id_int);$stmtUpdPreg->execute();
                    }
                }
            }

            // D) Insertar preguntas nuevas agregadas durante la edición
            if (!empty($preguntas_nuevas)) {
                // Obtener el máximo orden actual
                $resMax =$conexion->query("SELECT MAX(orden) AS max_orden FROM preguntas WHERE competencia_id = $comp_id");
                $max_orden = ($resMax &&$row = $resMax->fetch_assoc()) ? intval($row['max_orden']) : 0;

                $stmtInsPreg =$conexion->prepare("INSERT INTO preguntas (competencia_id, orden, pregunta, estado) VALUES (?, ?, ?, 'Activo')");
                foreach ($preguntas_nuevas as$p_nueva) {
                    $p_nueva_clean = trim($p_nueva);
                    if (!empty($p_nueva_clean)) {
                        $max_orden++;$stmtInsPreg->bind_param("iis", $comp_id,$max_orden, $p_nueva_clean);$stmtInsPreg->execute();
                    }
                }
            }

            $conexion->commit();$mensaje_exito = "¡Evaluación actualizada correctamente!";
        } catch (Exception $e) {
            $conexion->rollback();$mensaje_error = "Error al actualizar: " . $e->getMessage();
        }
    }
}

// 2. CONSULTAR DATOS DEL CASO
$stmtObtenerCaso =$conexion->prepare("SELECT c.*, comp.nombre as competencia_nombre FROM casos c INNER JOIN competencias comp ON c.competencia_id = comp.id WHERE c.id = ?");
$stmtObtenerCaso->bind_param("i", $caso_id);$stmtObtenerCaso->execute();
$caso =$stmtObtenerCaso->get_result()->fetch_assoc();

if (!$caso) {
    echo "El caso especificado no existe.";
    exit();
}

// 3. CONSULTAR PREGUNTAS ASOCIADAS A LA COMPETENCIA DEL CASO
$stmtObtenerPreg =$conexion->prepare("SELECT * FROM preguntas WHERE competencia_id = ? AND estado = 'Activo' ORDER BY orden ASC");
$stmtObtenerPreg->bind_param("i", $caso['competencia_id']);$stmtObtenerPreg->execute();
$resPreguntas =$stmtObtenerPreg->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Evaluación - SkillGap AI</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="dashboard_ins.css?v=<?= time() ?>">
</head>
<body>

<div class="dashboard">
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

    <main class="contenido">
        <div style="margin-bottom: 16px;">
            <a href="competencias.php" class="btn-volver"><i class="ri-arrow-left-line"></i> Volver a Competencias</a>
        </div>

        <div class="barra-superior">
            <div>
                <h2 style="font-size: 22px; color: #0F172A; font-weight: 700;">Editar Evaluación</h2>
                <p style="font-size: 13px; color: #64748B;">Competencia: <strong><?= htmlspecialchars($caso['competencia_nombre']) ?></strong></p>
            </div>
        </div>

        <?php if (!empty($mensaje_exito)): ?>
            <div class="alert-msg alert-success"><i class="ri-checkbox-circle-fill"></i> <?= htmlspecialchars($mensaje_exito) ?></div>
        <?php endif; ?>

        <?php if (!empty($mensaje_error)): ?>
            <div class="alert-msg alert-danger"><i class="ri-error-warning-fill"></i> <?= htmlspecialchars($mensaje_error) ?></div>
        <?php endif; ?>

        <form method="POST" action="editar_evaluacion.php?id=<?= $caso_id ?>">
            <input type="hidden" name="accion_actualizar" value="1">

            <!-- CONFIGURACIÓN DEL CASO -->
            <div class="form-section">
                <h3 class="seccion-titulo" style="margin-bottom: 16px;"><i class="ri-article-line"></i> Configuración del Caso Práctico</h3>

                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label for="titulo_caso">Título del Caso:</label>
                        <input type="text" name="titulo_caso" id="titulo_caso" class="form-control" value="<?= htmlspecialchars($caso['titulo']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="nivel_caso">Nivel:</label>
                        <select name="nivel_caso" id="nivel_caso" class="form-control">
                            <option value="Básico" <?= $caso['nivel'] == 'Básico' ? 'selected' : '' ?>>Básico</option>
                            <option value="Intermedio" <?= $caso['nivel'] == 'Intermedio' ? 'selected' : '' ?>>Intermedio</option>
                            <option value="Avanzado" <?= $caso['nivel'] == 'Avanzado' ? 'selected' : '' ?>>Avanzado</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="intentos_permitidos">Intentos Permitidos:</label>
                        <input type="number" name="intentos_permitidos" id="intentos_permitidos" class="form-control" min="1" max="10" value="<?= isset($caso['intentos_permitidos']) ? intval($caso['intentos_permitidos']) : 1 ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="descripcion_caso">Descripción / Planteamiento del Escenario:</label>
                    <textarea name="descripcion_caso" id="descripcion_caso" class="form-control" rows="5" required><?= htmlspecialchars($caso['descripcion']) ?></textarea>
                </div>
            </div>

            <!-- PREGUNTAS -->
            <div class="form-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h3 class="seccion-titulo" style="margin: 0;"><i class="ri-questionnaire-line"></i> Preguntas de la Evaluación</h3>
                    <button type="button" class="btn-limpiar" onclick="agregarNuevaPregunta()"><i class="ri-add-line"></i> Agregar Pregunta</button>
                </div>

                <div id="contenedorPreguntas">
                    <?php 
                    $num = 1;
                    if ($resPreguntas &&$resPreguntas->num_rows > 0): 
                        while ($p =$resPreguntas->fetch_assoc()): 
                    ?>
                        <div class="pregunta-item" style="position: relative; margin-bottom: 12px;">
                            <label style="font-weight: 600; color: #334155;">Pregunta <?= $num ?>:</label>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="text" name="preguntas_existentes[<?= $p['id'] ?>]" class="form-control" value="<?= htmlspecialchars($p['pregunta']) ?>" required>
                                <button type="button" onclick="marcarEliminar(this, <?= $p['id'] ?>)" style="background: #FEE2E2; color: #DC2626; border: 1px solid #FCA5A5; padding: 8px 12px; border-radius: 8px; cursor: pointer;" title="Eliminar pregunta">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </div>
                    <?php 
                        $num++;
                        endwhile; 
                    endif; 
                    ?>
                </div>
                <div id="inputsEliminar"></div>
            </div>

            <div style="text-align: right; margin-top: 24px;">
                <button type="submit" class="btn-filtrar" style="padding: 12px 32px; font-size: 15px;">
                    <i class="ri-save-line"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </main>
</div>

<script>
let contadorNuevas = 0;

function agregarNuevaPregunta() {
    contadorNuevas++;
    const contenedor = document.getElementById('contenedorPreguntas');
    
    const div = document.createElement('div');
    div.className = 'pregunta-item';
    div.style.position = 'relative';
    div.style.marginBottom = '12px';
    div.innerHTML = `
        <label style="font-weight: 600; color: #2563EB;">Nueva Pregunta:</label>
        <div style="display: flex; gap: 10px; align-items: center;">
            <input type="text" name="preguntas_nuevas[]" class="form-control" placeholder="Escribe la nueva pregunta..." required>
            <button type="button" onclick="this.parentElement.parentElement.remove()" style="background: #FEE2E2; color: #DC2626; border: 1px solid #FCA5A5; padding: 8px 12px; border-radius: 8px; cursor: pointer;">
                <i class="ri-close-line"></i>
            </button>
        </div>
    `;
    contenedor.appendChild(div);
}

function marcarEliminar(btn, preguntaId) {
    if (confirm("¿Seguro que deseas eliminar esta pregunta?")) {
        const divEliminar = document.getElementById('inputsEliminar');
        const inputHidden = document.createElement('input');
        inputHidden.type = 'hidden';
        inputHidden.name = 'eliminar_preguntas[]';
        inputHidden.value = preguntaId;
        divEliminar.appendChild(inputHidden);

        btn.parentElement.parentElement.remove();
    }
}
</script>

</body>
</html>