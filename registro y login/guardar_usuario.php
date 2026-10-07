<?php

require_once("conexion.php");

// Verificar que el formulario se envió por POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: registro.php");
    exit();
}

//==========================
// Obtener datos
//==========================

$nombre     = trim($_POST['nombre']);
$apellido   = trim($_POST['apellido']);
$documento  = trim($_POST['documento']);
$correo     = trim($_POST['correo']);
$usuario    = trim($_POST['usuario']);
$password   = $_POST['password']; // Contraseña en texto plano

// Programa
if ($_POST['programa'] == "Otro") {
    $programa = trim($_POST['otroPrograma']);
} else {
    $programa = trim($_POST['programa']);
}

// (Se eliminó el password_hash para guardar la contraseña tal cual)

//==========================
// Verificar documento
//==========================

$sql = "SELECT id FROM personas WHERE documento = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $documento);
$stmt->execute();
$stmt->store_result();

if($stmt->num_rows > 0){
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
    title:'Documento existente',
    text:'Ya existe un usuario con ese documento.'
}).then(()=>{
    window.location='registro.php';
});
</script>

</body>
</html>

<?php
exit();
}

$stmt->close();

//==========================
// Verificar correo
//==========================

$sql = "SELECT id FROM personas WHERE correo = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $correo);
$stmt->execute();
$stmt->store_result();

if($stmt->num_rows > 0){
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
    title:'Correo existente',
    text:'Ese correo ya está registrado.'
}).then(()=>{
    window.location='registro.php';
});
</script>

</body>
</html>

<?php
exit();
}

$stmt->close();

//==========================
// Verificar usuario
//==========================

$sql = "SELECT id FROM personas WHERE usuario = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $usuario);
$stmt->execute();
$stmt->store_result();

if($stmt->num_rows > 0){
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
    title:'Usuario existente',
    text:'Ese nombre de usuario ya está registrado.'
}).then(()=>{
    window.location='registro.php';
});
</script>

</body>
</html>

<?php
exit();
}

$stmt->close();

//==========================
// Insertar usuario
//==========================

$sql = "INSERT INTO personas
(
nombre,
apellido,
documento,
correo,
programa,
usuario,
password,
estado,
rol_id
)
VALUES
(
?,
?,
?,
?,
?,
?,
?,
'Activo',
3
)";

$stmt = $conexion->prepare($sql);

$stmt->bind_param(
    "sssssss",
    $nombre,
    $apellido,
    $documento,
    $correo,
    $programa,
    $usuario,
    $password // <--- Aquí se envía la contraseña en texto plano
);

if($stmt->execute()){
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
    icon:'success',
    title:'Registro exitoso',
    text:'La cuenta fue creada correctamente.'
}).then(()=>{
    window.location='login.php';
});
</script>

</body>
</html>

<?php
}else{
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
    title:'Error',
    text:'No fue posible registrar el usuario.'
}).then(()=>{
    window.location='registro.php';
});
</script>

</body>
</html>

<?php
}

$stmt->close();
$conexion->close();
?>