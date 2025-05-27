<?php
// CORS y tipo de contenido
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require 'conexión.php';

$conexion = getDatabaseConnection();

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a base de datos']);
    exit;
}

$id = $_POST['id'] ?? null;
$contrasena_actual = $_POST['contrasena_actual'] ?? null;
$nueva_contrasena = $_POST['nueva_contrasena'] ?? null;

if (!$id || !$contrasena_actual || !$nueva_contrasena) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos']);
    exit;
}

$stmt = $conexion->prepare('SELECT contrasena FROM usuario WHERE id_usuario = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($hash_contrasena);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    $stmt->close();
    $conexion->close();
    exit;
}
$stmt->close();

if (!password_verify($contrasena_actual, $hash_contrasena)) {
    echo json_encode(['success' => false, 'message' => 'La contraseña actual es incorrecta']);
    $conexion->close();
    exit;
}

if (strlen($nueva_contrasena) < 6) {
    echo json_encode(['success' => false, 'message' => 'La nueva contraseña debe tener al menos 6 caracteres']);
    $conexion->close();
    exit;
}

$nueva_contrasena_hashed = password_hash($nueva_contrasena, PASSWORD_DEFAULT);

$stmt = $conexion->prepare('UPDATE usuario SET contrasena = ? WHERE id_usuario = ?');
$stmt->bind_param('si', $nueva_contrasena_hashed, $id);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Contraseña cambiada exitosamente']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar la contraseña']);
}

$stmt->close();
$conexion->close();