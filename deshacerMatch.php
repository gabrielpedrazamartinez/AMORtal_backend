<?php
// CORS y tipo de contenido
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Solo GET permitido
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

require 'conexión.php';

// Recoger id_match de GET
$id_match = isset($_GET['id_match']) ? intval($_GET['id_match']) : 0;
if ($id_match <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de match inválido o faltante']);
    exit;
}

$conexion = getDatabaseConnection();

$sql = "DELETE FROM `match` WHERE id_match = ?";
$stmt = mysqli_prepare($conexion, $sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Error en la preparación de la consulta: ' . mysqli_error($conexion)]);
    exit;
}
mysqli_stmt_bind_param($stmt, "i", $id_match);

if (!mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => false, 'message' => 'Error al ejecutar la eliminación: ' . mysqli_stmt_error($stmt)]);
    exit;
}

$filas_afectadas = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);
mysqli_close($conexion);

if ($filas_afectadas > 0) {
    echo json_encode(['success' => true, 'message' => 'Match eliminado correctamente']);
} else {
    echo json_encode(['success' => false, 'message' => 'No se encontró ningún match con ese ID']);
}