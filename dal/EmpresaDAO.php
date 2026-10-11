<?php
require_once 'Database.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Empresa.php';
require_once __DIR__ . '/../models/Modalidad.php';
require_once __DIR__ . '/../models/Jornada.php';
require_once __DIR__ . '/../models/Carrera.php';
require_once __DIR__ . '/../models/PlanEstudio.php';
require_once __DIR__ . '/../models/Materia.php';
require_once __DIR__ . '/../models/Habilidad.php';
require_once __DIR__ . '/../models/PublicacionEmpleo.php';
require_once __DIR__ . '/../models/Postulacion.php';
require_once __DIR__ . '/../models/EstadoPostulacion.php';
require_once __DIR__ . '/../models/EstadoEmpleo.php';
require_once __DIR__ . '/../models/Alumno.php';
require_once __DIR__ . '/../models/Notificaciones.php';



class EmpresaDAO {
    private PDO $conn;

    public function __construct() {
        $this->conn = (new Database())->getConnection();
    }
    public function borrarPublicacion($id_publicacion) {
        try {
            $this->conn->beginTransaction();
            $query = "DELETE FROM habilidades_publicaciones WHERE id_publicacion = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':id', $id_publicacion, PDO::PARAM_INT);
            $stmt->execute();

            $query = "DELETE FROM materias_requeridas WHERE id_publicacionesempleos = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':id', $id_publicacion, PDO::PARAM_INT);
            $stmt->execute();
    
            $query = "DELETE FROM postulaciones WHERE id_publicacionesempleos = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':id', $id_publicacion, PDO::PARAM_INT);
            $stmt->execute();
    
            $query = "DELETE FROM publicaciones_empleos WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':id', $id_publicacion, PDO::PARAM_INT);
            $stmt->execute();
    
            $this->conn->commit();
    
            return true;
    
        } catch (PDOException $e) {
            $this->conn->rollBack();
            echo $e->getMessage();
            return false;
        }
    }
    public function editarPerfilEmpresa($id, $email, $nombreEmpresa, $telefono, $descripcion, $sitio_web, $foto_perfil)
    {
        try {


            $this->conn->beginTransaction();
   
            $queryEmpresa = "UPDATE empresas SET ";
            if ($nombreEmpresa !== null && $nombreEmpresa !== '') {
                $updateEmpresaFields[] = "razon_social = :nombreEmpresa";
                $paramsEmpresa[':nombreEmpresa'] = $nombreEmpresa;
            }
            if ($email !== null && $email !== '') {
                $updateEmpresaFields[] = "mail_corporativo = :email";
                $paramsEmpresa[':email'] = $email;
            }
            if ($descripcion !== null) {
                $updateEmpresaFields[] = "descripcion = :descripcion";
                $paramsEmpresa[':descripcion'] = $descripcion;
            }
            if ($sitio_web !== null) {
                $updateEmpresaFields[] = "sitio_web = :sitio_web";
                $paramsEmpresa[':sitio_web'] = $sitio_web;
            }
            if (count($updateEmpresaFields) > 0) {
                $queryEmpresa .= implode(", ", $updateEmpresaFields) . " WHERE id_usuario = :idUsuario";
                $paramsEmpresa[':idUsuario'] = $id;
    
                $stmtEmpresaUpdate = $this->conn->prepare($queryEmpresa);
                foreach ($paramsEmpresa as $key => $value) {
                    $stmtEmpresaUpdate->bindValue($key, $value);
                }
    
                $stmtEmpresaUpdate->execute();
            }

            $updateUserQuery = "UPDATE usuario SET ";
            $updateUserFields = [];
            $paramsUser = [];
            if ($foto_perfil !== null) {
                $updateUserFields[] = "foto_perfil = :fotoPerfil";
                $paramsUser[':fotoPerfil'] = $foto_perfil;
            }
            if ($telefono !== null && $telefono !== '') {
                $updateUserFields[] = "telefono = :telefono";
                $paramsUser[':telefono'] = $telefono;
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
    
            $this->conn->commit();
    
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            echo "Error al editar perfil: " . $e->getMessage();
            return false;
        }
    }
    
    public function listarPostulaciones($idPublicacion) {
        $queryPostulacion = "SELECT * FROM postulaciones WHERE id_publicacionesempleos = :id";
        $stmt = $this->conn->prepare($queryPostulacion);
        $stmt->bindParam(':id', $idPublicacion);
        $stmt->execute();
        $postulaciones = [];
        if ($stmt->rowCount() > 0) {
            $postulacionesArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($postulacionesArray as $postulacion) {
                $fechaPostulacion = DateTime::createFromFormat('Y-m-d', $postulacion['fecha']);
                $estadoPostulacion = $this->obtenerEstadoPostulacion($postulacion['id_estadopostulacion']);
                $postulante = $this->obtenerPostulante($postulacion['id_usuario']);
                $postulacionObj = new Postulacion();

                $postulacionObj->setId($postulacion['id']);
                $postulacionObj->setFechaPostulacion($fechaPostulacion);
                $postulacionObj->setEstadoPostulacion($estadoPostulacion);
                $postulacionObj->setPostulante($postulante);

                $postulaciones[] = $postulacionObj;
            }
        }
        return $postulaciones;
    }

    public function cambiarEstadoPostulacion($postulacion_id, $nuevo_estado_id) {
        $sql = "UPDATE postulaciones SET id_estadopostulacion = :nuevo_estado_id WHERE id = :postulacion_id";
    
        $stmt = $this->conn->prepare($sql);
    
        $stmt->bindParam(':nuevo_estado_id', $nuevo_estado_id, PDO::PARAM_INT);
        $stmt->bindParam(':postulacion_id', $postulacion_id, PDO::PARAM_INT);
    
        if ($stmt->execute()) {
            return true;
        } else {
            return null;
        }
    }
    public function cambiarEstadoPublicacion($publicacion_id, $nuevo_estado_id) {
        $sql = "UPDATE publicaciones_empleos SET id_estadopublicacion = :nuevo_estado_id WHERE id = :publicacionId";
    
        $stmt = $this->conn->prepare($sql);
    
        $stmt->bindParam(':nuevo_estado_id', $nuevo_estado_id, PDO::PARAM_INT);
        $stmt->bindParam(':publicacionId', $publicacion_id, PDO::PARAM_INT);
    
        if ($stmt->execute()) {
            return true;
        } else {
            return null;
        }
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
                  WHERE a.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
 
            $alumno = new Alumno();
            $alumno->setId($row['alumno_id']);
            $alumno->setUsuarioId($row['usuario_id']);
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
    public function listarAlumnos() {
        $queryAlumnos = "SELECT alumno.nombre, 
                            alumno.apellido, 
                            alumno.descripcion, 
                            alumno.id, 
                            usuario.foto_perfil, 
                            c.nombre_carrera
                        FROM alumno
                        JOIN usuario ON alumno.id_usuario = usuario.id
                        LEFT JOIN carreras_alumnos ca ON ca.id_usuario = alumno.id_usuario
                        LEFT JOIN carreras c ON ca.id_carrera = c.id";
        
        $stmt = $this->conn->prepare($queryAlumnos);
        $stmt->execute();
        
        $alumnosArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($alumnosArray) > 0) {
            $alumnos = [];
            foreach ($alumnosArray as $alumno) {
                $alumnoOBJ = new Alumno();
                $alumnoOBJ->setId($alumno['id']);
                $alumnoOBJ->setNombreAlumno($alumno['nombre']);
                $alumnoOBJ->setApellidoAlumno($alumno['apellido']);
                $alumnoOBJ->setDescripcion($alumno['descripcion'] ? $alumno['descripcion'] : '');
                $alumnoOBJ->setFotoPerfil($alumno['foto_perfil'] ? $alumno['foto_perfil'] : '');

                $carrera = new Carrera();
                $carrera->setNombreCarrera($alumno['nombre_carrera'] ? $alumno['nombre_carrera'] : '');
                $alumnoOBJ->setCarrera($carrera); 
                $alumnos[] = $alumnoOBJ;
            }
            return $alumnos;
        } else {
            return null;
        }
    }
    public function buscarAlumnos($query) {
        try {
            if ($query == '') {
                $sql = "
                SELECT alumno.nombre AS nombre_alumno, 
                       alumno.apellido, 
                       alumno.descripcion, 
                       alumno.id, 
                       usuario.foto_perfil, 
                       c.nombre_carrera
                FROM alumno
                JOIN usuario ON alumno.id_usuario = usuario.id
                LEFT JOIN carreras_alumnos ca ON ca.id_usuario = alumno.id_usuario
                LEFT JOIN carreras c ON ca.id_carrera = c.id";
                $stmt = $this->conn->prepare($sql);
            } else {
                $sql = "
                SELECT alumno.nombre AS nombre_alumno, 
                       alumno.apellido, 
                       alumno.descripcion, 
                       alumno.id, 
                       usuario.foto_perfil, 
                       c.nombre_carrera
                FROM alumno
                JOIN usuario ON alumno.id_usuario = usuario.id
                LEFT JOIN carreras_alumnos ca ON ca.id_usuario = alumno.id_usuario
                LEFT JOIN carreras c ON ca.id_carrera = c.id
                WHERE (alumno.nombre LIKE :query_nombre OR 
                       alumno.apellido LIKE :query_apellido OR 
                       alumno.descripcion LIKE :query_descripcion OR 
                       c.nombre_carrera LIKE :query_carrera)";
                $stmt = $this->conn->prepare($sql);
                $stmt->bindValue(':query_nombre', '%' . $query . '%');
                $stmt->bindValue(':query_apellido', '%' . $query . '%');
                $stmt->bindValue(':query_descripcion', '%' . $query . '%');
                $stmt->bindValue(':query_carrera', '%' . $query . '%');
            }
        


    
            $stmt->execute();
    
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            $alumnos = [];
            foreach ($result as $row) {
                $alumno = new Alumno();
                $alumno->setId($row['id']);
                $alumno->setNombreAlumno($row['nombre_alumno']);
                $alumno->setDescripcion($row['descripcion'] ?? '');
                $alumno->setApellidoAlumno($row['apellido']);
                $alumno->setFotoPerfil($row['foto_perfil'] ?? '');
                $carrera = new Carrera();
                $carrera->setNombreCarrera($row['nombre_carrera'] ?? '');
                $alumno->setCarrera($carrera);
    
                $alumnos[] = $alumno->toArray();
            }
    
            return $alumnos;
    
        } catch (Exception $e) {
            echo "Error en la búsqueda: " . $e->getMessage();
            return false;
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
                $habilidad->setNivelHabilidad($row['nivel_grado']);

                $habilidades[] = $habilidad;
            }
        }
        
        return $habilidades;
    }
    
    public function obtenerPostulante($idUsuario) {
        $query = "SELECT id, nombre, apellido FROM alumno WHERE id_usuario = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $postulante = new Alumno();
            $postulante->setId($row['id']);
            $postulante->setNombreAlumno($row['nombre']);
            $postulante->setApellidoAlumno($row['apellido']);
            
            return $postulante;
        } else {
            return null;
        }
    }
    public function obtenerEstadoPostulacion($id_estadopublicacion) {
        $query = "SELECT * FROM estados_postulacion WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id_estadopublicacion, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $estadoPostulacion = new EstadoPostulacion();
            $estadoPostulacion->setId($row['id']);
            $estadoPostulacion->setEstado($row['nombre']);
            
            return $estadoPostulacion;
        } else {
            return null;
        }
    }/*
    public function obtenerPublicacion($idPublicacion) {
        $queryPublicacion = "SELECT * FROM publicaciones_empleos WHERE id = :id";
        $stmt = $this->conn->prepare($queryPublicacion);
        $stmt->bindParam(':id', $idPublicacion);
        $stmt->execute();
        if($stmt->rowCount() > 0){
            $publicacion = $stmt->fetch(PDO::FETCH_ASSOC);
            $postulaciones = $this->listarPostulaciones($idPublicacion);
            $publicacionObj = new PublicacionEmpleo();
            $publicacionObj->setId($publicacion['id']);
            $publicacionObj->setTitulo($publicacion['puesto_ofrecido']);
            $publicacionObj->setDescripcion($publicacion['descripcion']);
            $publicacionObj->setPostulacion($postulaciones);
            return $publicacionObj;
        }
        return null;
    }*/

    public function obtenerPublicacion($idPublicacion) {
        try {
            $queryPublicacion = "SELECT * FROM publicaciones_empleos WHERE id = :id";
            $stmt = $this->conn->prepare($queryPublicacion);
            $stmt->bindParam(':id', $idPublicacion);
            $stmt->execute();
        
            if ($stmt->rowCount() > 0) {
                $publicacion = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $postulaciones = $this->listarPostulaciones($idPublicacion);
        
                $publicacionObj = new PublicacionEmpleo();
                $publicacionObj->setId($publicacion['id']);
                $publicacionObj->setTitulo($publicacion['puesto_ofrecido']);
                $publicacionObj->setDescripcion($publicacion['descripcion']);
                //$publicacionObj->setFecha($publicacion['fecha']); 
                $publicacionObj->setUbicacion($publicacion['ubicacion']);
                $publicacionObj->setPostulacion($postulaciones);
        
                $queryHabilidades = "
                    SELECT h.* 
                    FROM habilidades h
                    JOIN habilidades_publicaciones hp ON h.id = hp.id_habilidad
                    JOIN publicaciones_empleos pe ON hp.id_publicacion = pe.id
                    WHERE pe.id = :puestoId
                ";
                $stmt = $this->conn->prepare($queryHabilidades);
                $stmt->bindParam(':puestoId', $idPublicacion);
                $stmt->execute();
        
                $habilidadesarray = [];
                if ($stmt->rowCount() > 0) {
                    $habilidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($habilidades as $habilidad) {
                        $habilidadObj = new Habilidad();
                        $habilidadObj->setId($habilidad['id']);
                        $habilidadObj->setNombreHabilidad($habilidad['descripcion']);
                        $habilidadesarray[] = $habilidadObj;
                    }
                }
                $jornada = $this->getJornadaById($publicacion['id_jornada']);
                $modalidad = $this->getModalidadById($publicacion['id_modalidad']);

                $publicacionObj->setJornada($jornada);
                $publicacionObj->setModalidad($modalidad);
                $publicacionObj->setHabilidades($habilidadesarray);


                return $publicacionObj;
            }
        
            return null; 
        } catch (Exception $e) {
            error_log("Error al obtener publicación: " . $e->getMessage());
            return null;
        }
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
    public function getJornadaById($jornadaId) {
        $query = "SELECT * FROM jornadas WHERE id = :jornadaId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':jornadaId', $jornadaId); 
        $stmt->execute();
    
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $jornada = new Jornada($row['id'], $row['nombre']);
            return $jornada;
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
            $modalidad = new Modalidad($row['id'], $row['descripcion']);
            return $modalidad;
        } else {
            return null; 
        }
    }
    public function publicarEmpleo($titulo, $modalidad, $ubicacion, $jornada, $descripcion, $habilidades, $materias, $idUsuario) {
        date_default_timezone_set('America/Argentina/Buenos_Aires');
        $date = date('Y-m-d H:i:s');
        $estadoPublicacion = 1;
        $queryEmpleo = "
            INSERT INTO publicaciones_empleos (puesto_ofrecido, descripcion, fecha, ubicacion, id_usuario, id_estadopublicacion, id_jornada, id_modalidad)
            VALUES (:titulo, :descripcion, :date, :ubicacion, :idUsuario, :estadoPublicacion, :jornada, :modalidad)
        ";
        $stmt = $this->conn->prepare($queryEmpleo);
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':ubicacion', $ubicacion);
        $stmt->bindParam(':idUsuario', $idUsuario);
        $stmt->bindParam(':estadoPublicacion', $estadoPublicacion);
        $stmt->bindParam(':jornada', $jornada);
        $stmt->bindParam(':modalidad', $modalidad);
        $stmt->execute();
        if($stmt->rowCount() > 0){
            $empleoId = $this->conn->lastInsertId();
            if($habilidades) {
                foreach($habilidades as $habilidad) {
                    $queryHabilidades = "INSERT INTO habilidades_publicaciones (id_habilidad, id_publicacion) VALUES (:habilidad, :empleoId)";
                    $stmt = $this->conn->prepare($queryHabilidades);
                    $stmt->bindParam(':habilidad', $habilidad);
                    $stmt->bindParam(':empleoId', $empleoId);
                    $stmt->execute();
                }
            }
            if($materias){
                foreach($materias as $materia) {
                    $queryMaterias = "INSERT INTO materias_requeridas (id_materia, id_publicacionesempleos) VALUES (:materia, :empleoId)";
                    $stmt = $this->conn->prepare($queryMaterias);
                    $stmt->bindParam(':materia', $materia);
                    $stmt->bindParam(':empleoId', $empleoId);
                    $stmt->execute();
                }
            }
            return $empleoId;
        } else {
            return null;
        }
    }
    public function editarEmpleo($idEmpleo, $titulo, $modalidad, $ubicacion, $jornada, $descripcion, $idUsuario) {

        date_default_timezone_set('America/Argentina/Buenos_Aires');
        $date = date('Y-m-d H:i:s');
        $estadoPublicacion = 1;
        
        $queryEmpleo = "
            UPDATE publicaciones_empleos 
            SET puesto_ofrecido = :titulo, descripcion = :descripcion, fecha = :date, ubicacion = :ubicacion, 
                id_usuario = :idUsuario, id_estadopublicacion = :estadoPublicacion, id_jornada = :jornada, id_modalidad = :modalidad
            WHERE id = :idEmpleo
        ";
        
        $stmt = $this->conn->prepare($queryEmpleo);
        $stmt->bindParam(':idEmpleo', $idEmpleo);
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':ubicacion', $ubicacion);
        $stmt->bindParam(':idUsuario', $idUsuario);
        $stmt->bindParam(':estadoPublicacion', $estadoPublicacion);
        $stmt->bindParam(':jornada', $jornada);
        $stmt->bindParam(':modalidad', $modalidad);
    
        if($stmt->execute()) {
                /*
            $queryEliminarHabilidades = "DELETE FROM habilidades_publicaciones WHERE id_publicacion = :empleoId";
            $stmt = $this->conn->prepare($queryEliminarHabilidades);
            $stmt->bindParam(':empleoId', $idEmpleo);
            $stmt->execute();
      
            if($habilidades) {
                foreach($habilidades as $habilidad) {
                    $queryHabilidades = "INSERT INTO habilidades_publicaciones (id_habilidad, id_publicacion) VALUES (:habilidad, :empleoId)";
                    $stmt = $this->conn->prepare($queryHabilidades);
                    $stmt->bindParam(':habilidad', $habilidad);
                    $stmt->bindParam(':empleoId', $idEmpleo);
                    $stmt->execute();
                }
            }
      
            $queryEliminarMaterias = "DELETE FROM materias_requeridas WHERE id_publicacionesempleos = :empleoId";
            $stmt = $this->conn->prepare($queryEliminarMaterias);
            $stmt->bindParam(':empleoId', $idEmpleo);
            $stmt->execute();
    
         
            if($materias) {
                foreach($materias as $materia) {
                    $queryMaterias = "INSERT INTO materias_requeridas (id_materia, id_publicacionesempleos) VALUES (:materia, :empleoId)";
                    $stmt = $this->conn->prepare($queryMaterias);
                    $stmt->bindParam(':materia', $materia);
                    $stmt->bindParam(':empleoId', $idEmpleo);
                    $stmt->execute();
                }
            }
            */
            return $idEmpleo;
        } else {
            return null;
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
    public function obtenerHabilidad($id) {
        $query = "SELECT * FROM habilidades WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $habilidad = new Habilidad();
            $habilidad->setId($row['id']);
            $habilidad->setNombreHabilidad($row['descripcion']);
            $habilidad = $habilidad->toArray();
            return $habilidad;
        }
        return null;
    }
    public function obtenerMaterias($idPlanEstudio) {
        $queryMaterias = "
            SELECT 
                m.id, 
                m.nombre, 
                m.detalle
            FROM 
                materias m
            JOIN 
                planes_materias pm ON m.id = pm.id_materia
            WHERE 
                pm.id_planestudio = :idPlanEstudio;
        ";
        $stmt = $this->conn->prepare($queryMaterias);
        $stmt->bindParam(':idPlanEstudio', $idPlanEstudio, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $materiasArray = [];
            foreach($materias as $materia){
                $id = $materia['id'];
                $nombreMateria = $materia['nombre'];
                $detalleMateria = $materia['detalle'];
                $materiaOBJ = new Materia($id, $nombreMateria, $detalleMateria);
                $materiasArray[] = $materiaOBJ->toArray();
            }
            if($materiasArray){
                return $materiasArray;
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
    public function listarPublicaciones($idUsuario) {
        $queryPublicaciones = "SELECT * FROM publicaciones_empleos WHERE id_usuario = :id";
        $stmt = $this->conn->prepare($queryPublicaciones);
        $stmt->bindParam(":id", $idUsuario);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $publicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $publicacionesArray = [];
            foreach($publicaciones as $publicacion){
                $publicacionOBJ = new PublicacionEmpleo();
                $estadoEmpleo = $this->obtenerEstadoEmpleo($publicacion['id_estadopublicacion']);
                $fechaPostulacion = DateTime::createFromFormat('Y-m-d', $publicacion['fecha']);
                $publicacionOBJ->setId($publicacion['id']);
                $publicacionOBJ->setTitulo($publicacion['puesto_ofrecido']);
                $publicacionOBJ->setDescripcion($publicacion['descripcion']);
                $publicacionOBJ->setFecha($fechaPostulacion);
                $publicacionOBJ->setUbicacion($publicacion['ubicacion']);
                $publicacionOBJ->setEstadoEmpleo($estadoEmpleo);
                $publicacionesArray[] = $publicacionOBJ;
            }
            if($publicacionesArray){
                return $publicacionesArray;
            }
        }
        return [];
    }
    public function listarJornadas() {
        $queryJornadas = "SELECT * FROM jornadas";
        $stmt = $this->conn->prepare($queryJornadas);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $jornadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $jornadasArray = [];
            foreach($jornadas as $jornada){
                $id = $jornada['id'];
                $descripcion = $jornada['nombre'];
                $jornadaOBJ = new Jornada($id, $descripcion);
                $jornadasArray[] = $jornadaOBJ;
            }
            if($jornadasArray){
                return $jornadasArray;
            }
        }
        return null;
    }
    public function listarModalidades() {
        $queryModalidades = "SELECT * FROM modalidades";
        $stmt = $this->conn->prepare($queryModalidades);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $modalidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $modalidadesArray = [];
            foreach($modalidades as $modalidad){
                $id = $modalidad['id'];
                $descripcion = $modalidad['descripcion'];
                $modalidadOBJ = new Modalidad($id, $descripcion);
                $modalidadesArray[] = $modalidadOBJ;
            }
            if($modalidadesArray){
                return $modalidadesArray;
            }
        }
        return null;
    }

    public function agregarNotificacion($idUsuario, $descripcion)
    {
        $query = "INSERT INTO notificaciones (id_usuario, descripcion) VALUES (:id_usuario, :descripcion)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
        $stmt->execute();
    }

    public function obtenerPublicacionPorPostulacion($postulacion_id) {
        $sql = "SELECT pe.puesto_ofrecido, ep.nombre
                FROM postulaciones p
                JOIN publicaciones_empleos pe ON p.id_publicacionesempleos = pe.id
                JOIN estados_postulacion ep ON p.id_estadopostulacion = ep.id
                WHERE p.id = :postulacion_id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':postulacion_id', $postulacion_id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            return null;
        }
    }
    
    public function obtenerAlumnosPorPostulacion($postulacion_id) {
        $sql = "SELECT u.id 
                FROM postulaciones p
                JOIN usuario u ON p.id_usuario = u.id
                WHERE p.id = :postulacion_id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':postulacion_id', $postulacion_id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return null;
        }
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

    public function obtenerUsuarioPorId($id) {
        $query = "SELECT * FROM usuario WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $usuario = new Usuario();
            $usuario->setId($row['id']);
            // Asignar otros valores al objeto Usuario si es necesario
            return $usuario;
        } else {
            return null;  // No se encontró el usuario
        }
    }
    

    public function reclutarAlumno($usuario_id, $postulacion_id, $estadoId) {
        date_default_timezone_set('America/Argentina/Buenos_Aires');
        $fecha = date('Y-m-d H:i:s');
        
    
        $checkQuery = "SELECT id_estadopostulacion  FROM postulaciones WHERE id_usuario = :idAlumno AND id_publicacionesempleos = :idPublicacionesEmpleos";
        $stmtCheck = $this->conn->prepare($checkQuery);
        $stmtCheck->bindParam(':idAlumno', $usuario_id, PDO::PARAM_INT);
        $stmtCheck->bindParam(':idPublicacionesEmpleos', $postulacion_id, PDO::PARAM_INT);
        
        try {
            $stmtCheck->execute();
            $result  = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
            if ($result) {
                
                if ($result['id_estadopostulacion'] == 3) {
                    return ['success' => false, 'message' => 'Este usuario ya se encuentra reclutado.'];
                } else {

                $estadoId = 3; 
                $queryUpdate = "UPDATE postulaciones SET id_estadopostulacion = :id_estadopostulacion WHERE id_usuario = :idAlumno AND id_publicacionesempleos = :idPublicacionesEmpleos";
                $stmtUpdate = $this->conn->prepare($queryUpdate);
                $stmtUpdate->bindParam(':id_estadopostulacion', $estadoId, PDO::PARAM_INT);
                $stmtUpdate->bindParam(':idAlumno', $usuario_id, PDO::PARAM_INT);
                $stmtUpdate->bindParam(':idPublicacionesEmpleos', $postulacion_id, PDO::PARAM_INT);
                $stmtUpdate->execute();
                
                return ['success' => true, 'message' => 'Estado de postulación cambiado a Reclutado.'];
                }
            } else {
                $estadoId = 3;                 
                $queryInsert = "INSERT INTO postulaciones (id_usuario, id_publicacionesempleos, fecha, id_estadopostulacion) 
                                VALUES (:idAlumno, :idPublicacionesEmpleos, :fecha, :id_estadopostulacion)";
                
                $stmtInsert = $this->conn->prepare($queryInsert);
                $stmtInsert->bindParam(':idAlumno', $usuario_id, PDO::PARAM_INT);
                $stmtInsert->bindParam(':idPublicacionesEmpleos', $postulacion_id, PDO::PARAM_INT);
                $stmtInsert->bindParam(':fecha', $fecha, PDO::PARAM_STR);
                $stmtInsert->bindParam(':id_estadopostulacion', $estadoId, PDO::PARAM_INT);
                $stmtInsert->execute();
                
                return ['success' => true, 'message' => 'Postulación creada con éxito como Reclutado.'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error al realizar la postulación: ' . $e->getMessage()];
        }
    }
    
    
    
}