<?php
include 'config.php';
session_start();


if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}


$userId = $_SESSION['id_usuario'];
$stmt = $conn->prepare("SELECT log_tipo FROM usuarios WHERE id_usuario = :id_usuario");
$stmt->bindParam(':id_usuario', $userId);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$user || $user['log_tipo'] != 1) {
    header("Location: index.php"); 
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = intval($_POST['id_usuario']);
    $nome_usuario = $_POST['nome_usuario'];
    $email_usuario = $_POST['email_usuario'];
    $log_tipo = intval($_POST['log_tipo']);


    $stmt = $conn->prepare("UPDATE usuarios SET nome_usuario = :nome_usuario, email_usuario = :email_usuario, log_tipo = :log_tipo WHERE id_usuario = :id_usuario");
    $stmt->bindParam(':nome_usuario', $nome_usuario);
    $stmt->bindParam(':email_usuario', $email_usuario);
    $stmt->bindParam(':log_tipo', $log_tipo);
    $stmt->bindParam(':id_usuario', $id_usuario);


    if ($stmt->execute()) {

        header("Location: area-adm.php?success=Usuário atualizado com sucesso!");
        exit();
    } else {

        header("Location: area-adm.php?error=Erro ao atualizar o usuário.");
        exit();
    }
} else {

    header("Location: area-adm.php");
    exit();
}
?>
