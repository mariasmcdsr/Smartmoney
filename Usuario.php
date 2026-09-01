<?php
class Usuario {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function cadastrar($usuario, $senha) {
        $verificar = $this->conn->prepare("SELECT id_usuario FROM usuarios WHERE usuario = ?");
        $verificar->bind_param("s", $usuario);
        $verificar->execute();
        if ($verificar->get_result()->num_rows > 0) return "Esse usuário já existe.";

        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        $sql = $this->conn->prepare("INSERT INTO usuarios (usuario, senha) VALUES (?, ?)");
        $sql->bind_param("ss", $usuario, $senha_hash);
        return $sql->execute() ? true : "Erro ao cadastrar usuário.";
    }

    public function autenticar($usuario, $senha) {
        $sql = $this->conn->prepare("SELECT id_usuario, usuario, senha FROM usuarios WHERE usuario = ?");
        $sql->bind_param("s", $usuario);
        $sql->execute();
        $resultado = $sql->get_result();

        if ($resultado->num_rows == 1) {
            $dados = $resultado->fetch_assoc();
            if (password_verify($senha, $dados["senha"])) {
                $_SESSION["id_usuario"] = $dados["id_usuario"];
                $_SESSION["usuario"] = $dados["usuario"];
                return true;
            }
        }
        return false;
    }
}
?>