<?php
session_start();

// 1. Cargar la conexión desde la raíz
require_once __DIR__ . '/../conexion.php';

// 2. Validar sesión activa (usando la constante BASE_URL)
if (!isset($_SESSION['id']) && !isset($_SESSION['usuario']) && !isset($_SESSION['persona_id']) && !isset($_SESSION['id_usuario'])) {
    header("Location: " . BASE_URL . "registro_login/login.php");
    exit();
}

// Activar reporte de errores
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Variables de sesión y perfil
$persona_id = $_SESSION['persona_id'] ?? $_SESSION['id_usuario'] ?? $_SESSION['id'] ?? 1;
$nombre_aprendiz = $_SESSION['nombre'] ?? $_SESSION['usuario'] ?? 'Aprendiz';
$fichas_programa = $_SESSION['programa'] ?? 'Programa Formativo';

$mensaje = "";
$error = "";

// 3. Procesar el formulario cuando se presione "Guardar Cambios"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_perfil'])) {
    $nuevo_nombre = trim($_POST['nombre']);
    $nuevo_apellido = trim($_POST['apellido']);
    $nuevo_usuario = trim($_POST['usuario']);
    $nuevo_documento = trim($_POST['documento']);
    $nuevo_correo = trim($_POST['correo']);

    try {
        $stmtUpdate = $conexion->prepare("UPDATE personas SET nombre = ?, apellido = ?, usuario = ?, documento = ?, correo = ? WHERE id = ?");
        $stmtUpdate->bind_param("sssssi", $nuevo_nombre, $nuevo_apellido, $nuevo_usuario, $nuevo_documento, $nuevo_correo, $persona_id);
        
        if ($stmtUpdate->execute()) {
            // Actualizar variables de sesión si cambió el nombre o usuario
            $_SESSION['nombre'] = $nuevo_nombre;
            $_SESSION['usuario'] = $nuevo_usuario;
            $nombre_aprendiz = $nuevo_nombre;
            
            $mensaje = "¡Tus datos han sido actualizados exitosamente!";
        } else {
            $error = "Hubo un error al actualizar los datos.";
        }
    } catch (Exception $e) {
        $error = "Error en la base de datos: " . $e->getMessage();
    }
}

// 4. Consultar datos actuales del perfil de la base de datos
$datos_usuario = [];
try {
    $stmt = $conexion->prepare("SELECT nombre, apellido, documento, correo, programa, usuario FROM personas WHERE id = ?");
    $stmt->bind_param("i", $persona_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    if ($resultado->num_rows > 0) {
        $datos_usuario = $resultado->fetch_assoc();
    }
} catch (Exception $e) {
    $datos_usuario = [
        'nombre' => $nombre_aprendiz,
        'apellido' => '',
        'documento' => 'No registrado',
        'correo' => 'No registrado',
        'programa' => $fichas_programa,
        'usuario' => $nombre_aprendiz
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Portal Aprendiz</title>

    <!-- RemixIcon & Google Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS Unificado del Dashboard con versionamiento anti-caché -->
    <link rel="stylesheet" type="text/css" href="dashboard.css?v=<?php echo time(); ?>">
</head>
<body>

    <div class="dashboard">

        <!-- BARRA LATERAL -->
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
                <a href="mis_evaluaciones.php">
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
                <a href="configuracion.php" class="activo">
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

            <!-- CONTENIDO DE LA VISTA CONFIGURACIÓN -->
            <div class="quiz-container">
                <div class="quiz-header">
                    <div>
                        <h2><i class="ri-settings-4-line" style="color: #2563EB;"></i> Configuración de Cuenta</h2>
                        <small style="color: #64748B;">Actualiza tu información personal de forma rápida y segura.</small>
                    </div>
                </div>

                <!-- Alertas de éxito o error -->
                <?php if (!empty($mensaje)): ?>
                    <div style="background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; padding: 14px; border-radius: 8px; margin-top: 20px; display: flex; align-items: center; gap: 10px;">
                        <i class="ri-checkbox-circle-line" style="font-size: 20px;"></i>
                        <span><?php echo $mensaje; ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div style="background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; padding: 14px; border-radius: 8px; margin-top: 20px; display: flex; align-items: center; gap: 10px;">
                        <i class="ri-error-warning-line" style="font-size: 20px;"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <!-- Formulario de Edición de Perfil -->
                <div style="background: #FFFFFF; border: 1px solid #E2E8F0; padding: 30px; border-radius: 12px; margin-top: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                    
                    <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid #F1F5F9;">
                        <div style="width: 60px; height: 60px; background: #2563EB; color: #FFFFFF; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: 700;">
                            <?php echo strtoupper(substr($nombre_aprendiz, 0, 1)); ?>
                        </div>
                        <div>
                            <h3 style="color: #1E293B; font-size: 18px; margin-bottom: 4px;"><?php echo htmlspecialchars(($datos_usuario['nombre'] ?? '') . ' ' . ($datos_usuario['apellido'] ?? '')); ?></h3>
                            <p style="color: #64748B; font-size: 14px;">Programa: <strong><?php echo htmlspecialchars($datos_usuario['programa'] ?? 'Programa Formativo'); ?></strong></p>
                        </div>
                    </div>

                    <form action="configuracion.php" method="POST">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px;">
                            
                            <div>
                                <label style="display: block; color: #64748B; font-size: 12px; margin-bottom: 6px; text-transform: uppercase; font-weight: 600;">Nombre</label>
                                <input type="text" name="nombre" value="<?php echo htmlspecialchars($datos_usuario['nombre'] ?? ''); ?>" required style="width: 100%; padding: 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 14px; color: #1E293B;">
                            </div>

                            <div>
                                <label style="display: block; color: #64748B; font-size: 12px; margin-bottom: 6px; text-transform: uppercase; font-weight: 600;">Apellido</label>
                                <input type="text" name="apellido" value="<?php echo htmlspecialchars($datos_usuario['apellido'] ?? ''); ?>" required style="width: 100%; padding: 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 14px; color: #1E293B;">
                            </div>

                            <div>
                                <label style="display: block; color: #64748B; font-size: 12px; margin-bottom: 6px; text-transform: uppercase; font-weight: 600;">Nombre de Usuario</label>
                                <input type="text" name="usuario" value="<?php echo htmlspecialchars($datos_usuario['usuario'] ?? ''); ?>" required style="width: 100%; padding: 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 14px; color: #1E293B;">
                            </div>

                            <div>
                                <label style="display: block; color: #64748B; font-size: 12px; margin-bottom: 6px; text-transform: uppercase; font-weight: 600;">Número de Identificación</label>
                                <input type="text" name="documento" value="<?php echo htmlspecialchars($datos_usuario['documento'] ?? ''); ?>" required style="width: 100%; padding: 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 14px; color: #1E293B;">
                            </div>

                            <div style="grid-column: 1 / -1;">
                                <label style="display: block; color: #64748B; font-size: 12px; margin-bottom: 6px; text-transform: uppercase; font-weight: 600;">Correo Electrónico</label>
                                <input type="email" name="correo" value="<?php echo htmlspecialchars($datos_usuario['correo'] ?? ''); ?>" required style="width: 100%; padding: 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 14px; color: #1E293B;">
                            </div>

                        </div>

                        <div style="margin-top: 30px; display: flex; justify-content: flex-end;">
                            <button type="submit" name="actualizar_perfil" style="background: #2563EB; color: #FFFFFF; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 14px; transition: background 0.2s;">
                                <i class="ri-save-line" style="font-size: 18px;"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>

                </div>

            </div>

            <footer class="footer-dashboard" style="margin-top: 40px;">
                <p>&copy; <?php echo date('Y'); ?> SkillGap AI - Todos los derechos reservados.</p>
            </footer>

        </main>
    </div>
</body>
</html>