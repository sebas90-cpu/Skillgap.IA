<?php
require_once __DIR__ . '/../conexion.php';

// Verificar que el formulario se envió por POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: registro.php");
    exit();
}

//==========================
// Obtener datos
//==========================

$nombre    = trim($_POST['nombre']);
$apellido  = trim($_POST['apellido']);
$documento = trim($_POST['documento']);
$correo    = trim($_POST['correo']);
$usuario   = trim($_POST['usuario']);
$password  = $_POST['password']; // Contraseña en texto plano

// Programa
if (isset($_POST['programa']) && $_POST['programa'] == "Otro") {
    $programa = trim($_POST['otroPrograma']);
} else {
    $programa = isset($_POST['programa']) ? trim($_POST['programa']) : '';
}

//==========================
// Verificar duplicados (Documento, Correo, Usuario)
//==========================

$sql_check = "SELECT documento, correo, usuario FROM personas WHERE documento = ? OR correo = ? OR usuario = ? LIMIT 1";
$stmt_check = $conexion->prepare($sql_check);
$stmt_check->bind_param("sss", $documento, $correo, $usuario);
$stmt_check->execute();
$res_check = $stmt_check->get_result();

if ($res_check->num_rows > 0) {
    $existente = $res_check->fetch_assoc();
    $mensaje = "";

    if ($existente['documento'] === $documento) {
        $mensaje = "Ya existe un usuario registrado con ese número de documento.";
    } elseif ($existente['correo'] === $correo) {
        $mensaje = "El correo electrónico ingresado ya se encuentra registrado.";
    } elseif ($existente['usuario'] === $usuario) {
        $mensaje = "El nombre de usuario ya está en uso.";
    }

    echo '<!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body style="background:#f4f6f9;">
    <script>
    Swal.fire({
        icon: "error",
        title: "Dato ya existente",
        text: "' . $mensaje . '"
    }).then(() => {
        window.location = "registro.php";
    });
    </script>
    </body>
    </html>';
    $stmt_check->close();
    $conexion->close();
    exit();
}
$stmt_check->close();

//==========================
// Insertar usuario
//==========================

$sql = "INSERT INTO personas (nombre, apellido, documento, correo, programa, usuario, password, estado, rol_id) VALUES (?, ?, ?, ?, ?, ?, ?, 'Activo', 3)";

$stmt = $conexion->prepare($sql);
$stmt->bind_param(
    "sssssss",
    $nombre,
    $apellido,
    $documento,
    $correo,
    $programa,
    $usuario,
    $password
);

if ($stmt->execute()) {
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body style="background:#f4f6f9;">
<script>
Swal.fire({
    icon: 'success',
    title: 'Registro exitoso',
    text: 'La cuenta fue creada correctamente.'
}).then(() => {
    window.location = 'login.php';
});
</script>
</body>
</html>
<?php
} else {
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body style="background:#f4f6f9;">
<script>
Swal.fire({
    icon: 'error',
    title: 'Error de registro',
    text: 'No fue posible registrar el usuario en el sistema.'
}).then(() => {
    window.location = 'registro.php';
});
</script>
</body>
</html>
<?php
}

$stmt->close();
$conexion->close();
?>