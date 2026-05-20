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
    die("ID da receita não informado.");
}

$id_receita = intval($_GET["id"]);
$mensagem = "";

$sql = $conn->prepare("SELECT r.*, c.id_usuario, c.salario
FROM receitas r
INNER JOIN controle_financeiro c ON r.id_controle = c.id_controle
WHERE r.id_receita = ? AND c.id_usuario = ?");

$sql->bind_param("ii", $id_receita, $id_usuario);
$sql->execute();
$resultado = $sql->get_result();

if ($resultado->num_rows == 0) {
    die("Receita não encontrada ou você não tem permissão para editar.");
}

$receita = $resultado->fetch_assoc();
$id_controle = $receita["id_controle"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $acao = $_POST["acao"] ?? "salvar";
    $justificativa = trim($_POST["justificativa"] ?? "");
    $valor_antigo_texto = "Nome: " . $receita["tipo_receita"] . " | Valor: " . $receita["valor"] . " | Data: " . $receita["data_receita"];

    if ($acao === "excluir") {
        if (empty($justificativa)) {
            $mensagem = "Para remover a receita, preencha a justificativa.";
        } else {
            $deleteReceita = $conn->prepare("DELETE FROM receitas WHERE id_receita = ?");
            $deleteReceita->bind_param("i", $id_receita);

            if ($deleteReceita->execute()) {
                $valor_novo_texto = "Receita removida";

                $log = $conn->prepare("INSERT INTO justificativas_edicao
                (id_usuario, usuario, tipo_registro, id_registro, campo_editado, valor_antigo, valor_novo, justificativa)
                VALUES (?, ?, 'receita', ?, 'exclusao', ?, ?, ?)");

                $log->bind_param("isisss", $id_usuario, $usuario_logado, $id_receita, $valor_antigo_texto, $valor_novo_texto, $justificativa);
                $log->execute();

                recalcularControle($conn, $id_controle, $id_usuario);

                header("Location: detalhes.php?id=" . $id_controle);
                exit;
            } else {
                $mensagem = "Erro ao remover receita.";
            }
        }
    } else {
        $tipo_receita = trim($_POST["tipo_receita"] ?? "");
        $valor = floatval($_POST["valor"] ?? 0);
        $data_receita = $_POST["data_receita"] ?? "";

        if (empty($tipo_receita) || empty($data_receita) || empty($justificativa)) {
            $mensagem = "Preencha todos os campos, incluindo a justificativa.";
        } else {
            $valor_novo_texto = "Nome: " . $tipo_receita . " | Valor: " . $valor . " | Data: " . $data_receita;

            $updateReceita = $conn->prepare("UPDATE receitas 
            SET tipo_receita = ?, valor = ?, data_receita = ?
            WHERE id_receita = ?");

            $updateReceita->bind_param("sdsi", $tipo_receita, $valor, $data_receita, $id_receita);

            if ($updateReceita->execute()) {
                $log = $conn->prepare("INSERT INTO justificativas_edicao
                (id_usuario, usuario, tipo_registro, id_registro, campo_editado, valor_antigo, valor_novo, justificativa)
                VALUES (?, ?, 'receita', ?, 'nome_valor_data', ?, ?, ?)");

                $log->bind_param("isisss", $id_usuario, $usuario_logado, $id_receita, $valor_antigo_texto, $valor_novo_texto, $justificativa);
                $log->execute();

                recalcularControle($conn, $id_controle, $id_usuario);

                header("Location: detalhes.php?id=" . $id_controle);
                exit;
            } else {
                $mensagem = "Erro ao atualizar receita.";
            }
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
    <title>Editar receita - SmartMoney</title>
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
            width: 420px;
            box-shadow: 0 10px 15px rgba(0,0,0,0.2);
        }

        h2 { text-align: center; }

        label {
            display: block;
            margin-top: 12px;
            font-weight: bold;
        }

        input, textarea {
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

        textarea { min-height: 90px; resize: vertical; }

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

        .btn-remover {
            background: #dc2626;
            color: white;
        }

        .aviso-remocao {
            margin-top: 18px;
            padding: 10px;
            border-radius: 10px;
            background: rgba(220, 38, 38, 0.12);
            color: var(--texto);
            font-size: 13px;
            text-align: center;
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
    <h2>Editar receita</h2>

    <?php if (!empty($mensagem)) { ?>
        <p class="mensagem"><?php echo $mensagem; ?></p>
    <?php } ?>

    <form method="post">
        <label>Nome da receita:</label>
        <input type="text" name="tipo_receita" value="<?php echo htmlspecialchars($receita["tipo_receita"]); ?>" required>

        <label>Valor:</label>
        <input type="number" step="0.01" min="0" name="valor" value="<?php echo $receita["valor"]; ?>" required>

        <label>Data da receita:</label>
        <input type="date" name="data_receita" value="<?php echo $receita["data_receita"]; ?>" required>

        <label>Justificativa da alteração:</label>
        <textarea name="justificativa" placeholder="Explique o motivo da alteração. Ex: valor digitado errado, data corrigida, receita lançada incorretamente." required></textarea>

        <button type="submit" name="acao" value="salvar">Salvar alteração</button>

        <div class="aviso-remocao">
            Para remover esta receita, escreva a justificativa acima e clique no botão abaixo. O total do histórico será recalculado automaticamente.
        </div>

        <button type="submit" name="acao" value="excluir" class="btn-remover" onclick="return confirm('Tem certeza que deseja remover esta receita? Essa ação vai recalcular o histórico.');">Remover receita</button>
    </form>

    <div class="links">
        <a href="detalhes.php?id=<?php echo $id_controle; ?>">Voltar</a>
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
