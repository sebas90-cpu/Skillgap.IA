<?php
// Activar errores para diagnóstico
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ruta absoluta en el servidor
if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__ . '/');
}

// URL base limpia para redirecciones y enlaces
if (!defined('BASE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    define('BASE_URL', $protocol . "://" . $host . "/sistemascompetenciasia/");
}

// Configuración de base de datos
$host_db = "localhost";
$user_db = "root";
$pass_db = "";
$name_db = "competencias_ia";

$conexion = new mysqli($host_db, $user_db, $pass_db, $name_db);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");
?>