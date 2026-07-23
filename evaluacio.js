document.addEventListener('DOMContentLoaded', () => {

    const formEvaluacion = document.getElementById('formEvaluacion');
    const barraProgreso = document.getElementById('barraProgresoQuiz');
    const textoProgreso = document.getElementById('textoProgresoQuiz');
    
    // Obtenemos todos los cuadros de respuesta abierta
    const respuestasTexto = document.querySelectorAll('.pregunta-card textarea');
    const totalPreguntas = respuestasTexto.length;

    // -------------------------------------------------------------
    // 1. CALCULAR AVANCE SEGÚN TEXTO ESCRITO (Mínimo 5 caracteres)
    // -------------------------------------------------------------
    function actualizarProgreso() {
        let preguntasRespondidas = 0;

        respuestasTexto.forEach(textarea => {
            // Consideramos respondida si tiene al menos 5 caracteres (ignorando espacios vacíos)
            if (textarea.value.trim().length >= 5) {
                preguntasRespondidas++;
            }
        });

        // Calcular porcentaje
        const porcentaje = totalPreguntas > 0 ? Math.round((preguntasRespondidas / totalPreguntas) * 100) : 0;

        // Actualizar barra visual
        if (barraProgreso) {
            barraProgreso.style.width = `${porcentaje}%`;
        }

        // Actualizar texto descriptivo
        if (textoProgreso) {
            textoProgreso.textContent = `${preguntasRespondidas} de ${totalPreguntas} respondidas (${porcentaje}%)`;
        }
    }

    // -------------------------------------------------------------
    // 2. ESCUCHAR CADA TECLA MIENTRAS EL APRENDIZ ESCRIBE
    // -------------------------------------------------------------
    respuestasTexto.forEach(textarea => {
        // 'input' se dispara en tiempo real con cada letra que escribe o borra
        textarea.addEventListener('input', (e) => {
            actualizarProgreso();

            // Cambiar el borde del textarea según si respondió o no
            if (e.target.value.trim().length >= 5) {
                e.target.style.borderColor = '#2563EB';
                e.target.style.backgroundColor = '#F8FAFC';
            } else {
                e.target.style.borderColor = '#E2E8F0';
                e.target.style.backgroundColor = '#FFFFFF';
            }
        });
    });

    // -------------------------------------------------------------
    // 3. VALIDACIÓN ANTES DE ENVIAR A LA IA
    // -------------------------------------------------------------
    if (formEvaluacion) {
        formEvaluacion.addEventListener('submit', (e) => {
            let pendientes = 0;

            respuestasTexto.forEach(textarea => {
                if (textarea.value.trim().length < 5) {
                    pendientes++;
                }
            });

            if (pendientes > 0) {
                e.preventDefault();
                alert(`⚠️ Tienes ${pendientes} pregunta(s) sin responder o muy cortas. Escribe una respuesta más detallada para que la IA pueda analizarla.`);
            }
        });
    }

    // Inicializar avance
    actualizarProgreso();
});