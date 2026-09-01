<?php
session_start();
if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

require_once "classes/Database.php";
require_once "classes/Formatador.php";
require_once "classes/Financas.php";

$db = (new Database())->conectar();
$financas = new Financas($db);

$id_usuario = $_SESSION["id_usuario"];
$usuario_logado = $_SESSION["usuario"] ?? "";
$nome_controle = trim($_POST["nome_controle"] ?? "Controle financeiro");
$moeda = Formatador::moedaPermitida($_POST["moeda"] ?? "BRL");
$nome_parceiro = trim($_POST["nome_parceiro"] ?? "");
$renda_parceiro = max(0, floatval($_POST["renda_parceiro"] ?? 0));
$salario = max(0, floatval($_POST["salario"] ?? 0));

$nomes_receitas = $_POST["nome_receita"] ?? [];
$valores_receitas = $_POST["valor_receita"] ?? [];
$datas_receitas = $_POST["data_receita"] ?? [];

$nomes_gastos = $_POST["nome_gasto"] ?? [];
$valores_gastos = $_POST["valor_gasto"] ?? [];
$datas_gastos = $_POST["data_gasto"] ?? [];

$total_receitas_adicionais = 0;
$total_gastos = 0;

foreach ($valores_receitas as $i => $valor) {
    if (!empty($nomes_receitas[$i]) && floatval($valor) > 0 && !empty($datas_receitas[$i])) {
        $total_receitas_adicionais += floatval($valor);
    }
}

foreach ($valores_gastos as $valor) {
    $total_gastos += floatval($valor);
}

$total_rendas = $salario + $renda_parceiro + $total_receitas_adicionais;

$resultado = $financas->calcularSituacao($total_rendas, $total_gastos);
$sobra = $resultado["sobra"];
$porcentagem = $resultado["porcentagem"];
$situacao = $resultado["situacao"];
$cor = $resultado["cor"];

$sql = $db->prepare("INSERT INTO controle_financeiro (id_usuario, usuario, nome_controle, nome_parceiro, salario, renda_parceiro, moeda, total_rendas, total_gastos, sobra, porcentagem_gasta, situacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$sql->bind_param("isssddsdddds", $id_usuario, $usuario_logado, $nome_controle, $nome_parceiro, $salario, $renda_parceiro, $moeda, $total_rendas, $total_gastos, $sobra, $porcentagem, $situacao);

if ($sql->execute()) {
    $id_controle = $db->insert_id;
    
    $receita_sql = $db->prepare("INSERT INTO receitas (id_controle, usuario, tipo_receita, valor, data_receita) VALUES (?, ?, ?, ?, ?)");
    foreach ($nomes_receitas as $i => $nome) {
        $val = floatval($valores_receitas[$i] ?? 0);
        $dat = $datas_receitas[$i] ?? "";
        if (!empty($nome) && $val > 0 && !empty($dat)) {
            $receita_sql->bind_param("issds", $id_controle, $usuario_logado, trim($nome), $val, trim($dat));
            $receita_sql->execute();
        }
    }

    $gasto_sql = $db->prepare("INSERT INTO gastos (id_controle, usuario, tipo_gasto, valor, data_gasto) VALUES (?, ?, ?, ?, ?)");
    foreach ($nomes_gastos as $i => $nome) {
        $val = floatval($valores_gastos[$i] ?? 0);
        $dat = $datas_gastos[$i] ?? "";
        if (!empty($nome) && $val >= 0 && !empty($dat)) {
            $gasto_sql->bind_param("issds", $id_controle, $usuario_logado, trim($nome), $val, trim($dat));
            $gasto_sql->execute();
        }
    }
} else {
    die("Erro ao salvar no banco: " . $db->error);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    <link rel="stylesheet" href="responsivo.css">
</head>

<body>
<script>if(localStorage.getItem('tema') === 'escuro') document.body.classList.add('tema-escuro');</script>

<button class="btn-tema" onclick="trocarTema()" id="btnTema">🌙 Tema escuro</button>

<div class="container">
    <h2><?php echo htmlspecialchars($nome_controle); ?></h2>
    <p class="observacao"><strong>Controle financeiro</strong></p>

    <p><strong>Salário / renda principal:</strong> <?php echo Formatador::10 ($salario, $moeda); ?></p>
    <?php if ($renda_parceiro > 0 || $nome_parceiro !== "") { ?><p><strong>Segunda pessoa:</strong> <?php echo htmlspecialchars($nome_parceiro !== "" ? $nome_parceiro : "Pessoa 2"); ?> — <?php echo Formatador::10 ($renda_parceiro, $moeda); ?></p><?php } ?>
    <p><strong>Receitas adicionais:</strong> <?php echo Formatador::10($total_receitas_adicionais, $moeda); ?></p>
    <p><strong>Total de rendas:</strong> <?php echo Formatador::10($total_rendas, $moeda); ?></p>
    <p><strong>Total de gastos:</strong> <?php echo Formatador::10($total_gastos, $moeda); ?></p>
    <p><strong>Sobra:</strong> <?php echo Formatador::10($sobra, $moeda); ?></p>
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
                        <?php echo Formatador::10($valor, $moeda); ?>
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
                <?php echo 10 (floatval($valores_gastos[$i]), $moeda); ?>
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
