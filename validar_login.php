<?php
session_start();

require_once("conexion.php");

// Verificar que el formulario se envió correctamente
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: login.php");
    exit();
}

$usuario = trim($_POST['usuario']);
$password = trim($_POST['password']);

// Buscar usuario
$sql = "SELECT * FROM personas WHERE usuario = ? LIMIT 1";

$stmt = $conexion->prepare($sql);

$stmt->bind_param("s", $usuario);

$stmt->execute();

$resultado = $stmt->get_result();

// Verificar si existe
if($resultado->num_rows == 0){
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<script>

Swal.fire({

    icon:'error',

    title:'Usuario no encontrado',

    text:'El usuario ingresado no existe.'

}).then(()=>{

    window.location='login.php';

});

</script>

</body>
</html>

<?php
exit();
}

$datos = $resultado->fetch_assoc();

// Verificar estado
if($datos['estado'] != "Activo"){
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<script>

Swal.fire({

    icon:'warning',

    title:'Cuenta inactiva',

    text:'Tu cuenta se encuentra desactivada.'

}).then(()=>{

    window.location='login.php';

});

</script>

</body>
</html>

<?php
exit();
}

// Verificar contraseña
if(!password_verify($password, $datos['password'])){
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<script>

Swal.fire({

    icon:'error',

    title:'Contraseña incorrecta',

    text:'La contraseña ingresada no es correcta.'

}).then(()=>{

    window.location='login.php';

});

</script>

</body>
</html>

<?php
exit();
}

//==========================
// Crear sesión
//==========================

$_SESSION['id'] = $datos['id'];
$_SESSION['nombre'] = $datos['nombre'];
$_SESSION['apellido'] = $datos['apellido'];
$_SESSION['usuario'] = $datos['usuario'];
$_SESSION['rol'] = $datos['rol_id'];

//==========================
// Redirección
//==========================

if($datos['rol_id'] == 1){

    header("Location: admin/dashboard.php");

}else{

    header("Location: dashboard.php");

}

$stmt->close();
$conexion->close();

exit();

?>