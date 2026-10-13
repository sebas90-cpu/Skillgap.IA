<?php
require_once __DIR__ . '/../conexion.php';

// Verificar que el formulario se envió por POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: registro.php");
    exit();
}

//==========================
// Obtener datos del formulario
//==========================

$nombre    = trim($_POST['nombre']);
$apellido  = trim($_POST['apellido']);
$documento = trim($_POST['documento']);
$correo    = trim($_POST['correo']);
$usuario   = trim($_POST['usuario']);
$password  = $_POST['password']; // Contraseña recibida

// Obtener el rol_id dinámicamente (por defecto 2 = Aprendiz si viene vacío)
$rol_id    = isset($_POST['rol_id']) && !empty($_POST['rol_id']) ? intval($_POST['rol_id']) : 2;

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
// Insertar usuario guardando el rol_id seleccionado
//==========================

$sql = "INSERT INTO personas (nombre, apellido, documento, correo, programa, usuario, password, estado, rol_id) VALUES (?, ?, ?, ?, ?, ?, ?, 'Activo', ?)";

$stmt = $conexion->prepare($sql);

// "sssssssi" -> 7 strings y 1 entero (rol_id)
$stmt->bind_param(
    "sssssssi",
    $nombre,
    $apellido,
    $documento,
    $correo,
    $programa,
    $usuario,
    $password,
    $rol_id
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