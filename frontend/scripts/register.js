
document.addEventListener("DOMContentLoaded", function () {
    const tipoUsuario = document.getElementById('tipoUsuario');
    const formAlumno = document.getElementById('formAlumno');
    const formEmpresa = document.getElementById('formEmpresa');
    const errorAlert = document.getElementById("errorAlert");
    const successAlert = document.getElementById("successAlert");


    tipoUsuario.addEventListener('change', function() {
        const usuarioSeleccionado = tipoUsuario.value;
        formAlumno.classList.add('d-none');
        formEmpresa.classList.add('d-none');
        formEmpresa.classList.remove('was-validated');
        formAlumno.classList.remove('was-validated');
        
        if (usuarioSeleccionado == 2) {
            formAlumno.classList.remove('d-none');
        } else if (usuarioSeleccionado == 3) {
            formEmpresa.classList.remove('d-none');
        }
    });

    formAlumno.addEventListener('submit', async function(event) {
        event.preventDefault();
        if (formAlumno.checkValidity() === false) {
            formAlumno.classList.add("was-validated");
        } else {
            let alumnoData = {
                typeUser: tipoUsuario.value,
                email: document.getElementById('email').value,
                password: document.getElementById('password').value,
                repeatPassword: document.getElementById('repeatPassword').value,
                direccion: document.getElementById('direccion').value,
                telefono: document.getElementById('telefono').value,
                nombreUsuario: document.getElementById('nombreUsuario').value,
                nombre: document.getElementById('nombre').value,
                apellido: document.getElementById('apellido').value,
                dni: document.getElementById('dni').value,
            };
            const data = await registrarse(alumnoData);
            mostrarMensaje(data);
        }
    });

    formEmpresa.addEventListener('submit', async function(event) {
        event.preventDefault();
        if (formEmpresa.checkValidity() === false) {
            formEmpresa.classList.add("was-validated");
        } else {
            const empresaData = {
                typeUser: tipoUsuario.value,
                nombreUsuario: document.getElementById('nombreUsuarioEmpresa').value,
                nombreEmpresa: document.getElementById('nombreEmpresa').value,
                email: document.getElementById('emailEmpresa').value,
                password: document.getElementById('passwordEmpresa').value,
                repeatPassword: document.getElementById('repeatPasswordEmpresa').value,
                direccion: document.getElementById('direccionEmpresa').value,
                telefono: document.getElementById('telefonoEmpresa').value,
                RazonSocial: document.getElementById('RazonSocial').value,
                CUIT: document.getElementById('CUIT').value,
            };
            const data = await registrarse(empresaData);
            mostrarMensaje(data);
        }
    });

    async function registrarse(userData) {
        try {
            const response = await fetch('../../controllers/UsuarioController.php?endpoint=register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(userData)
            });
            return await response.json(); 
        } catch (error) {
            return { success: false, message: 'Error en el servidor: ' + error.message };
        }
    }

    function mostrarMensaje(data) {
        // Resetear mensajes anteriores
        errorAlert.classList.add("d-none");
        successAlert.classList.add("d-none");
        
        if (data.success) {
            successAlert.textContent = "Registro exitoso";
            successAlert.classList.remove("d-none");
        } else {
            errorAlert.textContent = data.message || "Error en el registro";
            errorAlert.classList.remove("d-none");
        }
    }
});

