<?php
session_start();
include 'config.php';
include 'get-cliente.php';
require 'vendor/autoload.php'; 

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit();
}
function formatarCNPJ($cnpj) {
    return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
}

function formatarTelefone($telefone) {
    return preg_replace('/(\d{2})(\d{4,5})(\d{4})/', '($1) $2-$3', $telefone);
}

$id_usuario = $_SESSION['id_usuario'];


try {
    $stmt_empresa = $conn->prepare("SELECT nome_empresa, cnpj_empresa, endereco_empresa, email_empresa, telefone_empresa, logotipo_empresa FROM usuarios WHERE id_usuario = :id_usuario");
    $stmt_empresa->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
    $stmt_empresa->execute();
    $empresa = $stmt_empresa->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar informações da empresa: " . $e->getMessage());
}


$cliente = isset($_SESSION['id_cliente']) ? buscarCliente($conn, $_SESSION['id_cliente'], $id_usuario) : null;


$orcamentos = $_SESSION['orcamentos'] ?? [];
$total_orcamento = array_sum(array_column($orcamentos, 'valor_total'));


$mpdf = new \Mpdf\Mpdf([
    'margin_top' => 5,
    'margin_bottom' => 5,
    'margin_left' => 5,
    'margin_right' => 5,
]);


$mpdf->SetAutoPageBreak(true, 10);  
$mpdf->shrink_tables_to_fit = 1;    



$css = "
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    
}

body {
    font-family: 'Poppins', sans-serif;
    color: #333;
    font-size: 16px;
    
}

.container-central {
    width: 100%;
    padding: 0px;
    margin-bottom: 0px;
    
}

.content {
    width: 100%;
    padding: 0px;
    margin-bottom: 10px;
}

.result-container {
    width: 100%;
    margin-bottom: 20px;
}

.result-container h1 {
    color: #ff4d4d;
    font-size: 20px;
}

.result {

    border-radius: 5px;
    padding: 1px;
    
    
}

.result table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1px;
    font-family: 'DejaVu Sans Mono', monospace;
    font-size: 14px;
    

}

.result th, .result td {
    border: 1px solid #ff4d4d;
    padding: 2px;
    text-align: left;
}

.result th {
    background-color: #333;
    color: #ff4d4d;
    border: 1px solid #ffffff;
    font-family: 'DejaVu Sans', sans-serif;

}

.image-container {
    float: left; 
    width: 85px; 
    height: 85px;  
    background-color: white;
    padding: 10px;
    overflow: hidden;
    
    
}

.image-container img {
    max-width: 90%;
    max-height: 90%;
    
}

.details {
    overflow: hidden; 
    border: 2px solid #272727;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 20px;

}

.total {
    font-weight: bold;
    font-size: 20px;
    color: #4CAF50;
    text-align: left;

}

.finalizar-container {
    display: inline-block;
    text-align: center;

}

.finalizar-container button {
    background-color: #ff4d4d;
    color: white;
    padding: 10px 40px;
    border: none;
    font-size: 18px;
    font-weight: 600;
}
.cliente-container {
    border: 2px solid #272727;
    padding: 10px; 
    width: 40%; 
    max-width: 500px; 
    border-radius: 20px;
}

.cliente {
    border: 1px solid #ff4d4d;
    padding: 0px;
    border-collapse: collapse;
    font-family: 'DejaVu Serif', serif;
    font-size: 10px; 
    width: 100%; 
}

.cliente td, .cliente th {
    padding: 4px; 
    vertical-align: top;
    border: 1px solid #ff4d4d;
}

.cliente th {
    background-color: #333;
    color: #ff4d4d;
    border: 1px solid #ffffff;
    text-align: left;
    font-family: 'DejaVu Sans', sans-serif;
    font-weight: bold;
}

.empresa {
    border: 2px solid #272727;
    padding: 6px;
    margin-bottom: 5px;
    border-radius: 20px;


}

.tabela-empresa {
    border: 1px solid #ff4d4d;
    font-size: 10px; 
    width: 100%; 
    border-collapse: collapse; 
    font-family: 'DejaVu Sans', sans-serif;

}

.tabela-empresa td {
    border: 1px solid #ff4d4d;
    padding: 4px; 
    text-align: left;
    vertical-align: top; 
    
}

.tabela-empresa th {

    background-color: #333;
    color: #ff4d4d;
    border: 1px solid #ffffff;
    text-align: left;
    font-weight: bold;
}


.logo {
    float: left; 
    width: 80px;
    height: 80px;
    margin-right: 16px; 


}
.clearfix::after {
    content: '';
    display: table;
    clear: both; 
}

.image-container:after, .logo:after {
    content: '';
    display: table;
    clear: both; 
}
";


$html = '
<html>
<head>
    <style>' . $css . '</style>
</head>
<body>

<div class="empresa">';

if ($empresa) {

    $empresa_campos = [
        'nome_empresa' => 'Nome da Empresa',
        'cnpj_empresa' => 'CNPJ',
        'endereco_empresa' => 'Endereço',
        'email_empresa' => 'E-mail',
        'telefone_empresa' => 'Telefone',

    ];


    $html .= '<table style="width: 100%; margin-bottom: 0px; ">'; 
    $html .= '<tr>';
    

    $html .= '<td style="width: 80px; padding-left: 10px; text-align: center; ">'; 
    $html .= '<img src="' . htmlspecialchars($empresa['logotipo_empresa']) . '" alt="Logomarca" class="logo" style="max-width: 100%; height: auto;">'; 
    $html .= '</td>';
    

    $html .= '<td style="vertical-align: top; padding-left: 0px;">'; 
    $html .= '<table class="tabela-empresa" style="width: 100%;">'; 
    

    foreach ($empresa_campos as $campo => $titulo) {
        if ($campo === 'cnpj_empresa') {
            $html .= '<tr><th>' . $titulo . ':</th><td>' . htmlspecialchars(formatarCNPJ($empresa[$campo])) . '</td></tr>';
        } elseif ($campo === 'telefone_empresa') {
            $html .= '<tr><th>' . $titulo . ':</th><td>' . htmlspecialchars(formatarTelefone($empresa[$campo])) . '</td></tr>';
        } else {
            $html .= '<tr><th>' . $titulo . ':</th><td>' . htmlspecialchars($empresa[$campo]) . '</td></tr>';
        }
    }
    
    
    $html .= '</table>';
    $html .= '</td>';
    
    $html .= '</tr>';
    $html .= '</table>';
} else {
    $html .= '<p>Informações da empresa não encontradas.</p>';
}




$html .= '</div>
        <div class="cliente-container">';

if ($cliente) {

    $cliente_campos = [
        'nome_cliente' => 'Cliente',
        'endereco_cliente' => 'Endereço',
        'telefone_cliente' => 'Telefone',

    ];

    $html .= '<table border="1" class="cliente">';
    

    foreach ($cliente_campos as $campo => $titulo) {
        if ($campo === 'telefone_cliente') {
            $html .= '<tr><th>• ' . $titulo . '</th><td>' . htmlspecialchars(formatarTelefone($cliente[$campo])) . '</td></tr>';
        } else {
            $html .= '<tr><th>• ' . $titulo . '</th><td>' . htmlspecialchars($cliente[$campo]) . '</td></tr>';
        }


        if ($campo === 'telefone_cliente') {
            $dataAtual = date('d/m/Y'); 
            $html .= '<tr><th>• Data</th><td>' . $dataAtual . '</td></tr>';
            

            $html .= '<tr><td colspan="2" style="text-align: center;">Orçamento válido por 10 dias</td></tr>';
            $html .= '<tr><td colspan="2" style="text-align: center;">Forma de pagamento: 50% de entrada, restante a combinar</td></tr>';
        }
    }
    
    $html .= '</table>';
} else {
    $html .= '<p>Informações do cliente não encontradas.</p>';
}

$html .= '</div>';


$html .= '<p style="text-align: left; font-size: 10px; color: #ff4d4d;">*IMAGENS MERAMENTE ILUSTRATIVAS</p>';

if (!empty($orcamentos)) {
    foreach ($orcamentos as $orcamento) {
        $html .= '<div class="result">';
        $html .= '<div class="image-container">';
        $html .= '<img src="imagens_predefinidas/' . htmlspecialchars($orcamento['imagem']) . '" alt="Imagem do Orçamento">';
        $html .= '</div>';
        $html .= '<div class="details">';
        $html .= '<table style="width: 100%; page-break-inside: avoid;">';
        $html .= '<tr><th>Descrição</th><td>' . htmlspecialchars($orcamento['descricao']) . '</td></tr>';
        $html .= '<tr><th>Medidas</th><td>' . number_format($orcamento['largura_mm'], 0, ',', '.') . ' x ' . number_format($orcamento['altura_mm'], 0, ',', '.') . '</td></tr>';
        $html .= '<tr><th>Quantidade</th><td>' . htmlspecialchars($orcamento['quantidade']) . '</td></tr>';
        $html .= '<tr><th>Preço Unitário</th><td>R$ ' . number_format($orcamento['valor_unitario'], 2, ',', '.') . '</td></tr>';
        $html .= '<tr><th>Preço Total</th><td>R$ ' . number_format($orcamento['valor_total'], 2, ',', '.') . '</td></tr>';
        $html .= '<tr><th>Cor do Perfil</th><td>' . htmlspecialchars($orcamento['cor']) . '</td></tr>';
        $html .= '<tr><th>Espessura do Vidro</th><td>' . number_format($orcamento['espessura'], 0, ',', '.') . ' mm</td></tr>';
        $html .= '<tr><th>Tipo/Cor do Vidro</th><td>' . htmlspecialchars($orcamento['tipo_vidro']) . '</td></tr>';
        $html .= '</table></div></div><hr>'; 
    }
    $html .= '<div class="total">Valor Total: R$ ' . number_format($total_orcamento, 2, ',', '.') . '</div>';
} else {
    $html .= '<p style="color: red;">Não há itens no seu orçamento.</p>';
}

$html .= '</div>
    </div>
</body>
</html>';

if ($empresa && isset($empresa['nome_empresa'])) {

    $mpdf->SetWatermarkText($empresa['nome_empresa'], 0.1); 
    $mpdf->showWatermarkText = true; 
}


$mpdf->WriteHTML($html);


$nome_cliente = isset($cliente['nome_cliente']) ? $cliente['nome_cliente'] : 'cliente_desconhecido';


$nome_cliente = preg_replace('/\s+/', '_', $nome_cliente);


$data_hora_atual = date('d-m-Y_His');


$nome_arquivo_pdf = "Orçamento_{$nome_cliente}_{$data_hora_atual}.pdf";


$mpdf->Output($nome_arquivo_pdf, 'D');
exit();

