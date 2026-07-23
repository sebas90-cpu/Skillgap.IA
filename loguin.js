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
                text: "Debe ingresar su usuario y contraseña."
            });

            return;
        }

        if (usuario.length < 4) {

            e.preventDefault();

            Swal.fire({
                icon: "warning",
                title: "Usuario inválido",
                text: "El usuario debe tener al menos 4 caracteres."
            });

            return;
        }

        if (clave.length < 8) {

            e.preventDefault();

            Swal.fire({
                icon: "warning",
                title: "Contraseña inválida",
                text: "La contraseña debe tener al menos 8 caracteres."
            });

            return;
        }

    });

}