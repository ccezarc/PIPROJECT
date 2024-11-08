<?php
session_start();
require 'config.php'; 

if (isset($_GET['id']) && isset($_GET['tipo_documento'])) {
    $id_historico = intval($_GET['id']);
    $tipo_documento = $_GET['tipo_documento'];

    $sql_buscar = "SELECT caminho_pdf, id_recibo, id_contrato FROM historicos WHERE id_historico = ? AND tipo_documento = ?";
    $stmt_buscar = $conn->prepare($sql_buscar);
    $stmt_buscar->execute([$id_historico, $tipo_documento]);
    $historico = $stmt_buscar->fetch(PDO::FETCH_ASSOC);

    if ($historico) {
        $sql_excluir_historico = "DELETE FROM historicos WHERE id_historico = ? AND tipo_documento = ?";
        $stmt_excluir_historico = $conn->prepare($sql_excluir_historico);
        $stmt_excluir_historico->execute([$id_historico, $tipo_documento]);


        if ($stmt_excluir_historico->rowCount()) {
            if ($tipo_documento === 'recibo' && $historico['id_recibo']) {
                $sql_excluir_recibo = "DELETE FROM recibos WHERE id_recibo = ?";
                $stmt_excluir_recibo = $conn->prepare($sql_excluir_recibo);
                $stmt_excluir_recibo->execute([$historico['id_recibo']]);
            } elseif ($tipo_documento === 'contrato' && $historico['id_contrato']) {
                $sql_excluir_contrato = "DELETE FROM contratos WHERE id_contrato = ?";
                $stmt_excluir_contrato = $conn->prepare($sql_excluir_contrato);
                $stmt_excluir_contrato->execute([$historico['id_contrato']]);
            }


            if (file_exists($historico['caminho_pdf'])) {
                unlink($historico['caminho_pdf']);
            }

            $_SESSION['mensagem_sucesso'] = 'Histórico e arquivo excluídos com sucesso!';
        } else {
            $_SESSION['mensagem_erro'] = 'Erro ao tentar excluir o histórico.';
        }
    } else {
        $_SESSION['mensagem_erro'] = 'Histórico não encontrado.';
    }


    header('Location: historico.php');
    exit();
} else {
    $_SESSION['mensagem_erro'] = 'ID do histórico ou tipo de documento inválido.';
    header('Location: historico.php');
    exit();
}
