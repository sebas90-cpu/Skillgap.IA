<?php
session_start();

// 1. Eliminar todas las variables de sesión
$_SESSION = [];

// 2. Borrar la cookie de sesión del navegador si existe
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// 3. Destruir la sesión en el servidor
session_destroy();

// 4. Prevenir almacenamiento en caché (evita volver con el botón "Atrás")
header("Cache-Control: no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cerrando sesión...</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body style="background: #f4f6f9;">

<script>
Swal.fire({
    icon: "success",
    title: "Sesión cerrada",
    text: "Has cerrado sesión correctamente.",
    timer: 1800,
    timerProgressBar: true,
    showConfirmButton: false,
    allowOutsideClick: false
}).then(() => {
    window.location.href = "login.php";
});
</script>

</body>
</html>