<?php
$conn = new mysqli("mysql-agudelo.alwaysdata.net", "agudelo", "juancho32", "agudelo_inventario");
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>
