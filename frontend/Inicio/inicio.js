
document.addEventListener("DOMContentLoaded", function () {
    const formInicio = document.getElementById('form-inicio');
    const btnRegister = document.getElementById('btnRegister');
    
    // Iniciar Sesion
    formInicio.addEventListener('submit', async function(event) {
        // Evitar que el formulario se envíe
        event.preventDefault();
        // Obtener los valores de los inputs
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        console.log("ANTES")
        const data = await iniciarSesion(email, password);
        console.log("DESPUES", data)
        if(data.success){
            window.location.href = "../Alumno/Empleos-Alumno/empleos-alumno.html";
        }

        // Simular un proceso de autenticación (podrías hacer algo más complejo aquí)
        // if (email === "usuario@gmail.com" && password === "contraseña123") {
        //     // Si los datos son correctos, redirigir a una nueva página
        //     window.location.href = "dashboard.html";
        // } 
        // // Validar todos los campos
        // else if (formInicio.checkValidity() === false) {
        //     // Mostrar los mensajes de error de HTML5
        //     formInicio.classList.add("was-validated");
        //     // Resetea el Formulario
        //     formInicio.reset();
        // }
    });

    // Registrarse
    btnRegister.addEventListener('click' , function(event) {
        event.preventDefault();
        window.location.href = 'register.html';
    });
});


async function iniciarSesion(email, password) {
    try {
        const response = await fetch('../../controllers/UsuarioController.php?endpoint=login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email, password })
        });

        if (!response.ok) {
            throw new Error('Error en la respuesta de la red');
        }

        const data = await response.json(); 

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