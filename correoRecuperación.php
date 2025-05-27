<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require 'conexión.php';

$conexion = getDatabaseConnection();

// CORS y tipo de contenido
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function generarToken($length = 32)
{
    return rtrim(strtr(base64_encode(random_bytes($length)), '+/', '-_'), '=');
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $correo = $_GET['correo'] ?? '';

    if (!$correo) {
        echo json_encode(['success' => false, 'message' => 'Email es obligatorio.']);
        exit;
    }

    // Buscar user_id según el correo
    $stmtUser = mysqli_prepare($conexion, "SELECT id_usuario FROM usuario WHERE correo = ?");
    if (!$stmtUser) {
        echo json_encode(['success' => false, 'message' => 'Error al preparar consulta de usuario.']);
        exit;
    }
    mysqli_stmt_bind_param($stmtUser, "s", $correo);
    mysqli_stmt_execute($stmtUser);
    mysqli_stmt_bind_result($stmtUser, $user_id);
    mysqli_stmt_fetch($stmtUser);
    mysqli_stmt_close($stmtUser);

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
        exit;
    }

    // Generar token y expiración (1 hora)
    $token = generarToken();
    $expires_at = date("Y-m-d H:i:s", time() + 3600);

    // Insertar token en tabla recuperaciones
    $stmtInsert = mysqli_prepare($conexion, "INSERT INTO recuperaciones (user_id, token, expires_at) VALUES (?, ?, ?)");
    if (!$stmtInsert) {
        echo json_encode(['success' => false, 'message' => 'Error al preparar inserción de token.']);
        exit;
    }
    mysqli_stmt_bind_param($stmtInsert, "iss", $user_id, $token, $expires_at);
    if (!mysqli_stmt_execute($stmtInsert)) {
        echo json_encode(['success' => false, 'message' => 'Error al guardar el token en la base de datos.']);
        exit;
    }
    mysqli_stmt_close($stmtInsert);

    // Enviar correo con PHPMailer
    $mail = new PHPMailer(true);
    try {
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = '2002gabrielpm@gmail.com';
        $mail->Password   = 'ckxxofbsjtmfqexs';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom('2002gabrielpm@gmail.com', 'Gabriel');
        $mail->addAddress($correo);

        $link = 'http://localhost/amortal/backend/correoRecuperaci%C3%B3n2.php?token=' . urlencode($token) . '&correo=' . urlencode($correo);

        $mail->isHTML(true);
        $mail->Subject = 'Correo de recuperación de contraseña';
        $mail->Body = 'Haz click <a href="' . $link . '">aquí</a> para restaurar la contraseña.';
        $mail->AltBody = 'visita ' . $link . ' para restaurar tu contraseña.';

        $mail->send();
        echo json_encode(['success' => true, 'message' => 'Correo enviado correctamente.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al enviar el correo: ' . $mail->ErrorInfo]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}