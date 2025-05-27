<?php
// CORS y tipo de contenido
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require 'conexión.php';

$conexion = getDataBaseConnection();

if (!isset($_GET['id_emisor'], $_GET['id_receptor'], $_GET['tipo'], $_GET['fecha'])) {
    echo json_encode(['error' => 'Faltan parámetros']);
    exit;
}

$id_emisor   = intval($_GET['id_emisor']);
$id_receptor = intval($_GET['id_receptor']);
$tipo        = mysqli_real_escape_string($conexion, $_GET['tipo']);
$fecha       = mysqli_real_escape_string($conexion, $_GET['fecha']);

// Validar tipo de like
$tipos_validos = ['like', 'superlike', 'x'];
if (!in_array($tipo, $tipos_validos)) {
    echo json_encode(['error' => 'Tipo de like no válido']);
    exit;
}

// No permitir likes a uno mismo
if ($id_emisor === $id_receptor) {
    echo json_encode(['error' => 'No puedes darte like a ti mismo']);
    exit;
}

// Insertar el like
$sql_like = "INSERT INTO `like` (id_emisor, id_receptor, tipo, fecha)
             VALUES ($id_emisor, $id_receptor, '$tipo', '$fecha')";

if (mysqli_query($conexion, $sql_like)) {

    // Verificar si el receptor ya dio like al emisor (para crear un match)
    $sql_verificar = "SELECT * FROM `like`
                      WHERE id_emisor = $id_receptor AND id_receptor = $id_emisor
                      AND tipo IN ('like', 'superlike')";

    $resultado = mysqli_query($conexion, $sql_verificar);

    if (mysqli_num_rows($resultado) > 0) {
        // Ya hay un like previo del receptor al emisor -> crear match
        $sql_match = "INSERT INTO `match` (id_usuario_1, id_usuario_2, fecha_match, estado)
              VALUES ($id_emisor, $id_receptor, NOW(), 'activo')";

        if (mysqli_query($conexion, $sql_match)) {
            echo json_encode(['success' => true, 'message' => 'Like registrado y MATCH creado']);
        } else {
            echo json_encode(['success' => true, 'warning' => 'Like registrado pero no se pudo crear el match']);
        }
    } else {
        echo json_encode(['success' => true, 'message' => 'Like registrado sin match']);
    }
} else {
    echo json_encode(['error' => 'Error al registrar like: ' . mysqli_error($conexion)]);
}

mysqli_close($conexion);