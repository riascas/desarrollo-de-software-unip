
// Esperar a que el DOM esté completamente cargado
document.addEventListener("DOMContentLoaded", function () {
    const tipoUsuario = document.getElementById('tipoUsuario');
    const formAlumno = document.getElementById('formAlumno');
    const formEmpresa = document.getElementById('formEmpresa');

    // Obtener los elementos del modal
    const modalHeader = document.getElementById('modalHeader');
    const modalBody = document.getElementById('modalBody');
    const modalButton = document.getElementById('modalButton');
    const confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));

    function mensaje (){
        // Si completó el formulario:
            // Cambiar modal a estilo de éxito
            modalHeader.classList.add('bg-success');
            modalButton.classList.add('btn-success');

            // Actualizar contenido del modal
            document.getElementById('confirmationModalLabel').textContent = "Solicitud enviada";
            modalBody.innerHTML = `Se ha enviado una petición de registro. Por favor esté atento su correo electrónico.`;

            // Mostrar el modal
            confirmationModal.show();

            // Redirigir después de cerrar el modal
            document.getElementById('confirmationModal').addEventListener('hidden.bs.modal', function () {
                window.location.href = "inicio.html";
            });
    };
    
    tipoUsuario.addEventListener('change', function() {
        const usuarioSeleccionado = tipoUsuario.value;

        // Ocultar ambos formularios al inicio
        formAlumno.classList.add('d-none');
        formEmpresa.classList.add('d-none');
        // Quita la validación al cambiar de usuario
        formEmpresa.classList.remove('was-validated');
        formAlumno.classList.remove('was-validated');

        // Mostrar el formulario correspondiente según la selección
        if (usuarioSeleccionado === '2') {
            formAlumno.classList.remove('d-none');
        } else if (usuarioSeleccionado === '3') {
            formEmpresa.classList.remove('d-none');
        }
    });

    // Validación de los formularios (Opcional)
    formAlumno.addEventListener('submit', async function(event) {
        event.preventDefault();

        if (formAlumno.checkValidity() === false) {
            // Mostrar los mensajes de error de HTML5
            formAlumno.classList.add("was-validated");
            // Resetea el Formulario
            //formAlumno.reset();
        }else{
            let alumnoData = {};
            alumnoData.typeUser = tipoUsuario.value;
            alumnoData.email = document.getElementById('email').value;
            alumnoData.password = document.getElementById('password').value;
            alumnoData.repeatPassword = document.getElementById('repeatPassword').value;
            alumnoData.direccion = document.getElementById('direccion').value;
            alumnoData.telefono = document.getElementById('telefono').value;
            alumnoData.nombreUsuario = document.getElementById('nombreUsuario').value;
            alumnoData.nombre = document.getElementById('nombre').value;
            alumnoData.apellido = document.getElementById('apellido').value;
            alumnoData.dni = document.getElementById('dni').value;
            const data = await registrarse(alumnoData);
            if(data.success){
                mensaje();
            }
        } 
    });

    formEmpresa.addEventListener('submit', async function(event) {
        event.preventDefault();

        if (formEmpresa.checkValidity() === false) {
            // Mostrar los mensajes de error de HTML5
            formEmpresa.classList.add("was-validated");
            // Resetea el Formulario
            formEmpresa.reset();
        }else{
            const empresaData = {}
            empresaData.typeUser = tipoUsuario.value;
            empresaData.nombreUsuario = document.getElementById('nombreUsuarioEmpresa').value;
            empresaData.nombreEmpresa = document.getElementById('nombreEmpresa').value;
            empresaData.email = document.getElementById('emailEmpresa').value;
            empresaData.password = document.getElementById('passwordEmpresa').value;
            empresaData.repeatPassword = document.getElementById('repeatPasswordEmpresa').value;
            empresaData.direccion = document.getElementById('direccionEmpresa').value;
            empresaData.telefono = document.getElementById('telefonoEmpresa').value;
            empresaData.RazonSocial = document.getElementById('RazonSocial').value;
            empresaData.CUIT = document.getElementById('CUIT').value;
            const data = await registrarse(empresaData);
            if(data.success){
                mensaje();
            }
        } 
        
    });

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

        if (!response.ok) {
            throw new Error('Error en la respuesta de la red');
        }

        const data = await response.json(); 
        console.log(data)
        // Manejo de datos
        if (data.success) {
            console.log('Inicio de sesión exitoso:', data.alumno);
            return data;
        } else {
            console.log('Error al iniciar sesión:', data.message);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}