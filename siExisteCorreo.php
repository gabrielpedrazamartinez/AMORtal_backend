<?php
// CORS y tipo de contenido
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require 'conexión.php';

$conexion = getDatabaseConnection();

if ($conexion->connect_error) {
    echo json_encode(['exists' => false, 'message' => 'Error de conexión a base de datos']);
    exit;
}

$email = $_GET['email'] ?? '';

if (!$email) {
    echo json_encode(['exists' => false, 'message' => 'No se proporcionó correo']);
    exit;
}

// Preparar la consulta para verificar si el email existe
$stmt = $conexion->prepare("SELECT 1 FROM usuario WHERE correo = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(['exists' => true]);
} else {
    echo json_encode(['exists' => false]);
}

$stmt->close();
$conexion->close();