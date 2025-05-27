<?php
// CORS y tipo de contenido
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require 'conexión.php';

// Validar parámetro
if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Falta el parámetro ID']);
    exit;
}

$id = intval($_GET['id']);
$conexion = getDatabaseConnection();

// Obtener datos del usuario
$sqlUsuario = "SELECT id_usuario, fecha_nacimiento, biografia FROM usuario WHERE id_usuario = $id";
$resultUsuario = mysqli_query($conexion, $sqlUsuario);

if (!$resultUsuario || mysqli_num_rows($resultUsuario) === 0) {
    echo json_encode(['error' => 'Usuario no encontrado']);
    exit;
}

$usuario = mysqli_fetch_assoc($resultUsuario);

// Obtener intereses
$intereses = [];
$sqlIntereses = "SELECT interes FROM intereses WHERE id_usuario = $id";
$resultIntereses = mysqli_query($conexion, $sqlIntereses);

if ($resultIntereses) {
    while ($fila = mysqli_fetch_assoc($resultIntereses)) {
        $intereses[] = $fila['interes'];
    }
}

// Obtener fotos
$fotos = [];
$sqlFotos = "SELECT ruta_foto FROM fotos WHERE id_usuario = $id";
$resultFotos = mysqli_query($conexion, $sqlFotos);

if ($resultFotos) {
    while ($fila = mysqli_fetch_assoc($resultFotos)) {
        $fotos[] = $fila['ruta_foto'];
    }
}

// Respuesta final
$usuario['intereses'] = $intereses;
$usuario['fotos'] = $fotos;

echo json_encode($usuario);