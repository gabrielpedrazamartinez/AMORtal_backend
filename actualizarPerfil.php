<?php
// CORS y tipo de contenido
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require 'conexión.php';

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar parámetros
if (!isset($_GET['id_usuario'], $_GET['biografia'], $_GET['intereses'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Faltan parámetros']);
    exit;
}

// Recoger datos
$id_usuario = intval($_GET['id_usuario']);
$biografia = urldecode($_GET['biografia']);
$intereses = urldecode($_GET['intereses']);
$intereses_array = array_map('trim', explode(',', $intereses));

$conexion = getDatabaseConnection();

if ($conexion->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión: ' . $conexion->connect_error]);
    exit;
}

// Actualizar biografía
$sql = "UPDATE usuario SET biografia = ? WHERE id_usuario = ?";
$stmt = $conexion->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta']);
    exit;
}
$stmt->bind_param("si", $biografia, $id_usuario);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al ejecutar la actualización de biografía']);
    exit;
}
$stmt->close();

// Actualizar intereses
mysqli_query($conexion, "DELETE FROM intereses WHERE id_usuario = $id_usuario");
$stmt = mysqli_prepare($conexion, "INSERT INTO intereses (id_usuario, interes) VALUES (?, ?)");
foreach ($intereses_array as $int) {
    $int = trim($int);
    if ($int !== '') {
        mysqli_stmt_bind_param($stmt, "is", $id_usuario, $int);
        mysqli_stmt_execute($stmt);
    }
}

mysqli_close($conexion);

// Enviar respuesta JSON
echo json_encode([
    'success' => true,
    'message' => 'Perfil actualizado correctamente',
    'datos_recibidos' => [
        'id_usuario' => $id_usuario,
        'biografia' => $biografia,
        'intereses' => $intereses_array
    ]
]);