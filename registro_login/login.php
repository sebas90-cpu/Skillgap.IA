<?php
require_once __DIR__ . '/../conexion.php';
session_start();

// Redireccionar si el usuario ya inició sesión previamente
if (isset($_SESSION['id'])) {
    if ($_SESSION['rol_id'] == 1) {
        header("Location: " . BASE_URL . "dashboard_instructor/dashboard.php");
    } else {
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

    <title>Iniciar Sesión | SkillGap AI</title>

    <!-- Enlace corregido usando la constante BASE_URL -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>estilos.css?v=<?php echo time(); ?>">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body>

<div class="contenedor-login">

    <!-- Panel izquierdo -->
    <div class="lado-izquierdo">
        <i class="fa-solid fa-brain logo-login"></i>
        <h1>SkillGap AI</h1>
        <p>
            Inicia sesión para acceder al sistema inteligente
            de evaluación de competencias mediante Inteligencia Artificial.
        </p>
        <i class="fa-solid fa-robot ilustracion"></i>
    </div>

    <!-- Panel derecho -->
    <div class="lado-derecho">
        <form id="formLogin" action="validar_login.php" method="POST">
            <h2>Bienvenido</h2>
            <p class="subtitulo">Ingresa tus credenciales para continuar.</p>

            <!-- Usuario -->
            <div class="grupo">
                <label>Usuario</label>
                <div class="input-icon">
                    <i class="fa-solid fa-user"></i>
                    <input
                        type="text"
                        name="usuario"
                        id="usuario"
                        placeholder="Ingrese su usuario"
                        required>
                </div>
            </div>

            <!-- Contraseña -->
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

            <button type="submit" class="btn">
                <i class="fa-solid fa-right-to-bracket"></i>
                Iniciar sesión
            </button>

            <p class="registro-link">
                ¿Aún no tienes una cuenta?
                <a href="registro.php">Regístrate aquí</a>
            </p>
        </form>
    </div>

</div>

<script src="login.js"></script>

</body>
</html>