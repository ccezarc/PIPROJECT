<?php
include 'config.php';
session_start(); 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_produto = intval($_POST['id_produto']);
    $descricao = trim($_POST['descricao']);
    $tipo_categoria = trim($_POST['tipo_categoria']);

    try {
        if ($tipo_categoria === 'vidro' || $tipo_categoria === 'cor') {
            $valor_extra = floatval($_POST['valor_extra']); 
            $update = $conn->prepare("UPDATE produtos SET descricao = :descricao, valor_extra = :valor_extra, tipo_categoria = :tipo_categoria WHERE id_produto = :id_produto");
            $update->bindValue(":valor_extra", $valor_extra);
        } else {
            $valor = floatval($_POST['valor']); 
            $update = $conn->prepare("UPDATE produtos SET descricao = :descricao, valor = :valor, tipo_categoria = :tipo_categoria WHERE id_produto = :id_produto");
            $update->bindValue(":valor", $valor);
        }


        $update->bindValue(":descricao", $descricao);
        $update->bindValue(":tipo_categoria", $tipo_categoria);
        $update->bindValue(":id_produto", $id_produto, PDO::PARAM_INT);


        if ($update->execute()) {
            header("Location: area-adm.php"); 
            exit;
        } else {
            echo "Erro ao atualizar o produto.";
        }
    } catch (PDOException $e) {
        echo "Erro na atualização: " . $e->getMessage();
    }
} else {
    echo "Método de requisição inválido.";
}
?>
