<?php
// Configuração do banco de dados
$servername = "localhost";
$username = "hvnivrhy_CineStream";
$password = "EEcwPCF8tcN9NKWSEn8q";
$dbname = "hvnivrhy_CineStream";

// Cria a conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
