<?php
$host = "sql206.infinityfree.com";
$usuario = "if0_41922453";
$senha = "smartmoney123";
$banco = "if0_41922453_smartmoney";

$conn = new mysqli($host, $usuario, $senha, $banco);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

$conn->set_charset("utf8");
?>