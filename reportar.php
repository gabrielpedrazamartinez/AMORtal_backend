<?php
// CORS y tipo de contenido
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Preflight para OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

require 'conexión.php';

// Capturar y limpiar los datos recibidos desde GET
$id_reportante = isset($_GET['id_reportante']) ? (int)$_GET['id_reportante'] : null;
$id_reportado = isset($_GET['id_reportado']) ? (int)$_GET['id_reportado'] : null;
$mensaje = trim($_GET['mensaje'] ?? '');

if (!$id_reportante || !$id_reportado || !$mensaje) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios']);
    exit;
}

$conexion = getDatabaseConnection();

$sql = "INSERT INTO reporte (id_reportante, id_reportado, mensaje, estado) VALUES (?, ?, ?, 'pendiente')";
$stmt = mysqli_prepare($conexion, $sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Error en la preparación de la consulta']);
    exit;
}

mysqli_stmt_bind_param($stmt, "iis", $id_reportante, $id_reportado, $mensaje);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Reporte enviado correctamente']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al enviar reporte: ' . mysqli_error($conexion)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conexion);
