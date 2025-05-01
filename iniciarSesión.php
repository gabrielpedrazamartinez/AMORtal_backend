<?php
// Configuración de CORS y cabeceras
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Responder a preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verificación de método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

require 'conexión.php';

// Obtener datos de la solicitud
$data    = json_decode(file_get_contents('php://input'), true);
$correo  = trim($data['email'] ?? '');
$clave   = $data['password']   ?? '';

// Validación básica
if (empty($correo) || empty($clave)) {
    echo json_encode(['success' => false, 'message' => 'Correo y contraseña requeridos.']);
    exit;
}

// Conexión y consulta
$conexion = getDatabaseConnection();
$correoEsc = mysqli_real_escape_string($conexion, $correo);

$query = "SELECT id_usuario, contrasena, estado, nombre FROM usuario WHERE correo = '$correoEsc'";
$result = mysqli_query($conexion, $query);

if (!$result || mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Usuario no existe.']);
    mysqli_close($conexion);
    exit;
}

// Verificar contraseña
$usuario = mysqli_fetch_assoc($result);
if (!password_verify($clave, $usuario['contrasena'])) {
    echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta.']);
    mysqli_close($conexion);
    exit;
}

// Respuesta final
echo json_encode([
    'success' => true,
    'state'   => $usuario['estado'] === 'por terminar' ? 'por_terminar' : 'terminado',
    'userId'  => $usuario['id_usuario'],
    'nombre'  => $usuario['nombre'],
]);

mysqli_close($conexion);
?>