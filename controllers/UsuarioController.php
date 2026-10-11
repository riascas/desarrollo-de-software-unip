<?php
require_once __DIR__ . '/../dal/UsuarioDAO.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Empresa.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
$usuarioController = new UsuarioController();

$endpoint = htmlspecialchars($_GET['endpoint'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $endpoint === "register") {
    $resultado = $usuarioController->register();
    return $resultado;
    exit;
}
class UsuarioController {
    private UsuarioDAO $usuarioDao;

    public function __construct() {
        $this->usuarioDao = new UsuarioDAO();
        
    }


    public function verifyEmail() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = htmlspecialchars($_POST['mail'] ?? '');

            if (empty($email)) {
                return[
                    'success' => false,
                    'message' => 'Faltan datos obligatorios.'
                ];
            }

            $usuarioDAO = new UsuarioDAO();
            $usuario = $usuarioDAO->buscarPorEmail($email);
    
            header('Content-Type: application/json'); // Asegura que el contenido sea JSON
    
            // Agregar debug para ver el contenido de la variable usuario
            error_log("Resultado de la búsqueda de email: " . print_r($usuario, true));
    
            if ($usuario) {
                echo json_encode(['success' => true, 'message' => 'El correo está registrado.']); 
                echo json_encode(['success' => false, 'message' => 'El correo no está registrado.']);
            }
        } else {
            http_response_code(405); // Método no permitido 
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
        }
    }

    public function resetPassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = htmlspecialchars($_POST['mail'] ?? '');
            $newPassword = htmlspecialchars($_POST['newPassword'] ?? '');

            if (empty($email) || empty($newPassword)) {
                return[
                    'success' => false,
                    'message' => 'Faltan datos obligatorios.'
                ];
            }

            $usuarioDAO = new UsuarioDAO();
            $usuario = $usuarioDAO->buscarPorEmail($email);

            if ($usuario) {
                $hashPassword = ($newPassword);
                $usuarioDAO->actualizarClave($usuario['id'], $hashPassword);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar la contraseña.']);
            }
        }
    }


    public function iniciarSesion() {
        $email = htmlspecialchars($_POST['email'] ?? '');
        $password = htmlspecialchars($_POST['password'] ?? '');

        if (empty($email) || empty($password)) {
            return[
                'success' => false,
                'message' => 'Faltan datos obligatorios.'
            ];
        }

        $usuario = $this->usuarioDao->iniciarSesion($email, $password);

        if ($usuario && isset($usuario['user_id'], $usuario['user_type'])) {
            return [
                'success' => true,
                'message' => 'Inicio de sesión exitoso',
                'usuario' => $usuario,
            ];
        } else {
            return [
                'status_code' => 401,
                'success' => false,
                'message' => $usuario['error'] ?? 'Credenciales incorrectas',
            ];
        }
    }

    public function register() {

        $input = json_decode(file_get_contents('php://input'), true);
        $typeUser = htmlspecialchars($input['typeUser'] ?? '');
        $email = htmlspecialchars($input['email'] ?? '');
        $password = htmlspecialchars($input['password'] ?? '');
        $repeatPassword = htmlspecialchars($input['repeatPassword'] ?? '');
        $direccion = htmlspecialchars($input['direccion'] ?? '');
        $telefono = htmlspecialchars($input['telefono'] ?? '');
        $nombreUsuario = htmlspecialchars($input['nombreUsuario'] ?? '');

        if (empty($typeUser) || empty($email) || empty($password) || empty($repeatPassword) || empty($direccion) || empty($telefono) || empty($nombreUsuario)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Faltan datos obligatorios.'
            ]);
            return;
        }
            
    
        if ($password !== $repeatPassword) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Las contraseñas no coinciden.'
            ]);
            return;
        }

        if ($typeUser == 2) {
            $nombre = htmlspecialchars($input['nombre'] ?? '');
            $apellido = htmlspecialchars($input['apellido'] ?? '');
            $dni = htmlspecialchars($input['dni'] ?? '');
    
            if (empty($nombre) || empty($apellido) || empty($dni)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Faltan datos obligatorios para el alumno.'
                ]);
                return;
            }

            $respose = $this->usuarioDao->registerAlumno($nombreUsuario, $password, $email, $telefono, $direccion, $nombre, $apellido, $dni);
    
        } else if ($typeUser == 3) {
            $RazonSocial = htmlspecialchars($input['RazonSocial'] ?? '');
            $CUIT = htmlspecialchars($input['CUIT'] ?? '');

            if (empty($RazonSocial) || empty($CUIT)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Faltan datos obligatorios para la empresa.'
                ]);
                return;
            }
    
            $respose = $this->usuarioDao->registerEmpresa($nombreUsuario, $password, $email, $telefono, $direccion, $RazonSocial, $CUIT);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Tipo de usuario no válido.'
            ]);
            return;
        }
        if ($respose['success']) {
            echo json_encode($respose);
            return;
        } else {
            http_response_code(500);
            echo json_encode($respose);
            return;
        }
        
    }

    public function obtenerEmpresa(){
        $idUsuario = $_SESSION['user']['user_id'];
        $empresa = $this->usuarioDao->obtenerEmpresa($idUsuario);
        if($empresa){
            return[
                "success" => true,
                "body" => $empresa
            ];
        }
        else {
            return[
                'success' => false,
                'message' => 'Error: No se encontro la empresa',
            ];
        }
    }  

}



$action = htmlspecialchars_decode($_GET['action'] ?? '');
$controller = new UsuarioController();

if ($action === 'verifyEmail') {
    $controller->verifyEmail();
} elseif ($action === 'resetPassword') {
    $controller->resetPassword();
}