/*====================================================
        VALIDACIONES REGISTRO (validaciones.js)
====================================================*/

document.addEventListener("DOMContentLoaded", () => {

    //=============================================
    // FUNCIÓN AUXILIAR: MOSTRAR / OCULTAR CONTRASEÑA
    //=============================================
    const setupTogglePassword = (btnId, inputId) => {
        const btn = document.getElementById(btnId);
        const input = document.getElementById(inputId);

        if (btn && input) {
            btn.addEventListener("click", () => {
                if (input.type === "password") {
                    input.type = "text";
                    btn.classList.remove("fa-eye");
                    btn.classList.add("fa-eye-slash");
                } else {
                    input.type = "password";
                    btn.classList.remove("fa-eye-slash");
                    btn.classList.add("fa-eye");
                }
            });
        }
    };

    setupTogglePassword("verPassword", "password");
    setupTogglePassword("verConfirmar", "confirmar");

    //=============================================
    // VALIDACIONES EN TIEMPO REAL
    //=============================================
    const nombre = document.getElementById("nombre");
    const apellido = document.getElementById("apellido");
    const documento = document.getElementById("documento");
    const correo = document.getElementById("correo");
    const usuario = document.getElementById("usuario");

    // Nombre
    if (nombre) {
        nombre.addEventListener("input", () => {
            const regex = /^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/;
            nombre.style.borderColor = regex.test(nombre.value.trim()) ? "#22c55e" : "#ef4444";
        });
    }

    // Apellido
    if (apellido) {
        apellido.addEventListener("input", () => {
            const regex = /^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/;
            apellido.style.borderColor = regex.test(apellido.value.trim()) ? "#22c55e" : "#ef4444";
        });
    }

    // Documento (solo números, mín. 6 dígitos)
    if (documento) {
        documento.addEventListener("input", () => {
            documento.value = documento.value.replace(/\D/g, '');
            documento.style.borderColor = documento.value.length >= 6 ? "#22c55e" : "#ef4444";
        });
    }

    // Correo
    if (correo) {
        correo.addEventListener("input", () => {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            correo.style.borderColor = regex.test(correo.value.trim()) ? "#22c55e" : "#ef4444";
        });
    }

    // Usuario (sin espacios, mín. 4 caracteres)
    if (usuario) {
        usuario.addEventListener("input", () => {
            usuario.value = usuario.value.replace(/\s/g, '');
            usuario.style.borderColor = usuario.value.length >= 4 ? "#22c55e" : "#ef4444";
        });
    }

    //=====================================
    // MOSTRAR PROGRAMA PERSONALIZADO
    //=====================================
    const programa = document.getElementById("programa");
    const otroProgramaDiv = document.getElementById("otroProgramaDiv");
    const otroPrograma = document.getElementById("otroPrograma");

    if (programa && otroProgramaDiv && otroPrograma) {
        programa.addEventListener("change", function () {
            if (this.value === "Otro") {
                otroProgramaDiv.style.display = "block";
                otroPrograma.required = true;
            } else {
                otroProgramaDiv.style.display = "none";
                otroPrograma.required = false;
                otroPrograma.value = "";
                otroPrograma.style.borderColor = "";
            }
        });
    }

    //==============================================
    // VALIDACIÓN FINAL DEL FORMULARIO
    //==============================================
    const formulario = document.getElementById("formRegistro");

    if (formulario) {
        formulario.addEventListener("submit", function (e) {
            e.preventDefault();

            const valNombre = document.getElementById("nombre")?.value.trim() || "";
            const valApellido = document.getElementById("apellido")?.value.trim() || "";
            const valDocumento = document.getElementById("documento")?.value.trim() || "";
            const valCorreo = document.getElementById("correo")?.value.trim() || "";
            const valPrograma = document.getElementById("programa")?.value.trim() || "";
            const valOtroPrograma = document.getElementById("otroPrograma")?.value.trim() || "";
            const valUsuario = document.getElementById("usuario")?.value.trim() || "";
            const valPassword = document.getElementById("password")?.value || "";
            const valConfirmar = document.getElementById("confirmar")?.value || "";

            // 1. Campos vacíos
            if (!valNombre || !valApellido || !valDocumento || !valCorreo || !valPrograma || !valUsuario || !valPassword || !valConfirmar) {
                Swal.fire({
                    icon: "warning",
                    title: "Campos incompletos",
                    text: "Todos los campos principales son obligatorios."
                });
                return;
            }

            // Validar campo "Otro programa" si seleccionó la opción "Otro"
            if (valPrograma === "Otro" && !valOtroPrograma) {
                Swal.fire({
                    icon: "warning",
                    title: "Especificar programa",
                    text: "Por favor, ingresa el nombre de tu programa de formación."
                });
                return;
            }

            // 2. Contraseñas iguales
            if (valPassword !== valConfirmar) {
                Swal.fire({
                    icon: "error",
                    title: "Contraseñas diferentes",
                    text: "Las contraseñas ingresadas no coinciden."
                });
                return;
            }

            // 3. Requisitos de contraseña segura
            const regexPassword = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;
            if (!regexPassword.test(valPassword)) {
                Swal.fire({
                    icon: "warning",
                    title: "Contraseña poco segura",
                    html: `
                        La contraseña debe cumplir con los siguientes requisitos:<br><br>
                        ✔ Mínimo <b>8 caracteres</b><br>
                        ✔ Al menos <b>1 letra mayúscula</b><br>
                        ✔ Al menos <b>1 letra minúscula</b><br>
                        ✔ Al menos <b>1 número</b>
                    `
                });
                return;
            }

            // 4. Confirmación e intercepción antes de enviar al backend
            Swal.fire({
                title: "¿Registrar usuario?",
                text: "Se creará una nueva cuenta en la plataforma.",
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Sí, registrar",
                cancelButtonText: "Cancelar"
            }).then((result) => {
                if (result.isConfirmed) {
                    formulario.submit();
                }
            });
        });
    }
});