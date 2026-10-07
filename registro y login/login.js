/*====================================================
            LOGIN
====================================================*/

const formLogin = document.getElementById("formLogin");

if (formLogin) {

    // Mostrar / ocultar contraseña
    const verPassword = document.getElementById("verPassword");
    const password = document.getElementById("password");

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

    // Validación del formulario
    formLogin.addEventListener("submit", function (e) {

        let usuario = document.getElementById("usuario").value.trim();
        let clave = document.getElementById("password").value.trim();

        if (usuario === "" || clave === "") {

            e.preventDefault();

            Swal.fire({
                icon: "warning",
                title: "Campos incompletos",
                text: "Debe ingresar su correo y contraseña."
            });

            return;
        }

        // Validación específica para detectar errores comunes como "@gamil.com"
        if (usuario.includes("@gamil.com")) {
            e.preventDefault();

            Swal.fire({
                icon: "warning",
                title: "¿Quisiste decir gmail.com?",
                text: "Detectamos 'gamil.com' en tu correo. Por favor, corrígelo antes de continuar."
            });

            return;
        }

        if (usuario.length < 4) {

            e.preventDefault();

            Swal.fire({
                icon: "warning",
                title: "Usuario inválido",
                text: "El campo de usuario o correo debe ser válido."
            });

            return;
        }

        if (clave.length < 4) { // Ajustado a 4 o lo que requieras para tus pruebas

            e.preventDefault();

            Swal.fire({
                icon: "warning",
                title: "Contraseña inválida",
                text: "La contraseña es muy corta."
            });

            return;
        }

    });

}