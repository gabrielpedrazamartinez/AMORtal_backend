<?php
// CORS y tipo de contenido
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require 'conexión.php';

$conexion = getDatabaseConnection();

// Validar parámetro userId
if (!isset($_GET['userId'])) {
    echo json_encode(['error' => 'Falta el parámetro userId']);
    exit;
}

$userId = intval($_GET['userId']);

// Consulta para obtener el estado del usuario
$sql = "SELECT estado FROM usuario WHERE id_usuario = ?";
$stmt = $conexion->prepare($sql);

if (!$stmt) {
    echo json_encode(['error' => 'Error en la preparación de la consulta']);
    exit;
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Usuario no encontrado']);
} else {
    $row = $result->fetch_assoc();
    $estado = $row['estado'];
    $baneado = ($estado === 'baneado');
    echo json_encode(['baneado' => $baneado]);
}

$stmt->close();
$conexion->close();