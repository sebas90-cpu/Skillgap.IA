<?php
session_start();

// 1. Validar sesión de usuario
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

require_once("conexion.php");
require_once("config.php"); // <--- Cargamos el archivo seguro con la constante GEMINI_API_KEY

// 2. Validar que la petición sea POST y contenga los datos necesarios
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['competencia_id']) || empty($_POST['respuestas'])) {
    die("Acceso no válido o datos incompletos.");
}

$persona_id = $_SESSION['id'];
$competencia_id = intval($_POST['competencia_id']);
$respuestas_recibidas = $_POST['respuestas']; // Arreglo [pregunta_id => respuesta_texto]

// 3. Consultar datos de la competencia
$stmtComp = $conexion->prepare("SELECT nombre, descripcion FROM competencias WHERE id = ?");
$stmtComp->bind_param("i", $competencia_id);
$stmtComp->execute();
$resComp = $stmtComp->get_result();

if ($resComp->num_rows === 0) {
    die("La competencia especificada no existe.");
}

$competencia = $resComp->fetch_assoc();

// 4. Guardar las respuestas en la base de datos y construir el prompt
$prompt_casos = "";

$stmtInsertResp = $conexion->prepare("INSERT INTO respuestas (persona_id, pregunta_id, respuesta) VALUES (?, ?, ?)");

$i = 1;
foreach ($respuestas_recibidas as $pregunta_id => $texto_respuesta) {
    $pregunta_id = intval($pregunta_id);
    $texto_respuesta = trim($texto_respuesta);

    if (empty($texto_respuesta)) {
        continue;
    }

    // A. Guardar respuesta en la BD
    $stmtInsertResp->bind_param("iis", $persona_id, $pregunta_id, $texto_respuesta);
    $stmtInsertResp->execute();

    // B. Obtener el enunciado de la pregunta
    $stmtPreg = $conexion->prepare("SELECT pregunta FROM preguntas WHERE id = ?");
    $stmtPreg->bind_param("i", $pregunta_id);
    $stmtPreg->execute();
    $resPreg = $stmtPreg->get_result()->fetch_assoc();

    if ($resPreg) {
        $prompt_casos .= "Caso / Pregunta {$i}: " . $resPreg['pregunta'] . "\n";
        $prompt_casos .= "Respuesta del aprendiz: " . $texto_respuesta . "\n\n";
    }
    $i++;
}

// 5. Construcción de Instrucciones y Prompt para Gemini
$system_instruction = "Eres un instructor y evaluador experto en desarrollo de competencias laborales. "
                    . "Tu tarea es analizar las respuestas de un aprendiz ante un conjunto de casos prácticos sobre una competencia específica. "
                    . "Debes evaluar con objetividad, rigor y enfoque de desarrollo profesional.\n\n"
                    . "DEBES responder ÚNICAMENTE con un objeto JSON estrictamente válido con la siguiente estructura:\n"
                    . "{\n"
                    . '  "nivel": "Inicial" | "Intermedio" | "Avanzado",' . "\n"
                    . '  "puntaje": 85,' . "\n"
                    . '  "fortalezas": ["Fortaleza 1", "Fortaleza 2"],' . "\n"
                    . '  "oportunidades": ["Oportunidad de mejora 1", "Oportunidad de mejora 2"],' . "\n"
                    . '  "recomendaciones": ["Recomendación 1", "Recomendación 2"],' . "\n"
                    . '  "analisis_general": "Resumen cualitativo de la evaluación en 2 párrafos."' . "\n"
                    . "}";

$user_prompt = "Competencia evaluada: " . $competencia['nombre'] . "\n"
             . "Descripción de la competencia: " . $competencia['descripcion'] . "\n\n"
             . "A continuación se presentan los casos y respuestas entregadas por el aprendiz:\n\n"
             . $prompt_casos;

// 6. Configurar la llamada a la API de Gemini (Utilizando la constante segura)
$endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . GEMINI_API_KEY;
$payload = [
    "system_instruction" => [
        "parts" => [
            ["text" => $system_instruction]
        ]
    ],
    "contents" => [
        [
            "parts" => [
                ["text" => $user_prompt]
            ]
        ]
    ],
    "generationConfig" => [
        "response_mime_type" => "application/json",
        "temperature" => 0.2
    ]
];

// Petición HTTP cURL
$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json"
    ],
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    die("Error de conexión cURL con la API de Gemini: " . $error);
}

// 7. Procesar la respuesta JSON de Gemini
$resultado = json_decode($response, true);

if (!isset($resultado['candidates'][0]['content']['parts'][0]['text'])) {
    die("Error en la respuesta de la API de Gemini: " . print_r($resultado, true));
}

$raw_json = $resultado['candidates'][0]['content']['parts'][0]['text'];
$datos_ia = json_decode($raw_json, true);

if (!$datos_ia) {
    die("No se pudo procesar el JSON devuelto por Gemini.");
}

// Extraer variables recibidas del JSON
$nivel          = isset($datos_ia['nivel']) ? $datos_ia['nivel'] : 'Intermedio';
$puntaje         = isset($datos_ia['puntaje']) ? intval($datos_ia['puntaje']) : 50;
$fortalezas      = isset($datos_ia['fortalezas']) ? implode("\n• ", $datos_ia['fortalezas']) : '';
$oportunidades   = isset($datos_ia['oportunidades']) ? implode("\n• ", $datos_ia['oportunidades']) : '';
$recomendaciones = isset($datos_ia['recomendaciones']) ? implode("\n• ", $datos_ia['recomendaciones']) : '';
$analisis_general= isset($datos_ia['analisis_general']) ? $datos_ia['analisis_general'] : '';

if (!empty($fortalezas)) $fortalezas = "• " . $fortalezas;
if (!empty($oportunidades)) $oportunidades = "• " . $oportunidades;
if (!empty($recomendaciones)) $recomendaciones = "• " . $recomendaciones;

// 8. Guardar el análisis en la tabla 'analisis_ia'
$stmtIA = $conexion->prepare("INSERT INTO analisis_ia (persona_id, competencia_id, nivel, fortalezas, oportunidades, recomendaciones, analisis_completo) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmtIA->bind_param("iisssss", $persona_id, $competencia_id, $nivel, $fortalezas, $oportunidades, $recomendaciones, $analisis_general);

if ($stmtIA->execute()) {
    // 9. Actualizar o crear el registro en 'persona_competencia' para la gráfica del Dashboard
    $stmtCheckPC = $conexion->prepare("SELECT id FROM persona_competencia WHERE persona = ? AND competencia = ?");
    $stmtCheckPC->bind_param("ii", $persona_id, $competencia_id);
    $stmtCheckPC->execute();
    $resPC = $stmtCheckPC->get_result();

    if ($resPC->num_rows > 0) {
        $stmtUpdatePC = $conexion->prepare("UPDATE persona_competencia SET despues = ? WHERE persona = ? AND competencia = ?");
        $stmtUpdatePC->bind_param("iii", $puntaje, $persona_id, $competencia_id);
        $stmtUpdatePC->execute();
    } else {
        $antes_simulado = rand(30, 50); // Baseline inicial
        $stmtInsertPC = $conexion->prepare("INSERT INTO persona_competencia (persona, competencia, antes, despues) VALUES (?, ?, ?, ?)");
        $stmtInsertPC->bind_param("iiii", $persona_id, $competencia_id, $antes_simulado, $puntaje);
        $stmtInsertPC->execute();
    }
    // Redirigir a la vista de resultados de este módulo específico
    header("Location: resultado_ia.php?competencia_id=" . $competencia_id);
    exit();
}   