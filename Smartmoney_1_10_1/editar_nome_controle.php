<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

include "conexao.php";

$id_usuario = $_SESSION["id_usuario"];
$id_controle = intval($_POST["id_controle"] ?? 0);
$nome_controle = trim($_POST["nome_controle"] ?? "");

if ($id_controle <= 0 || $nome_controle === "") {
    header("Location: historico.php");
    exit;
}

$nome_controle = mb_substr($nome_controle, 0, 150, "UTF-8");

$sql = $conn->prepare("
    UPDATE controle_financeiro
    SET nome_controle = ?
    WHERE id_controle = ?
      AND id_usuario = ?
");

if (!$sql) {
    die("Erro ao preparar atualização: " . $conn->error);
}

$sql->bind_param("sii", $nome_controle, $id_controle, $id_usuario);
$sql->execute();

$sql->close();

header("Location: historico.php");
exit;
?>
