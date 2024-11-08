<?php
require 'vendor/autoload.php'; 
include 'config.php'; 
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit();
}

function formatValor($valor) {
    return 'R$ ' . number_format(floatval($valor) / 100, 2, ',', '.');
}

function gerarPDF() {

    if (!isset($_SESSION['nome_contratante']) || !isset($_SESSION['nome_empresa'])) {
        return; 
    }


    $nome_contratante = $_SESSION['nome_contratante'];
    $cpf_contratante = $_SESSION['cpf_contratante'];
    $endereco_contratante = $_SESSION['endereco_contratante'];
    $descricao_servico = $_SESSION['descricao_servico'];
    $valor_total = formatValor($_SESSION['valor_total']);
    $valor_entrada = formatValor($_SESSION['valor_entrada']);
    $valor_restante = formatValor($_SESSION['valor_restante']);
    $forma_pagamento = $_SESSION['forma_pagamento'];
    $data_contrato = date('d/m/Y', strtotime($_SESSION['data_contrato']));
    $data_entrega = date('d/m/Y', strtotime($_SESSION['data_entrega']));
    $nome_empresa = htmlspecialchars($_SESSION['nome_empresa']);
    $cnpj_empresa = htmlspecialchars($_SESSION['cnpj_empresa']);
    $endereco_empresa = htmlspecialchars($_SESSION['endereco_empresa']);
    $userId = $_SESSION['id_usuario'];
    $clienteId = $_SESSION['id_cliente'];


    $html = '
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Contrato de Prestação de Serviços</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 20px;
                line-height: 1.5;
            }
            h1, h2, h3 {
                text-align: center;
                color: #333;
            }
            h3 {
                margin-top: 30px;
                border-top: 2px solid #000;
                padding-bottom: 10px;
            }
            p {
                margin: 10px 0;
            }
            strong {
                color: #000;
            }
            .signature {
                margin-top: 90px;
                overflow: hidden;
            }
            .signature div {
                width: 48%;
                float: left;
                text-align: center;
                padding-top: 10px;
            }
            .signature div:last-child {
                float: right;
            }
        </style>
    </head>
    <body>
        <h1>Contrato de Prestação de Serviços</h1>
        <h3>Identificação das Partes</h3>
        <p>CONTRATANTE:  <strong>' . $nome_contratante . '</strong> devidamente inscrito no CPF: <strong>' . $cpf_contratante . '</strong>,<br> Endereço: <strong>' . $endereco_contratante . '</strong>.</p>
        <p>CONTRATADA: <strong>' . $nome_empresa . '</strong>, pessoa jurídica, devidamente inscrita no CNPJ sob o n.º <strong>' . $cnpj_empresa . '</strong>, com sede na <strong>' . $endereco_empresa . '</strong>.</p>
        <p>As partes acima identificadas têm, entre si, justo e acertado o presente Contrato de Prestação de Serviços, que se regerá pelas cláusulas seguintes e pelas condições de preço, forma e termo de pagamento descritas no presente.</p>
        
        <h3>DO OBJETO DO CONTRATO</h3>
        <p><strong>Cláusula 1ª:</strong> É objeto do presente contrato a prestação do serviço de <strong>' . $descricao_servico . '</strong>.</p>
        
        <h3>DO PREÇO E DAS CONDIÇÕES DE PAGAMENTO</h3>
        <p><strong>Cláusula 2ª:</strong> O presente serviço será remunerado pelo valor total de <strong>' . $valor_total . '</strong>,<br> sendo <strong>' . $valor_entrada . '</strong> de entrada e o restante no valor de <strong>' . $valor_restante . '</strong>.<br>
        Os pagamentos serão efetuados da seguinte forma <strong>' . $forma_pagamento . '</strong>.</p>
        
        <h3>OBRIGAÇÕES DA CONTRATADA</h3>
        <p><strong>Cláusula 3ª:</strong> A CONTRATADA obriga-se a executar o serviço discriminado, empregando exclusivamente materiais e mão de obra de boa qualidade, sendo estes fornecidos pela mesma.</p>
        <p><strong>Cláusula 4ª:</strong> É dever da CONTRATADA oferecer ao CONTRATANTE a cópia do presente instrumento, contendo todas as especificidades da prestação do serviço contratado.</p>
        <p><strong>Parágrafo único:</strong><p>Prazo de entrega da obra até: <strong>' . $data_entrega . '</strong>.</p> Em caso de eventuais atrasos, decorrentes de fatos alheios às vontades das partes, a CONTRATADA informará o CONTRATANTE de maneira prévia.</p>
        
        <h3>OBRIGAÇÕES DO CONTRATANTE</h3>
        <p><strong>Cláusula 5ª:</strong> O CONTRATANTE deverá efetuar o pagamento na forma e condições estabelecidas na cláusula 2ª.</p>
        
        <h3>FORÇA MAIOR E CASO FORTUITO</h3>
        <p><strong>Cláusula 6ª:</strong> O CONTRATANTE fica ciente de que a CONTRATADA não responde pelos prejuízos resultantes de caso fortuito ou força maior, decorrentes de eventos da natureza e ações da vontade humana.</p>
        <p><strong>Parágrafo único:</strong> O presente contrato é regido pela Lei 8.078/1990, estando o CONTRATANTE de plena ciência de todos os termos avençados.</p><br>
        <p>Data atual: <strong>' . $data_contrato . '</strong>.</p>
        <div class="signature">
            <div>________________________________<br>ASSINATURA DO CONTRATANTE:<br></div>
            <div>________________________________<br>ASSINATURA DA CONTRATADA:<br></div>
        </div>
    </body>
    </html>';

    if (!file_exists('contratos')) {
        mkdir('contratos', 0777, true);
    }
    
    $pdfFilePath = __DIR__ . '/contratos/contrato_' . time() . '.pdf'; // Caminho absoluto
    $mpdf = new \Mpdf\Mpdf([
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 20,
        'margin_bottom' => 20,
        'margin_header' => 10,
        'margin_footer' => 10
    ]);
    

    $mpdf->SetWatermarkText($nome_empresa);
    $mpdf->showWatermarkText = true;
    

    $mpdf->watermark_font = 'DejaVuSansCondensed'; 
    $mpdf->watermarkTextAlpha = 0.1; 
    
    $mpdf->WriteHTML($html);

    $mpdf->Output($pdfFilePath, 'F');

    $mpdf->Output('contrato_' . time() . '.pdf', 'D');


    include 'config.php';

    try {
        $conn->beginTransaction();


        $stmt_contrato = $conn->prepare("INSERT INTO contratos (id_usuario, id_cliente, data_criacao_contrato) VALUES (:id_usuario, :id_cliente, NOW())");
        $stmt_contrato->bindParam(':id_usuario', $userId, PDO::PARAM_INT);
        $stmt_contrato->bindParam(':id_cliente', $clienteId, PDO::PARAM_INT);
        $stmt_contrato->execute();
        $contratoId = $conn->lastInsertId();


        $stmt_historico = $conn->prepare("INSERT INTO historicos (id_contrato, tipo_documento, caminho_pdf, data_criacao_historico, id_usuario) VALUES (:id_contrato, 'contrato', :caminho_pdf, NOW(), :id_usuario)");
        $stmt_historico->bindParam(':id_contrato', $contratoId, PDO::PARAM_INT);
        $stmt_historico->bindParam(':caminho_pdf', $pdfFilePath, PDO::PARAM_STR);
        $stmt_historico->bindParam(':id_usuario', $userId, PDO::PARAM_INT);
        $stmt_historico->execute();

        $conn->commit();

        echo "Contrato gerado com sucesso. O PDF foi salvo e registrado no histórico.";

    } catch (PDOException $e) {
        $conn->rollBack();
        echo "Erro ao salvar contrato no banco de dados: " . $e->getMessage();
    }
}

gerarPDF();
?>
