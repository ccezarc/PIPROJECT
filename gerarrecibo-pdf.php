<?php
require 'vendor/autoload.php'; 

include 'config.php'; 
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit();
}

// Recebendo os dados do formulário
$nome = isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : '';
$cpf = isset($_POST['cpf']) ? htmlspecialchars($_POST['cpf']) : '';
$clienteId = isset($_POST['id_cliente']) ? intval($_POST['id_cliente']) : null; 
$valor = isset($_POST['valor']) ? htmlspecialchars($_POST['valor']) : '';
$data = isset($_POST['data']) ? htmlspecialchars($_POST['data']) : '';
$descricao = isset($_POST['descricao']) ? htmlspecialchars($_POST['descricao']) : '';


$userId = $_SESSION['id_usuario'];
$empresa = null;

try {
    $stmt_empresa = $conn->prepare("SELECT nome_empresa, cnpj_empresa, endereco_empresa, email_empresa, telefone_empresa, logotipo_empresa FROM usuarios WHERE id_usuario = :id_usuario");
    $stmt_empresa->bindParam(':id_usuario', $userId, PDO::PARAM_INT);
    $stmt_empresa->execute();

    $empresa = $stmt_empresa->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar informações da empresa: " . $e->getMessage());
}


$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4-L',
    'margin_left' => 15,
    'margin_right' => 15,
    'margin_top' => 20,
    'margin_bottom' => 20,
    'margin_header' => 10,
    'margin_footer' => 10
]);


if ($empresa) {
    $mpdf->SetWatermarkText(htmlspecialchars($empresa['nome_empresa']));
    $mpdf->showWatermarkText = true;
    $mpdf->watermarkTextAlpha = 0.1; 
}


function numeroPorExtenso($valor) {
    $unidade = [
        '', 'um', 'dois', 'três', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove', 'dez',
        'onze', 'doze', 'treze', 'quatorze', 'quinze', 'dezesseis', 'dezessete', 'dezoito', 'dezenove'
    ];
    $dezenas = [
        '', '', 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa'
    ];
    $centenas = [
        '', 'cem', 'duzentos', 'trezentos', 'quatrocentos', 'quinhentos', 'seiscentos', 'setecentos', 'oitocentos', 'novecentos'
    ];

    $inteiro = intval($valor);
    $centavos = round(($valor - $inteiro) * 100);
    $resultado = '';

    if ($inteiro >= 1000) {
        $milhares = intval($inteiro / 1000);
        $resultado .= $unidade[$milhares] . ' mil ';
        $inteiro %= 1000;

        if ($inteiro > 0) {
            $resultado .= 'e ';
        }
    }

    if ($inteiro >= 100) {
        $resultado .= $centenas[intval($inteiro / 100)] . ' ';
        $inteiro %= 100;
    }

    if ($inteiro >= 20) {
        $resultado .= $dezenas[intval($inteiro / 10)] . ' ';
        $inteiro %= 10;
    }

    if ($inteiro > 0) {
        $resultado .= $unidade[$inteiro] . ' ';
    }

    $resultado = trim($resultado) . ' reais';

    if ($centavos > 0) {
        $resultado .= ' e ' . $centavos . ' centavos';
    }

    return $resultado;
}


$valorNumerico = floatval($valor);
$valorExtenso = numeroPorExtenso($valorNumerico);

$html = '
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Pagamento</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 19px;
        }
        .logo {
            width: 150px;
        }
        .info-empresa {
            margin-bottom: 20px;
            border: 2px solid #000;
            padding: 10px;
            border-radius: 20px;
        }
        .highlight {
            font-weight: bold;
            color: #ff4d4d;
        }
        h3 {
           text-align: center;
           margin-bottom: 20px;
           color: #0d7377;
           font-size: 25px;
        }
    </style>
</head>
<body>
    <h3>Recibo de Pagamento</h3>';


if ($empresa) {
    $html .= '
    <div class="info-empresa">
        <table>
            <tr>
                <td><img src="' . htmlspecialchars($empresa['logotipo_empresa']) . '" alt="Logomarca" class="logo"></td>
                <td>
                    <strong>Empresa:</strong> ' . htmlspecialchars($empresa['nome_empresa']) . '<br>
                    <strong>CNPJ:</strong> ' . htmlspecialchars($empresa['cnpj_empresa']) . '<br>
                    <strong>Endereço:</strong> ' . htmlspecialchars($empresa['endereco_empresa']) . '<br>
                    <strong>Email:</strong> ' . htmlspecialchars($empresa['email_empresa']) . '<br>
                    <strong>Telefone:</strong> ' . htmlspecialchars($empresa['telefone_empresa']) . '
                </td>
            </tr>
        </table>
    </div>';
}

$html .= '
    <p><span class="highlight">Data:</span> ' . date('d/m/Y', strtotime($data)) . '</p>
    <p>Recebi(emos) de <span class="highlight">' . $nome . '</span> CPF: <span class="highlight">' . $cpf . '</span></p>
    <p>
        A importância de 
        <span class="highlight" style="color: #ff4d4d;">R$ ' . number_format($valorNumerico, 2, ',', '.') . '</span> 
        <span style="color: #000;">(' . $valorExtenso . ')</span>
    </p>
    <p>Referente à <span class="highlight">' . $descricao . '</span></p>
    <p>Para maior clareza firmo(amos) o presente recibo para que produza os seus efeitos, dando plena, rasa e irrevogável quitação, pelo valor recebido.</p>
    <hr>
</body>
</html>';


if (!file_exists('recibos')) {
    mkdir('recibos', 0777, true);
}


$pdfFilePath = __DIR__ . '/recibos/recibo_' . time() . '.pdf'; 
$mpdf->WriteHTML($html);

$mpdf->Output($pdfFilePath, 'F');


$mpdf->Output('recibo_' . time() . '.pdf', 'D');

try {
    $conn->beginTransaction();

 
    $stmt_recibo = $conn->prepare("INSERT INTO recibos (id_usuario, id_cliente) VALUES (:id_usuario, :id_cliente)");
    $stmt_recibo->bindParam(':id_usuario', $userId, PDO::PARAM_INT);
    $stmt_recibo->bindParam(':id_cliente', $clienteId, PDO::PARAM_INT);
    $stmt_recibo->execute();
    $reciboId = $conn->lastInsertId(); 


    $stmt_historico = $conn->prepare("INSERT INTO historicos (id_recibo, tipo_documento, caminho_pdf, data_criacao_historico, id_usuario) VALUES (:id_recibo, :tipo_documento, :caminho_pdf, NOW(), :id_usuario)");
    $tipo_documento = 'recibo';
    $stmt_historico->bindParam(':id_recibo', $reciboId, PDO::PARAM_INT);
    $stmt_historico->bindParam(':tipo_documento', $tipo_documento, PDO::PARAM_STR);
    $stmt_historico->bindParam(':caminho_pdf', $pdfFilePath, PDO::PARAM_STR);
    $stmt_historico->bindParam(':id_usuario', $userId, PDO::PARAM_INT);
    $stmt_historico->execute();

    $conn->commit();
    echo "Recibo gerado e salvo com sucesso!";
} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Erro ao salvar o recibo ou histórico: " . $e->getMessage());
    echo "Erro ao salvar o recibo: " . $e->getMessage();
}
?>