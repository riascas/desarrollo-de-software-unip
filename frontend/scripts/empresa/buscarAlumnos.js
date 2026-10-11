document.addEventListener('DOMContentLoaded', function () {
    const buscarButton = document.getElementById('buscarAlumnos');
    const buscarInput = document.getElementById('buscarInput');
    
    buscarButton.addEventListener('click', function(event) {
        event.preventDefault();
        
        const query = buscarInput.value.trim();
        let url = '../../controllers/EmpresaController.php?buscarAlumnos=';

        if (query !== '') {
            url += encodeURIComponent(query);
        }
        fetch(url, {
            method: 'GET', 
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            const resultadosContainer = document.getElementById('resultadosBusqueda');
            resultadosContainer.innerHTML = '';
            if (data.body && data.body.length > 0) {
                data.body.forEach(alumno => {
                    const alumnoDiv = document.createElement('div');
                    alumnoDiv.classList.add('col-12', 'list-group-item', 'list-group-item-action');

                    alumnoDiv.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center p-3 rounded w-100">
                            <div class="d-flex align-items-center w-100">
                                <img src="../img/${alumno.fotoPerfil || '../img/perfil.jpg'}" alt="Foto de perfil de ${alumno.nombre} ${alumno.apellido}" class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover; margin-right: 10px;">
                                <div class="w-100">
										<span class="fw-bold w-100">${alumno.nombre} ${alumno.apellido}</span>
                                        <span class="w-100 d-block">Carrera: ${alumno.carrera.nombreCarrera}</span>
										${alumno.descripcion ? `<span>${alumno.descripcion}</span>` : ''}
								</div>
                            </div>
                            <a href="empresa-visualizar-alumno.php?id=${alumno.id}" class="btn btn-success">Perfil</a>
                        </div>
                    `;

                    resultadosContainer.appendChild(alumnoDiv);
                });
            } else {
                resultadosContainer.innerHTML = '<p>No se encontraron resultados.</p>';
            }
        })
        .catch(error => {
            console.error('Error en la solicitud Fetch:', error);
        });
    });
});
