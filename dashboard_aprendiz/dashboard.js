document.addEventListener('DOMContentLoaded', () => {

    // 1. FUNCIONALIDAD DEL BOTÓN "INICIAR PRUEBA IA"
    const btnIA = document.getElementById('btnComenzarIA');
    
    if (btnIA) {
        btnIA.addEventListener('click', () => {
            // Redirección usando la constante global si está definida, o relativa simple
            const baseUrl = typeof BASE_URL !== 'undefined' ? BASE_URL + 'modulo_aprendiz/' : '';
            window.location.href = baseUrl + 'evaluacion_ia.php';
        });
    }

    // 2. MANEJO DE ENLACES CON '#' EN EL MENÚ LATERAL
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