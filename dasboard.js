document.addEventListener('DOMContentLoaded', () => {

    // 1. FUNCIONALIDAD DEL BOTÓN "INICIAR PRUEBA IA"
    const btnIA = document.getElementById('btnComenzarIA');
    
    if (btnIA) {
        btnIA.addEventListener('click', () => {
            // Aquí puedes redirigir al módulo de la prueba o abrir un modal
            alert('¡Iniciando el módulo de Evaluación por IA!');
            // Ejemplo de redirección futura:
            // window.location.href = 'evaluacion_ia.php';
        });
    }

    // 2. CAMBIO DE CLASE ACTIVA EN EL MENÚ LATERAL
    const enlacesMenu = document.querySelectorAll('.dashboard .sidebar .menu a');

    enlacesMenu.forEach(enlace => {
        enlace.addEventListener('click', function(e) {
            // Si el enlace es '#', evitamos el salto brusco de pantalla
            if (this.getAttribute('href') === '#') {
                e.preventDefault();
            }

            // Quitar clase 'activo' de todos los enlaces y ponerla en el cliqueado
            enlacesMenu.forEach(item => item.classList.remove('activo'));
            this.classList.add('activo');
        });
    });

    // 3. BOTÓN DE NOTIFICACIONES
    const btnNotificaciones = document.querySelector('.notificaciones i');
    if (btnNotificaciones) {
        btnNotificaciones.addEventListener('click', () => {
            alert('No tienes notificaciones pendientes por el momento.');
        });
    }

});