<?php
// CORS y tipo de contenido
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

require 'conexión.php';

$conexion = getDatabaseConnection();

if ($conexion->connect_errno) {
    http_response_code(500);
    echo json_encode(["error" => "Error de conexión a la base de datos"]);
    exit();
}

if (!isset($_GET['userId']) || !is_numeric($_GET['userId'])) {
    http_response_code(400);
    echo json_encode(["error" => "Falta userId o no es válido"]);
    exit();
}

$userId = intval($_GET['userId']);

$query = "SELECT rol_usuario FROM usuario WHERE id_usuario = ? LIMIT 1";
$stmt = $conexion->prepare($query);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "Error en la preparación de la consulta"]);
    exit();
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($rol);

if ($stmt->fetch()) {
    // Definimos si es admin según el rol
    $isAdmin = ($rol === 'admin');
    echo json_encode(["isAdmin" => $isAdmin]);
} else {
    // Usuario no encontrado
    http_response_code(404);
    echo json_encode(["error" => "Usuario no encontrado"]);
}

$stmt->close();
$conexion->close();