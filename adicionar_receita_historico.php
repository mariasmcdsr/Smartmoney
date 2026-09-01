<?php
session_start();
if (!isset($_SESSION["id_usuario"])) { header("Location: login.php"); exit; }

require_once "classes/Database.php";
require_once "classes/Formatador.php";
require_once "classes/Financas.php";

$db = (new Database())->conectar();
$financas = new Financas($db);

$id_usuario = $_SESSION["id_usuario"];
$usuario_logado = $_SESSION["usuario"] ?? "";
if (!isset($_GET["id"])) die("ID não informado.");
$id_controle = intval($_GET["id"]);

// Busca dados e valida
$sql = $db->prepare("SELECT moeda FROM controle_financeiro WHERE id_controle = ? AND id_usuario = ?");
$sql->bind_param("ii", $id_controle, $id_usuario);
$sql->execute();
$resultado = $sql->get_result();
if ($resultado->num_rows == 0) die("Histórico não encontrado.");
$moeda = Formatador::moedaPermitida($resultado->fetch_assoc()["moeda"]);

$mensagem = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tipo = trim($_POST["tipo_receita"]);
    $valor = floatval($_POST["valor"]);
    $data = $_POST["data_receita"];

    if (empty($tipo) || empty($data) || $valor <= 0) {
        $mensagem = "Preencha todos os dados corretamente.";
    } else {
        $insert = $db->prepare("INSERT INTO receitas (id_controle, usuario, tipo_receita, valor, data_receita) VALUES (?, ?, ?, ?, ?)");
        $insert->bind_param("issds", $id_controle, $usuario_logado, $tipo, $valor, $data);
        if ($insert->execute()) {
            $financas->recalcularControle($id_controle, $id_usuario);
            header("Location: detalhes.php?id=" . $id_controle);
            exit;
        } else {
            $mensagem = "Erro ao adicionar.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar receita - SmartMoney</title>
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
    <link rel="stylesheet" href="responsivo.css">
</head>

<body>
<script>if(localStorage.getItem('tema') === 'escuro') document.body.classList.add('tema-escuro');</script>

<button class="btn-tema" onclick="trocarTema()" id="btnTema">🌙 Tema escuro</button>

<div class="container">
    <h2>Adicionar receita esquecida</h2>

    <div class="aviso">
        Esta receita será incluído no histórico selecionado. Depois disso, o sistema recalcula automaticamente o total de receitas, a sobra, a porcentagem e a situação financeira.
    </div>

    <?php if (!empty($mensagem)) { ?>
        <p class="mensagem"><?php echo $mensagem; ?></p>
    <?php } ?>

    <form method="post">
        <label>Nome do receita</label>
        <input type="text" name="tipo_receita" placeholder="Ex: mercado, transporte, remédio" required>

        <label>Valor (<?php echo htmlspecialchars(Formatador::simboloMoeda($moeda)); ?>)</label>
        <input type="number" step="0.01" min="0.01" name="valor" placeholder="Ex: 50.00" required>

        <label>Data do receita</label>
        <input type="date" name="data_receita" required>

        <button type="submit">Salvar receita no histórico</button>
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
