<?php
$host = "localhost";
$user = "root";   // padrão do XAMPP
$pass = "";       // senha em branco no XAMPP
$db   = "controle_visitante"; // nome do seu banco criado no phpMyAdmin

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}
?>