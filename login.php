<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulário de Login e Cadastro</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <?php
    include "config.php";
    session_start(); 

    $alertType = '';
    $alertMessage = '';

    if (isset($_POST['cadastrar'])) {
        $nome = trim($_POST['nome']);
        $email = trim($_POST['email']);
        $senha = trim($_POST['senha']);
        $senhaRepetida = trim($_POST['senha_repetida']);

        if ($senha !== $senhaRepetida) {
            $alertType = 'danger';
            $alertMessage = 'As senhas não coincidem. Por favor, tente novamente.';
        } else {
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

            $verificaEmail = $conn->prepare("SELECT * FROM usuarios WHERE email_usuario = :email");
            $verificaEmail->bindValue(":email", $email);
            $verificaEmail->execute();

            if ($verificaEmail->rowCount() > 0) {
                $alertType = 'danger';
                $alertMessage = 'Já existe um usuário com esse e-mail.';
            } else {
                $grava = $conn->prepare("INSERT INTO usuarios (nome_usuario, email_usuario, senha_usuario, data_criacao_usuario) 
                                         VALUES (:nome, :email, :senha, current_timestamp())");
                $grava->bindValue(":nome", $nome);
                $grava->bindValue(":email", $email);
                $grava->bindValue(":senha", $senhaHash);

                if ($grava->execute()) {
                    $alertType = 'success';
                    $alertMessage = 'Cadastro realizado com sucesso! Você pode agora fazer login.';
                } else {
                    $alertType = 'danger';
                    $alertMessage = 'Erro ao cadastrar usuário. Tente novamente mais tarde.';
                }
            }
        }
    }

    if (isset($_POST['login'])) {
        $email = trim($_POST['email']);
        $senha = trim($_POST['senha']);
    
        $consulta = $conn->prepare("SELECT * FROM usuarios WHERE email_usuario = :email");
        $consulta->bindValue(":email", $email);
        $consulta->execute();
        $usuario = $consulta->fetch(PDO::FETCH_ASSOC);
    
        if ($usuario && password_verify($senha, $usuario['senha_usuario'])) {
            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['usuario_nome'] = $usuario['nome_usuario'];
    

            if ($usuario['log_tipo'] == 1) {
                header("Location: area-adm.php");
            } else {
                header("Location: area-usuario.php");
            }
            exit();
        } else {
            $alertType = 'danger';
            $alertMessage = 'Email ou senha incorretos.';
        }
    }
    
    ?>

    <div class="flip-container">
        <div class="flipper" id="flipper">
            <div class="front">
                <a href="index.php" class="icon-button">
                    <img src="imagens/homelogo.png" alt="Ícone" class="icon-image">
                </a>
                <h2>Login</h2>
                <form action="login.php" method="POST">
                    <input type="email" class="input-field" name="email" placeholder="E-mail" required>
                    <input type="password" class="input-field" name="senha" placeholder="Senha" required>
                    <button type="submit" class="button" name="login">Entrar</button>
                </form>
                <span class="toggle-button" onclick="flipCard()">Não tem conta? Cadastre-se</span>
            </div>

            <div class="back">
                <a href="index.php" class="icon-button">
                    <img src="imagens/homelogo.png" alt="Ícone" class="icon-image">
                </a>
                <h2>Cadastro</h2>
                <form action="login.php" method="POST">
                    <input type="text" class="input-field" name="nome" placeholder="Nome Completo" required>
                    <input type="email" class="input-field" name="email" placeholder="E-mail" required>
                    <input type="password" class="input-field" name="senha" placeholder="Senha" required>
                    <input type="password" class="input-field" name="senha_repetida" placeholder="Repita sua Senha" required>
                    <button type="submit" class="button" name="cadastrar">Cadastrar</button>
                </form>
                <span class="toggle-button" onclick="flipCard()">Já tem conta? Faça login</span>
            </div>
        </div>
    </div>

    <?php if ($alertType && $alertMessage): ?>
    <div class="modal" id="modalAlert">
        <div class="modal-content <?php echo $alertType; ?>">
            <span class="close-button" onclick="closeModal()">&times;</span>
            <p><?php echo $alertMessage; ?></p>
        </div>
    </div>
    <?php endif; ?>

    <script>
        function flipCard() {
            var flipper = document.getElementById("flipper");
            flipper.classList.toggle("flipped");
        }

        function closeModal() {
            var modal = document.getElementById('modalAlert');
            if (modal) {
                modal.style.opacity = '0';
                setTimeout(function() {
                    modal.style.display = 'none';
                }, 300);
            }
        }

        window.onload = function() {
            var modal = document.getElementById('modalAlert');
            if (modal) {
                setTimeout(closeModal, 1000); 
            }
        }
    </script>
</body>
</html>
