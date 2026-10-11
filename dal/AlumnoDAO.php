<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Alumno.php';
require_once __DIR__ . '/../models/PlanEstudio.php';
require_once __DIR__ . '/../models/Carrera.php';
require_once __DIR__ . '/../models/Habilidad.php';
require_once __DIR__ . '/../models/Evento.php';
require_once __DIR__ . '/../models/PublicacionEmpleo.php';
require_once __DIR__ . '/../models/Modalidad.php';
require_once __DIR__ . '/../models/EstadoEmpleo.php';
require_once __DIR__ . '/../models/Jornada.php';
require_once __DIR__ . '/../models/EstadoPostulacion.php';
require_once __DIR__ . '/../models/Postulacion.php';
require_once __DIR__ . '/../models/Suscripcion.php';
require_once __DIR__ . '/../models/Notificaciones.php';

class AlumnoDAO
{

    private PDO $conn;

    public function __construct()
    {
        $this->conn = (new Database())->getConnection();
    }
    public function darBaja($postulacion_id) {
        try {
            $sql = "DELETE FROM postulaciones WHERE id = :postulacion_id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':postulacion_id', $postulacion_id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            echo "Error al dar de baja la postulacion: " . $e->getMessage();
            return false;
        }
    }
    public function checkPostulacion($idUsuario, $idPublicacion){
        $query = "SELECT * FROM postulaciones WHERE id_usuario = :idUsuario AND id_publicacionesempleos = :idPublicacion";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':idUsuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindParam(':idPublicacion', $idPublicacion, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            return true;
        } else {
            return false;
        }
    }
    public function editarPerfilAlumno($id, $email, $nombre, $apellido, $telefono, $direccion, $fotoPerfil, $deBaja, $habilidades): ?Alumno
    {
        // Actualizar información del usuario
        $updateUserQuery = "UPDATE usuario SET ";
        $updateUserFields = [];
        $paramsUser = [];

        if ($email !== null) {
            $updateUserFields[] = "mail = :email";
            $paramsUser[':email'] = $email;
        }
        
        if ($telefono !== null) {
            $updateUserFields[] = "telefono = :telefono";
            $paramsUser[':telefono'] = $telefono;
        }
        if ($direccion !== null) {
            $updateUserFields[] = "direccion = :direccion";
            $paramsUser[':direccion'] = $direccion;
        }
        if ($fotoPerfil !== null) {
            $updateUserFields[] = "foto_perfil = :fotoPerfil";
            $paramsUser[':fotoPerfil'] = $fotoPerfil;
        }
        if ($deBaja !== null) {
            $updateUserFields[] = "de_baja = :deBaja";
            $paramsUser[':deBaja'] = $deBaja;
        }

        if (count($updateUserFields) > 0) {
            $updateUserQuery .= implode(", ", $updateUserFields) . " WHERE id = :idUsuario";
            $paramsUser[':idUsuario'] = $id;

            $stmtUserUpdate = $this->conn->prepare($updateUserQuery);
            foreach ($paramsUser as $key => $value) {
                $stmtUserUpdate->bindValue($key, $value);
            }

            $stmtUserUpdate->execute();
        }

        $updateAlumnoQuery = "UPDATE alumno SET ";
        $updateAlumnoFields = [];
        $paramsAlumno = [];

        if ($nombre !== null) {
            $updateAlumnoFields[] = "nombre = :nombre";
            $paramsAlumno[':nombre'] = $nombre;
        }
        if ($apellido !== null) {
            $updateAlumnoFields[] = "apellido = :apellido";
            $paramsAlumno[':apellido'] = $apellido;
        }

        // if ($dni !== null) {
        //     $updateAlumnoFields[] = "DNI_Alumno = :dni";
        //     $paramsAlumno[':dni'] = $dni;
        // }

        if (count($updateAlumnoFields) > 0) {
            $updateAlumnoQuery .= implode(", ", $updateAlumnoFields) . " WHERE id_usuario = :idUsuario";
            $paramsAlumno[':idUsuario'] = $id;

            $stmtAlumnoUpdate = $this->conn->prepare($updateAlumnoQuery);
            foreach ($paramsAlumno as $key => $value) {
                $stmtAlumnoUpdate->bindValue($key, $value);
            }

            $stmtAlumnoUpdate->execute();
        }


        // $habilidades = json_decode($habilidades, true); // Aseguramos que es un array
        // if (!is_array($habilidades)) {
        //     $habilidades = [];  // En caso de que no sea un array, lo inicializamos como un array vacío
        // }

        // Verificar las habilidades actuales del usuario
        $queryHabilidadesActuales = "
            SELECT id_habilidad FROM habilidades_alumnos WHERE id_usuario = :idAlumno
        ";
        $stmtHabilidades = $this->conn->prepare($queryHabilidadesActuales);
        $stmtHabilidades->bindValue(':idAlumno', $id);
        $stmtHabilidades->execute();

        $habilidadesExistentes = $stmtHabilidades->fetchAll(PDO::FETCH_COLUMN);

        // Verificar si $habilidadesExistentes es un array
        if (!is_array($habilidadesExistentes)) {
            $habilidadesExistentes = [];
        }

        // Encontrar las habilidades que ya no están en la lista de habilidades
        $habilidadesAEliminar = array_diff($habilidadesExistentes, $habilidades);

        // Eliminar las habilidades que ya no están asociadas al usuario
        if (count($habilidadesAEliminar) > 0) {
            $queryEliminarHabilidades = "
        DELETE FROM habilidades_alumnos 
        WHERE id_usuario = :idAlumno AND id_habilidad IN (" . implode(",", array_map('intval', $habilidadesAEliminar)) . ")
    ";
            $stmtEliminar = $this->conn->prepare($queryEliminarHabilidades);
            $stmtEliminar->bindValue(':idAlumno', $id);
            $stmtEliminar->execute();
        }

        // Encontrar las habilidades que no están asociadas y agregar las nuevas
        $habilidadesANuevas = array_diff($habilidades, $habilidadesExistentes);

        // Insertar las nuevas habilidades
        if (count($habilidadesANuevas) > 0) {
            $queryAgregarHabilidades = "
        INSERT INTO habilidades_alumnos (id_usuario, id_habilidad) 
        VALUES (:idAlumno, :idHabilidad)
    ";
            $stmtAgregar = $this->conn->prepare($queryAgregarHabilidades);

            foreach ($habilidadesANuevas as $habilidadId) {
                $stmtAgregar->bindValue(':idAlumno', $id);
                $stmtAgregar->bindValue(':idHabilidad', $habilidadId);
                $stmtAgregar->execute();
            }
        }

        return $this->obtenerAlumno($id);
    }

    public function obtenerAlumno($id) {
        $query = "SELECT u.id AS usuario_id, u.mail, u.telefono, u.direccion, u.foto_perfil,
                         a.id AS alumno_id, a.nombre AS alumno_nombre, a.apellido, a.descripcion, a.id_usuario,
                         ca.id_carrera,
                         ma.id_materia AS materia_id, m.nombre AS materia_nombre
                  FROM alumno AS a
                  JOIN usuario AS u ON u.id = a.id_usuario
                  LEFT JOIN carreras_alumnos AS ca ON ca.id_usuario = u.id
                  LEFT JOIN materias_aprobadas AS ma ON ma.id_alumno = a.id
                  LEFT JOIN materias AS m ON m.id = ma.id_materia
                  WHERE u.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
            $alumno = new Alumno();
            $alumno->setId($row['alumno_id']);
            $alumno->setNombreAlumno($row['alumno_nombre']);
            $alumno->setApellidoAlumno($row['apellido']);
            $alumno->setEmail($row['mail']);
            $alumno->setTelefono($row['telefono']);
            $alumno->setUbicacion($row['direccion']);
            if ($row['descripcion']) {
                $alumno->setDescripcion($row['descripcion']);
            } else {
                $alumno->setDescripcion('');
            }
    
            if ($row['foto_perfil']) {
                $alumno->setFotoPerfil($row['foto_perfil']);
            } else {
                $alumno->setFotoPerfil('');
            }
            $habilidades = $this->obtenerHabilidadesDelAlumno($id);
            $alumno->setHabilidades($habilidades);

            $carrera = $this->obtenerCarreraPorId($row['id_carrera']);
            if ($carrera) {
                $alumno->setCarrera($carrera);
            }
            $materiasAprobadas = [];
            do {
                if ($row['materia_id']) {
                    $materiasAprobadas[] = $row['materia_nombre'];
                }
            } while ($row = $stmt->fetch(PDO::FETCH_ASSOC));
    
            $alumno->setMateriasAprobadas($materiasAprobadas);
    
            return $alumno;
        } else {
            return null;
        }
    }

    public function obtenerCarreraPorId($idCarrera) {
        $query = "SELECT * FROM carreras WHERE id = :id_carrera";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_carrera', $idCarrera, PDO::PARAM_INT);
        $stmt->execute();
    
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $carrera = new Carrera();
            $carrera->setId($row['id']);
            $carrera->setNombreCarrera($row['nombre_carrera']);
            
            return $carrera;
        }
        return null;
    }

    public function obtenerHabilidadesDelAlumno($idAlumno) {
        $query = "SELECT h.id, h.descripcion, ha.nivel_grado
                  FROM habilidades AS h
                  JOIN habilidades_alumnos AS ha ON h.id = ha.id_habilidad
                  WHERE ha.id_usuario = :id_usuario";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_usuario', $idAlumno, PDO::PARAM_INT);
        $stmt->execute();
        
        $habilidades = [];
        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $habilidad = new Habilidad();
                $habilidad->setId($row['id']);
                $habilidad->setNombreHabilidad($row['descripcion']);
                // $habilidad->setNivelHabilidad($row['nivel_grado']);

                $habilidades[] = $habilidad;
            }
        }
        
        return $habilidades;
    }

//     public function obtenerAlumnoPorId($id): ?Alumno
//     {
//         // Implementación de consulta para obtener el Alumno por su ID (FK_idUsuario)
//         $query = "
//     SELECT 
//         a.*, 
//         h.*, 
//         u.*, 
//         ha.*
//     FROM alumno a
//     INNER JOIN usuario u ON a.id_usuario = u.id
//     LEFT JOIN habilidades_alumnos ha ON a.id_usuario = ha.id_usuario
//     LEFT JOIN habilidades h ON ha.id_habilidad = h.id
//     WHERE a.id_usuario = :idUsuario;
// ";

//         $stmt = $this->conn->prepare($query);
//         $stmt->bindParam(':idUsuario', $id);
//         $stmt->execute();

//         if ($stmt->rowCount() > 0) {
//             $rowAlumno = $stmt->fetch(PDO::FETCH_ASSOC);

//             echo "<pre>";
//             print_r($rowAlumno);
//             echo "</pre>";
//             $alumno = new Alumno();
//             $alumno->setNombreAlumno($rowAlumno['nombre']);
//             $alumno->setApellidoAlumno($rowAlumno['apellido']);
//             $alumno->setUbicacion($rowAlumno['direccion']);
//             $alumno->setTelefono($rowAlumno['telefono']);
//             $alumno->setEmail($rowAlumno['mail']);
//             return $alumno;
//         }

//         return null;
//     }


    private function obtenerUsuarioPorId($id): ?Usuario 
    {
        $query = "SELECT * FROM usuario WHERE id = :idUsuario LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':idUsuario', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $rowUsuario = $stmt->fetch(PDO::FETCH_ASSOC);
            $usuario  = new Alumno();
            $usuario->setId($rowUsuario['id']);
            $usuario->setEmail($rowUsuario['mail']);
            return $usuario;
        }

        return null;
    }

    public function getHabilidades()
    {
        $query = "SELECT * FROM habilidades";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        $habilidades = [];

        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $habilidad = new Habilidad();

                $habilidad->setId($row['id']);
                $habilidad->setNombreHabilidad($row['descripcion']);

                $habilidades[] = $habilidad;
            }
        }

        return $habilidades;
    }


    

    public function getEventos() {
        $queryEventos = "SELECT * FROM eventos WHERE id_estadoeventos = 1;";
        $stmt = $this->conn->prepare($queryEventos);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $eventosArray = [];
            foreach($eventos as $evento){
                $id = $evento['id'];
                $descripcion = $evento['descripcion'];
                $nombre = $evento['nombre'];
                $fecha = $evento['fecha'];
                $tipo = $evento['tipo'];
                $creditos = $evento['creditos'] ? $evento['creditos'] : 0; 
                //$ubicacion = $evento['ubicacion']; falta en la bbdd
                //$modalidad = $evento['modalidad']; falta en la bbdd
                
                $eventoOBJ = new Evento();

                $eventoOBJ->setId($id);
                $eventoOBJ->setNombreEvento($nombre);
                $eventoOBJ->setFechaEvento($fecha);
                $eventoOBJ->setDescripcionEvento($descripcion);
                $eventoOBJ->setCreditos($creditos);
                $eventoOBJ->setTipoEvento($tipo);
                //$eventoOBJ = new Evento($id, $nombre, $fecha, $ubicacion, $modalidad, $descripcion, $creditos, $tipo); agregando $ubicacion, $modalidad que falta en la bbdd
                $eventosArray[] = $eventoOBJ->toArray();
            }
            if($eventosArray){
                return $eventosArray;
            }
        }
        return null;
    }

    public function getPostulaciones($id) {

        $queryPostulaciones = "SELECT * FROM postulaciones WHERE id_usuario = :id";
        $stmt = $this->conn->prepare($queryPostulaciones);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
    
        $postulacionesArray = [];
    
        if ($stmt->rowCount() > 0) {
            // todas las postulaciones
            $postulaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            foreach ($postulaciones as $postulacion) {
                $id = $postulacion['id'];
                $puestoId = $postulacion['id_publicacionesempleos'];
                $postulanteId = $postulacion['id_usuario'];
                $estadoId = $postulacion['id_estadopostulacion'];
    
                // detalles de la publicación de empleo
                $queryPuesto = "SELECT * FROM publicaciones_empleos WHERE id = :puestoId";
                $stmtPuesto = $this->conn->prepare($queryPuesto);
                $stmtPuesto->bindParam(':puestoId', $puestoId);
                $stmtPuesto->execute();
                $puesto = $stmtPuesto->fetch(PDO::FETCH_ASSOC);
    
                // habilidades asociadas al puesto
                $queryHabilidades = "SELECT HD.descripcion FROM habilidades_publicaciones HP 
                                     JOIN habilidades HD ON HP.id_habilidad = HD.id
                                     WHERE HP.id_publicacion = :puestoId";
                $stmtHabilidades = $this->conn->prepare($queryHabilidades);
                $stmtHabilidades->bindParam(':puestoId', $puestoId);
                $stmtHabilidades->execute();
                $habilidades = $stmtHabilidades->fetchAll(PDO::FETCH_COLUMN);
    
                // modalidad asociada al puesto
                 $queryModalidad = "SELECT descripcion FROM modalidades WHERE id = :modalidadId";
                 $stmtModalidad = $this->conn->prepare($queryModalidad);
                 $stmtModalidad->bindParam(':modalidadId', $puesto['id_modalidad']);
                 $stmtModalidad->execute();
                 $modalidad = $stmtModalidad->fetch(PDO::FETCH_ASSOC)['descripcion'];
    
                // jornada asociada al puesto
                $queryJornada = "SELECT nombre FROM jornadas WHERE id = :jornadaId";
                $stmtJornada = $this->conn->prepare($queryJornada);
                $stmtJornada->bindParam(':jornadaId', $puesto['id_jornada']);
                $stmtJornada->execute();
                $jornada = $stmtJornada->fetch(PDO::FETCH_ASSOC)['nombre'];
    
                // estado de la postulación
                $queryEstado = "SELECT * FROM estados_postulacion WHERE id = :estadoId";
                $stmtEstado = $this->conn->prepare($queryEstado);
                $stmtEstado->bindParam(':estadoId', $estadoId);
                $stmtEstado->execute();
                $estado = $stmtEstado->fetch(PDO::FETCH_ASSOC);
                $estadoPostulacion = new EstadoPostulacion();
                $estadoPostulacion->setEstado($estado['nombre']);
                $estadoPostulacion->setId($estado['id']);
                // detalles del postulante (usuario)
                $usuario = $this->obtenerUsuarioPorId($postulanteId);
    
                
                $postulacionOBJ = new Postulacion();

                $postulacionOBJ->setId($id); 
                $postulacionOBJ->setPuestoOfrecido($puesto['puesto_ofrecido']);
                $postulacionOBJ->setDescripcion($puesto['descripcion']);
                $postulacionOBJ->setUbicacion($puesto['ubicacion']);
                $postulacionOBJ->setHabilidades($habilidades);
                $postulacionOBJ->setModalidad($modalidad);
                $postulacionOBJ->setJornada($jornada);
                $postulacionOBJ->setEstadoPostulacion($estadoPostulacion);
                $postulacionOBJ->setPostulante($usuario);
    
                $postulacionesArray[] = $postulacionOBJ;
            }
        }
        return $postulacionesArray;
    }
    public function aplicarEmpleo($idUsuario, $id_publicacion) {
        date_default_timezone_set('America/Argentina/Buenos_Aires');
        $fecha = date('Y-m-d H:i:s');
        $id_estadopostulacion = '1';
    
        $checkQuery = "SELECT COUNT(*) FROM postulaciones WHERE id_usuario = :idAlumno AND id_publicacionesempleos = :idPublicacionesEmpleos";
        $stmtCheck = $this->conn->prepare($checkQuery);
        $stmtCheck->bindParam(':idAlumno', $idUsuario, PDO::PARAM_INT);
        $stmtCheck->bindParam(':idPublicacionesEmpleos', $id_publicacion, PDO::PARAM_INT);
    
        try {
            $stmtCheck->execute();
            $count = $stmtCheck->fetchColumn(); 
    
            if ($count > 0) {
                return ['success' => false, 'message' => 'Ya has postulado a este empleo.'];
            } else {
                $query = "INSERT INTO postulaciones (id_usuario, id_publicacionesempleos, fecha, id_estadopostulacion) 
                          VALUES (:idAlumno, :idPublicacionesEmpleos, :fecha, :idestadopostulacion)";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':idAlumno', $idUsuario, PDO::PARAM_INT);
                $stmt->bindParam(':idPublicacionesEmpleos', $id_publicacion, PDO::PARAM_INT);
                $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
                $stmt->bindParam(':idestadopostulacion', $id_estadopostulacion, PDO::PARAM_INT);
    
                $stmt->execute();
    
                return ['success' => true, 'message' => 'Postulación realizada con éxito.'];
            }
    
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error al realizar la postulación: ' . $e->getMessage()];
        }
    }
    public function getJornadaById($jornadaId) {
        $query = "SELECT * FROM jornadas WHERE id = :jornadaId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':jornadaId', $jornadaId); 
        $stmt->execute();
    
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $jornadaId = new Jornada($row['id'], $row['nombre']);
          //  $descripcion = $jornadaId->getDescripcionJornada();
            return $jornadaId;
        } else {
            return null; 
        }
    }

    public function getEstadoById($estadoId) {
        $query = "SELECT * FROM estados_publicacion WHERE id = :estadoId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':estadoId', $estadoId);
        $stmt->execute();
    
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $estadoId = new EstadoEmpleo($row['id'], $row['nombre']);
            // $estadoId = new EstadoEmpleo();
             $estadoId->setId($row['id']);
             $estadoId->setEstado($row['nombre']);
            // $detalle = $estadoId->getEstado();
            return $estadoId;
        } else {
            return null; 
        }
    }


    public function getModalidadById($modalidadId) {
        $query = "SELECT * FROM modalidades WHERE id = :modalidadId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':modalidadId', $modalidadId);
        $stmt->execute();
    
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            // $modalidad = new Modalidad($row['id'], $row['descripcion']);                        
            // $descripcion = $modalidad->getDescripcionModalidad();
            $modalidad = new Modalidad($row['id'], $row['descripcion']);
            return $modalidad;
        } else {
            return null; 
        }
    }

    public function obtenerEstadoEmpleo($id_estadopublicacion) {
        $query = "SELECT * FROM estados_publicacion WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id_estadopublicacion, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $estadoPublicacion = new EstadoEmpleo();
            $estadoPublicacion->setId($row['id']);
            $estadoPublicacion->setEstado($row['nombre']);
            
            return $estadoPublicacion;
        } else {
            return null;
        }
    }


    public function getEmpleos() {
        $queryEmpleos = "SELECT * FROM publicaciones_empleos WHERE id_estadopublicacion != 3";
        $stmt = $this->conn->prepare($queryEmpleos);
        $stmt->execute();
       
        if ($stmt->rowCount() > 0) {
            $empleos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $empleosArray = [];
            foreach($empleos as $empleo){
                $id = $empleo['id'];
                $descripcion = $empleo['descripcion'];
                $titulo = $empleo['puesto_ofrecido'];
                $fecha = $empleo['fecha'];
                $modalidadId = $empleo['id_modalidad'];
                $modalidad = $this->getModalidadById($modalidadId);
                $estadoId = $empleo['id_estadopublicacion'];
                $estadoEmpleo = $this->getEstadoById($estadoId);
                $jornadaId =  $empleo['id_jornada'];
                $jornada = $this->getJornadaById($jornadaId);
                $ubicacion = $empleo['ubicacion'];
                
                $queryHabilidades = "SELECT * FROM habilidades_publicaciones HP 
                                     JOIN habilidades HD ON HP.id_habilidad = HD.id
                                     WHERE HP.id_publicacion = :puestoId";
                $stmtHabilidades = $this->conn->prepare($queryHabilidades);
                $stmtHabilidades->bindParam(':puestoId', $id);
                $stmtHabilidades->execute();
                $habilidadesData = $stmtHabilidades->fetchAll(PDO::FETCH_ASSOC);
                
                $habilidades = [];
                foreach ($habilidadesData as $habilidadData) {
                    $habilidad = new Habilidad();
                    $habilidad->setId($habilidadData['id']);
                    $habilidad->setNombreHabilidad($habilidadData['descripcion']);
                    $habilidades[] = $habilidad;
                }
                
                $empleoOBJ = new PublicacionEmpleo();
                $empleoOBJ->setId($id);  
                $empleoOBJ->setTitulo($titulo);  
                $empleoOBJ->setModalidad($modalidad);
                $empleoOBJ->setDescripcion($descripcion);  
                $empleoOBJ->setEstadoEmpleo($estadoEmpleo);  
                $empleoOBJ->setJornada($jornada);  
                $empleoOBJ->setUbicacion($ubicacion); 
                $empleoOBJ->setHabilidades($habilidades);
    
                $empleosArray[] = $empleoOBJ;
            }
            
            if($empleosArray){
                return $empleosArray;
            }
        }
        return null;
    }
    public function getBusquedaEmpleo($idUsuario, $buscar) {
        try {
            if ($buscar === '') {
                $sql = "SELECT * FROM publicaciones_empleos WHERE id_estadopublicacion != 3"; 
            } else {
                $sql = "SELECT * FROM publicaciones_empleos
                        WHERE id_estadopublicacion != 3
                        AND (puesto_ofrecido LIKE :buscar
                             OR ubicacion LIKE :buscar
                             OR descripcion LIKE :buscar
                             OR id_modalidad IN (SELECT id FROM modalidades WHERE descripcion LIKE :buscar)
                             OR id_jornada IN (SELECT id FROM jornadas WHERE descripcion LIKE :buscar)
                             OR id IN (SELECT id_publicacion FROM habilidades_publicaciones HP
                                       JOIN habilidades HD ON HP.id_habilidad = HD.id
                                       WHERE HD.descripcion LIKE :buscar))";
            }
    
            $stmt = $this->conn->prepare($sql);
            
            if ($buscar !== '') {
                $stmt->bindValue(':buscar', '%' . $buscar . '%');
            }
    
            $stmt->execute();
            $empleos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            if (count($empleos) > 0) {
                $empleosArray = [];
                foreach($empleos as $empleo) {
                    $id = $empleo['id'];
                    $descripcion = $empleo['descripcion'];
                    $titulo = $empleo['puesto_ofrecido'];
                    $fecha = $empleo['fecha'];
                    $modalidadId = $empleo['id_modalidad'];
                    $modalidad = $this->getModalidadById($modalidadId);
                    $estadoId = $empleo['id_estadopublicacion'];
                    $estadoEmpleo = $this->getEstadoById($estadoId);
                    $jornadaId = $empleo['id_jornada'];
                    $jornada = $this->getJornadaById($jornadaId);
                    $ubicacion = $empleo['ubicacion']; 
    
                    $queryHabilidades = "SELECT * FROM habilidades_publicaciones HP 
                                         JOIN habilidades HD ON HP.id_habilidad = HD.id
                                         WHERE HP.id_publicacion = :puestoId";
                    $stmtHabilidades = $this->conn->prepare($queryHabilidades);
                    $stmtHabilidades->bindParam(':puestoId', $id);
                    $stmtHabilidades->execute();
                    $habilidadesData = $stmtHabilidades->fetchAll(PDO::FETCH_ASSOC);
    
                    $habilidades = [];
                    foreach ($habilidadesData as $habilidadData) {
                        $habilidad = new Habilidad();
                        $habilidad->setId($habilidadData['id']);
                        $habilidad->setNombreHabilidad($habilidadData['descripcion']);
                        $habilidades[] = $habilidad;
                    }
    
                    $empleoOBJ = new PublicacionEmpleo();
                    $empleoOBJ->setId($id);  
                    $empleoOBJ->setTitulo($titulo);  
                    $empleoOBJ->setModalidad($modalidad);
                    $empleoOBJ->setDescripcion($descripcion);  
                    $empleoOBJ->setEstadoEmpleo($estadoEmpleo);  
                    $empleoOBJ->setJornada($jornada);  
                    $empleoOBJ->setUbicacion($ubicacion); 
                    $empleoOBJ->setHabilidades($habilidades);
                    $empleoOBJ->setCheckPostulado($this->checkPostulacion($idUsuario, $id));
                    $empleosArray[] = $empleoOBJ->toArray();
                }
    
                return $empleosArray;
            }
    
            return null;
    
        } catch (Exception $e) {
            echo "Error en la búsqueda de empleos: " . $e->getMessage();
            return false;
        }
    }
    
    public function listarCarreras() {
        $queryCarreras = "SELECT * FROM carreras";
        $stmt = $this->conn->prepare($queryCarreras);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $carreras = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $carrerasArray = [];
            foreach($carreras as $carrera){
                $id = $carrera['id'];
                $nombreCarrera = $carrera['nombre_carrera'];
                $planEstudios = $this->obtenerPlanesEstudio($id);
                $carreraOBJ = new Carrera();
                $carreraOBJ->setId($id);
                $carreraOBJ->setNombreCarrera($nombreCarrera);
                $carreraOBJ->setPlanEstudios($planEstudios);
                $carrerasArray[] = $carreraOBJ;
            }
            if($carrerasArray){
                return $carrerasArray;
            }
        }
        return null;
    }

    public function obtenerPlanesEstudio($idCarrera) {
        $queryPlanes = "SELECT * FROM plan_estudio WHERE id_carrera = :idCarrera";
        $stmt = $this->conn->prepare($queryPlanes);
        $stmt->bindParam(':idCarrera', $idCarrera, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $planes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $planesArray = [];
            foreach($planes as $plan){
                $id = $plan['id'];
                $nombrePlan = $plan['nombre'];
                $planOBJ = new PlanEstudio($id, $nombrePlan);
                $planesArray[] = $planOBJ->toArray();
            }
            if($planesArray){
                return $planesArray;
            }
        }
        return null;
    }


    public function getSuscripciones($idUsuario) {
        
            $query = " SELECT e.* FROM eventos e INNER JOIN suscripciones s ON e.id = s.id_evento WHERE s.id_usuario = $idUsuario;";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $suscripciones = [];
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $suscripciones[] = $row;
                }
                return $suscripciones;
            } else {
                return []; 
            }
        }

        // Obtener notificaciones de un usuario específico
        public function obtenerNotificaciones($idUsuario)
        {
            $query = "SELECT * FROM notificaciones WHERE id_usuario = :id_usuario ORDER BY id DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
            $stmt->execute();
    
            $notificaciones = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $notificacion = new Notificacion();
                $notificacion->setId($row['id']);
                $notificacion->setIdUsuario($row['id_usuario']);
                $notificacion->setDescripcion($row['descripcion']);
                $notificaciones[] = $notificacion;
            }
    
            return $notificaciones;
        }
    
        
        public function agregarNotificacion($idUsuario, $descripcion)
        {
            $query = "INSERT INTO notificaciones (id_usuario, descripcion) VALUES (:id_usuario, :descripcion)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
            $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
            $stmt->execute();
        }


        public function eliminarSuscripcion($eventoId,$idUsuario) {
            try {
    
                $querySuscripcion = "DELETE FROM suscripciones WHERE id_evento = :evento_id AND id_usuario = :idUsuario";
                $stmtSuscripcion = $this->conn->prepare($querySuscripcion);
                $stmtSuscripcion->bindParam(':evento_id', $eventoId, PDO::PARAM_INT);
                $stmtSuscripcion->bindParam(':idUsuario', $idUsuario, PDO::PARAM_INT);
    
                if (!$stmtSuscripcion->execute()) {
                    return false;
                }
    
                return true; 

            } catch (PDOException $e) {
                // Manejo de errores
                echo "Error: " . $e->getMessage();
                return false;
            }
    
        }

        public function agregarSuscripcion($idUsuario, $eventoId) {
           
            $insertQuery = "INSERT INTO suscripciones (id_usuario, id_evento) VALUES (:idUsuario, :evento_id)";
            $insertStmt = $this->conn->prepare($insertQuery);
    
            $insertStmt->bindParam(':idUsuario', $idUsuario, PDO::PARAM_INT);
            $insertStmt->bindParam(':evento_id', $eventoId, PDO::PARAM_INT);
    
            if (!$insertStmt->execute()) {
                return false;
            }

            return true; 
        }


        public function getUsuarioAdmin() {
            $Query = "SELECT usuario.id FROM usuario
                        INNER JOIN roles_usuario ON usuario.id = roles_usuario.id_usuario
                        INNER JOIN rol ON roles_usuario.id_rol = rol.id
                        WHERE rol.id = 1";  // Suponiendo que el rol con id 1 es el administrador
            $Stmt = $this->conn->prepare($Query);
        
            if (!$Stmt->execute()) {
                return false;
            }
        
            // Obtener el ID del administrador
            $usuario = $Stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usuario) {
                return $usuario['id'];  // Devolver solo el ID del administrador
            } else {
                return false;  // Si no hay administrador, devolver false
            }
        }

        public function getEventoNombreById($eventoId) {
            $query = "SELECT nombre FROM eventos WHERE id = :eventoId";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':eventoId', $eventoId, PDO::PARAM_INT);
            $stmt->execute();
        
            // Si encontramos un evento, devolvemos el nombre
            if ($stmt->rowCount() > 0) {
                $evento = $stmt->fetch(PDO::FETCH_ASSOC);
                return $evento['nombre']; // Devolvemos solo el nombre del evento
            }
            return null; // Si no encontramos el evento
        }

        public function eliminarHabilidad($idAlumno, $idHabilidad)
        {
            $query = "DELETE FROM habilidades_alumnos WHERE id_usuario = :idAlumno AND id_habilidad = :idHabilidad";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':idAlumno', $idAlumno);
            $stmt->bindValue(':idHabilidad', $idHabilidad);
            $stmt->execute();
    
            if ($stmt->rowCount() > 0) {
                return ['success' => true];
            } else {
                return ['success' => false];
            }
        }

        public function agregarHabilidad($idAlumno, $idHabilidad, $nivelHabilidad = 0)
        {
            $queryExiste = "SELECT 1 FROM habilidades_alumnos
                            WHERE id_usuario = :idAlumno AND id_habilidad = :idHabilidad";
            $stmtExiste = $this->conn->prepare($queryExiste);
            $stmtExiste->bindValue(':idAlumno', $idAlumno, PDO::PARAM_INT);
            $stmtExiste->bindValue(':idHabilidad', $idHabilidad, PDO::PARAM_INT);
            $stmtExiste->execute();

            if ($stmtExiste->fetchColumn()) {
                return ['success' => true, 'message' => 'La habilidad ya estaba asociada.'];
            }

            $query = "INSERT INTO habilidades_alumnos (id_usuario, id_habilidad, nivel_grado)
                      VALUES (:idAlumno, :idHabilidad, :nivelHabilidad)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':idAlumno', $idAlumno, PDO::PARAM_INT);
            $stmt->bindValue(':idHabilidad', $idHabilidad, PDO::PARAM_INT);
            $stmt->bindValue(':nivelHabilidad', $nivelHabilidad, PDO::PARAM_INT);
            $stmt->execute();

            return ['success' => true, 'message' => 'Habilidad agregada correctamente.'];
        }



}