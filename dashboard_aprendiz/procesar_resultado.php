<?php
session_start();

// 1. Cargar conexión y configuración desde las rutas correctas
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/config.php';

// 2. Validar sesión de usuario (usando BASE_URL si existe o redirección relativa)
if (!isset($_SESSION['id']) && !isset($_SESSION['usuario']) && !isset($_SESSION['persona_id']) && !isset($_SESSION['id_usuario'])) {
    $redirect_url = defined('BASE_URL') ? BASE_URL . "registro_login/login.php" : "login.php";
    header("Location: " . $redirect_url);
    exit();
}

// Activar reporte de errores MySQLi
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// 3. Validar que la clave GROQ_API_KEY esté definida
if (!defined('GROQ_API_KEY') || empty(GROQ_API_KEY)) {
    die("Error de configuración: La clave 'GROQ_API_KEY' no está definida en config.php.");
}

// 4. Validar que la petición sea POST y contenga los datos necesarios
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['competencia_id']) || empty($_POST['respuestas'])) {
    die("Acceso no válido o datos incompletos.");
}

$persona_id = $_SESSION['persona_id'] ?? $_SESSION['id_usuario'] ?? $_SESSION['id'] ?? 1;
$competencia_id = intval($_POST['competencia_id']);
$respuestas_recibidas = $_POST['respuestas']; // Arreglo [pregunta_id => respuesta_texto]

// 5. Consultar datos de la competencia
$stmtComp = $conexion->prepare("SELECT nombre, descripcion FROM competencias WHERE id = ?");
$stmtComp->bind_param("i", $competencia_id);
$stmtComp->execute();
$resComp = $stmtComp->get_result();

if ($resComp->num_rows === 0) {
    die("La competencia especificada no existe.");
}

$competencia = $resComp->fetch_assoc();

// --- OBTENER EL CASO_ID ASOCIADO A LA COMPETENCIA ---
$stmtCaso = $conexion->prepare("SELECT id FROM casos WHERE competencia_id = ? LIMIT 1");
$stmtCaso->bind_param("i", $competencia_id);
$stmtCaso->execute();
$resCaso = $stmtCaso->get_result();

if ($rowCaso = $resCaso->fetch_assoc()) {
    $caso_id = intval($rowCaso['id']);
} else {
    die("Error: No existe un caso configurado para esta competencia. Por favor ejecuta la limpieza y reinicio de la tabla 'casos'.");
}

// 6. Guardar las respuestas en la base de datos y construir el prompt
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

// 7. Construcción de Instrucciones y Prompt para Groq
$system_instruction = "Eres un instructor y evaluador experto en desarrollo de competencias laborales. "
                    . "Tu tarea es analizar las respuestas de un aprendiz ante un conjunto de preguntas abiertas sobre una competencia específica. "
                    . "Debes evaluar con objetividad, rigor y enfoque de desarrollo profesional.\n\n"
                    . "REGLA DE FORMATO OBLIGATORIA:\n"
                    . "Debes responder ÚNICAMENTE con un objeto JSON strictly válido. No incluyas texto antes ni después del JSON.\n"
                    . "Si el campo 'analisis_general' contiene varios párrafos, colócalos dentro de un SOLO string separados por '\\n\\n' (NO uses comillas adicionales ni cierres el string entre párrafos).\n\n"
                    . "Estructura JSON requerida:\n"
                    . "{\n"
                    . '  "nivel": "Inicial" | "Intermedio" | "Avanzado",' . "\n"
                    . '  "puntaje": 85,' . "\n"
                    . '  "fortalezas": ["Fortaleza 1", "Fortaleza 2"],' . "\n"
                    . '  "oportunidades": ["Oportunidad de mejora 1", "Oportunidad de mejora 2"],' . "\n"
                    . '  "recomendaciones": ["Recomendación 1", "Recomendación 2"],' . "\n"
                    . '  "analisis_general": "Primer párrafo del análisis.\\n\\nSegundo párrafo del análisis."' . "\n"
                    . "}";

$user_prompt = "Competencia evaluada: " . $competencia['nombre'] . "\n"
             . "Descripción de la competencia: " . $competencia['descripcion'] . "\n\n"
             . "A continuación se presentan las preguntas y respuestas entregadas por el aprendiz:\n\n"
             . $prompt_casos;

// 8. Configurar la llamada a la API de Groq
$endpoint = "https://api.groq.com/openai/v1/chat/completions";

$payload = [
    "model" => "qwen/qwen3.8-27b",
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
    "temperature" => 0.3
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
    CURLOPT_SSL_VERIFYPEER => false // Para entornos de desarrollo local en XAMPP
]);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    die("Error de conexión cURL con la API de Groq: " . $error);
}

// 9. Procesar la respuesta JSON de Groq
$resultado = json_decode($response, true);

if (!isset($resultado['choices'][0]['message']['content'])) {
    die("Error en la respuesta de la API de Groq: " . print_r($resultado, true));
}

$raw_json = $resultado['choices'][0]['message']['content'];

// Intento de decodificación directa
$datos_ia = json_decode($raw_json, true);

// Si falla la decodificación, aplicamos limpieza sobre comillas entre párrafos
if (!$datos_ia) {
    $cleaned_json = preg_replace('/"\s*\n\s*"/m', '\n\n', $raw_json);
    $datos_ia = json_decode($cleaned_json, true);
}

if (!$datos_ia) {
    die("No se pudo procesar el JSON devuelto por Groq. Respuesta recibida: " . htmlspecialchars($raw_json));
}

// Extraer variables del JSON recibido
$nivel               = $datos_ia['nivel'] ?? 'Intermedio';
$puntaje             = isset($datos_ia['puntaje']) ? floatval($datos_ia['puntaje']) : 50;

$fortalezas_arr      = (isset($datos_ia['fortalezas']) && is_array($datos_ia['fortalezas'])) ? $datos_ia['fortalezas'] : [];
$oportunidades_arr   = (isset($datos_ia['oportunidades']) && is_array($datos_ia['oportunidades'])) ? $datos_ia['oportunidades'] : [];
$recomendaciones_arr = (isset($datos_ia['recomendaciones']) && is_array($datos_ia['recomendaciones'])) ? $datos_ia['recomendaciones'] : [];

// Formateo con viñetas
$fortalezas      = !empty($fortalezas_arr) ? "• " . implode("\n• ", $fortalezas_arr) : '';
$oportunidades   = !empty($oportunidades_arr) ? "• " . implode("\n• ", $oportunidades_arr) : '';
$recomendaciones = !empty($recomendaciones_arr) ? "• " . implode("\n• ", $recomendaciones_arr) : '';

$analisis_general = $datos_ia['analisis_general'] ?? '';

// 10. Guardar el análisis en la tabla 'analisis_ia'
$stmtIA = $conexion->prepare("INSERT INTO analisis_ia (persona_id, competencia_id, nivel, fortalezas, oportunidades, recomendaciones, analisis_completo) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmtIA->bind_param("iisssss", $persona_id, $competencia_id, $nivel, $fortalezas, $oportunidades, $recomendaciones, $analisis_general);

if ($stmtIA->execute()) {

    // 10.1 Guardar los datos en la tabla 'evaluaciones'
    $stmtEval = $conexion->prepare("INSERT INTO evaluaciones (persona_id, caso_id, respuesta, puntaje, retroalimentacion, fecha) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmtEval->bind_param("iisds", $persona_id, $caso_id, $todas_las_respuestas_texto, $puntaje, $analisis_general);
    $stmtEval->execute();

    // 11. Actualizar o crear el registro en 'persona_competencia'
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

    // 12. Actualizar o insertar el puntaje en la tabla 'progreso'
    $stmtCheckProg = $conexion->prepare("SELECT id FROM progreso WHERE persona_id = ? AND competencia_id = ?");
    $stmtCheckProg->bind_param("ii", $persona_id, $competencia_id);
    $stmtCheckProg->execute();
    $resProg = $stmtCheckProg->get_result();

    if ($resProg->num_rows > 0) {
        $stmtUpdateProg = $conexion->prepare("UPDATE progreso SET nivel_actual = ?, ultima_actualizacion = NOW() WHERE persona_id = ? AND competencia_id = ?");
        $stmtUpdateProg->bind_param("dii", $puntaje, $persona_id, $competencia_id);
        $stmtUpdateProg->execute();
    } else {
        $stmtInsertProg = $conexion->prepare("INSERT INTO progreso (persona_id, competencia_id, nivel_inicial, nivel_actual, ultima_actualizacion) VALUES (?, ?, ?, ?, NOW())");
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