<?php
require_once __DIR__ . '/../conexion.php';
session_start();

// Validar que la petición venga por POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit();
}

$login_input = trim($_POST['usuario'] ?? $_POST['correo'] ?? '');
$password    = $_POST['password'] ?? '';

if (empty($login_input) || empty($password)) {
    mostrarAlertaError("Por favor ingrese usuario/correo y contraseña.");
}

// 1. Consultar el usuario en la base de datos (por correo o usuario)
$sql = "SELECT id, nombre, apellido, correo, usuario, password, estado, rol_id, programa 
        FROM personas 
        WHERE correo = ? OR usuario = ? 
        LIMIT 1";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("ss", $login_input, $login_input);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 1) {
    $usuario = $resultado->fetch_assoc();

    // 2. Validar estado de la cuenta
    if (isset($usuario['estado']) && $usuario['estado'] !== 'Activo') {
        mostrarAlertaError("Tu cuenta se encuentra inactiva. Contacta al administrador.");
    }

    // 3. Validar contraseña (soporta tanto password_hash como texto plano)
    $password_valida = false;
    if (password_verify($password, $usuario['password'])) {
        $password_valida = true;
    } elseif ($password === $usuario['password']) {
        $password_valida = true;
    }

    if ($password_valida) {
        // 4. Crear variables de sesión unificadas
        $_SESSION['id']          = $usuario['id'];
        $_SESSION['persona_id']  = $usuario['id'];
        $_SESSION['id_usuario']  = $usuario['id'];
        $_SESSION['nombre']      = $usuario['nombre'];
        $_SESSION['apellido']    = $usuario['apellido'];
        $_SESSION['usuario']     = $usuario['usuario'];
        $_SESSION['correo']      = $usuario['correo'];
        $_SESSION['rol_id']      = intval($usuario['rol_id']);
        $_SESSION['programa']    = $usuario['programa'];

        // 5. Determinar la ruta de redirección según el rol_id de la BD
        $redirect_path = "";

        switch (intval($usuario['rol_id'])) {
            case 1: // Administrador
                $redirect_path = defined('BASE_URL') ? BASE_URL . "dashboard_admin/dashboard_admin.php" : "../dashboard_admin/dashboard_admin.php";
                break;

            case 3: // Evaluador / Instructor
                $redirect_path = defined('BASE_URL') ? BASE_URL . "dashboard_ins/dashboard_ins.php" : "../dashboard_ins/dashboard_ins.php";
                break;

            case 2: // Aprendiz
            default:
                $redirect_path = defined('BASE_URL') ? BASE_URL . "dashboard_aprendiz/dashboard.php" : "../dashboard_aprendiz/dashboard.php";
                break;
        }

        // Redireccionar inmediatamente
        header("Location: " . $redirect_path);
        exit();

    } else {
        mostrarAlertaError("La contraseña ingresada es incorrecta.");
    }
} else {
    mostrarAlertaError("No existe una cuenta registrada con este correo o usuario.");
}

$stmt->close();
$conexion->close();

/**
 * Muestra una alerta gráfica de error con SweetAlert2 y redirige al login
 * 
 * @param string $mensaje
 */
function mostrarAlertaError(string $mensaje) {
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
        title: "Error de inicio de sesión",
        text: "' . htmlspecialchars($mensaje) . '"
    }).then(() => {
        window.location = "login.php";
    });
    </script>
    </body>
    </html>';
    exit();
}
?>