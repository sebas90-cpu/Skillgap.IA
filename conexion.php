<?php

//==========================================
// CONEXIÓN A LA BASE DE DATOS
//==========================================

$host = "localhost";
$usuario = "root";
$password = "";
$bd = "competencias_ia";

// Crear conexión
$conexion = new mysqli($host, $usuario, $password, $bd);

// Verificar conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Configurar codificación UTF-8
$conexion->set_charset("utf8");

?>