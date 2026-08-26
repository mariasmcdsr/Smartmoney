<?php
session_start();
include "conexao.php";

$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST["usuario"]);
    $senha = $_POST["senha"];

    if (empty($usuario) || empty($senha)) {
        $mensagem = "Preencha todos os campos.";
    } else {
        $sql = $conn->prepare("SELECT id_usuario, usuario, senha FROM usuarios WHERE usuario = ?");
        $sql->bind_param("s", $usuario);
        $sql->execute();
        $resultado = $sql->get_result();

        if ($resultado->num_rows == 1) {
            $dados = $resultado->fetch_assoc();

            if (password_verify($senha, $dados["senha"])) {
                $_SESSION["id_usuario"] = $dados["id_usuario"];
                $_SESSION["usuario"] = $dados["usuario"];

                header("Location: money.php");
                exit;
            } else {
                $mensagem = "Usuário ou senha incorretos.";
            }
        } else {
            $mensagem = "Usuário ou senha incorretos.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - SmartMoney</title>

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
            padding: 0;
            font-family: Arial, sans-serif;
            background: var(--fundo);
            color: var(--texto);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            transition: 0.3s;
        }

        .container {
            background: var(--card);
            color: var(--texto);
            padding: 25px;
            border-radius: 12px;
            width: 350px;
            box-shadow: 0 10px 15px rgba(0,0,0,0.2);
            text-align: center;
        }

        h2 {
            margin-bottom: 15px;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-top: 15px;
            border-radius: 10px;
            border: 1px solid var(--borda);
            box-sizing: border-box;
            background: var(--input);
            color: var(--texto);
        }

        input::placeholder {
            color: var(--texto-secundario);
        }

        .campo-senha {
            position: relative;
            width: 100%;
        }

        .campo-senha input {
            padding-right: 45px;
        }

        .campo-senha span {
            position: absolute;
            right: 12px;
            top: 25px;
            cursor: pointer;
            font-size: 17px;
            user-select: none;
        }

        button {
            width: 100%;
            padding: 10px;
            margin-top: 20px;
            border-radius: 10px;
            background: var(--botao);
            color: var(--botao-texto);
            border: none;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            opacity: 0.85;
        }

        .mensagem {
            color: red;
            margin-top: 10px;
            font-size: 14px;
        }

        a {
            display: block;
            margin-top: 15px;
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
    <h2>Login</h2>

    <form method="post">
        <input type="text" name="usuario" placeholder="Nome de usuário" required>

        <div class="campo-senha">
            <input type="password" id="senha" name="senha" placeholder="Senha" required>
            <span onclick="mostrarSenha('senha', this)">👁️</span>
        </div>

        <button type="submit">Entrar</button>
    </form>

    <?php if (!empty($mensagem)) { ?>
        <p class="mensagem"><?php echo $mensagem; ?></p>
    <?php } ?>

    <a href="cadastro.php">Criar conta</a>
</div>

<script>
function mostrarSenha(idCampo, icone) {
    const campo = document.getElementById(idCampo);

    if (campo.type === "password") {
        campo.type = "text";
        icone.textContent = "🙈";
    } else {
        campo.type = "password";
        icone.textContent = "👁️";
    }
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