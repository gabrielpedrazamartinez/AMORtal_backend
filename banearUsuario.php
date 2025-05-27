<?php
// CORS y tipo de contenido
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

// Validar parámetro
if (!isset($_GET['userId'])) {
    http_response_code(400);
    echo json_encode(["error" => "Falta el parámetro userId."]);
    exit;
}

// Formatear entrada
$userId = intval($_GET['userId']);

require 'conexión.php';

$conexion = getDatabaseConnection();

// Preparar consulta
$query = $conexion->prepare("UPDATE usuario SET estado = 'baneado' WHERE id_usuario = ?");
$query->bind_param("i", $userId);

if ($query->execute()) {
    echo json_encode(["success" => true, "mensaje" => "Usuario baneado con éxito."]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Error al banear usuario: " . $conexion->error]);
}

$query->close();
$conexion->close();