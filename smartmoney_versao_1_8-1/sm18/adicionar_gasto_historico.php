<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

include "conexao.php";

$id_usuario = $_SESSION["id_usuario"];
$usuario_logado = $_SESSION["usuario"] ?? "";

if (!isset($_GET["id"])) {
    die("ID do histórico não informado.");
}

$id_controle = intval($_GET["id"]);
$mensagem = "";

$sql = $conn->prepare("SELECT * FROM controle_financeiro WHERE id_controle = ? AND id_usuario = ?");
$sql->bind_param("ii", $id_controle, $id_usuario);
$sql->execute();
$resultado = $sql->get_result();

if ($resultado->num_rows == 0) {
    die("Histórico não encontrado ou você não tem permissão para alterar.");
}

$controle = $resultado->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tipo_gasto = trim($_POST["tipo_gasto"]);
    $valor = floatval($_POST["valor"]);
    $data_gasto = $_POST["data_gasto"];

    if (empty($tipo_gasto) || empty($data_gasto) || $valor <= 0) {
        $mensagem = "Preencha nome, valor maior que zero e data do gasto.";
    } else {
        $insert = $conn->prepare("INSERT INTO gastos (id_controle, usuario, tipo_gasto, valor, data_gasto) VALUES (?, ?, ?, ?, ?)");
        $insert->bind_param("issds", $id_controle, $usuario_logado, $tipo_gasto, $valor, $data_gasto);

        if ($insert->execute()) {
            recalcularControle($conn, $id_controle, $id_usuario);
            header("Location: detalhes.php?id=" . $id_controle);
            exit;
        } else {
            $mensagem = "Erro ao adicionar gasto.";
        }
    }
}

function recalcularControle($conn, $id_controle, $id_usuario) {
    $sqlGastos = $conn->prepare("SELECT SUM(valor) AS total FROM gastos WHERE id_controle = ?");
    $sqlGastos->bind_param("i", $id_controle);
    $sqlGastos->execute();
    $resultadoGastos = $sqlGastos->get_result();
    $dadosGastos = $resultadoGastos->fetch_assoc();
    $total_gastos = floatval($dadosGastos["total"]);

    $sqlReceitas = $conn->prepare("SELECT SUM(valor) AS total FROM receitas WHERE id_controle = ?");
    $sqlReceitas->bind_param("i", $id_controle);
    $sqlReceitas->execute();
    $resultadoReceitas = $sqlReceitas->get_result();
    $dadosReceitas = $resultadoReceitas->fetch_assoc();
    $total_receitas_adicionais = floatval($dadosReceitas["total"]);

    $sqlControle = $conn->prepare("SELECT salario FROM controle_financeiro WHERE id_controle = ? AND id_usuario = ?");
    $sqlControle->bind_param("ii", $id_controle, $id_usuario);
    $sqlControle->execute();
    $resultadoControle = $sqlControle->get_result();
    $dadosControle = $resultadoControle->fetch_assoc();

    $salario = floatval($dadosControle["salario"]);
    $total_rendas = $salario + $total_receitas_adicionais;
    $sobra = $total_rendas - $total_gastos;
    $porcentagem = ($total_rendas > 0) ? ($total_gastos / $total_rendas) * 100 : 0;

    if ($sobra > 0 && $porcentagem <= 70) {
        $situacao = "Controlado";
    } elseif ($sobra > 0 && $porcentagem <= 100) {
        $situacao = "Alerta";
    } else {
        $situacao = "Endividado";
    }

    $updateControle = $conn->prepare("UPDATE controle_financeiro
    SET total_rendas = ?, total_gastos = ?, sobra = ?, porcentagem_gasta = ?, situacao = ?
    WHERE id_controle = ? AND id_usuario = ?");

    $updateControle->bind_param("ddddsii", $total_rendas, $total_gastos, $sobra, $porcentagem, $situacao, $id_controle, $id_usuario);
    $updateControle->execute();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Adicionar gasto - SmartMoney</title>
    <style>
        :root {
            --fundo: linear-gradient(to right, #4facfe, #00f2fe);
            --card: white;
            --texto: #222;
            --texto-secundario: #555;
            --input: white;
            --borda: #ccc;
            --azul: #4facfe;
            --botao: #4facfe;
            --botao-texto: white;
        }

        body.tema-escuro {
            --fundo: linear-gradient(to right, #141e30, #243b55);
            --card: #1f2937;
            --texto: #f5f5f5;
            --texto-secundario: #d1d5db;
            --input: #111827;
            --borda: #4b5563;
            --azul: #38bdf8;
            --botao: #111827;
            --botao-texto: #f5f5f5;
        }

        body {
            margin: 0;
            padding: 30px;
            font-family: Arial, sans-serif;
            background: var(--fundo);
            color: var(--texto);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            background: var(--card);
            color: var(--texto);
            padding: 25px;
            border-radius: 12px;
            width: 430px;
            box-shadow: 0 10px 15px rgba(0,0,0,0.2);
        }

        h2 { text-align: center; }

        .aviso {
            background: rgba(79, 172, 254, 0.12);
            border: 1px solid var(--borda);
            padding: 12px;
            border-radius: 10px;
            color: var(--texto-secundario);
            margin-bottom: 15px;
            font-size: 14px;
        }

        label {
            display: block;
            margin-top: 12px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border-radius: 10px;
            border: 1px solid var(--borda);
            box-sizing: border-box;
            background: var(--input);
            color: var(--texto);
            font-family: Arial, sans-serif;
        }

        button {
            width: 100%;
            padding: 10px;
            margin-top: 18px;
            border-radius: 10px;
            background: var(--botao);
            color: var(--botao-texto);
            border: none;
            font-weight: bold;
            cursor: pointer;
        }

        .mensagem {
            color: red;
            text-align: center;
            font-weight: bold;
        }

        .links { text-align: center; margin-top: 15px; }
        .links a { color: var(--azul); text-decoration: none; font-weight: bold; }

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
    <h2>Adicionar gasto esquecido</h2>

    <div class="aviso">
        Este gasto será incluído no histórico selecionado. Depois disso, o sistema recalcula automaticamente o total de gastos, a sobra, a porcentagem e a situação financeira.
    </div>

    <?php if (!empty($mensagem)) { ?>
        <p class="mensagem"><?php echo $mensagem; ?></p>
    <?php } ?>

    <form method="post">
        <label>Nome do gasto</label>
        <input type="text" name="tipo_gasto" placeholder="Ex: mercado, transporte, remédio" required>

        <label>Valor</label>
        <input type="number" step="0.01" min="0.01" name="valor" placeholder="Ex: 50.00" required>

        <label>Data do gasto</label>
        <input type="date" name="data_gasto" required>

        <button type="submit">Salvar gasto no histórico</button>
    </form>

    <div class="links">
        <a href="detalhes.php?id=<?php echo $id_controle; ?>">Voltar aos detalhes</a>
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
