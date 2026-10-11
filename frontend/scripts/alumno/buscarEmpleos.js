document.addEventListener('DOMContentLoaded', function () {
    const buscarButton = document.getElementById('buscarBoton');
    const buscarInput = document.getElementById('buscarInput');
    const resultadosContainer = document.getElementById('resultadosBusqueda');

    function toggleEmpleoDetails(event) {
        const empleoDetails = event.target.closest('li').querySelector('.empleo-details');
        if (empleoDetails) {
            empleoDetails.classList.toggle('d-none');
        }
    }
    resultadosContainer.addEventListener('click', async function (event) {
        if (event.target && event.target.classList.contains('aplicar-empleo')) {
            const button = event.target;
            const empleoId = button.getAttribute('data-empleo-id');

            event.preventDefault();
            const postulacionData = { empleoId: empleoId };

            fetch(`../../controllers/AlumnoController.php?aplicarEmpleo`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(postulacionData)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        button.disabled = true;
                        button.innerText = "Postulado";
                    } else {
                        alert('Error al postularte: ' + data.message);
                    }
                })
                .catch(error => console.error('Error en la solicitud Fetch:', error));
        }
    });

    buscarButton.addEventListener('click', function (event) {
        event.preventDefault();

        const query = buscarInput.value.trim();
        let url = '../../controllers/AlumnoController.php?buscarEmpleos=';

        if (query !== '') {
            url += encodeURIComponent(query);
        }

        fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                resultadosContainer.innerHTML = '';

                if (data.body && data.body.length > 0) {
                    data.body.forEach(empleo => {
                        const empleoLi = document.createElement('li');
                        empleoLi.classList.add('list-group-item', 'list-group-item-action', 'bg-white', 'border');

                        empleoLi.innerHTML = `
                        <div class="w-100 justify-content-between">
                            <button class="toggleButton btn border-0 w-100 d-flex flex-column text-start">
                                <h5 class="mb-1">
                                    <div class="titulo-empleo">${empleo.titulo}</div>
                                </h5>
                                <p class="mb-1">${empleo.descripcion}</p>
                                <small>${empleo.ubicacion}</small>
                                <div class="mt-4">${empleo.estadoEmpleo.estado}</div>
                            </button>
                        </div>
                        <div class="empleo-details d-none">
                            <div class="mt-4">
                                <strong>Habilidades Necesarias:</strong>
                                <ul>
                                    ${empleo.habilidades.map(habilidad => `<li>${habilidad.nombre}</li>`).join('')}
                                </ul>
                            </div>
                            <div class="mt-4">
                                <strong>Disponibilidad Horaria:</strong>
                                <ul>
                                    ${empleo.jornada.descripcion ? `<li>${empleo.jornada.descripcion}</li>` : '<li>No disponible</li>'}
                                </ul>
                            </div>
                            <div class="mt-4">
                                <strong>Modalidad:</strong>
                                <ul>
                                    ${empleo.modalidad.descripcion ? `<li>${empleo.modalidad.descripcion}</li>` : '<li>No disponible</li>'}
                                </ul>
                            </div>
                        </div>
                        <div class="mt-2">
                            <div class="d-flex align-items-center">
                                <div class="btn-group btn-group-sm" role="group" aria-label="Acciones de empleo">
                                    ${
                                        empleo.estadoEmpleo.id === 2 
                                        ? ''
                                        : `<button type="button" class="btn btn-success btn-sm aplicar-empleo" data-empleo-id="${empleo.id}" ${empleo.checkPostulado ? 'disabled' : ''}>
                                            ${empleo.checkPostulado ? 'Postulado' : 'Aplicar'}
                                        </button>`
                                    }
                                </div>
                            </div>
                        </div>
                    `;
                    const toggleButton = empleoLi.querySelector('.toggleButton');
                    toggleButton.addEventListener('click', toggleEmpleoDetails);
                    resultadosContainer.appendChild(empleoLi);
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
