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

if (!isset($_GET["id"])) {
    die("ID do gasto não informado.");
}

$id_gasto = intval($_GET["id"]);
$mensagem = "";

$sql = $db->prepare("SELECT g.*, c.id_usuario, c.salario, c.total_rendas FROM gastos g INNER JOIN controle_financeiro c ON g.id_controle = c.id_controle WHERE g.id_gasto = ? AND c.id_usuario = ?");
$sql->bind_param("ii", $id_gasto, $id_usuario);
$sql->execute();
$resultado = $sql->get_result();

if ($resultado->num_rows == 0) {
    die("Gasto não encontrado ou você não tem permissão para editar.");
}

$gasto = $resultado->fetch_assoc();
$id_controle = $gasto["id_controle"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $acao = $_POST["acao"] ?? "salvar";
    $justificativa = trim($_POST["justificativa"] ?? "");
    $valor_antigo_texto = "Nome: " . $gasto["tipo_gasto"] . " | Valor: " . $gasto["valor"] . " | Data: " . $gasto["data_gasto"];

    if ($acao === "excluir") {
        if (empty($justificativa)) {
            $mensagem = "Para remover o gasto, preencha a justificativa.";
        } else {
            $deleteGasto = $db->prepare("DELETE FROM gastos WHERE id_gasto = ?");
            $deleteGasto->bind_param("i", $id_gasto);

            if ($deleteGasto->execute()) {
                $valor_novo_texto = "Gasto removido";
                $log = $db->prepare("INSERT INTO justificativas_edicao (id_usuario, usuario, tipo_registro, id_registro, campo_editado, valor_antigo, valor_novo, justificativa) VALUES (?, ?, 'gasto', ?, 'exclusao', ?, ?, ?)");
                $log->bind_param("isisss", $id_usuario, $usuario_logado, $id_gasto, $valor_antigo_texto, $valor_novo_texto, $justificativa);
                $log->execute();

                // Usando a classe Financas para recalcular!
                $financas->recalcularControle($id_controle, $id_usuario);

                header("Location: detalhes.php?id=" . $id_controle);
                exit;
            } else {
                $mensagem = "Erro ao remover gasto.";
            }
        }
    } else {
        $tipo_gasto = trim($_POST["tipo_gasto"] ?? "");
        $valor = floatval($_POST["valor"] ?? 0);
        $data_gasto = $_POST["data_gasto"] ?? "";

        if (empty($tipo_gasto) || empty($data_gasto) || empty($justificativa)) {
            $mensagem = "Preencha todos os campos, incluindo a justificativa.";
        } else {
            $valor_novo_texto = "Nome: " . $tipo_gasto . " | Valor: " . $valor . " | Data: " . $data_gasto;
            $updateGasto = $db->prepare("UPDATE gastos SET tipo_gasto = ?, valor = ?, data_gasto = ? WHERE id_gasto = ?");
            $updateGasto->bind_param("sdsi", $tipo_gasto, $valor, $data_gasto, $id_gasto);

            if ($updateGasto->execute()) {
                $log = $db->prepare("INSERT INTO justificativas_edicao (id_usuario, usuario, tipo_registro, id_registro, campo_editado, valor_antigo, valor_novo, justificativa) VALUES (?, ?, 'gasto', ?, 'nome_valor_data', ?, ?, ?)");
                $log->bind_param("isisss", $id_usuario, $usuario_logado, $id_gasto, $valor_antigo_texto, $valor_novo_texto, $justificativa);
                $log->execute();

                // Usando a classe Financas para recalcular!
                $financas->recalcularControle($id_controle, $id_usuario);

                header("Location: detalhes.php?id=" . $id_controle);
                exit;
            } else {
                $mensagem = "Erro ao atualizar gasto.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar gasto - SmartMoney</title>
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
            margin-top: 12px;
            padding: 10px;
            border-radius: 10px;
            background: rgba(220, 38, 38, 0.12);
            color: var(--texto);
            font-size: 14px;
            line-height: 1.4;
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
    <h2>Editar gasto</h2>

    <?php if (!empty($mensagem)) { ?>
        <p class="mensagem"><?php echo $mensagem; ?></p>
    <?php } ?>

    <form method="post">
        <label>Nome do gasto:</label>
        <input type="text" name="tipo_gasto" value="<?php echo htmlspecialchars($gasto["tipo_gasto"]); ?>" required>

        <label>Valor:</label>
        <input type="number" step="0.01" min="0" name="valor" value="<?php echo $gasto["valor"]; ?>" required>

        <label>Data do gasto:</label>
        <input type="date" name="data_gasto" value="<?php echo $gasto["data_gasto"]; ?>" required>

        <label>Justificativa da alteração:</label>
        <textarea name="justificativa" placeholder="Explique o motivo da alteração. Ex: valor digitado errado, data corrigida, gasto lançado incorretamente." required></textarea>

        <button type="submit" name="acao" value="salvar">Salvar alteração</button>

        <div class="aviso-remocao">
            Para remover este gasto, escreva a justificativa acima e clique no botão abaixo. O total do histórico será recalculado automaticamente.
        </div>

        <button type="submit" name="acao" value="excluir" class="btn-remover" onclick="return confirm('Tem certeza que deseja remover este gasto? Essa ação vai recalcular o histórico.');">Remover gasto</button>
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
