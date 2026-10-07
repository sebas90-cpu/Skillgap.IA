<?php
session_start();
require_once("conexion.php");

// Verificar que el formulario se envió correctamente por POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: login.php");
    exit();
}

$usuario = trim($_POST['usuario']);
$password = trim($_POST['password']);

// Buscar usuario en la base de datos
$sql = "SELECT * FROM personas WHERE correo = ? LIMIT 1";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $usuario);
$stmt->execute();
$resultado = $stmt->get_result();

// 1. Verificar si el usuario existe
if($resultado->num_rows == 0){
    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
    echo '<body style="background:#f4f6f9;"><script>
    Swal.fire({
        icon: "error",
        title: "Usuario no encontrado",
        text: "El usuario ingresado no existe."
    }).then(()=>{ window.location="login.php"; });
    </script></body>';
    exit();
}

$datos = $resultado->fetch_assoc();

// 2. Verificar estado de la cuenta
if($datos['estado'] != "Activo"){
    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
    echo '<body style="background:#f4f6f9;"><script>
    Swal.fire({
        icon: "warning",
        title: "Cuenta inactiva",
        text: "Tu cuenta se encuentra desactivada."
    }).then(()=>{ window.location="login.php"; });
    </script></body>';
    exit();
}

// 3. Verificar contraseña en TEXTO PLANO (Comparación exacta)
if($password !== $datos['password']){
    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
    echo '<body style="background:#f4f6f9;"><script>
    Swal.fire({
        icon: "error",
        title: "Contraseña incorrecta",
        text: "La contraseña ingresada no es correcta."
    }).then(()=>{ window.location="login.php"; });
    </script></body>';
    exit();
}

//==========================
// Crear sesión de usuario
//==========================
$_SESSION['id'] = $datos['id'];
$_SESSION['nombre'] = $datos['nombre'];
$_SESSION['apellido'] = $datos['apellido'];
$_SESSION['usuario'] = $datos['usuario'];
$_SESSION['rol'] = $datos['rol_id'];

// Redireccionar al dashboard del aprendiz
header("Location: dashboard.php");

$stmt->close();
$conexion->close();
exit();
?>