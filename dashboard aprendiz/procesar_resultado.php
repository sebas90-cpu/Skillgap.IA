<?php
session_start();

// 1. Validar sesión de usuario
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

require_once("conexion.php");
require_once __DIR__ . "/config.php";

// 2. Validar que la constante GROQ_API_KEY esté correctamente definida
if (!defined('GROQ_API_KEY') || empty(GROQ_API_KEY)) {
    die("Error de configuración: La clave 'GROQ_API_KEY' no está definida en config.php.");
}

// 3. Validar que la petición sea POST y contenga los datos necesarios
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['competencia_id']) || empty($_POST['respuestas'])) {
    die("Acceso no válido o datos incompletos.");
}

$persona_id = $_SESSION['id'];
$competencia_id = intval($_POST['competencia_id']);
$caso_id = isset($_POST['caso_id']) ? intval($_POST['caso_id']) : 1; 

$respuestas_recibidas = $_POST['respuestas']; // Arreglo [pregunta_id => respuesta_texto]

// 4. Consultar datos de la competencia
$stmtComp = $conexion->prepare("SELECT nombre, descripcion FROM competencias WHERE id = ?");
$stmtComp->bind_param("i", $competencia_id);
$stmtComp->execute();
$resComp = $stmtComp->get_result();

if ($resComp->num_rows === 0) {
    die("La competencia especificada no existe.");
}

$competencia = $resComp->fetch_assoc();

// 5. Guardar las respuestas en la base de datos y construir el prompt
$prompt_casos = "";
$todas_las_respuestas_texto = ""; 

$stmtInsertResp = $conexion->prepare("INSERT INTO respuestas (persona_id, pregunta_id, respuesta) VALUES (?, ?, ?)");

$i = 1;
foreach ($respuestas_recibidas as $pregunta_id => $texto_respuesta) {
    $pregunta_id = intval($pregunta_id);
    $texto_respuesta = trim($texto_respuesta);

    if (empty($texto_respuesta)) {
        continue;
    }

    // A. Guardar respuesta individual en la BD
    $stmtInsertResp->bind_param("iis", $persona_id, $pregunta_id, $texto_respuesta);
    $stmtInsertResp->execute();

    // Acumular texto para la tabla evaluaciones
    $todas_las_respuestas_texto .= "Pregunta ID {$pregunta_id}: " . $texto_respuesta . "\n";

    // B. Obtener el enunciado de la pregunta
    $stmtPreg = $conexion->prepare("SELECT pregunta FROM preguntas WHERE id = ?");
    $stmtPreg->bind_param("i", $pregunta_id);
    $stmtPreg->execute();
    $resPreg = $stmtPreg->get_result()->fetch_assoc();

    if ($resPreg) {
        $prompt_casos .= "Pregunta {$i}: " . $resPreg['pregunta'] . "\n";
        $prompt_casos .= "Respuesta del aprendiz: " . $texto_respuesta . "\n\n";
    }
    $i++;
}

// 6. Construcción de Instrucciones y Prompt para Groq
$system_instruction = "Eres un instructor y evaluador experto en desarrollo de competencias laborales. "
                    . "Tu tarea es analizar las respuestas de un aprendiz ante un conjunto de preguntas abiertas sobre una competencia específica. "
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
             . "A continuación se presentan las preguntas y respuestas entregadas por el aprendiz:\n\n"
             . $prompt_casos;

// 7. Configurar la llamada a la API de Groq
$endpoint = "https://api.groq.com/openai/v1/chat/completions";

$payload = [
    "model" => "openai/gpt-oss-120b",
    "messages" => [
        [
            "role" => "system",
            "content" => $system_instruction
        ],
        [
            "role" => "user",
            "content" => $user_prompt
        ]
    ],
    "response_format" => [
        "type" => "json_object"
    ],
    "temperature" => 0.2
];

// Petición HTTP cURL hacia Groq
$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Authorization: Bearer " . GROQ_API_KEY
    ],
    CURLOPT_TIMEOUT => 45,
    CURLOPT_SSL_VERIFYPEER => false // Desactiva verificación SSL en entorno XAMPP local
]);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    die("Error de conexión cURL con la API de Groq: " . $error);
}

// 8. Procesar la respuesta JSON de Groq
$resultado = json_decode($response, true);

if (!isset($resultado['choices'][0]['message']['content'])) {
    die("Error en la respuesta de la API de Groq: " . print_r($resultado, true));
}

$raw_json = $resultado['choices'][0]['message']['content'];
$datos_ia = json_decode($raw_json, true);

if (!$datos_ia) {
    die("No se pudo procesar el JSON devuelto por Groq. Respuesta recibida: " . htmlspecialchars($raw_json));
}

// Extraer variables recibidas del JSON
$nivel            = isset($datos_ia['nivel']) ? $datos_ia['nivel'] : 'Intermedio';
$puntaje          = isset($datos_ia['puntaje']) ? floatval($datos_ia['puntaje']) : 50;

$fortalezas       = (isset($datos_ia['fortalezas']) && is_array($datos_ia['fortalezas'])) ? implode("\n• ", $datos_ia['fortalezas']) : '';
$oportunidades    = (isset($datos_ia['oportunidades']) && is_array($datos_ia['oportunidades'])) ? implode("\n• ", $datos_ia['oportunidades']) : '';
$recomendaciones  = (isset($datos_ia['recomendaciones']) && is_array($datos_ia['recomendaciones'])) ? implode("\n• ", $datos_ia['recomendaciones']) : '';

$analisis_general = isset($datos_ia['analisis_general']) ? $datos_ia['analisis_general'] : '';

if (!empty($fortalezas)) $fortalezas = "• " . $fortalezas;
if (!empty($oportunidades)) $oportunidades = "• " . $oportunidades;
if (!empty($recomendaciones)) $recomendaciones = "• " . $recomendaciones;

// 9. Guardar el análisis en la tabla 'analisis_ia'
$stmtIA = $conexion->prepare("INSERT INTO analisis_ia (persona_id, competencia_id, nivel, fortalezas, oportunidades, recomendaciones, analisis_completo) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmtIA->bind_param("iisssss", $persona_id, $competencia_id, $nivel, $fortalezas, $oportunidades, $recomendaciones, $analisis_general);

if ($stmtIA->execute()) {

    // 9.1 Guardar los datos en la tabla 'evaluaciones' para el historial (Mis Evaluaciones)
    $stmtEval = $conexion->prepare("INSERT INTO evaluaciones (persona_id, caso_id, respuesta, puntaje, retroalimentacion, fecha) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmtEval->bind_param("iisds", $persona_id, $caso_id, $todas_las_respuestas_texto, $puntaje, $analisis_general);
    $stmtEval->execute();

    // 10. Actualizar o crear el registro en 'persona_competencia' para la gráfica del Dashboard
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

    // 11. Actualizar o insertar el puntaje en la tabla 'progreso'
    $stmtCheckProg = $conexion->prepare("SELECT id FROM progreso WHERE persona_id = ? AND competencia_id = ?");
    if (!$stmtCheckProg) {
        die("Error en prepare (SELECT progreso): " . $conexion->error);
    }
    
    $stmtCheckProg->bind_param("ii", $persona_id, $competencia_id);
    $stmtCheckProg->execute();
    $resProg = $stmtCheckProg->get_result();

    if ($resProg->num_rows > 0) {
        $stmtUpdateProg = $conexion->prepare("UPDATE progreso SET nivel_actual = ?, ultima_actualizacion = NOW() WHERE persona_id = ? AND competencia_id = ?");
        if (!$stmtUpdateProg) {
            die("Error en prepare (UPDATE progreso): " . $conexion->error);
        }
        $stmtUpdateProg->bind_param("dii", $puntaje, $persona_id, $competencia_id);
        $stmtUpdateProg->execute();
    } else {
        $stmtInsertProg = $conexion->prepare("INSERT INTO progreso (persona_id, competencia_id, nivel_inicial, nivel_actual, ultima_actualizacion) VALUES (?, ?, ?, ?, NOW())");
        if (!$stmtInsertProg) {
            die("Error en prepare (INSERT progreso): " . $conexion->error);
        }
        $stmtInsertProg->bind_param("iidd", $persona_id, $competencia_id, $puntaje, $puntaje);
        $stmtInsertProg->execute();
    }
    
    // Redirigir a la vista de resultados
    header("Location: resultado_ia.php?competencia_id=" . $competencia_id);
    exit();
} else {
    die("Error al guardar el análisis de la IA en la base de datos.");
}
?>