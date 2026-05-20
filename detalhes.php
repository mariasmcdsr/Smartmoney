<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

include "conexao.php";

$id_usuario = $_SESSION["id_usuario"];

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

$sql_receitas = $conn->prepare("SELECT * FROM receitas WHERE id_controle = ? ORDER BY data_receita ASC");
$sql_receitas->bind_param("i", $id_controle);
$sql_receitas->execute();
$receitas = $sql_receitas->get_result();

$sql_gastos = $conn->prepare("SELECT * FROM gastos WHERE id_controle = ? ORDER BY data_gasto ASC");
$sql_gastos->bind_param("i", $id_controle);
$sql_gastos->execute();
$gastos = $sql_gastos->get_result();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
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
</head>

<body>
<button class="btn-tema" onclick="trocarTema()" id="btnTema">🌙 Tema escuro</button>

<div class="container">
    <h2>Detalhes do cálculo</h2>

    <div class="resumo">
        <p><strong>Salário / renda principal:</strong> R$ <?php echo number_format($dados_controle["salario"], 2, ',', '.'); ?></p>
        <p><strong>Total de rendas:</strong> R$ <?php echo number_format($dados_controle["total_rendas"], 2, ',', '.'); ?></p>
        <p><strong>Total de gastos:</strong> R$ <?php echo number_format($dados_controle["total_gastos"], 2, ',', '.'); ?></p>
        <p><strong>Sobra:</strong> R$ <?php echo number_format($dados_controle["sobra"], 2, ',', '.'); ?></p>
        <p><strong>Porcentagem dos gastos:</strong> <?php echo number_format($dados_controle["porcentagem_gasta"], 2, ',', '.'); ?>%</p>
        <p>
            <strong>Situação:</strong>
            <span class="situacao <?php echo $dados_controle["situacao"]; ?>">
                <?php echo $dados_controle["situacao"]; ?>
            </span>
        </p>
        <p><strong>Data do cálculo:</strong> <?php echo date("d/m/Y H:i", strtotime($dados_controle["data_registro"])); ?></p>
    </div>

    <h3>Receitas adicionais</h3>
    <table>
        <tr>
            <th>Nome da receita</th>
            <th>Valor</th>
            <th>Data da receita</th>
            <th>Ação</th>
        </tr>

        <?php if ($receitas->num_rows == 0) { ?>
            <tr><td colspan="4">Nenhuma receita adicional cadastrada.</td></tr>
        <?php } ?>

        <?php while ($linha = $receitas->fetch_assoc()) { ?>
            <tr>
                <td><?php echo htmlspecialchars($linha["tipo_receita"]); ?></td>
                <td>R$ <?php echo number_format($linha["valor"], 2, ',', '.'); ?></td>
                <td><?php echo date("d/m/Y", strtotime($linha["data_receita"])); ?></td>
                <td>
                    <a class="btn-editar" href="editar_receita.php?id=<?php echo $linha["id_receita"]; ?>">Editar</a>
                </td>
            </tr>
        <?php } ?>
    </table>

    <h3>Gastos</h3>
    <div class="area-botao">
        <a class="btn-adicionar" href="adicionar_gasto_historico.php?id=<?php echo $id_controle; ?>">+ Adicionar gasto esquecido</a>
    </div>
    <table>
        <tr>
            <th>Nome do gasto</th>
            <th>Valor</th>
            <th>Data do gasto</th>
            <th>Ação</th>
        </tr>

        <?php while ($linha = $gastos->fetch_assoc()) { ?>
            <tr>
                <td><?php echo htmlspecialchars($linha["tipo_gasto"]); ?></td>
                <td>R$ <?php echo number_format($linha["valor"], 2, ',', '.'); ?></td>
                <td><?php echo date("d/m/Y", strtotime($linha["data_gasto"])); ?></td>
                <td>
                    <a class="btn-editar" href="editar_gasto.php?id=<?php echo $linha["id_gasto"]; ?>">Editar</a>
                </td>
            </tr>
        <?php } ?>
    </table>

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
