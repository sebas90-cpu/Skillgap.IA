document.addEventListener('DOMContentLoaded', () => {

    // 1. FUNCIONALIDAD DEL BOTÓN "INICIAR PRUEBA IA" (Con bloqueo para evitar dobles clics)
    const btnIA = document.getElementById('btnComenzarIA');
    
    if (btnIA) {
        btnIA.addEventListener('click', (e) => {
            // Evitar clics repetidos bloqueando el botón
            btnIA.disabled = true;
            btnIA.style.pointerEvents = 'none';
            btnIA.style.opacity = '0.7';
            btnIA.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Cargando prueba...';

            // Redirección usando la constante global si está definida, o relativa simple
            const baseUrl = typeof BASE_URL !== 'undefined' ? BASE_URL + 'modulo_aprendiz/' : '';
            window.location.href = baseUrl + 'evaluacion_ia.php';
        });
    }

    // 2. BLOQUEO DE ENVÍO REPETIDO SI LA EVALUACIÓN UTILIZA UN FORMULARIO
    const formEvaluacion = document.getElementById('formEvaluacionIA'); // Ajusta con el ID de tu formulario si aplica

    if (formEvaluacion) {
        formEvaluacion.addEventListener('submit', function (e) {
            const btnEnviar = formEvaluacion.querySelector("button[type='submit']");

            if (btnEnviar) {
                // Deshabilitar el botón y mostrar loader
                btnEnviar.disabled = true;
                btnEnviar.style.pointerEvents = 'none';
                btnEnviar.style.opacity = '0.7';
                btnEnviar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando análisis con IA...';
            }
        });
    }

    // 3. MANEJO DE ENLACES CON '#' EN EL MENÚ LATERAL
    const enlacesMenu = document.querySelectorAll('.dashboard .sidebar .menu a');

    enlacesMenu.forEach(enlace => {
        enlace.addEventListener('click', function(e) {
            // Si el enlace es '#', evitamos el salto de pantalla
            if (this.getAttribute('href') === '#') {
                e.preventDefault();
                enlacesMenu.forEach(item => item.classList.remove('activo'));
                this.classList.add('activo');
            }
        });
    });

});