<?php
// CORS y tipo de contenido
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=UTF-8');

include 'conexión.php';

$conexion = getDatabaseConnection();
if (!$conexion) {
    echo json_encode(['error' => 'Error de conexión: ' . mysqli_connect_error()]);
    exit;
}

// Verificación de los parámetros
$required_params = ['id_match', 'id_usuario_emisor', 'texto', 'fecha_mensaje'];

foreach ($required_params as $param) {
    if (!isset($_GET[$param])) {
        echo json_encode(['error' => "Falta el parámetro $param"]);
        exit;
    }
}

// limpiar valores
$id_match = intval($_GET['id_match']);
$id_usuario_emisor = intval($_GET['id_usuario_emisor']);
$texto = mysqli_real_escape_string($conexion, $_GET['texto']);
$fecha_mensaje = mysqli_real_escape_string($conexion, $_GET['fecha_mensaje']);

// Preparar la consulta para evitar inyecciones SQL
$sql = "INSERT INTO mensaje (id_match, id_usuario_emisor, texto, fecha_mensaje)
        VALUES (?, ?, ?, ?)";

$stmt = mysqli_prepare($conexion, $sql);

if ($stmt === false) {
    echo json_encode(['error' => 'Error al preparar la consulta: ' . mysqli_error($conexion)]);
    exit;
}

// Enlazar los parámetros con los valores
mysqli_stmt_bind_param($stmt, 'iiss', $id_match, $id_usuario_emisor, $texto, $fecha_mensaje);

// Ejecutar la consulta
if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => 'Mensaje insertado correctamente']);
} else {
    echo json_encode(['error' => 'Error al insertar mensaje: ' . mysqli_error($conexion)]);
}

// Cerrar la conexión
mysqli_stmt_close($stmt);
mysqli_close($conexion);