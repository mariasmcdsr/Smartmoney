<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

include "conexao.php";

$id_usuario = $_SESSION["id_usuario"];
$ultimo_salario = null;

$consulta_salario = $conn->prepare("SELECT salario FROM controle_financeiro WHERE id_usuario = ? ORDER BY id_controle DESC LIMIT 1");
$consulta_salario->bind_param("i", $id_usuario);
$consulta_salario->execute();
$resultado_salario = $consulta_salario->get_result();

if ($resultado_salario->num_rows > 0) {
    $dados_salario = $resultado_salario->fetch_assoc();
    $ultimo_salario = $dados_salario["salario"];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>SmartMoney</title>
    <style>
        :root {
            --fundo: linear-gradient(to right, #4facfe, #00f2fe);
            --card: white;
            --texto: #222;
            --texto-secundario: #555;
            --input: white;
            --borda: #ccc;
            --caixa: #f4f4f4;
            --azul: #4facfe;
            --botao: #4facfe;
            --botao-texto: white;
            --vermelho: #dc3545;
            --verde: #16a34a;
        }

        body.tema-escuro {
            --fundo: linear-gradient(to right, #141e30, #243b55);
            --card: #1f2937;
            --texto: #f5f5f5;
            --texto-secundario: #d1d5db;
            --input: #111827;
            --borda: #4b5563;
            --caixa: #374151;
            --azul: #38bdf8;
            --botao: #111827;
            --botao-texto: #f5f5f5;
            --vermelho: #991b1b;
            --verde: #15803d;
        }

        body {
            margin: 0;
            padding: 30px;
            font-family: Arial, sans-serif;
            background: var(--fundo);
            color: var(--texto);
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
        }

        .container {
            background: var(--card);
            color: var(--texto);
            padding: 25px;
            border-radius: 12px;
            width: 460px;
            box-shadow: 0 10px 15px rgba(0,0,0,0.2);
        }

        h2, h3 {
            text-align: center;
            margin-bottom: 10px;
        }

        .usuario {
            text-align: center;
            font-size: 14px;
            margin-bottom: 15px;
            color: var(--texto-secundario);
        }

        input {
            width: 100%;
            padding: 9px;
            margin-top: 10px;
            border-radius: 10px;
            border: 1px solid var(--borda);
            box-sizing: border-box;
            background: var(--input);
            color: var(--texto);
        }

        input::placeholder {
            color: var(--texto-secundario);
        }

        .bloco {
            background: var(--caixa);
            padding: 12px;
            border-radius: 10px;
            margin-top: 12px;
        }

        .label-data {
            display: block;
            margin-top: 10px;
            font-size: 13px;
            color: var(--texto-secundario);
        }

        .observacao {
            font-size: 13px;
            color: var(--texto-secundario);
            text-align: center;
            margin-top: 5px;
        }

        .btn-principal,
        .btn-add,
        .btn-salario {
            width: 100%;
            padding: 10px;
            margin-top: 15px;
            border-radius: 10px;
            background: var(--botao);
            color: var(--botao-texto);
            border: none;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-add.receita {
            background: var(--verde);
            color: white;
        }

        .btn-remover {
            width: 100%;
            padding: 8px;
            margin-top: 10px;
            border-radius: 10px;
            background: var(--vermelho);
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }

        button:hover {
            opacity: 0.85;
        }

        .links {
            text-align: center;
            margin-top: 15px;
        }

        .links a {
            color: var(--azul);
            text-decoration: none;
            margin: 0 5px;
            font-size: 14px;
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
    </style>
</head>

<body>
<button class="btn-tema" onclick="trocarTema()" id="btnTema">🌙 Tema escuro</button>

<div class="container">
    <h2>SmartMoney</h2>

    <p class="usuario">
        Usuário: <?php echo htmlspecialchars($_SESSION["usuario"]); ?>
    </p>

    <form action="salario.php" method="post">
        <h3>Renda principal do mês</h3>
        <p class="observacao">Informe o salário ou renda principal deste período.</p>
        <input type="number" step="0.01" min="0" id="salario" name="salario" placeholder="Salário / renda principal" required>

        <?php if ($ultimo_salario !== null) { ?>
            <button type="button" class="btn-salario" onclick="usarUltimoSalario('<?php echo number_format($ultimo_salario, 2, '.', ''); ?>')">
                Usar salário anterior: R$ <?php echo number_format($ultimo_salario, 2, ',', '.'); ?>
            </button>
        <?php } else { ?>
            <p class="observacao">Ainda não existe salário anterior salvo para este usuário.</p>
        <?php } ?>

        <h3>Receitas adicionais</h3>
        <p class="observacao">Use somente se tiver renda extra, como hora extra, bico, venda ou pagamento recebido.</p>

        <div id="area-receitas"></div>

        <button type="button" class="btn-add receita" onclick="adicionarReceita()">+ Adicionar receita</button>

        <h3>Gastos</h3>

        <div id="area-gastos">
            <div class="bloco">
                <input type="text" name="nome_gasto[]" placeholder="Nome do gasto. Ex: Mercado" required>
                <input type="number" step="0.01" min="0" name="valor_gasto[]" placeholder="Valor do gasto" required>

                <label class="label-data">Data em que o gasto foi feito:</label>
                <input type="date" name="data_gasto[]" required>
            </div>
        </div>

        <button type="button" class="btn-add" onclick="adicionarGasto()">+ Adicionar gasto</button>

        <button type="submit" class="btn-principal">Calcular</button>
    </form>

    <div class="links">
        <a href="historico.php">Histórico</a>
        |
        <a href="logout.php">Sair</a>
    </div>
</div>

<script>
function usarUltimoSalario(valor) {
    document.getElementById("salario").value = valor;
}

function adicionarReceita() {
    const area = document.getElementById("area-receitas");
    const div = document.createElement("div");
    div.className = "bloco";

    div.innerHTML = `
        <input type="text" name="nome_receita[]" placeholder="Nome da receita. Ex: Hora extra">
        <input type="number" step="0.01" min="0" name="valor_receita[]" placeholder="Valor da receita">

        <label class="label-data">Data em que a receita entrou:</label>
        <input type="date" name="data_receita[]">

        <button type="button" class="btn-remover" onclick="removerBloco(this)">Remover receita</button>
    `;

    area.appendChild(div);
}

function adicionarGasto() {
    const area = document.getElementById("area-gastos");
    const div = document.createElement("div");
    div.className = "bloco";

    div.innerHTML = `
        <input type="text" name="nome_gasto[]" placeholder="Nome do gasto. Ex: Internet" required>
        <input type="number" step="0.01" min="0" name="valor_gasto[]" placeholder="Valor do gasto" required>

        <label class="label-data">Data em que o gasto foi feito:</label>
        <input type="date" name="data_gasto[]" required>

        <button type="button" class="btn-remover" onclick="removerBloco(this)">Remover gasto</button>
    `;

    area.appendChild(div);
}

function removerBloco(botao) {
    botao.parentElement.remove();
}

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
