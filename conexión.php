<?php
function getDatabaseConnection() {
    $host = 'localhost';
    $dbname = 'amortal';
    $username = 'root';
    $password = '';

    $conexion = mysqli_connect($host, $username, $password, $dbname);

    if (!$conexion) {
        die("Error de conexión: " . mysqli_connect_error());
    }

    return $conexion;
}
?>