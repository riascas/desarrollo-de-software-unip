document.addEventListener('DOMContentLoaded', function() {
    const tipoUsuario = document.getElementById('tipoUsuario');
    const formAlumno = document.getElementById('formAlumno');
    const formEmpresa = document.getElementById('formEmpresa');
    const successAlert = document.getElementById('successAlert');
    const errorAlert = document.getElementById('errorAlert');
    // Escuchar el cambio en el tipo de usuario
    tipoUsuario.addEventListener('change', function() {
        const usuarioSeleccionado = tipoUsuario.value;

        console.log('Usuario seleccionado:', usuarioSeleccionado);
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
        
        let alumnoData = {
            typeUser: tipoUsuario.value,
            email: document.getElementById('email').value,
            password: document.getElementById('clave').value,
            nombreUsuario: document.getElementById('nombreUsuario').value,
            nombre: document.getElementById('nombre').value,
            apellido: document.getElementById('apellido').value,
        };

        if (!alumnoData.email || !alumnoData.password || !alumnoData.nombre || !alumnoData.apellido || !alumnoData.nombreUsuario) {
            mostrarMensaje({ success: false, message: "Por favor, complete todos los campos." });
            return;
        }

        console.log(alumnoData);
        try {
            const data = await registrarse(alumnoData);
            mostrarMensaje(data);
        } catch (error) {
            console.error(error);
            mostrarMensaje({ success: false, message: 'Error en el servidor: ' + error.message });
        }
    });

    formEmpresa.addEventListener('submit', async function(event) {
        event.preventDefault();
        
        const empresaData = {
            typeUser: tipoUsuario.value,
            nombreUsuario: document.getElementById('nombreUsuarioEmpresa').value,
            email: document.getElementById('emailEmpresa').value,
            password: document.getElementById('claveEmpresa').value,
            RazonSocial: document.getElementById('razonSocial').value,
        };

        if (!empresaData.email || !empresaData.password || !empresaData.nombreUsuario || !empresaData.RazonSocial) {
            mostrarMensaje({ success: false, message: "Por favor, complete todos los campos." });
            return;
        }

        console.log(empresaData);
        try {
            const data = await registrarse(empresaData);
            mostrarMensaje(data);
        } catch (error) {
            console.error(error);
            mostrarMensaje({ success: false, message: 'Error en el servidor: ' + error.message });
        }
    });

    async function registrarse(userData) {
        try {
            const response = await fetch(`http://localhost:8000/controllers/AdministradorController.php?endpoint=register`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(userData)
            });

            if (!response.ok) {
                throw new Error('No se pudo registrar el usuario');
            }

            return await response.json(); 
        } catch (error) {
            return { success: false, message: 'Error en el servidor: ' + error.message };
        }
    }

    // Función para mostrar los mensajes de error o éxito
    function mostrarMensaje(data) {
        if (errorAlert) errorAlert.classList.add("d-none");
        if (successAlert) successAlert.classList.add("d-none");

        if (data.success) {
            if (successAlert) {
                successAlert.textContent = data.message || "Registro exitoso";
                successAlert.classList.remove("d-none");
            }
        } else {
            if (errorAlert) {
                errorAlert.textContent = data.message || "Error en el registro";
                errorAlert.classList.remove("d-none");
            }
        }
    }
});