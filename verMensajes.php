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
$matches = [];

$sql = "SELECT id_match,
               CASE 
                   WHEN id_usuario_1 = $id THEN id_usuario_2 
                   ELSE id_usuario_1 
               END AS otro_usuario 
        FROM `match` 
        WHERE id_usuario_1 = $id OR id_usuario_2 = $id";

$resultado = mysqli_query($conexion, $sql);
if (!$resultado) {
    echo json_encode(['error' => 'Error al obtener matches: ' . mysqli_error($conexion)]);
    exit;
}

while ($row = mysqli_fetch_assoc($resultado)) {
    $id_match = $row['id_match'];
    $otro_id = $row['otro_usuario'];

    // Obtener nombre del otro usuario
    $sqlNombre = "SELECT nombre FROM usuario WHERE id_usuario = $otro_id";
    $resNombre = mysqli_query($conexion, $sqlNombre);
    if (!$resNombre) {
        echo json_encode(['error' => 'Error al obtener nombre: ' . mysqli_error($conexion)]);
        exit;
    }

    $nombreRow = mysqli_fetch_assoc($resNombre);
    $nombre = $nombreRow ? $nombreRow['nombre'] : 'Desconocido';

    // Obtener fotos del otro usuario
    $fotos = [];
    $rf = mysqli_query($conexion, "SELECT ruta_foto FROM fotos WHERE id_usuario = $otro_id");
    if ($rf) {
        while ($f = mysqli_fetch_assoc($rf)) {
            $fotos[] = $f['ruta_foto'];
        }
    }

    // Obtener mensajes del match
    $sqlMensajes = "SELECT id_usuario_emisor, texto, fecha_mensaje 
                    FROM mensaje 
                    WHERE id_match = $id_match 
                    ORDER BY fecha_mensaje ASC";
    $resMensajes = mysqli_query($conexion, $sqlMensajes);
    if (!$resMensajes) {
        echo json_encode(['error' => 'Error al obtener mensajes: ' . mysqli_error($conexion)]);
        exit;
    }

    $mensajes = [];
    while ($msg = mysqli_fetch_assoc($resMensajes)) {
        $mensajes[] = $msg;
    }

    $matches[] = [
        'id_usuario' => $otro_id,
        'nombre' => $nombre,
        'fotos' => $fotos,
        'mensajes' => $mensajes
    ];
}

echo json_encode($matches);
mysqli_close($conexion);