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
    die("Histórico não encontrado ou você não tem permissão para remover.");
}

$controle = $resultado->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $justificativa = trim($_POST["justificativa"] ?? "");

    if (empty($justificativa)) {
        $mensagem = "Preencha a justificativa para remover este histórico.";
    } else {
        $valor_antigo_texto = "Histórico removido | Renda principal: " . $controle["salario"] .
            " | Total de rendas: " . $controle["total_rendas"] .
            " | Total de gastos: " . $controle["total_gastos"] .
            " | Sobra: " . $controle["sobra"] .
            " | Situação: " . $controle["situacao"] .
            " | Data: " . $controle["data_registro"];

        $valor_novo_texto = "Histórico removido por completo";

        $log = $conn->prepare("INSERT INTO justificativas_edicao
        (id_usuario, usuario, tipo_registro, id_registro, campo_editado, valor_antigo, valor_novo, justificativa)
        VALUES (?, ?, 'historico', ?, 'exclusao_total', ?, ?, ?)");
        $log->bind_param("isisss", $id_usuario, $usuario_logado, $id_controle, $valor_antigo_texto, $valor_novo_texto, $justificativa);
        $log->execute();

        $deleteGastos = $conn->prepare("DELETE FROM gastos WHERE id_controle = ?");
        $deleteGastos->bind_param("i", $id_controle);
        $deleteGastos->execute();

        $deleteReceitas = $conn->prepare("DELETE FROM receitas WHERE id_controle = ?");
        $deleteReceitas->bind_param("i", $id_controle);
        $deleteReceitas->execute();

        $deleteControle = $conn->prepare("DELETE FROM controle_financeiro WHERE id_controle = ? AND id_usuario = ?");
        $deleteControle->bind_param("ii", $id_controle, $id_usuario);

        if ($deleteControle->execute()) {
            header("Location: historico.php");
            exit;
        } else {
            $mensagem = "Erro ao remover histórico.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Remover histórico - SmartMoney</title>
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
            --botao: #374151;
            --botao-texto: #f5f5f5;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background: var(--fundo);
            color: var(--texto);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            width: 430px;
            max-width: 100%;
            background: var(--card);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.25);
        }

        h2 { text-align: center; margin-top: 0; }
        p { color: var(--texto-secundario); line-height: 1.5; }
        strong { color: var(--texto); }

        textarea {
            width: 100%;
            min-height: 100px;
            padding: 10px;
            border-radius: 10px;
            border: 1px solid var(--borda);
            box-sizing: border-box;
            background: var(--input);
            color: var(--texto);
            font-family: Arial, sans-serif;
            resize: vertical;
        }

        button {
            width: 100%;
            padding: 10px;
            margin-top: 18px;
            border-radius: 10px;
            background: #dc2626;
            color: white;
            border: none;
            font-weight: bold;
            cursor: pointer;
        }

        .mensagem {
            color: red;
            text-align: center;
            font-weight: bold;
        }

        .aviso {
            background: rgba(220, 38, 38, 0.12);
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 15px;
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
    <h2>Remover histórico</h2>

    <?php if (!empty($mensagem)) { ?>
        <p class="mensagem"><?php echo $mensagem; ?></p>
    <?php } ?>

    <div class="aviso">
        <p><strong>Atenção:</strong> essa ação remove o histórico completo selecionado, incluindo os gastos e receitas vinculados a ele.</p>
        <p>Renda principal: <strong>R$ <?php echo number_format($controle["salario"], 2, ',', '.'); ?></strong></p>
        <p>Total de rendas: <strong>R$ <?php echo number_format($controle["total_rendas"], 2, ',', '.'); ?></strong></p>
        <p>Total de gastos: <strong>R$ <?php echo number_format($controle["total_gastos"], 2, ',', '.'); ?></strong></p>
    </div>

    <form method="post">
        <label>Justificativa da remoção:</label>
        <textarea name="justificativa" placeholder="Explique o motivo da remoção. Ex: histórico lançado por engano, registro duplicado, dados incorretos." required></textarea>
        <button type="submit" onclick="return confirm('Tem certeza que deseja remover este histórico completo?');">Remover histórico completo</button>
    </form>

    <div class="links">
        <a href="historico.php">Voltar</a>
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
