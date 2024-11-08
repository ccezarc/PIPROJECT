<?php
include 'config.php';
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['id_usuario'];
$userData = [];

try {
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id_usuario = :id_usuario");
    $stmt->bindParam(':id_usuario', $userId);
    $stmt->execute();
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar dados do usuário: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_usuario = filter_input(INPUT_POST, 'nome_usuario', FILTER_SANITIZE_STRING);
    $email_usuario = filter_input(INPUT_POST, 'email_usuario', FILTER_SANITIZE_EMAIL);
    $senha_usuario = filter_input(INPUT_POST, 'senha_usuario', FILTER_SANITIZE_STRING);
    $nome_empresa = filter_input(INPUT_POST, 'nome_empresa', FILTER_SANITIZE_STRING);
    $cnpj_empresa = filter_input(INPUT_POST, 'cnpj_empresa', FILTER_SANITIZE_STRING);
    $cnpj_empresa = preg_replace('/\D/', '', $cnpj_empresa); 
    $endereco_empresa = filter_input(INPUT_POST, 'endereco_empresa', FILTER_SANITIZE_STRING);
    $email_empresa = filter_input(INPUT_POST, 'email_empresa', FILTER_SANITIZE_EMAIL);
    $telefone_empresa = filter_input(INPUT_POST, 'telefone_empresa', FILTER_SANITIZE_STRING);

    $errors = [];

    if (empty($nome_usuario)) {
        $errors[] = "Nome de usuário é obrigatório.";
    }

    if (!filter_var($email_usuario, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email inválido.";
    }

    if (strlen($cnpj_empresa) !== 14) {
        $errors[] = "CNPJ deve ter 14 dígitos.";
    }

    if (isset($_FILES['fileToUpload']) && $_FILES['fileToUpload']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['fileToUpload']['tmp_name'];
        $fileName = $_FILES['fileToUpload']['name'];
        $fileSize = $_FILES['fileToUpload']['size'];
        $fileType = $_FILES['fileToUpload']['type'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $maxFileSize = 5 * 1024 * 1024; 

        if (!in_array($fileExtension, $allowedExtensions)) {
            $errors[] = "Formato de arquivo inválido. Permitidos: JPG, JPEG, PNG, GIF.";
        }

        if ($fileSize > $maxFileSize) {
            $errors[] = "O tamanho do arquivo excede o limite de 5MB.";
        }

        if (empty($errors)) {
            $uploadFileDir = './uploads/';
            $destPath = $uploadFileDir . $fileName;

            if (!move_uploaded_file($fileTmpPath, $destPath)) {
                $errors[] = "Erro ao mover o arquivo para o diretório de upload.";
            }
        }
    }

    if (empty($errors)) {
        try {
            $sql = "UPDATE usuarios SET 
                nome_usuario = :nome_usuario, 
                email_usuario = :email_usuario,
                nome_empresa = :nome_empresa,
                cnpj_empresa = :cnpj_empresa,
                endereco_empresa = :endereco_empresa,
                email_empresa = :email_empresa,
                telefone_empresa = :telefone_empresa";

            if (!empty($senha_usuario)) {
                $sql .= ", senha_usuario = :senha_usuario";
            }

            $sql .= " WHERE id_usuario = :id_usuario";

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':nome_usuario', $nome_usuario);
            $stmt->bindParam(':email_usuario', $email_usuario);
            $stmt->bindParam(':nome_empresa', $nome_empresa);
            $stmt->bindParam(':cnpj_empresa', $cnpj_empresa);
            $stmt->bindParam(':endereco_empresa', $endereco_empresa);
            $stmt->bindParam(':email_empresa', $email_empresa);
            $stmt->bindParam(':telefone_empresa', $telefone_empresa);

            if (!empty($senha_usuario)) {
                $hashed_password = password_hash($senha_usuario, PASSWORD_DEFAULT);
                $stmt->bindParam(':senha_usuario', $hashed_password);
            }

            $stmt->bindParam(':id_usuario', $userId);
            $stmt->execute();

            $_SESSION['mensagem_sucesso'] = "Dados atualizados com sucesso!";
            header("Location: perfil-usuario.php");
            exit();

        } catch (PDOException $e) {
            $_SESSION['mensagem_erro'] = "Erro ao atualizar dados: " . $e->getMessage();
            header("Location: perfil-usuario.php");
            exit();
        }
    } else {
        $_SESSION['mensagem_erro'] = implode("<br>", $errors);
        header("Location: perfil-usuario.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil do Usuário</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="perfil-usuario.css">
</head>
<body>
<a href="area-usuario.php" class="back-button">Voltar</a>

<div class="form-container">

    <div class="form-info">
        <h1>Olá, <?php echo htmlspecialchars($userData['nome_usuario']); ?></h1>
    </div>
    <div class="gif-container">
        <img src="imagens/pug.gif" alt="GIF animado">
    </div>
    <?php if (isset($_SESSION['mensagem_sucesso'])): ?>
         <div class="modal sucesso">
          <button class="close-btn" onclick="closeModal()">×</button>
        <div class="modal-content">
            <?php echo $_SESSION['mensagem_sucesso']; unset($_SESSION['mensagem_sucesso']); ?>
        </div>
       </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['mensagem_erro'])): ?>
        <div class="modal erro">
            <button class="close-btn" onclick="closeModal()">×</button>
            <div class="modal-content">
                <?php echo $_SESSION['mensagem_erro']; unset($_SESSION['mensagem_erro']); ?>
            </div>
        </div>
    <?php endif; ?>
    <div class="details-container">
        <form action="update.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_usuario" value="<?php echo htmlspecialchars($userId); ?>">

            <div class="form-group">
                <label for="nome_usuario">Nome:</label>
                <input type="text" id="nome_usuario" name="nome_usuario" required value="<?php echo isset($userData['nome_usuario']) ? htmlspecialchars($userData['nome_usuario']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="email_usuario">Email:</label>
                <input type="email" id="email_usuario" name="email_usuario" required value="<?php echo isset($userData['email_usuario']) ? htmlspecialchars($userData['email_usuario']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="senha_usuario">Senha:</label>
                <input type="password" id="senha_usuario" name="senha_usuario" placeholder="Digite a nova senha (deixe em branco para manter)">
            </div>

            <div class="form-group">
                <label for="nome_empresa">Nome <br> Empresa:</label>
                <input type="text" id="nome_empresa" name="nome_empresa" value="<?php echo isset($userData['nome_empresa']) ? htmlspecialchars($userData['nome_empresa']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="cnpj_empresa">CNPJ:</label>
                <input type="text" id="cnpj_empresa" name="cnpj_empresa" maxlength="18" value="<?php echo isset($userData['cnpj_empresa']) ? htmlspecialchars($userData['cnpj_empresa']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="endereco_empresa">Endereço:</label>
                <input type="text" id="endereco_empresa" name="endereco_empresa" value="<?php echo isset($userData['endereco_empresa']) ? htmlspecialchars($userData['endereco_empresa']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="email_empresa">Email <br> Empresa:</label>
                <input type="email" id="email_empresa" name="email_empresa" value="<?php echo isset($userData['email_empresa']) ? htmlspecialchars($userData['email_empresa']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="telefone_empresa">Telefone:</label>
                <input type="text" id="telefone_empresa" name="telefone_empresa" maxlength="15" value="<?php echo isset($userData['telefone_empresa']) ? htmlspecialchars($userData['telefone_empresa']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="fileToUpload">Logomarca:</label>
                <input type="file" name="fileToUpload" id="fileToUpload" class="custom-file-input">
            </div>
            
            <button type="submit" class="update-button">Salvar</button>
        </form>
    </div>
</div>

</body>
<script>
    function closeModal() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => modal.style.display = 'none');
    }

    setTimeout(() => {
        closeModal();
    }, 5000);

    function mascaraCNPJ(cnpj) {
        cnpj = cnpj.replace(/\D/g, ''); 
        
  
        if (cnpj.length <= 2) {
            return cnpj; 
        } else if (cnpj.length <= 5) {
            return cnpj.replace(/(\d{2})(\d{0,3})/, '$1.$2'); 
        } else if (cnpj.length <= 8) {
            return cnpj.replace(/(\d{2})(\d{3})(\d{0,3})/, '$1.$2.$3'); 
        } else if (cnpj.length <= 12) {
            return cnpj.replace(/(\d{2})(\d{3})(\d{3})(\d{0,4})/, '$1.$2.$3/$4'); 
        } else if (cnpj.length <= 14) {
            return cnpj.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{0,2})/, '$1.$2.$3/$4-$5'); 
        }
        return cnpj.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5'); 
    }

    function mascaraTelefone(telefone) {
        telefone = telefone.replace(/\D/g, ''); 
        if (telefone.length > 10) {
            telefone = telefone.replace(/(\d{2})(\d{5})(\d)/, '($1) $2-$3'); 
        } else {
            telefone = telefone.replace(/(\d{2})(\d{4})(\d)/, '($1) $2-$3'); 
        }
        return telefone;
    }

    function aplicarMascaraCNPJ() {
        const cnpjInput = document.getElementById('cnpj_empresa');
        cnpjInput.addEventListener('input', function() {
            this.value = mascaraCNPJ(this.value);
        });
    }

    function aplicarMascaraTelefone() {
        const telefoneInput = document.getElementById('telefone_empresa');
        telefoneInput.addEventListener('input', function() {
            this.value = mascaraTelefone(this.value);
        });
    }

    window.onload = function() {
        aplicarMascaraCNPJ();
        aplicarMascaraTelefone();
    }
</script>
</html>
