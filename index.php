<?php
require_once __DIR__ . '/conexion.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillGap AI</title>

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>estilos.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>

<!-- ================= NAVBAR ================= -->
<nav>
    <div class="logo">
        <i class="fa-solid fa-brain"></i>
        <span>SkillGap AI</span>
    </div>

    <ul>
        <li><a href="#inicio">Inicio</a></li>
        <li><a href="#acerca">Acerca</a></li>
        <li><a href="#contacto">Contacto</a></li>
        <li><a href="<?php echo BASE_URL; ?>registro_login/login.php" class="nav-btn">Iniciar Sesión</a></li>
    </ul>
</nav>

<!-- ================= HERO ================= -->
<section class="hero" id="inicio">
    <div class="hero-text">
        <h1>Sistema Inteligente de Evaluación de Competencias</h1>
        <p>
            Evalúa las competencias de los aprendices mediante Inteligencia Artificial.
            Obtén retroalimentación automática y visualiza el progreso de cada persona.
        </p>

        <a href="<?php echo BASE_URL; ?>registro_login/login.php" class="btn">
            Acceder al sistema
        </a>
    </div>

    <div class="hero-img">
        <i class="fa-solid fa-robot"></i>
    </div>
</section>

<!-- ================= ACERCA ================= -->
<section class="acerca" id="acerca">
    <div class="contenedor">
        <h2>¿Qué es SkillGap AI?</h2>
        <p>
            SkillGap AI es una plataforma web desarrollada para evaluar competencias
            mediante Inteligencia Artificial. El sistema permite registrar aprendices,
            presentar casos prácticos, analizar respuestas abiertas y generar
            retroalimentación automática para apoyar el proceso de aprendizaje.
        </p>

        <div class="cards">
            <div class="card">
                <i class="fa-solid fa-brain"></i>
                <h3>Evaluación Inteligente</h3>
                <p>
                    La IA analiza cada respuesta del aprendiz y proporciona una
                    retroalimentación objetiva y personalizada.
                </p>
            </div>

            <div class="card">
                <i class="fa-solid fa-chart-line"></i>
                <h3>Seguimiento</h3>
                <p>
                    Permite visualizar el avance de cada competencia y conocer las
                    fortalezas y oportunidades de mejora.
                </p>
            </div>

            <div class="card">
                <i class="fa-solid fa-user-graduate"></i>
                <h3>Aprendizaje Continuo</h3>
                <p>
                    El aprendiz recibe recomendaciones para fortalecer sus competencias
                    y cerrar las brechas identificadas.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ================= CONTACTO ================= -->
<footer id="contacto">
    <h2>Contacto</h2>
    <p>
        Proyecto académico desarrollado como prototipo de evaluación de competencias
        mediante Inteligencia Artificial.
    </p>
    <br>
    <p>
        <i class="fa-solid fa-envelope"></i>
        contacto@skillgapai.com
    </p>
    <p>
        <i class="fa-solid fa-code"></i>
        Desarrollado por: Sebastian Peñaloza/OVERLAP
    </p>
    <p>
        <i class="fa-solid fa-graduation-cap"></i>
        SENA - Programación de software
    </p>
    <br>
    <p>
        © 2026 SkillGap AI - Todos los derechos reservados.
    </p>
</footer>

</body>
</html>