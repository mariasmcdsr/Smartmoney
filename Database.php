<?php
class Database {
    private $host = "sql206.infinityfree.com";
    private $usuario = "if0_41922453";
    private $senha = "smartmoney123";
    private $banco = "if0_41922453_smartmoney";
    private $conn;

    public function conectar() {
        $this->conn = new mysqli($this->host, $this->usuario, $this->senha, $this->banco);
        if ($this->conn->connect_error) {
            die("Erro de conexão: " . $this->conn->connect_error);
        }
        $this->conn->set_charset("utf8");
        return $this->conn;
    }
}
?>