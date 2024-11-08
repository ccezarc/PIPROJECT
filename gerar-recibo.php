<?php
include 'config.php';
include 'get-cliente.php';
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['id_usuario'];
$userName = 'Usuário';
$empresa = null;

try {
    $stmt = $conn->prepare("SELECT nome_usuario FROM usuarios WHERE id_usuario = :id_usuario");
    $stmt->bindParam(':id_usuario', $userId);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $userName = htmlspecialchars($user['nome_usuario']);
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar dados do usuário: " . $e->getMessage());
}

$clientes = [];
try {
    $stmtClientes = $conn->prepare("SELECT id_cliente, nome_cliente, cpf FROM clientes WHERE id_usuario = :id_usuario");
    $stmtClientes->bindParam(':id_usuario', $userId);
    $stmtClientes->execute();
    $clientes = $stmtClientes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar clientes: " . $e->getMessage());
}

try {
    $sql_empresa = "SELECT nome_empresa, cnpj_empresa, endereco_empresa, email_empresa, telefone_empresa, logotipo_empresa FROM usuarios WHERE id_usuario = :id_usuario";
    $stmt_empresa = $conn->prepare($sql_empresa);
    $stmt_empresa->bindParam(':id_usuario', $userId, PDO::PARAM_INT);
    $stmt_empresa->execute();

    $empresa = $stmt_empresa->fetch(PDO::FETCH_ASSOC);

    if (!$empresa) {
        error_log("Nenhuma empresa encontrada para o ID de usuário: " . $userId);
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar informações da empresa: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aluminnium</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="gerar-recibo.css">
</head>
<body>
    <div class="container">
        <div class="navbar">
            <h1>Criar Recibos</h1>
            <button class="btn-back" onclick="window.location.href='area-usuario.php'">Voltar</button>
        </div>



        <div class="sidebar">
            <div class="box">
                <h2>Preencha os dados do recibo</h2>
                <form class="receipt-form" method="POST">
                    <select id="cliente" name="cliente" onchange="preencherDadosCliente()" required>
                        <option value="">Selecione um cliente</option>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?php echo htmlspecialchars($cliente['id_cliente']); ?>">
                                <?php echo htmlspecialchars($cliente['nome_cliente']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>


                    <input type="text" id="valor" name="valor" placeholder="Valor do Recibo" required>
                    <input type="date" id="data" name="data" required>
                    <textarea id="descricao" name="descricao" rows="4" placeholder="Descrição do serviço prestado..." required></textarea>
                    <button type="submit">Gerar Recibo</button>
                </form>
            </div>
        </div>
        

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (isset($_POST['cliente']) && !empty($_POST['cliente'])) {
                $clienteId = htmlspecialchars($_POST['cliente']);


                try {
                    $stmt_cliente = $conn->prepare("SELECT cpf, nome_cliente FROM clientes WHERE id_cliente = :id_cliente AND id_usuario = :id_usuario");
                    $stmt_cliente->bindParam(':id_cliente', $clienteId);
                    $stmt_cliente->bindParam(':id_usuario', $userId);
                    $stmt_cliente->execute();

                    if ($stmt_cliente->rowCount() > 0) {
                        $cliente = $stmt_cliente->fetch(PDO::FETCH_ASSOC);
                        $cpfCliente = $cliente['cpf'];
                        $nomeCliente = $cliente['nome_cliente'];
                    } else {
                        echo "Erro: Cliente não encontrado.";
                        exit();
                    }
                } catch (PDOException $e) {
                    echo "Erro ao buscar cliente: " . $e->getMessage();
                    exit();
                }
            } else {
                echo "Erro: Cliente não foi selecionado.";
                exit();
            }


            function formatCPF($cpf) {
                if (preg_match('/^\d{11}$/', $cpf)) {
                    return preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $cpf);
                }
                return $cpf; 
            }

            function formatValor($valor) {
                return 'R$ ' . number_format(floatval($valor), 2, ',', '.');
            }

 
            $valor = isset($_POST['valor']) ? formatValor(htmlspecialchars($_POST['valor'])) : '';
            $data = isset($_POST['data']) ? htmlspecialchars($_POST['data']) : '';
            $descricao = isset($_POST['descricao']) ? htmlspecialchars($_POST['descricao']) : '';

            
        ?>
        
        <div class="receipt-output">
            <h3>Recibo de Pagamento</h3>
            <?php if ($empresa): ?>
                <div class="info-empresa">
                    <div class="logo-info-container">
                        <img src="<?php echo htmlspecialchars($empresa['logotipo_empresa']); ?>" alt="Logomarca" class="logo">
                        <table class="tabela-empresa">
                            <tr>
                                <td><strong>Empresa:</strong></td>
                                <td><?php echo htmlspecialchars($empresa['nome_empresa']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>CNPJ:</strong></td>
                                <td><?php echo htmlspecialchars($empresa['cnpj_empresa']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Endereço:</strong></td>
                                <td><?php echo htmlspecialchars($empresa['endereco_empresa']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td><?php echo htmlspecialchars($empresa['email_empresa']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Telefone:</strong></td>
                                <td><?php echo htmlspecialchars($empresa['telefone_empresa']); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <p><span class="highlight">Data:</span> <?php echo date('d/m/Y', strtotime($data)); ?></p>
            <p>Recebi(emos) de <span class="highlight"><?php echo htmlspecialchars($nomeCliente); ?></span> CPF: <span class="highlight"><?php echo formatCPF($cpfCliente); ?></span></p>
            <p>a importância de <span class="highlight"><?php echo $valor; ?></span></p>
            <p>referente à <span class="highlight"><?php echo htmlspecialchars($descricao); ?></span></p>
            <p>Para maior clareza firmo(amos) o presente recibo para que produza os seus efeitos, dando plena, rasa e irrevogável quitação, pelo valor recebido.</p>

            <div class="button-group">
                <form method="POST" action="gerarrecibo-pdf.php">
                    <input type="hidden" name="id_cliente" value="<?php echo htmlspecialchars($clienteId); ?>">
                    <input type="hidden" name="nome" value="<?php echo htmlspecialchars($nomeCliente); ?>">
                    <input type="hidden" name="cpf" value="<?php echo formatCPF($cpfCliente); ?>">
                    <input type="hidden" name="valor" value="<?php echo htmlspecialchars($_POST['valor']); ?>">
                    <input type="hidden" name="data" value="<?php echo htmlspecialchars($_POST['data']); ?>">
                    <input type="hidden" name="descricao" value="<?php echo htmlspecialchars($_POST['descricao']); ?>">
                    <button type="submit">Baixar PDF</button>
                </form>
            </div>
        </div>
        
        <?php
        }
        ?>
    </div>

    <script>
    function preencherDadosCliente() {
        const selectElement = document.getElementById('cliente');
        const clienteId = selectElement.value; 

        if (clienteId) {
            fetch('get-cliente.php?id=' + clienteId)
            .then(response => response.json())
            .then(data => {
                document.getElementById('cpf').value = data.cpf; 
                document.getElementById('nome').value = data.nome; 
            })
            .catch(error => console.error('Erro ao buscar dados do cliente:', error));
        } else {
            document.getElementById('cpf').value = ''; 
            document.getElementById('nome').value = ''; 
        }
    }



    
function abrirModal() {
    document.getElementById('modal-confirmacao').style.display = 'block';
}


function fecharModal() {
    document.getElementById('modal-confirmacao').style.display = 'none';
    
    
    window.location.href = 'gerar-recibo.php';
}




window.onclick = function(event) {
    const modal = document.getElementById('modal-confirmacao');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}


document.querySelector('form[action="gerarrecibo-pdf.php"]').addEventListener('submit', function(event) {
    event.preventDefault(); 
    const form = this;
    

    setTimeout(function() {
        abrirModal();  
        form.submit(); 
    }, 500); 
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

