<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

include "conexao.php";

$id_usuario = $_SESSION["id_usuario"];
$usuario_logado = $_SESSION["usuario"] ?? "";

$salario = isset($_POST["salario"]) ? floatval($_POST["salario"]) : 0;

$nomes_receitas = isset($_POST["nome_receita"]) ? $_POST["nome_receita"] : [];
$valores_receitas = isset($_POST["valor_receita"]) ? $_POST["valor_receita"] : [];
$datas_receitas = isset($_POST["data_receita"]) ? $_POST["data_receita"] : [];

$nomes_gastos = isset($_POST["nome_gasto"]) ? $_POST["nome_gasto"] : [];
$valores_gastos = isset($_POST["valor_gasto"]) ? $_POST["valor_gasto"] : [];
$datas_gastos = isset($_POST["data_gasto"]) ? $_POST["data_gasto"] : [];

$total_receitas_adicionais = 0;
$total_gastos = 0;

for ($i = 0; $i < count($valores_receitas); $i++) {
    $nome = isset($nomes_receitas[$i]) ? trim($nomes_receitas[$i]) : "";
    $valor = isset($valores_receitas[$i]) ? floatval($valores_receitas[$i]) : 0;
    $data = isset($datas_receitas[$i]) ? trim($datas_receitas[$i]) : "";

    if ($nome !== "" && $valor > 0 && $data !== "") {
        $total_receitas_adicionais += $valor;
    }
}

for ($i = 0; $i < count($valores_gastos); $i++) {
    $valor = floatval($valores_gastos[$i]);
    $total_gastos += $valor;
}

$total_rendas = $salario + $total_receitas_adicionais;
$sobra = $total_rendas - $total_gastos;

if ($total_rendas > 0) {
    $porcentagem = ($total_gastos / $total_rendas) * 100;
} else {
    $porcentagem = 0;
}

if ($sobra > 0 && $porcentagem <= 70) {
    $situacao = "Controlado";
    $cor = "green";
} elseif ($sobra > 0 && $porcentagem <= 100) {
    $situacao = "Alerta";
    $cor = "orange";
} else {
    $situacao = "Endividado";
    $cor = "red";
}

$sql = $conn->prepare("INSERT INTO controle_financeiro 
(id_usuario, usuario, salario, total_rendas, total_gastos, sobra, porcentagem_gasta, situacao)
VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

$sql->bind_param(
    "isddddds",
    $id_usuario,
    $usuario_logado,
    $salario,
    $total_rendas,
    $total_gastos,
    $sobra,
    $porcentagem,
    $situacao
);

if ($sql->execute()) {
    $id_controle = $conn->insert_id;

    $receita_sql = $conn->prepare("INSERT INTO receitas 
    (id_controle, usuario, tipo_receita, valor, data_receita) 
    VALUES (?, ?, ?, ?, ?)");

    for ($i = 0; $i < count($nomes_receitas); $i++) {
        $tipo_receita = isset($nomes_receitas[$i]) ? trim($nomes_receitas[$i]) : "";
        $valor_receita = isset($valores_receitas[$i]) ? floatval($valores_receitas[$i]) : 0;
        $data_receita = isset($datas_receitas[$i]) ? trim($datas_receitas[$i]) : "";

        if ($tipo_receita !== "" && $valor_receita > 0 && $data_receita !== "") {
            $receita_sql->bind_param("issds", $id_controle, $usuario_logado, $tipo_receita, $valor_receita, $data_receita);
            $receita_sql->execute();
        }
    }

    $gasto_sql = $conn->prepare("INSERT INTO gastos 
    (id_controle, usuario, tipo_gasto, valor, data_gasto) 
    VALUES (?, ?, ?, ?, ?)");

    for ($i = 0; $i < count($nomes_gastos); $i++) {
        $tipo_gasto = trim($nomes_gastos[$i]);
        $valor_gasto = floatval($valores_gastos[$i]);
        $data_gasto = $datas_gastos[$i];

        if ($tipo_gasto !== "" && $valor_gasto >= 0 && $data_gasto !== "") {
            $gasto_sql->bind_param("issds", $id_controle, $usuario_logado, $tipo_gasto, $valor_gasto, $data_gasto);
            $gasto_sql->execute();
        }
    }

} else {
    die("Erro ao salvar no banco: " . $conn->error);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Resultado - SmartMoney</title>
    <style>
        :root {
            --fundo: linear-gradient(to right, #4facfe, #00f2fe);
            --card: white;
            --texto: #222;
            --texto-secundario: #555;
            --caixa: #f4f4f4;
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
            --azul: #38bdf8;
            --botao: #111827;
            --botao-texto: #f5f5f5;
        }

        body {
            font-family: Arial, sans-serif;
            background: var(--fundo);
            color: var(--texto);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 30px;
        }

        .container {
            background: var(--card);
            color: var(--texto);
            padding: 25px;
            border-radius: 12px;
            width: 430px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            text-align: center;
        }

        .situacao {
            font-weight: bold;
            margin-top: 15px;
            font-size: 18px;
        }

        .lista {
            text-align: left;
            margin-top: 15px;
            background: var(--caixa);
            padding: 12px;
            border-radius: 10px;
        }

        .lista h3 {
            text-align: center;
            margin-top: 0;
        }

        button {
            margin-top: 15px;
            padding: 10px;
            width: 100%;
            border: none;
            border-radius: 8px;
            background: var(--botao);
            color: var(--botao-texto);
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            opacity: 0.85;
        }

        a {
            display: block;
            margin-top: 12px;
            color: var(--azul);
            text-decoration: none;
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
    <h2>Resultado</h2>

    <p><strong>Salário / renda principal:</strong> R$ <?php echo number_format($salario, 2, ',', '.'); ?></p>
    <p><strong>Receitas adicionais:</strong> R$ <?php echo number_format($total_receitas_adicionais, 2, ',', '.'); ?></p>
    <p><strong>Total de rendas:</strong> R$ <?php echo number_format($total_rendas, 2, ',', '.'); ?></p>
    <p><strong>Total de gastos:</strong> R$ <?php echo number_format($total_gastos, 2, ',', '.'); ?></p>
    <p><strong>Sobra:</strong> R$ <?php echo number_format($sobra, 2, ',', '.'); ?></p>
    <p><strong>Porcentagem dos gastos:</strong> <?php echo number_format($porcentagem, 2, ',', '.'); ?>%</p>

    <p class="situacao" style="color: <?php echo $cor; ?>">
        <?php echo $situacao; ?>
    </p>

    <?php if ($total_receitas_adicionais > 0) { ?>
        <div class="lista">
            <h3>Receitas adicionais cadastradas</h3>
            <?php for ($i = 0; $i < count($nomes_receitas); $i++) {
                $nome = isset($nomes_receitas[$i]) ? trim($nomes_receitas[$i]) : "";
                $valor = isset($valores_receitas[$i]) ? floatval($valores_receitas[$i]) : 0;
                $data = isset($datas_receitas[$i]) ? trim($datas_receitas[$i]) : "";
                if ($nome !== "" && $valor > 0 && $data !== "") { ?>
                    <p>
                        <strong><?php echo htmlspecialchars($nome); ?>:</strong>
                        R$ <?php echo number_format($valor, 2, ',', '.'); ?>
                        <br>
                        <small>Data da receita: <?php echo date("d/m/Y", strtotime($data)); ?></small>
                    </p>
                <?php }
            } ?>
        </div>
    <?php } ?>

    <div class="lista">
        <h3>Gastos cadastrados</h3>
        <?php for ($i = 0; $i < count($nomes_gastos); $i++) { ?>
            <p>
                <strong><?php echo htmlspecialchars($nomes_gastos[$i]); ?>:</strong>
                R$ <?php echo number_format(floatval($valores_gastos[$i]), 2, ',', '.'); ?>
                <br>
                <small>Data do gasto: <?php echo date("d/m/Y", strtotime($datas_gastos[$i])); ?></small>
            </p>
        <?php } ?>
    </div>

    <form action="money.php">
        <button type="submit">Calcular novamente</button>
    </form>

    <a href="historico.php">Ver histórico</a>
    <a href="logout.php">Sair</a>
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
