<?php
// Arquivo de conexão com o banco de dados.
// Edite os dados abaixo conforme o ambiente usado.
// No XAMPP geralmente é: host localhost, usuario root, senha vazia e banco smartmoney.
// No InfinityFree use os dados exibidos no painel MySQL Databases.

$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "smartmoney";

$conn = new mysqli($host, $usuario, $senha, $banco);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

$conn->set_charset("utf8");
?>
