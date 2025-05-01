<?php
// CORS y tipo de contenido
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

require 'conexión.php';

// Captura de datos
$email     = trim($_POST['email'] ?? '');
$nombre    = trim($_POST['nombre'] ?? '');
$orient    = trim($_POST['orientacion'] ?? '');
$fecha     = trim($_POST['fecha_nacimiento'] ?? '');
$genero    = trim($_POST['genero'] ?? '');
$bio       = trim($_POST['biografia'] ?? '');
$intereses = $_POST['intereses'] ?? [];
$fotos     = $_FILES['fotos'] ?? null;

if (!$email || !$nombre || !$orient || !$fecha || !$genero) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios']);
    exit;
}

$conexion     = getDatabaseConnection();
$emailEsc = mysqli_real_escape_string($conexion, $email);

// Actualizar usuario
$sql = "UPDATE usuario SET 
            rol_usuario = 'cliente',
            correo_verificado = 0,
            nombre = ?, 
            fecha_nacimiento = ?, 
            genero = ?, 
            biografia = ?, 
            estado = 'terminado', 
            `orientación` = ? 
        WHERE correo = ?";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "ssssss", $nombre, $fecha, $genero, $bio, $orient, $email);
if (!mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar usuario: ' . mysqli_error($conexion)]);
    exit;
}

// Obtener ID del usuario
$res  = mysqli_query($conexion, "SELECT id_usuario FROM usuario WHERE correo = '$emailEsc'");
$user = mysqli_fetch_assoc($res);
$userId = $user['id_usuario'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    exit;
}

// Guardar intereses
if (!empty($intereses)) {
    mysqli_query($conexion, "DELETE FROM intereses WHERE id_usuario = $userId");
    $stmt = mysqli_prepare($conexion, "INSERT INTO intereses (id_usuario, interes) VALUES (?, ?)");
    foreach ($intereses as $int) {
        mysqli_stmt_bind_param($stmt, "is", $userId, $int);
        mysqli_stmt_execute($stmt);
    }
}

// Guardar fotos
if ($fotos && isset($fotos['tmp_name'])) {
    mysqli_query($conexion, "DELETE FROM fotos WHERE id_usuario = $userId");
    $uploadDir = __DIR__ . '/imagenes/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    foreach ($fotos['tmp_name'] as $i => $tmp) {
        $name = basename($fotos['name'][$i]);
        $ext  = pathinfo($name, PATHINFO_EXTENSION);
        $new  = uniqid("f_{$userId}_") . ".$ext";
        $dest = $uploadDir . $new;
        if (move_uploaded_file($tmp, $dest)) {
            $path = "imagenes/$new";
            mysqli_query($conexion, "INSERT INTO fotos (id_usuario, ruta_foto) VALUES ($userId, '$path')");
        }
    }
}

mysqli_close($conexion);
echo json_encode(['success' => true, 'message' => 'Registro finalizado correctamente']);
?>