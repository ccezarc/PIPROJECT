<?php
include 'config.php';
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit();
}

$id_usuario = $_POST['id_usuario'];
$nome_usuario = $_POST['nome_usuario'];
$email_usuario = $_POST['email_usuario'];
$senha_usuario = $_POST['senha_usuario'];
$nome_empresa = $_POST['nome_empresa'];
$cnpj_empresa = $_POST['cnpj_empresa'];
$endereco_empresa = $_POST['endereco_empresa'];
$email_empresa = $_POST['email_empresa'];
$telefone_empresa = $_POST['telefone_empresa'];

if (empty($senha_usuario)) {
    $senha_usuario = null;
} else {
    $senha_usuario = password_hash($senha_usuario, PASSWORD_DEFAULT);
}

$logotipo_empresa = null;
$uploadOk = 1;

if (isset($_FILES["fileToUpload"]) && $_FILES["fileToUpload"]["error"] == 0) {
    $targetDir = "uploads/";
    $targetFile = $targetDir . basename($_FILES["fileToUpload"]["name"]);
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
    if ($check === false) {
        $uploadOk = 0;
    }

    if (file_exists($targetFile)) {
        unlink($targetFile); 
    }

    if ($_FILES["fileToUpload"]["size"] > 1000000) { 
        $uploadOk = 0;
    }

    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        $uploadOk = 0;
    }

    if ($uploadOk == 1) {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $targetFile)) {
            $logotipo_empresa = $targetFile;
        } else {
            $_SESSION['mensagem_erro'] = "Houve um erro ao enviar seu arquivo.";
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit();
        }
    } else {
        $_SESSION['mensagem_erro'] = "Desculpe, seu arquivo não foi enviado. A imagem excede o tamanho permitido de 1MB.";
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    }
}

try {
    $stmt = $conn->prepare("SELECT logotipo_empresa FROM usuarios WHERE id_usuario = :id_usuario");
    $stmt->bindParam(':id_usuario', $id_usuario);
    $stmt->execute();
    $usuarioExistente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuarioExistente) {
        if ($logotipo_empresa && !empty($usuarioExistente['logotipo_empresa']) && file_exists($usuarioExistente['logotipo_empresa'])) {
            unlink($usuarioExistente['logotipo_empresa']);
        }

        $sql = "UPDATE usuarios SET 
                    nome_usuario = :nome_usuario, 
                    email_usuario = :email_usuario, 
                    nome_empresa = :nome_empresa, 
                    cnpj_empresa = :cnpj_empresa, 
                    endereco_empresa = :endereco_empresa, 
                    email_empresa = :email_empresa, 
                    telefone_empresa = :telefone_empresa";

        if ($senha_usuario) {
            $sql .= ", senha_usuario = :senha_usuario";
        }
        if ($logotipo_empresa) {
            $sql .= ", logotipo_empresa = :logotipo_empresa";
        }
        $sql .= " WHERE id_usuario = :id_usuario";
        $stmt = $conn->prepare($sql);

        $stmt->bindParam(':nome_usuario', $nome_usuario);
        $stmt->bindParam(':email_usuario', $email_usuario);
        if ($senha_usuario) {
            $stmt->bindParam(':senha_usuario', $senha_usuario);
        }
        $stmt->bindParam(':nome_empresa', $nome_empresa);
        $stmt->bindParam(':cnpj_empresa', $cnpj_empresa);
        $stmt->bindParam(':endereco_empresa', $endereco_empresa);
        $stmt->bindParam(':email_empresa', $email_empresa);
        $stmt->bindParam(':telefone_empresa', $telefone_empresa);
        if ($logotipo_empresa) {
            $stmt->bindParam(':logotipo_empresa', $logotipo_empresa);
        }
        $stmt->bindParam(':id_usuario', $id_usuario);

        $stmt->execute();

        $_SESSION['mensagem_sucesso'] = "Usuário atualizado com sucesso!";
    } else {
        $sql = "INSERT INTO usuarios (nome_usuario, email_usuario, senha_usuario, nome_empresa, cnpj_empresa, endereco_empresa, email_empresa, telefone_empresa, logotipo_empresa) 
                VALUES (:nome_usuario, :email_usuario, :senha_usuario, :nome_empresa, :cnpj_empresa, :endereco_empresa, :email_empresa, :telefone_empresa, :logotipo_empresa)";

        $stmt = $conn->prepare($sql);

        $stmt->bindParam(':nome_usuario', $nome_usuario);
        $stmt->bindParam(':email_usuario', $email_usuario);
        $stmt->bindParam(':senha_usuario', $senha_usuario);
        $stmt->bindParam(':nome_empresa', $nome_empresa);
        $stmt->bindParam(':cnpj_empresa', $cnpj_empresa);
        $stmt->bindParam(':endereco_empresa', $endereco_empresa);
        $stmt->bindParam(':email_empresa', $email_empresa);
        $stmt->bindParam(':telefone_empresa', $telefone_empresa);
        $stmt->bindParam(':logotipo_empresa', $logotipo_empresa);

        $stmt->execute();

        $_SESSION['mensagem_sucesso'] = "Usuário salvo com sucesso!";
    }

    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
} catch (PDOException $e) {
    error_log("Erro ao processar dados do usuário: " . $e->getMessage());
    $_SESSION['mensagem_erro'] = "Erro ao salvar os dados.";
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}
?>
