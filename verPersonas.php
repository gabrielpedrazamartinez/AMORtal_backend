<?php
// CORS y tipo de contenido
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require 'conexión.php';

$conexion = getDatabaseConnection();

if (!isset($_GET['id'], $_GET['sexo'], $_GET['edadMin'], $_GET['edadMax'])) {
    echo json_encode(['error' => 'Faltan parámetros necesarios']);
    exit;
}

$id_usuario = intval($_GET['id']);
$sexo_raw   = strtolower($_GET['sexo']);
$edad_min   = intval($_GET['edadMin']);
$edad_max   = intval($_GET['edadMax']);

$sexo_cond = ($sexo_raw === 'ambos') ? '' : "AND genero = '" . mysqli_real_escape_string($conexion, $sexo_raw) . "'";

$sql = "SELECT 
    id_usuario, nombre, fecha_nacimiento, biografia
FROM usuario
WHERE id_usuario != $id_usuario
  $sexo_cond
  AND TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN $edad_min AND $edad_max
  AND id_usuario NOT IN (
    SELECT id_receptor FROM `like` WHERE id_emisor = $id_usuario
)";

$result = mysqli_query($conexion, $sql);
if (!$result) {
    echo json_encode(['error' => 'Error en la consulta: ' . mysqli_error($conexion)]);
    exit;
}

$personas = [];
while ($row = mysqli_fetch_assoc($result)) {
    $uid = $row['id_usuario'];
    // intereses
    $intereses = [];
    $ri = mysqli_query($conexion, "SELECT interes FROM intereses WHERE id_usuario = $uid");
    while ($f = mysqli_fetch_assoc($ri)) {
        $intereses[] = $f['interes'];
    }
    // fotos
    $fotos = [];
    $rf = mysqli_query($conexion, "SELECT ruta_foto FROM fotos WHERE id_usuario = $uid");
    while ($f = mysqli_fetch_assoc($rf)) {
        $fotos[] = $f['ruta_foto'];
    }
    $row['intereses'] = $intereses;
    $row['fotos']      = $fotos;
    $personas[]        = $row;
}

echo json_encode($personas);
mysqli_close($conexion);