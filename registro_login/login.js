/*====================================================
            LOGIN (registro_login/login.js)
====================================================*/

document.addEventListener("DOMContentLoaded", () => {
    const formLogin = document.getElementById("formLogin");

    if (formLogin) {
        const verPassword = document.getElementById("verPassword");
        const password = document.getElementById("password");

        // Mostrar / ocultar contraseña de forma segura
        if (verPassword && password) {
            verPassword.addEventListener("click", () => {
                if (password.type === "password") {
                    password.type = "text";
                    verPassword.classList.remove("fa-eye");
                    verPassword.classList.add("fa-eye-slash");
                } else {
                    password.type = "password";
                    verPassword.classList.remove("fa-eye-slash");
                    verPassword.classList.add("fa-eye");
                }
            });
        }

        // Validación del formulario antes de enviar
        formLogin.addEventListener("submit", function (e) {
            const usuarioInput = document.getElementById("usuario");
            const claveInput = document.getElementById("password");

            const usuario = usuarioInput ? usuarioInput.value.trim() : "";
            const clave = claveInput ? claveInput.value.trim() : "";

            // 1. Campos vacíos
            if (usuario === "" || clave === "") {
                e.preventDefault();
                Swal.fire({
                    icon: "warning",
                    title: "Campos incompletos",
                    text: "Debe ingresar su usuario o correo y contraseña."
                });
                return;
            }

            // 2. Detección de typo común en correos (@gamil.com)
            if (usuario.toLowerCase().includes("@gamil.com")) {
                e.preventDefault();
                Swal.fire({
                    icon: "warning",
                    title: "¿Quisiste decir gmail.com?",
                    text: "Detectamos 'gamil.com' en tu correo. Por favor, corrígelo antes de continuar."
                });
                return;
            }

            // 3. Longitud mínima de usuario
            if (usuario.length < 4) {
                e.preventDefault();
                Swal.fire({
                    icon: "warning",
                    title: "Usuario inválido",
                    text: "El nombre de usuario o correo debe tener al menos 4 caracteres."
                });
                return;
            }

            // 4. Longitud mínima de contraseña
            if (clave.length < 4) {
                e.preventDefault();
                Swal.fire({
                    icon: "warning",
                    title: "Contraseña inválida",
                    text: "La contraseña debe tener al menos 4 caracteres."
                });
                return;
            }
        });
    }
});