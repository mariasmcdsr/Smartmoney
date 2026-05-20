<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

include "conexao.php";

$id_usuario = $_SESSION["id_usuario"];

$periodo = isset($_GET["periodo"]) ? $_GET["periodo"] : "ano";
$anoSelecionado = isset($_GET["ano"]) ? intval($_GET["ano"]) : intval(date("Y"));
$mesSelecionado = isset($_GET["mes"]) ? intval($_GET["mes"]) : intval(date("m"));

$nomesMeses = [
    1 => "Jan", 2 => "Fev", 3 => "Mar", 4 => "Abr", 5 => "Mai", 6 => "Jun",
    7 => "Jul", 8 => "Ago", 9 => "Set", 10 => "Out", 11 => "Nov", 12 => "Dez"
];

$labelsGrafico = [];
$valoresGrafico = [];
$tituloGrafico = "Gastos no ano " . $anoSelecionado;

if ($periodo == "ano") {
    for ($mes = 1; $mes <= 12; $mes++) {
        $labelsGrafico[] = $nomesMeses[$mes];
        $valoresGrafico[] = 0;
    }

    $sqlGrafico = $conn->prepare("SELECT MONTH(data_registro) AS mes, SUM(total_gastos) AS total
    FROM controle_financeiro
    WHERE id_usuario = ? AND YEAR(data_registro) = ?
    GROUP BY MONTH(data_registro)");
    $sqlGrafico->bind_param("ii", $id_usuario, $anoSelecionado);
    $sqlGrafico->execute();
    $resultadoGrafico = $sqlGrafico->get_result();

    while ($linhaGrafico = $resultadoGrafico->fetch_assoc()) {
        $indice = intval($linhaGrafico["mes"]) - 1;
        $valoresGrafico[$indice] = floatval($linhaGrafico["total"]);
    }
} elseif ($periodo == "6m" || $periodo == "3m") {
    $quantidadeMeses = ($periodo == "6m") ? 6 : 3;
    $tituloGrafico = "Gastos dos últimos " . $quantidadeMeses . " meses";

    $inicio = new DateTime($anoSelecionado . "-" . str_pad($mesSelecionado, 2, "0", STR_PAD_LEFT) . "-01");
    $inicio->modify("-" . ($quantidadeMeses - 1) . " months");

    $datasMeses = [];
    for ($i = 0; $i < $quantidadeMeses; $i++) {
        $dataAtual = clone $inicio;
        $dataAtual->modify("+" . $i . " months");
        $chave = $dataAtual->format("Y-m");
        $datasMeses[$chave] = $i;
        $labelsGrafico[] = $nomesMeses[intval($dataAtual->format("m"))] . "/" . $dataAtual->format("Y");
        $valoresGrafico[] = 0;
    }

    $dataInicio = $inicio->format("Y-m-01");
    $fim = new DateTime($anoSelecionado . "-" . str_pad($mesSelecionado, 2, "0", STR_PAD_LEFT) . "-01");
    $fim->modify("last day of this month");
    $dataFim = $fim->format("Y-m-d");

    $sqlGrafico = $conn->prepare("SELECT DATE_FORMAT(data_registro, '%Y-%m') AS mes, SUM(total_gastos) AS total
    FROM controle_financeiro
    WHERE id_usuario = ? AND DATE(data_registro) BETWEEN ? AND ?
    GROUP BY DATE_FORMAT(data_registro, '%Y-%m')");
    $sqlGrafico->bind_param("iss", $id_usuario, $dataInicio, $dataFim);
    $sqlGrafico->execute();
    $resultadoGrafico = $sqlGrafico->get_result();

    while ($linhaGrafico = $resultadoGrafico->fetch_assoc()) {
        $chave = $linhaGrafico["mes"];
        if (isset($datasMeses[$chave])) {
            $valoresGrafico[$datasMeses[$chave]] = floatval($linhaGrafico["total"]);
        }
    }
} else {
    $tituloGrafico = "Registros de gastos em " . $nomesMeses[$mesSelecionado] . "/" . $anoSelecionado;

    $sqlGrafico = $conn->prepare("SELECT id_controle, total_gastos
    FROM controle_financeiro
    WHERE id_usuario = ? AND MONTH(data_registro) = ? AND YEAR(data_registro) = ?
    ORDER BY id_controle ASC");
    $sqlGrafico->bind_param("iii", $id_usuario, $mesSelecionado, $anoSelecionado);
    $sqlGrafico->execute();
    $resultadoGrafico = $sqlGrafico->get_result();

    $numeroGrafico = 1;
    while ($linhaGrafico = $resultadoGrafico->fetch_assoc()) {
        // Mostra uma numeração visual do usuário no gráfico, em vez do ID real do banco.
        $labelsGrafico[] = $numeroGrafico;
        $valoresGrafico[] = floatval($linhaGrafico["total_gastos"]);
        $numeroGrafico++;
    }

    if (count($labelsGrafico) == 0) {
        $labelsGrafico[] = "Sem dados";
        $valoresGrafico[] = 0;
    }
}

$sql = $conn->prepare("SELECT c.*,
GREATEST(
    IFNULL((SELECT MAX(g.data_gasto) FROM gastos g WHERE g.id_controle = c.id_controle), '1000-01-01'),
    IFNULL((SELECT MAX(r.data_receita) FROM receitas r WHERE r.id_controle = c.id_controle), '1000-01-01'),
    DATE(c.data_registro)
) AS ultima_movimentacao
FROM controle_financeiro c
WHERE c.id_usuario = ?
ORDER BY c.id_controle DESC");

$sql->bind_param("i", $id_usuario);
$sql->execute();
$resultado = $sql->get_result();

$registros = [];
while ($linha = $resultado->fetch_assoc()) {
    $registros[] = $linha;
}

$larguraSvg = 900;
$alturaSvg = 340;
$margemEsquerda = 70;
$margemDireita = 30;
$margemTopo = 30;
$margemBaixo = 70;
$larguraGrafico = $larguraSvg - $margemEsquerda - $margemDireita;
$alturaGrafico = $alturaSvg - $margemTopo - $margemBaixo;

$maiorValor = max($valoresGrafico);
if ($maiorValor <= 0) { $maiorValor = 1; }

$pontos = "";
$circulos = "";
$labelsX = "";
$linhasGrade = "";
$totalPontos = count($valoresGrafico);

for ($i = 0; $i < $totalPontos; $i++) {
    if ($totalPontos == 1) {
        $x = $margemEsquerda + ($larguraGrafico / 2);
    } else {
        $x = $margemEsquerda + ($i * ($larguraGrafico / ($totalPontos - 1)));
    }

    $valor = $valoresGrafico[$i];
    $y = $margemTopo + ($alturaGrafico - (($valor / $maiorValor) * $alturaGrafico));
    $pontos .= $x . "," . $y . " ";
    $valorFormatado = number_format($valor, 2, ',', '.');

    $circulos .= "<circle cx='$x' cy='$y' r='5' class='ponto'><title>R$ $valorFormatado</title></circle>";

    $mostrarLabel = true;
    if ($periodo == "1m" && $totalPontos > 12 && $i % 2 != 0 && $i != $totalPontos - 1) {
        $mostrarLabel = false;
    }

    if ($mostrarLabel) {
        $label = $labelsGrafico[$i];
        $labelsX .= "<text x='$x' y='" . ($alturaSvg - 35) . "' class='label-x'>$label</text>";
    }
}

for ($i = 0; $i <= 4; $i++) {
    $valorLinha = ($maiorValor / 4) * $i;
    $yLinha = $margemTopo + ($alturaGrafico - (($valorLinha / $maiorValor) * $alturaGrafico));
    $valorLinhaFormatado = number_format($valorLinha, 0, ',', '.');

    $linhasGrade .= "
        <line x1='$margemEsquerda' y1='$yLinha' x2='" . ($larguraSvg - $margemDireita) . "' y2='$yLinha' class='grade'></line>
        <text x='10' y='" . ($yLinha + 4) . "' class='label-y'>R$ $valorLinhaFormatado</text>
    ";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Histórico - SmartMoney</title>
    <style>
        :root {
            --fundo: linear-gradient(to right, #4facfe, #00f2fe);
            --card: white;
            --texto: #222;
            --texto-secundario: #555;
            --caixa: #f4f4f4;
            --tabela: white;
            --borda: #ddd;
            --azul: #4facfe;
            --botao: #4facfe;
            --botao-texto: white;
        }

        body.tema-escuro {
            --fundo: linear-gradient(to right, #141e30, #243b55);
            --card: #1f2937;
            --texto: #f5f5f5;
            --texto-secundario: #d1d5db;
            --caixa: #374151;
            --tabela: #111827;
            --borda: #4b5563;
            --azul: #38bdf8;
            --botao: #111827;
            --botao-texto: #f5f5f5;
        }

        body {
            font-family: Arial, sans-serif;
            background: var(--fundo);
            color: var(--texto);
            margin: 0;
            padding: 30px;
        }

        .container {
            background: var(--card);
            color: var(--texto);
            padding: 25px;
            border-radius: 12px;
            max-width: 1100px;
            margin: auto;
            box-shadow: 0 10px 15px rgba(0,0,0,0.2);
        }

        h2, h3 { text-align: center; }

        .filtros {
            background: var(--caixa);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        select, button {
            padding: 8px;
            border-radius: 8px;
            border: 1px solid var(--borda);
            margin: 5px;
            background: var(--card);
            color: var(--texto);
        }

        button {
            background: var(--botao);
            color: var(--botao-texto);
            font-weight: bold;
            cursor: pointer;
        }

        .grafico {
            background: var(--caixa);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            overflow-x: auto;
        }

        svg { min-width: 900px; width: 100%; height: 340px; }
        .linha { fill: none; stroke: var(--azul); stroke-width: 3; }
        .ponto { fill: var(--azul); }
        .grade { stroke: var(--borda); stroke-dasharray: 4; }
        .label-x, .label-y { fill: var(--texto); font-size: 12px; text-anchor: middle; }
        .label-y { text-anchor: start; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            color: var(--texto);
        }

        th, td {
            padding: 9px;
            border: 1px solid var(--borda);
            text-align: center;
        }

        th { background: var(--botao); color: var(--botao-texto); }
        td { background: var(--tabela); }

        .Controlado { color: #22c55e; font-weight: bold; }
        .Alerta { color: orange; font-weight: bold; }
        .Endividado { color: red; font-weight: bold; }

        .btn-detalhes {
            background: var(--botao);
            color: var(--botao-texto);
            padding: 7px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            margin: 2px;
        }

        .btn-remover-historico {
            background: #dc2626;
            color: white;
            padding: 7px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            margin: 2px;
        }

        .links { text-align: center; margin-top: 20px; }
        .links a { color: var(--azul); text-decoration: none; margin: 0 8px; font-weight: bold; }

        .btn-tema {
            position: fixed;
            top: 15px;
            right: 15px;
            width: auto;
            padding: 10px 14px;
            border-radius: 20px;
            border: none;
            background: var(--botao);
            color: var(--botao-texto);
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            z-index: 999;
        }

        @media (max-width: 800px) {
            .container { overflow-x: auto; }
            table { min-width: 1000px; }
        }
    </style>
</head>

<body>
<button class="btn-tema" onclick="trocarTema()" id="btnTema">🌙 Tema escuro</button>

<div class="container">
    <h2>Histórico financeiro</h2>

    <form class="filtros" method="get">
        <label>Período:</label>
        <select name="periodo">
            <option value="ano" <?php if ($periodo == "ano") echo "selected"; ?>>Ano completo</option>
            <option value="6m" <?php if ($periodo == "6m") echo "selected"; ?>>Últimos 6 meses</option>
            <option value="3m" <?php if ($periodo == "3m") echo "selected"; ?>>Últimos 3 meses</option>
            <option value="1m" <?php if ($periodo == "1m") echo "selected"; ?>>1 mês</option>
        </select>

        <label>Mês:</label>
        <select name="mes">
            <?php for ($m = 1; $m <= 12; $m++) { ?>
                <option value="<?php echo $m; ?>" <?php if ($mesSelecionado == $m) echo "selected"; ?>><?php echo $nomesMeses[$m]; ?></option>
            <?php } ?>
        </select>

        <label>Ano:</label>
        <select name="ano">
            <?php for ($a = date("Y") - 3; $a <= date("Y") + 1; $a++) { ?>
                <option value="<?php echo $a; ?>" <?php if ($anoSelecionado == $a) echo "selected"; ?>><?php echo $a; ?></option>
            <?php } ?>
        </select>

        <button type="submit">Filtrar</button>
    </form>

    <div class="grafico">
        <h3><?php echo $tituloGrafico; ?></h3>
        <svg viewBox="0 0 <?php echo $larguraSvg; ?> <?php echo $alturaSvg; ?>">
            <?php echo $linhasGrade; ?>
            <polyline points="<?php echo trim($pontos); ?>" class="linha"></polyline>
            <?php echo $circulos; ?>
            <?php echo $labelsX; ?>
        </svg>
    </div>

    <table>
        <tr>
            <th>Nº</th>
            <th>Renda principal</th>
            <th>Total de rendas</th>
            <th>Total de gastos</th>
            <th>Sobra</th>
            <th>% gasto</th>
            <th>Situação</th>
            <th>Data da movimentação</th>
            <th>Detalhes</th>
            <th>Remover</th>
        </tr>

        <?php if (count($registros) == 0) { ?>
            <tr><td colspan="10">Nenhum registro encontrado.</td></tr>
        <?php } ?>

        <?php $numeroHistorico = 1; ?>
        <?php foreach ($registros as $linha) { ?>
            <tr>
                <td><?php echo $numeroHistorico; ?></td>
                <td>R$ <?php echo number_format($linha["salario"], 2, ',', '.'); ?></td>
                <td>R$ <?php echo number_format($linha["total_rendas"], 2, ',', '.'); ?></td>
                <td>R$ <?php echo number_format($linha["total_gastos"], 2, ',', '.'); ?></td>
                <td>R$ <?php echo number_format($linha["sobra"], 2, ',', '.'); ?></td>
                <td><?php echo number_format($linha["porcentagem_gasta"], 2, ',', '.'); ?>%</td>
                <td class="<?php echo $linha["situacao"]; ?>"><?php echo $linha["situacao"]; ?></td>
                <td>
                    <?php
                    if (!empty($linha["ultima_movimentacao"])) {
                        echo date("d/m/Y", strtotime($linha["ultima_movimentacao"]));
                    } else {
                        echo date("d/m/Y", strtotime($linha["data_registro"]));
                    }
                    ?>
                </td>
                <td>
                    <a class="btn-detalhes" href="detalhes.php?id=<?php echo $linha["id_controle"]; ?>">Ver</a>
                </td>
                <td>
                    <a class="btn-remover-historico" href="remover_historico.php?id=<?php echo $linha["id_controle"]; ?>">Remover</a>
                </td>
            </tr>
            <?php $numeroHistorico++; ?>
        <?php } ?>
    </table>

    <div class="links">
        <a href="money.php">Novo cálculo</a>
        |
        <a href="logout.php">Sair</a>
    </div>
</div>

<script>
function aplicarTemaSalvo() {
    const temaSalvo = localStorage.getItem("tema");
    if (temaSalvo === "escuro") {
        document.body.classList.add("tema-escuro");
        document.getElementById("btnTema").textContent = "☀️ Tema claro";
    } else {
        document.body.classList.remove("tema-escuro");
        document.getElementById("btnTema").textContent = "🌙 Tema escuro";
    }
}

function trocarTema() {
    document.body.classList.toggle("tema-escuro");
    if (document.body.classList.contains("tema-escuro")) {
        localStorage.setItem("tema", "escuro");
        document.getElementById("btnTema").textContent = "☀️ Tema claro";
    } else {
        localStorage.setItem("tema", "claro");
        document.getElementById("btnTema").textContent = "🌙 Tema escuro";
    }
}

aplicarTemaSalvo();
</script>
</body>
</html>
