<?php
require_once __DIR__ . '/../conexion.php';
session_start();

// Si ya hay una sesión activa, redirigir según el rol_id de la BD
if (isset($_SESSION['id']) || isset($_SESSION['persona_id'])) {
    $rol = $_SESSION['rol_id'] ?? 2;
    
    if ($rol == 3) { // Evaluador / Instructor
        header("Location: " . BASE_URL . "dashboard_ins/dashboard_ins.php");
    } else if ($rol == 1) { // Administrador
        header("Location: " . BASE_URL . "dashboard_admin/dashboard_admin.php");
    } else { // Aprendiz (rol_id = 2)
        header("Location: " . BASE_URL . "dashboard_aprendiz/dashboard.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registro | SkillGap AI</title>

    <!-- Enlace con la constante BASE_URL -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>estilos.css?v=<?php echo time(); ?>">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body>

<div class="contenedor-registro">

    <!-- Panel izquierdo -->
    <div class="lado-izquierdo">

        <i class="fa-solid fa-brain logo-registro"></i>

        <h1>SkillGap AI</h1>

        <p>
            Bienvenido al sistema inteligente para la evaluación de competencias
            mediante Inteligencia Artificial.
        </p>

        <i class="fa-solid fa-user-graduate ilustracion"></i>

    </div>

    <!-- Panel derecho -->
    <div class="lado-derecho">

        <form id="formRegistro" method="POST" action="guardar_usuario.php">

            <h2>Crear Cuenta</h2>

            <!-- NUEVO: Selector Desplegable de Rol desde la BD -->
            <div class="grupo">
                <label>Rol de Usuario</label>
                <div class="input-icon">
                    <i class="fa-solid fa-user-gear"></i>
                    <select name="rol_id" id="rol_id" required>
                        <option value="" disabled selected>Seleccione un rol</option>
                        <?php
                        // Consulta directa a la tabla 'roles' ordenados por id
                        $resRoles = $conexion->query("SELECT id, nombre FROM roles WHERE id IN (2, 3) ORDER BY id ASC");
                        if ($resRoles && $resRoles->num_rows > 0) {
                            while ($rol = $resRoles->fetch_assoc()) {
                                echo '<option value="' . $rol['id'] . '">' . htmlspecialchars($rol['nombre']) . '</option>';
                            }
                        } else {
                            // Fallback de seguridad con tus IDs exactos
                            echo '<option value="2">Aprendiz</option>';
                            echo '<option value="3">Evaluador</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="grupo">
                <label>Nombre</label>
                <div class="input-icon">
                    <i class="fa-solid fa-user"></i>
                    <input
                        type="text"
                        name="nombre"
                        id="nombre"
                        placeholder="Ingrese su nombre"
                        required>
                </div>
            </div>

            <div class="grupo">
                <label>Apellido</label>
                <div class="input-icon">
                    <i class="fa-solid fa-user"></i>
                    <input
                        type="text"
                        name="apellido"
                        id="apellido"
                        placeholder="Ingrese su apellido"
                        required>
                </div>
            </div>

            <div class="grupo">
                <label>Documento</label>
                <div class="input-icon">
                    <i class="fa-solid fa-id-card"></i>
                    <input
                        type="text"
                        name="documento"
                        id="documento"
                        placeholder="Número de documento"
                        required>
                </div>
            </div>

            <div class="grupo">
                <label>Correo electrónico</label>
                <div class="input-icon">
                    <i class="fa-solid fa-envelope"></i>
                    <input
                        type="email"
                        name="correo"
                        id="correo"
                        placeholder="correo@ejemplo.com"
                        required>
                </div>
            </div>

            <div class="grupo">
                <label>Programa de formación</label>
                <div class="input-icon">
                    <i class="fa-solid fa-graduation-cap"></i>
                    <select
                        name="programa"
                        id="programa"
                        required>
                        <option value="">Seleccione un programa</option>
                        <option value="ADSO">ADSO</option>
                        <option value="Programación de Software">Programación de Software</option>
                        <option value="Sistemas Teleinformáticos">Sistemas Teleinformáticos</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
            </div>

            <div class="grupo" id="otroProgramaDiv" style="display: none;">
                <label>Especifique el programa</label>
                <div class="input-icon">
                    <i class="fa-solid fa-book"></i>
                    <input
                        type="text"
                        name="otroPrograma"
                        id="otroPrograma"
                        placeholder="Ingrese el nombre del programa">
                </div>
            </div>

            <div class="grupo">
                <label>Usuario</label>
                <div class="input-icon">
                    <i class="fa-solid fa-at"></i>
                    <input
                        type="text"
                        name="usuario"
                        id="usuario"
                        placeholder="Nombre de usuario"
                        required>
                </div>
            </div>

            <div class="grupo">
                <label>Contraseña</label>
                <div class="password-box">
                    <i class="fa-solid fa-lock icono-input"></i>
                    <input
                        type="password"
                        name="password"
                        id="password"
                        placeholder="********"
                        required>
                    <i class="fa-solid fa-eye" id="verPassword"></i>
                </div>
            </div>

            <div class="grupo">
                <label>Confirmar contraseña</label>
                <div class="password-box">
                    <i class="fa-solid fa-lock icono-input"></i>
                    <input
                        type="password"
                        name="confirmar"
                        id="confirmar"
                        placeholder="********"
                        required>
                    <i class="fa-solid fa-eye" id="verConfirmar"></i>
                </div>
            </div>

            <button class="btn" type="submit">
                Registrarme
            </button>

            <p class="login-link">
                ¿Ya tienes una cuenta?
                <a href="login.php">Iniciar sesión</a>
            </p>

        </form>

    </div>

</div>

<script src="validaciones.js"></script>

</body>
</html>