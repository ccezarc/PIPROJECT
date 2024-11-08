<?php
include 'config.php';
include 'get-cliente.php';

session_start();

function formatCPF($cpf) {
    if (preg_match('/^\d{11}$/', $cpf)) {
        return preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $cpf);
    }
    return $cpf;
}

function formatCNPJ($cnpj) {
    if (preg_match('/^\d{14}$/', $cnpj)) {
        return preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $cnpj);
    }
    return $cnpj;
}

function formatValor($valor) {
    return 'R$ ' . number_format(floatval($valor) / 100, 2, ',', '.');
}

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

$clientes = [];
try {
    $stmt = $conn->prepare("SELECT id_cliente, nome_cliente, cpf, endereco_cliente FROM clientes WHERE id_usuario = :id_usuario");
    $stmt->bindParam(':id_usuario', $userId);
    $stmt->execute();
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar clientes: " . $e->getMessage());
}

$nome_empresa = htmlspecialchars($userData['nome_empresa']);
$cnpj_empresa = formatCNPJ(htmlspecialchars($userData['cnpj_empresa']));
$endereco_empresa = htmlspecialchars($userData['endereco_empresa']);



$_SESSION['nome_empresa'] = $nome_empresa;
$_SESSION['cnpj_empresa'] = $cnpj_empresa;
$_SESSION['endereco_empresa'] = $endereco_empresa;



?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aluminnium</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="gerar-contrato.css">
    <script>
        function preencherDados(cliente) {
            if (cliente) {
                const dados = JSON.parse(cliente);
                document.getElementById('cpf_contratante').value = dados.cpf;
                document.getElementById('endereco_contratante').value = dados.endereco_cliente;
                document.getElementById('nome_contratante').value = dados.nome_cliente; 
            } else {
                document.getElementById('cpf_contratante').value = '';
                document.getElementById('endereco_contratante').value = '';
                document.getElementById('nome_contratante').value = '';
            }
        }

        function formatarValor(input) {

            let valor = input.value.replace(/\D/g, '');


            document.getElementById(input.name + '_raw').value = valor; 


            if (valor) {
                input.value = (parseFloat(valor) / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
            } else {
                input.value = '';
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="navbar">
            <h1>CRIAR CONTRATO</h1>
            <button class="btn-back" onclick="window.location.href='area-usuario.php'">Voltar</button>
        </div>

        <div class="sidebar">
            <div class="box">
                <h2>Preencha os dados do contrato</h2>
                <form class="contract-form" method="POST">
                    <div class="cliente-group">
                        <select id="cliente_select" name="cliente_select" onchange="preencherDados(this.options[this.selectedIndex].getAttribute('data-cliente'))" class="form-input" required>
                            <option value="">Selecione um cliente</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?php echo $cliente['id_cliente']; ?>" data-cliente='<?php echo json_encode($cliente); ?>'>
                                    <?php echo htmlspecialchars($cliente['nome_cliente']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <input type="hidden" id="nome_contratante" name="nome_contratante">

                        <input type="text" id="cpf_contratante" name="cpf_contratante" placeholder="CPF do Contratante" maxlength="11" required readonly class="form-input">

                        <input type="text" id="endereco_contratante" name="endereco_contratante" placeholder="Endereço do Contratante" required readonly class="form-input">
                    </div>

                    <textarea id="descricao_servico" name="descricao_servico" rows="4" placeholder="Digite aqui a descrição do serviço prestado..." required class="form-input"></textarea>

                    <input type="text" id="valor_total" name="valor_total" placeholder="Valor Total" required class="form-input" oninput="formatarValor(this)">
                    <input type="hidden" id="valor_total_raw" name="valor_total_raw">

                    <div class="valor-group">
                        <input type="text" id="valor_entrada" name="valor_entrada" placeholder="Valor Entrada" required class="form-input" oninput="formatarValor(this)">
                        <input type="hidden" id="valor_entrada_raw" name="valor_entrada_raw">

                        <input type="text" id="valor_restante" name="valor_restante" placeholder="Valor Restante" required class="form-input" oninput="formatarValor(this)">
                        <input type="hidden" id="valor_restante_raw" name="valor_restante_raw">
                    </div>

                    <textarea id="forma_pagamento" name="forma_pagamento" placeholder="Forma de Pagamento (Ex: 50% de entrada, restante na entrega da obra)" required class="form-input"></textarea>

                    <div class="data-group">
                        <label for="data_contrato">Data Atual:</label>
                        <input type="date" id="data_contrato" name="data_contrato" required class="form-input">

                        <label for="data_entrega">Prazo de Entrega:</label>
                        <input type="date" id="data_entrega" name="data_entrega" required class="form-input">
                    </div>
                    <button type="submit">Gerar Contrato</button>
                </form>
            </div>
        </div>

        <div class="content">
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {

                $clienteId = htmlspecialchars($_POST['cliente_select'] ?? '');
            

                $_SESSION['id_cliente'] = $clienteId;

                $nome_contratante = htmlspecialchars($_POST['nome_contratante'] ?? '');
                $cpf_contratante = formatCPF(htmlspecialchars($_POST['cpf_contratante'] ?? ''));
                $endereco_contratante = htmlspecialchars($_POST['endereco_contratante'] ?? '');
                $descricao_servico = htmlspecialchars($_POST['descricao_servico'] ?? '');


                $valor_total = $_POST['valor_total_raw'] ?? '0';
                $valor_entrada = $_POST['valor_entrada_raw'] ?? '0';
                $valor_restante = $_POST['valor_restante_raw'] ?? '0';


                $valor_total_formatado = formatValor($valor_total);
                $valor_entrada_formatado = formatValor($valor_entrada);
                $valor_restante_formatado = formatValor($valor_restante);


                $forma_pagamento = htmlspecialchars($_POST['forma_pagamento'] ?? '');
                $data_contrato = htmlspecialchars($_POST['data_contrato'] ?? '');
                $data_entrega = htmlspecialchars($_POST['data_entrega'] ?? '');


                $_SESSION['nome_contratante'] = $nome_contratante;
                $_SESSION['cpf_contratante'] = $cpf_contratante;
                $_SESSION['endereco_contratante'] = $endereco_contratante;
                $_SESSION['descricao_servico'] = $descricao_servico;
                $_SESSION['valor_total'] = $valor_total; 
                $_SESSION['valor_entrada'] = $valor_entrada; 
                $_SESSION['valor_restante'] = $valor_restante; 
                $_SESSION['forma_pagamento'] = $forma_pagamento;
                $_SESSION['data_contrato'] = $data_contrato;
                $_SESSION['data_entrega'] = $data_entrega;


                $nome_empresa = htmlspecialchars($userData['nome_empresa']);
                $cnpj_empresa = formatCNPJ(htmlspecialchars($userData['cnpj_empresa']));
                $endereco_empresa = htmlspecialchars($userData['endereco_empresa']);

                echo '<div class="contract-output">';
                echo '<h1>Contrato de Prestação de Serviços</h1>';
                echo '<h3>Identificação das Partes</h3>';
                echo '<p>CONTRATANTE:  <strong>' . $nome_contratante . '</strong> devidamente inscrito no CPF: <strong>' . $cpf_contratante . '</strong>,<br> Endereço: <strong>' . $endereco_contratante . '</strong>.</p>';
                echo '<p>CONTRATADA: <strong>' . $nome_empresa . '</strong>, pessoa jurídica, devidamente inscrita no CNPJ sob o n.º <strong>' . $cnpj_empresa . '</strong>, com sede na <strong>' . $endereco_empresa . '</strong>.</p>';
                echo '<p>As partes acima identificadas têm, entre si, justo e acertado o presente Contrato de Prestação de Serviços, que se regerá pelas cláusulas seguintes e pelas condições de preço, forma e termo de pagamento descritas no presente.</p>';
                
                echo '<h3>DO OBJETO DO CONTRATO</h3>';
                echo '<p><strong>Cláusula 1ª:</strong> É objeto do presente contrato a prestação do serviço de <strong>' . $descricao_servico . '</strong>.</p>';
                
                echo '<h3>DO PREÇO E DAS CONDIÇÕES DE PAGAMENTO</h3>';
                echo '<p><strong>Cláusula 2ª:</strong> O presente serviço será remunerado pelo valor total de <strong>' . $valor_total_formatado . '</strong>,<br> sendo <strong>' . $valor_entrada_formatado . '</strong> de entrada e o restante no valor de <strong>' . $valor_restante_formatado . '</strong>.<br>
                Os pagamentos serão efetuados da seguinte forma <strong>' . $forma_pagamento . '</strong>.</p>';
                echo '<h3>OBRIGAÇÕES DA CONTRATADA</h3>';
                echo '<p><strong>Cláusula 3ª:</strong> A CONTRATADA obriga-se a executar o serviço discriminado, empregando exclusivamente materiais e mão de obra de boa qualidade, sendo estes fornecidos pela mesma.</p>
                <p><strong>Cláusula 4ª:</strong> É dever da CONTRATADA oferecer ao CONTRATANTE a cópia do presente instrumento, contendo todas as especificidades da prestação do serviço contratado.</p>
                <p><strong>Parágrafo único:</strong><p>Prazo de entrega da obra até: <strong>' . date('d/m/Y', strtotime($data_contrato)) . '</strong>.</p> Em caso de eventuais atrasos, decorrentes de fatos alheios às vontades das partes, a CONTRATADA informará o CONTRATANTE de maneira prévia.</p>';
                
                echo '<h3>OBRIGAÇÕES DO CONTRATANTE</h3>';
                echo '<p><strong>Cláusula 5ª:</strong> O CONTRATANTE deverá efetuar o pagamento na forma e condições estabelecidas na cláusula 2ª.</p>';
                
                echo '<h3>FORÇA MAIOR E CASO FORTUITO</h3>
                <p><strong>Cláusula 6ª:</strong> O CONTRATANTE fica ciente de que a CONTRATADA não responde pelos prejuízos resultantes de caso fortuito ou força maior, decorrentes de eventos da natureza e ações da vontade humana.</p>
                <p><strong>Parágrafo único:</strong> O presente contrato é regido pela Lei 8.078/1990, estando o CONTRATANTE de plena ciência de todos os termos avençados.</p><br>
                <p>Data atual: <strong>' . date('d/m/Y', strtotime($data_contrato)) . '</strong>.</p>';

                echo '        <div class="signature">
                <div>________________________________<br>ASSINATURA DO CONTRATANTE:<br></div>
                <div>________________________________<br>ASSINATURA DA CONTRATADA:<br></div>
            </div>';

                echo '<div class="button-group">';
                echo '<button type="button" onclick="window.location.href=\'gerar-contrato-pdf.php?gerar_pdf=true\'">Baixar PDF</button>';
                echo '<button onclick="window.location.href=\'gerar-contrato.php\'">Criar Novo Contrato</button>';
                echo '</div>';                
                echo '</div>';
            }
            ?>
        </div>
    </div>
<script>
l
function abrirModal() {
    document.getElementById('modal-confirmacao').style.display = 'block';
}


function fecharModal() {
    document.getElementById('modal-confirmacao').style.display = 'none';
    

    window.location.href = 'gerar-contrato.php';
}


document.querySelector('button[onclick^="window.location.href=\'gerar-contrato-pdf.php"]').addEventListener('click', function(event) {
    event.preventDefault(); 
    abrirModal();  
});


</script>

<div id="modal-confirmacao" class="modal">
    <div class="modal-content">
        <span class="close" onclick="fecharModal()">&times;</span>
        <p>Seu PDF foi baixado e salvo no seu Historico com sucesso!</p>
        <button class="modal-btn" onclick="fecharModal()">Fechar</button>
    </div>
</div>

</body>
</html>
