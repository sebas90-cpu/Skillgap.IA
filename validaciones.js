//=============================================
// MOSTRAR / OCULTAR CONTRASEÑA
//=============================================

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

const verConfirmar = document.getElementById("verConfirmar");
const confirmar = document.getElementById("confirmar");

verConfirmar.addEventListener("click", () => {

if (confirmar.type === "password") {

confirmar.type = "text";
verConfirmar.classList.remove("fa-eye");
verConfirmar.classList.add("fa-eye-slash");

} else {

confirmar.type = "password";
verConfirmar.classList.remove("fa-eye-slash");
verConfirmar.classList.add("fa-eye");

}

});
//=============================================
// VALIDACIONES EN TIEMPO REAL
//=============================================

const nombre = document.getElementById("nombre");
const apellido = document.getElementById("apellido");
const documento = document.getElementById("documento");
const correo = document.getElementById("correo");
const usuario = document.getElementById("usuario");

//====================
// Nombre
//====================

nombre.addEventListener("input", () => {

const regex = /^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/;

if (regex.test(nombre.value)) {

nombre.style.borderColor = "#22c55e";

} else {

nombre.style.borderColor = "#ef4444";

}

});

//====================
// Apellido
//====================

apellido.addEventListener("input", () => {

const regex = /^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/;

if (regex.test(apellido.value)) {

apellido.style.borderColor = "#22c55e";

} else {

apellido.style.borderColor = "#ef4444";

}

});

//====================
// Documento
//====================

documento.addEventListener("input", () => {

documento.value = documento.value.replace(/\D/g,'');

if(documento.value.length >= 6){

documento.style.borderColor="#22c55e";

}else{

documento.style.borderColor="#ef4444";

}

});

//====================
// Correo
//====================

correo.addEventListener("input",()=>{

    const regex=/^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if(regex.test(correo.value)){

        correo.style.borderColor="#22c55e";

    }else{

        correo.style.borderColor="#ef4444";

    }

});

//====================
// Usuario
//====================

usuario.addEventListener("input",()=>{

    usuario.value=usuario.value.replace(/\s/g,'');

    if(usuario.value.length>=4){

        usuario.style.borderColor="#22c55e";

    }else{

        usuario.style.borderColor="#ef4444";

    }

});
//==============================================
// VALIDACIÓN FINAL DEL FORMULARIO
//==============================================

const formulario = document.getElementById("formRegistro");

formulario.addEventListener("submit", function(e){

    e.preventDefault();

    const nombre = document.getElementById("nombre").value.trim();
    const apellido = document.getElementById("apellido").value.trim();
    const documento = document.getElementById("documento").value.trim();
    const correo = document.getElementById("correo").value.trim();
    const programa = document.getElementById("programa").value.trim();
    const usuario = document.getElementById("usuario").value.trim();
    const password = document.getElementById("password").value;
    const confirmar = document.getElementById("confirmar").value;

    // Campos vacíos
    if(
        nombre === "" ||
        apellido === "" ||
        documento === "" ||
        correo === "" ||
        programa === "" ||
        usuario === "" ||
        password === "" ||
        confirmar === ""
    ){

        Swal.fire({
            icon: "warning",
            title: "Campos incompletos",
            text: "Todos los campos son obligatorios."
        });

        return;
    }

    // Contraseñas iguales
    if(password !== confirmar){

        Swal.fire({
            icon: "error",
            title: "Contraseñas diferentes",
            text: "Las contraseñas no coinciden."
        });

        return;
    }

    // Contraseña segura
const regexPassword = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;

if(!regexPassword.test(password)){

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
    // Confirmación antes de registrar
    Swal.fire({
        title: "¿Registrar usuario?",
        text: "Se creará una nueva cuenta.",
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Sí, registrar",
        cancelButtonText: "Cancelar"
    }).then((result)=>{

        if(result.isConfirmed){

            formulario.submit();

        }

    });

});
//=====================================
// Mostrar programa personalizado
//=====================================

const programa = document.getElementById("programa");
const otroProgramaDiv = document.getElementById("otroProgramaDiv");
const otroPrograma = document.getElementById("otroPrograma");

programa.addEventListener("change", function(){

    if(this.value === "Otro"){

        otroProgramaDiv.classList.add("mostrar");
        otroPrograma.required = true;

    }else{

        otroProgramaDiv.classList.remove("mostrar");
        otroPrograma.required = false;
        otroPrograma.value = "";

    }

});