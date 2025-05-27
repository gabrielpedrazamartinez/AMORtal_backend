<?php
// CORS y tipo de contenido
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=UTF-8');

include 'conexión.php';

$conexion = getDatabaseConnection();

$id1 = $_GET['id1'];
$id2 = $_GET['id2'];

$id1 = mysqli_real_escape_string($conexion, $id1);
$id2 = mysqli_real_escape_string($conexion, $id2);

$sql = "SELECT id_match FROM `match`
        WHERE (id_usuario_1 = $id1 AND id_usuario_2 = $id2) 
           OR (id_usuario_1 = $id2 AND id_usuario_2 = $id1) 
        LIMIT 1";

$result = mysqli_query($conexion, $sql);

if (!$result) {
    echo json_encode(['error' => 'Error en la consulta SQL: ' . mysqli_error($conexion)]);
    exit;
}

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode(['id_match' => $row['id_match']]);
} else {
    echo json_encode(['error' => 'No se encontró match']);
}

mysqli_close($conexion);