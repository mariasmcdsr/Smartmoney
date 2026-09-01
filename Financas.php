<?php
class Financas {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function buscarUltimoSalario($id_usuario) {
        $sql = $this->conn->prepare("SELECT salario, moeda, nome_parceiro, renda_parceiro FROM controle_financeiro WHERE id_usuario = ? ORDER BY id_controle DESC LIMIT 1");
        $sql->bind_param("i", $id_usuario);
        $sql->execute();
        return $sql->get_result()->fetch_assoc();
    }

    public function calcularSituacao($total_rendas, $total_gastos) {
        $sobra = $total_rendas - $total_gastos;
        $porcentagem = ($total_rendas > 0) ? ($total_gastos / $total_rendas) * 100 : 0;

        if ($sobra > 0 && $porcentagem <= 70) {
            return ["situacao" => "Controlado", "cor" => "green", "porcentagem" => $porcentagem, "sobra" => $sobra];
        } elseif ($sobra > 0 && $porcentagem <= 100) {
            return ["situacao" => "Alerta", "cor" => "orange", "porcentagem" => $porcentagem, "sobra" => $sobra];
        } else {
            return ["situacao" => "Endividado", "cor" => "red", "porcentagem" => $porcentagem, "sobra" => $sobra];
        }
    }

    public function recalcularControle($id_controle, $id_usuario) {
        $sqlGastos = $this->conn->prepare("SELECT SUM(valor) AS total FROM gastos WHERE id_controle = ?");
        $sqlGastos->bind_param("i", $id_controle);
        $sqlGastos->execute();
        $total_gastos = floatval($sqlGastos->get_result()->fetch_assoc()["total"]);

        $sqlReceitas = $this->conn->prepare("SELECT SUM(valor) AS total FROM receitas WHERE id_controle = ?");
        $sqlReceitas->bind_param("i", $id_controle);
        $sqlReceitas->execute();
        $total_receitas_adicionais = floatval($sqlReceitas->get_result()->fetch_assoc()["total"]);

        $sqlControle = $this->conn->prepare("SELECT salario, renda_parceiro FROM controle_financeiro WHERE id_controle = ? AND id_usuario = ?");
        $sqlControle->bind_param("ii", $id_controle, $id_usuario);
        $sqlControle->execute();
        $dadosControle = $sqlControle->get_result()->fetch_assoc();

        $salario = floatval($dadosControle["salario"]);
        $renda_parceiro = floatval($dadosControle["renda_parceiro"] ?? 0);
        $total_rendas = $salario + $renda_parceiro + $total_receitas_adicionais;
        
        $resultado = $this->calcularSituacao($total_rendas, $total_gastos);
        
        $update = $this->conn->prepare("UPDATE controle_financeiro SET total_rendas = ?, total_gastos = ?, sobra = ?, porcentagem_gasta = ?, situacao = ? WHERE id_controle = ? AND id_usuario = ?");
        $update->bind_param("ddddsii", $total_rendas, $total_gastos, $resultado["sobra"], $resultado["porcentagem"], $resultado["situacao"], $id_controle, $id_usuario);
        $update->execute();
    }
}
?>