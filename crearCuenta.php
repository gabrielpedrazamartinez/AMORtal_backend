<?php
// CORS y tipo de contenido
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

require 'conexión.php';

// Leer datos del body
$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

// Validación básica
if (!$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'Correo y contraseña son requeridos.']);
    exit;
}

$conexion = getDatabaseConnection();
$emailEsc = mysqli_real_escape_string($conexion, $email);

// Verificar duplicado
$query = "SELECT id_usuario FROM usuario WHERE correo = '$emailEsc'";
if (mysqli_num_rows(mysqli_query($conexion, $query)) > 0) {
    echo json_encode(['success' => false, 'message' => 'El correo ya está registrado.']);
    exit;
}

// Insertar usuario
$hash = password_hash($password, PASSWORD_DEFAULT);
$sql = "INSERT INTO usuario (correo, contrasena, estado) VALUES ('$emailEsc', '$hash', 'por terminar')";

if (mysqli_query($conexion, $sql)) {
    echo json_encode(['success' => true, 'message' => 'Cuenta creada exitosamente.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al crear la cuenta: ' . mysqli_error($conexion)]);
}

mysqli_close($conexion);