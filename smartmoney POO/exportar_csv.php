<?php
session_start();
if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

require_once "classes/Database.php";
require_once "classes/Formatador.php";

$db = (new Database())->conectar();

$id_usuario = $_SESSION["id_usuario"];
$id_controle = intval($_GET["id"] ?? 0);

if ($id_controle <= 0) { die("ID inválido."); }

$sql = $db->prepare("SELECT * FROM controle_financeiro WHERE id_controle = ? AND id_usuario = ?");
$sql->bind_param("ii", $id_controle, $id_usuario);
$sql->execute();
$controle = $sql->get_result()->fetch_assoc();

if (!$controle) { die("Registro não encontrado ou sem permissão."); }

$moeda = Formatador::moedaPermitida($controle['moeda'] ?? 'BRL');
$simbolo = Formatador::simboloMoeda($moeda);

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename=Relatorio_SmartMoney_' . date('Y-m-d') . '.xls');
header('Cache-Control: max-age=0');

$html = '
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head><meta charset="UTF-8"></head>
<body>
    <table border="1" style="font-family: Arial, sans-serif; border-collapse: collapse;">
        <tr>
            <th colspan="4" style="background-color: #4facfe; color: white; font-size: 16px; padding: 10px; text-align: center;">
                Resumo do Controle: ' . htmlspecialchars($controle['nome_controle']) . '
            </th>
        </tr>
        <tr>
            <td colspan="3"><strong>Renda Principal</strong></td>
            <td>' . $simbolo . ' ' . number_format($controle['salario'], 2, ',', '.') . '</td>
        </tr>';

if ($controle['renda_parceiro'] > 0) {
    $html .= '
        <tr>
            <td colspan="3"><strong>Renda da 2ª Pessoa</strong></td>
            <td>' . $simbolo . ' ' . number_format($controle['renda_parceiro'], 2, ',', '.') . '</td>
        </tr>';
}


$corSituacao = 'green';
if ($controle['situacao'] == 'Alerta') { $corSituacao = 'orange'; }
if ($controle['situacao'] == 'Endividado') { $corSituacao = 'red'; }

$html .= '
        <tr>
            <td colspan="3"><strong>Total de Rendas</strong></td>
            <td>' . $simbolo . ' ' . number_format($controle['total_rendas'], 2, ',', '.') . '</td>
        </tr>
        <tr>
            <td colspan="3"><strong>Total de Gastos</strong></td>
            <td>' . $simbolo . ' ' . number_format($controle['total_gastos'], 2, ',', '.') . '</td>
        </tr>
        <tr>
            <td colspan="3"><strong>Sobra</strong></td>
            <td>' . $simbolo . ' ' . number_format($controle['sobra'], 2, ',', '.') . '</td>
        </tr>
        <tr>
            <td colspan="3"><strong>Situação</strong></td>
            <td style="font-weight: bold; color: ' . $corSituacao . ';">' . $controle['situacao'] . '</td>
        </tr>
        <tr><td colspan="4"></td></tr>
        
        <tr>
            <th style="background-color: #222222; color: white; padding: 8px;">Tipo</th>
            <th style="background-color: #222222; color: white; padding: 8px;">Nome do Registro</th>
            <th style="background-color: #222222; color: white; padding: 8px;">Valor</th>
            <th style="background-color: #222222; color: white; padding: 8px;">Data</th>
        </tr>
';

$sqlRec = $db->prepare("SELECT tipo_receita, valor, data_receita FROM receitas WHERE id_controle = ? ORDER BY data_receita ASC");
$sqlRec->bind_param("i", $id_controle);
$sqlRec->execute();
$receitas = $sqlRec->get_result();
while ($r = $receitas->fetch_assoc()) {
    $html .= '<tr>
        <td>Receita Extra</td>
        <td>' . htmlspecialchars($r['tipo_receita']) . '</td>
        <td>' . $simbolo . ' ' . number_format($r['valor'], 2, ',', '.') . '</td>
        <td>' . date("d/m/Y", strtotime($r['data_receita'])) . '</td>
    </tr>';
}

$sqlGas = $db->prepare("SELECT tipo_gasto, valor, data_gasto FROM gastos WHERE id_controle = ? ORDER BY data_gasto ASC");
$sqlGas->bind_param("i", $id_controle);
$sqlGas->execute();
$gastos = $sqlGas->get_result();
while ($g = $gastos->fetch_assoc()) {
    $html .= '<tr>
        <td>Gasto</td>
        <td>' . htmlspecialchars($g['tipo_gasto']) . '</td>
        <td>' . $simbolo . ' ' . number_format($g['valor'], 2, ',', '.') . '</td>
        <td>' . date("d/m/Y", strtotime($g['data_gasto'])) . '</td>
    </tr>';
}

$html .= '</table></body></html>';

echo $html;
exit;
?>