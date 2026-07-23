<?php
session_start();

// Eliminar todas las variables de sesión
$_SESSION = [];

// Destruir la sesión
session_destroy();
?>

<!DOCTYPE html>
<html lang="es">
<head>

    <meta charset="UTF-8">
    <title>Cerrando sesión...</title>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>
<body>

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