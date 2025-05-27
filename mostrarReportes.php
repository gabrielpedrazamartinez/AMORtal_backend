<?php
// CORS y tipo de contenido
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

require 'conexión.php';
$conexion = getDatabaseConnection();
if ($conexion->connect_errno) {
    http_response_code(500);
    echo json_encode(["error" => "Error de conexión a la base de datos"]);
    exit();
}

// Base URL para las imágenes
$baseUrl = "http://localhost/amortal/backend/";

$query = "SELECT id_reporte, id_reportante, id_reportado, mensaje 
          FROM reporte 
          WHERE estado = 'pendiente'";
$result = $conexion->query($query);
if (!$result) {
    http_response_code(500);
    echo json_encode(["error" => "Error al obtener los reportes"]);
    exit();
}

$reportes = [];
while ($r = $result->fetch_assoc()) {
    $rid  = $r['id_reporte'];
    $rep  = $r['id_reportante'];
    $redo = $r['id_reportado'];
    $msg  = $r['mensaje'];

    // Nombre + primera foto del reportante
    $sqlUser = "SELECT u.nombre, f.ruta_foto 
                FROM usuario u
                LEFT JOIN fotos f ON u.id_usuario = f.id_usuario
                WHERE u.id_usuario = ?
                ORDER BY f.id_foto ASC
                LIMIT 1";
    $stmt = $conexion->prepare($sqlUser);
    $stmt->bind_param("i", $rep);
    $stmt->execute();
    $stmt->bind_result($nameRep, $rutaRep);
    $stmt->fetch();
    $stmt->close();
    $fotoRep = $rutaRep ? $baseUrl . $rutaRep : null;

    // Nombre + primera foto del reportado
    $stmt = $conexion->prepare($sqlUser);
    $stmt->bind_param("i", $redo);
    $stmt->execute();
    $stmt->bind_result($nameRedo, $rutaRedo);
    $stmt->fetch();
    $stmt->close();
    $fotoRedo = $rutaRedo ? $baseUrl . $rutaRedo : null;

    // Buscar id_match
    $sqlMatch = "SELECT id_match FROM `match` 
                 WHERE (id_usuario_1=? AND id_usuario_2=?) 
                    OR (id_usuario_1=? AND id_usuario_2=?)
                 LIMIT 1";
    $stmt = $conexion->prepare($sqlMatch);
    $stmt->bind_param("iiii", $rep, $redo, $redo, $rep);
    $stmt->execute();
    $stmt->bind_result($mid);
    $hasMatch = $stmt->fetch();
    $stmt->close();

    // Mensajes
    $mensajes = [];
    if ($hasMatch) {
        $sqlMsgs = "SELECT id_mensaje, id_usuario_emisor, texto, fecha_mensaje 
                    FROM mensaje 
                    WHERE id_match = ? 
                    ORDER BY fecha_mensaje ASC";
        $stmt = $conexion->prepare($sqlMsgs);
        $stmt->bind_param("i", $mid);
        $stmt->execute();
        $resM = $stmt->get_result();
        while ($m = $resM->fetch_assoc()) {
            $mensajes[] = $m;
        }
        $stmt->close();
    }

    $reportes[] = [
        "id_reporte"      => $rid,
        "id_reportante"   => $rep,
        "id_reportado"    => $redo,
        "reportante"      => ["nombre" => $nameRep,  "foto" => $fotoRep],
        "reportado"       => ["nombre" => $nameRedo, "foto" => $fotoRedo],
        "mensaje_reporte" => $msg,
        "id_match"        => $hasMatch ? $mid : null,
        "mensajes"        => $mensajes
    ];
}

echo json_encode($reportes);
$conexion->close();