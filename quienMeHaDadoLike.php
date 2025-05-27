<?php
// CORS y tipo de contenido
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=UTF-8');

include 'conexión.php';

$conexion = getDatabaseConnection();

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Falta el parámetro id']);
    exit;
}

$id = intval($_GET['id']);
if ($id <= 0) {
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

$likesRecibidos = [];

$sql = "SELECT l.id_like, l.tipo, l.id_emisor, u.nombre
        FROM `like` l
        JOIN usuario u ON l.id_emisor = u.id_usuario
        WHERE l.id_receptor = $id";

$resultado = mysqli_query($conexion, $sql);

if (!$resultado) {
    echo json_encode(['error' => 'Error en la consulta: ' . mysqli_error($conexion)]);
    exit;
}

while ($row = mysqli_fetch_assoc($resultado)) {
    $idEmisor = intval($row['id_emisor']);
    $nombre = $row['nombre'];
    $tipo = $row['tipo'];
    $fotos = [];

    // Obtener fotos del emisor
    $resFotos = mysqli_query($conexion, "SELECT ruta_foto FROM fotos WHERE id_usuario = $idEmisor");
    if ($resFotos) {
        while ($foto = mysqli_fetch_assoc($resFotos)) {
            $fotos[] = $foto['ruta_foto'];
        }
    }

    $likesRecibidos[] = [
        'id_usuario' => $idEmisor,
        'nombre' => $nombre,
        'tipo' => $tipo,
        'fotos' => $fotos
    ];
}

echo json_encode($likesRecibidos);
mysqli_close($conexion);