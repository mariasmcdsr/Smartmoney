<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

include "conexao.php";
include "funcoes.php";

$id_usuario = $_SESSION["id_usuario"];

$buscaItens = isset($_GET["busca"]) ? trim($_GET["busca"]) : "";
$tipoItem = isset($_GET["tipo_item"]) ? $_GET["tipo_item"] : "todos";
$tiposItensPermitidos = ["todos", "gastos", "receitas"];
if (!in_array($tipoItem, $tiposItensPermitidos)) {
    $tipoItem = "todos";
}

function executarConsultaPreparada($conn, $sql, $tipos = "", $valores = []) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Erro ao preparar consulta: " . $conn->error);
    }

    if ($tipos != "" && count($valores) > 0) {
        $referencias = [];
        foreach ($valores as $chave => $valor) {
            $referencias[$chave] = &$valores[$chave];
        }
        array_unshift($referencias, $tipos);
        call_user_func_array([$stmt, "bind_param"], $referencias);
    }

    $stmt->execute();
    return $stmt->get_result();
}

if (!isset($_GET["id"])) {
    die("ID não informado.");
}

$id_controle = intval($_GET["id"]);

$sql = $conn->prepare("SELECT * FROM controle_financeiro WHERE id_controle = ? AND id_usuario = ?");
$sql->bind_param("ii", $id_controle, $id_usuario);
$sql->execute();
$controle = $sql->get_result();

if ($controle->num_rows == 0) {
    die("Registro não encontrado.");
}

$dados_controle = $controle->fetch_assoc();

$receitasLista = [];
$gastosLista = [];
$totalReceitasFiltradas = 0;
$totalGastosFiltrados = 0;

if ($tipoItem == "todos" || $tipoItem == "receitas") {
    $sqlReceitasTexto = "SELECT * FROM receitas WHERE id_controle = ?";
    $tiposReceitas = "i";
    $valoresReceitas = [$id_controle];

    if ($buscaItens != "") {
        $sqlReceitasTexto .= " AND tipo_receita LIKE ?";
        $tiposReceitas .= "s";
        $valoresReceitas[] = "%" . $buscaItens . "%";
    }

    $sqlReceitasTexto .= " ORDER BY data_receita ASC";
    $receitas = executarConsultaPreparada($conn, $sqlReceitasTexto, $tiposReceitas, $valoresReceitas);
    while ($linhaReceita = $receitas->fetch_assoc()) {
        $receitasLista[] = $linhaReceita;
        $totalReceitasFiltradas += floatval($linhaReceita["valor"]);
    }
}

if ($tipoItem == "todos" || $tipoItem == "gastos") {
    $sqlGastosTexto = "SELECT * FROM gastos WHERE id_controle = ?";
    $tiposGastos = "i";
    $valoresGastos = [$id_controle];

    if ($buscaItens != "") {
        $sqlGastosTexto .= " AND tipo_gasto LIKE ?";
        $tiposGastos .= "s";
        $valoresGastos[] = "%" . $buscaItens . "%";
    }

    $sqlGastosTexto .= " ORDER BY data_gasto ASC";
    $gastos = executarConsultaPreparada($conn, $sqlGastosTexto, $tiposGastos, $valoresGastos);
    while ($linhaGasto = $gastos->fetch_assoc()) {
        $gastosLista[] = $linhaGasto;
        $totalGastosFiltrados += floatval($linhaGasto["valor"]);
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes - SmartMoney</title>
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
            max-width: 950px;
            margin: auto;
            box-shadow: 0 10px 15px rgba(0,0,0,0.2);
        }

        h2, h3 {
            text-align: center;
        }

        .filtros-itens, .resumo-busca {
            background: var(--caixa);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .resumo-busca {
            text-align: left;
            border-left: 5px solid var(--azul);
            line-height: 1.6;
        }

        select, input[type="text"], button {
            padding: 8px;
            border-radius: 8px;
            border: 1px solid var(--borda);
            margin: 5px;
            background: var(--card);
            color: var(--texto);
        }

        .campo-busca { width: 260px; max-width: 90%; }

        .resumo {
            background: var(--caixa);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .situacao { font-weight: bold; }
        .Controlado { color: #22c55e; }
        .Alerta { color: orange; }
        .Endividado { color: red; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            color: var(--texto);
        }

        th, td {
            padding: 10px;
            border: 1px solid var(--borda);
            text-align: center;
        }

        th {
            background: var(--botao);
            color: var(--botao-texto);
        }

        td { background: var(--tabela); }

        .btn-editar, .btn-adicionar {
            background: var(--botao);
            color: var(--botao-texto);
            padding: 7px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            display: inline-block;
            font-weight: bold;
        }

        .area-botao {
            text-align: right;
            margin: 8px 0 10px 0;
        }

        .links {
            text-align: center;
            margin-top: 20px;
        }

        .links a {
            color: var(--azul);
            text-decoration: none;
            margin: 0 8px;
            font-weight: bold;
        }

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

        @media (max-width: 700px) {
            .container { overflow-x: auto; }
            table { min-width: 750px; }
        }
    </style>
    <link rel="stylesheet" href="responsivo.css">
</head>

<body>
<button class="btn-tema" onclick="trocarTema()" id="btnTema">🌙 Tema escuro</button>

<div class="container">
    <h2>Detalhes do cálculo</h2>

    <div class="resumo">
        <p><strong>Salário / renda principal:</strong> <?php echo formatarMoeda($dados_controle["salario"], $dados_controle["moeda"] ?? "BRL"); ?></p>
        <p><strong>Total de rendas:</strong> <?php echo formatarMoeda($dados_controle["total_rendas"], $dados_controle["moeda"] ?? "BRL"); ?></p>
        <p><strong>Total de gastos:</strong> <?php echo formatarMoeda($dados_controle["total_gastos"], $dados_controle["moeda"] ?? "BRL"); ?></p>
        <p><strong>Sobra:</strong> <?php echo formatarMoeda($dados_controle["sobra"], $dados_controle["moeda"] ?? "BRL"); ?></p>
        <p><strong>Porcentagem dos gastos:</strong> <?php echo number_format($dados_controle["porcentagem_gasta"], 2, ',', '.'); ?>%</p>
        <p>
            <strong>Situação:</strong>
            <span class="situacao <?php echo $dados_controle["situacao"]; ?>">
                <?php echo $dados_controle["situacao"]; ?>
            </span>
        </p>
        <?php if (!empty($dados_controle["nome_parceiro"]) || floatval($dados_controle["renda_parceiro"] ?? 0) > 0) { ?>
            <p><strong>Segunda pessoa:</strong> <?php echo htmlspecialchars($dados_controle["nome_parceiro"] ?: "Pessoa 2"); ?> — <?php echo formatarMoeda($dados_controle["renda_parceiro"] ?? 0, $dados_controle["moeda"] ?? "BRL"); ?></p>
        <?php } ?>
        <p><strong>Moeda:</strong> <?php echo htmlspecialchars(nomeMoeda($dados_controle["moeda"] ?? "BRL")); ?></p>
        <p><strong>Data do cálculo:</strong> <?php echo date("d/m/Y H:i", strtotime($dados_controle["data_registro"])); ?></p>
    </div>

    <form class="filtros-itens" method="get">
        <input type="hidden" name="id" value="<?php echo $id_controle; ?>">
        <label>Pesquisar neste histórico:</label>
        <input class="campo-busca" type="text" name="busca" placeholder="Ex.: mercado, Uber, bico" value="<?php echo htmlspecialchars($buscaItens); ?>">

        <label>Tipo:</label>
        <select name="tipo_item">
            <option value="todos" <?php if ($tipoItem == "todos") echo "selected"; ?>>Gastos e receitas</option>
            <option value="gastos" <?php if ($tipoItem == "gastos") echo "selected"; ?>>Somente gastos</option>
            <option value="receitas" <?php if ($tipoItem == "receitas") echo "selected"; ?>>Somente receitas</option>
        </select>

        <button type="submit">Pesquisar</button>
        <?php if ($buscaItens != "") { ?>
            <a class="btn-editar" href="detalhes.php?id=<?php echo $id_controle; ?>">Limpar</a>
        <?php } ?>
    </form>

    <?php if ($buscaItens != "") { ?>
        <div class="resumo-busca">
            <strong>Resumo da busca por:</strong> <?php echo htmlspecialchars($buscaItens); ?><br>
            <?php if ($tipoItem == "todos" || $tipoItem == "receitas") { ?>
                Receitas encontradas: <?php echo count($receitasLista); ?> | Total: <?php echo formatarMoeda($totalReceitasFiltradas, $dados_controle["moeda"] ?? "BRL"); ?><br>
            <?php } ?>
            <?php if ($tipoItem == "todos" || $tipoItem == "gastos") { ?>
                Gastos encontrados: <?php echo count($gastosLista); ?> | Total: <?php echo formatarMoeda($totalGastosFiltrados, $dados_controle["moeda"] ?? "BRL"); ?>
            <?php } ?>
        </div>
    <?php } ?>


    <h3>Receitas adicionais</h3>
    <div class="area-botao">
        <a class="btn-adicionar" href="adicionar_receita_historico.php?id=<?php echo $id_controle; ?>">+ Adicionar receita esquecida</a>
    </div>
    <div class="tabela-scroll"><table>
        <tr>
            <th>Nome da receita</th>
            <th>Valor</th>
            <th>Data da receita</th>
            <th>Ação</th>
        </tr>

        <?php if (count($receitasLista) == 0) { ?>
            <tr><td colspan="4">Nenhuma receita adicional cadastrada.</td></tr>
        <?php } ?>

        <?php foreach ($receitasLista as $linha) { ?>
            <tr>
                <td><?php echo htmlspecialchars($linha["tipo_receita"]); ?></td>
                <td><?php echo formatarMoeda($linha["valor"], $dados_controle["moeda"] ?? "BRL"); ?></td>
                <td><?php echo date("d/m/Y", strtotime($linha["data_receita"])); ?></td>
                <td>
                    <a class="btn-editar" href="editar_receita.php?id=<?php echo $linha["id_receita"]; ?>">Editar / remover</a>
                </td>
            </tr>
        <?php } ?>
    </table></div>

    <h3>Gastos</h3>
    <div class="area-botao">
        <a class="btn-adicionar" href="adicionar_gasto_historico.php?id=<?php echo $id_controle; ?>">+ Adicionar gasto esquecido</a>
    </div>
    <div class="tabela-scroll"><table>
        <tr>
            <th>Nome do gasto</th>
            <th>Valor</th>
            <th>Data do gasto</th>
            <th>Ação</th>
        </tr>

        <?php if (count($gastosLista) == 0) { ?>
            <tr><td colspan="4">Nenhum gasto encontrado.</td></tr>
        <?php } ?>

        <?php foreach ($gastosLista as $linha) { ?>
            <tr>
                <td><?php echo htmlspecialchars($linha["tipo_gasto"]); ?></td>
                <td><?php echo formatarMoeda($linha["valor"], $dados_controle["moeda"] ?? "BRL"); ?></td>
                <td><?php echo date("d/m/Y", strtotime($linha["data_gasto"])); ?></td>
                <td>
                    <a class="btn-editar" href="editar_gasto.php?id=<?php echo $linha["id_gasto"]; ?>">Editar / remover</a>
                </td>
            </tr>
        <?php } ?>
    </table></div>

    <div class="links">
        <a href="historico.php">Voltar ao histórico</a>
        |
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
