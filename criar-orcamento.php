<?php
include 'config.php';
include 'get-cliente.php';
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$cliente = null;

function buscarTodosClientes($conn, $id_usuario) {
    $clientes = [];
    try {
        $stmt = $conn->prepare("SELECT id_cliente, nome_cliente FROM clientes WHERE id_usuario = :id_usuario");
        $stmt->bindParam(':id_usuario', $id_usuario);
        $stmt->execute();
        $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar clientes: " . $e->getMessage());
    }
    return $clientes;
}

function formatarCNPJ($cnpj) {
    return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
}


function formatarCPF($cpf) {
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}


function formatarTelefone($telefone) {
    return preg_replace('/(\d{2})(\d{4,5})(\d{4})/', '($1) $2-$3', $telefone);
}

function buscarValorProduto($conn, $descricao) {
    try {
        $stmt = $conn->prepare("SELECT valor FROM produtos WHERE descricao = :descricao");
        $stmt->bindParam(':descricao', $descricao);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ? (float)$resultado['valor'] : 0;
    } catch (PDOException $e) {
        error_log("Erro ao buscar valor do produto: " . $e->getMessage());
        return 0;
    }
}

function buscarValorExtraProduto($conn, $descricao) {
    try {
        $stmt = $conn->prepare("SELECT valor_extra FROM produtos WHERE descricao = :descricao");
        $stmt->bindParam(':descricao', $descricao);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ? (float)$resultado['valor_extra'] : 0;
    } catch (PDOException $e) {
        error_log("Erro ao buscar valor extra do produto: " . $e->getMessage());
        return 0;
    }
}

function buscarMateriais($conn) {
    $materiais = [];
    try {
        $stmt = $conn->prepare("SELECT DISTINCT descricao FROM produtos WHERE descricao IN ('em Vidro Temperado', 'em Esquadria de Alumínio')");
        $stmt->execute();
        $materiais = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Erro ao buscar materiais: " . $e->getMessage());
    }
    return $materiais;
}

$clientes = buscarTodosClientes($conn, $id_usuario);

if (isset($_GET['id_cliente'])) {
    $id_cliente = intval($_GET['id_cliente']);
    $cliente = buscarCliente($conn, $id_cliente, $id_usuario);
    $_SESSION['id_cliente'] = $id_cliente; 
} elseif (isset($_SESSION['id_cliente'])) {
    $id_cliente = $_SESSION['id_cliente'];
    $cliente = buscarCliente($conn, $id_cliente, $id_usuario);
} else {
    $cliente = null; 
}

if (!isset($_SESSION['orcamentos'])) {
    $_SESSION['orcamentos'] = [];
}

try {
    $sql_empresa = "SELECT nome_empresa, cnpj_empresa, endereco_empresa, email_empresa, telefone_empresa, logotipo_empresa FROM usuarios WHERE id_usuario = :id_usuario";
    $stmt_empresa = $conn->prepare($sql_empresa);
    $stmt_empresa->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
    $stmt_empresa->execute();
    $empresa = $stmt_empresa->fetch(PDO::FETCH_ASSOC); 

    if (!$empresa) {
        echo "Nenhuma empresa encontrada para o ID de usuário: " . $id_usuario;
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar informações da empresa: " . $e->getMessage());
    echo "Erro na consulta: " . $e->getMessage();
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_index'])) {
    $index = intval($_POST['delete_index']);
    if (isset($_SESSION['orcamentos'][$index])) {
        unset($_SESSION['orcamentos'][$index]);
        $_SESSION['orcamentos'] = array_values($_SESSION['orcamentos']); 
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['delete_index'])) {
    $tipo = isset($_POST['tipo']) ? htmlspecialchars($_POST['tipo']) : 'Tipo padrão';
    $abertura = isset($_POST['abertura']) ? htmlspecialchars($_POST['abertura']) : 'Abertura padrão';
    $folhas = isset($_POST['folhas']) ? htmlspecialchars($_POST['folhas']) : 'Número de folhas padrão';
    $material = isset($_POST['material']) ? htmlspecialchars($_POST['material']) : 'Material padrão';
    
    $descricao = "$tipo $abertura $folhas, $material";

    $largura_mm = isset($_POST['largura']) ? $_POST['largura'] : 0;
    $altura_mm = isset($_POST['altura']) ? $_POST['altura'] : 0;
    $cor = isset($_POST['cor']) ? $_POST['cor'] : '';
    $espessura = isset($_POST['espessura']) ? $_POST['espessura'] : 0;
    $quantidade = isset($_POST['quantidade']) ? intval($_POST['quantidade']) : 1;
    $tipo_vidro = isset($_POST['tipo_vidro']) ? $_POST['tipo_vidro'] : 'Vidro Comum Incolor'; 

    $imagem = isset($_POST['imagem']) ? $_POST['imagem'] : 'default.jpg'; 

    $largura_m = $largura_mm / 1000;
    $altura_m = $altura_mm / 1000;

    if (is_numeric($largura_m) && is_numeric($altura_m) && $largura_m > 0 && $altura_m > 0 && $quantidade > 0) {

        $valor_unitario_base = buscarValorProduto($conn, $material);
    

        if ($material == 'em Vidro Temperado' || $material == 'em Esquadria de Alumínio') {
            $valor_unitario_base *= ($largura_m * $altura_m);
        } else {
            $valor_unitario_base = 0; 
        }


        $valor_extra_vidro = buscarValorExtraProduto($conn, $tipo_vidro);

        $valor_extra_cor = buscarValorExtraProduto($conn, $cor);

        $valor_unitario = $valor_unitario_base + $valor_extra_vidro + $valor_extra_cor;
        $valor_total = $valor_unitario * $quantidade;

        $_SESSION['orcamentos'][] = [
            'descricao' => $descricao,
            'largura_mm' => $largura_mm,
            'altura_mm' => $altura_mm,
            'cor' => $cor,
            'espessura' => $espessura,
            'valor_unitario' => $valor_unitario,
            'quantidade' => $quantidade,
            'valor_total' => $valor_total,
            'imagem' => $imagem, 
            'tipo_vidro' => $tipo_vidro 
        ];

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aluminnium</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="criar-orcamento.css"> 
</head>
<body>
    <div class="container">
                <div class="navbar">
            <h1>Criar Orçamento</h1>

            </form>
            <button class="btn-back" onclick="window.location.href='destroy-session.php'" style="float: right;">Voltar</button>

         </form>
        </div>

        <div class="sidebar">
            <div class="box">
                <h2>Preencha os dados do orçamento</h2>
                <form class="contract-form" method="POST">
                    <select id="tipo" name="tipo" required>
                        <option value="" disabled selected>Selecione o Tipo</option>
                        <option value="Janela">Janela</option>
                        <option value="Porta">Porta</option>
                    </select>

                    <select id="abertura" name="abertura" required>
                        <option value="" disabled selected>Selecione a Abertura</option>
                        <option value="De Correr">De Correr</option>
                        <option value="De Giro">De Giro</option>
                        <option value="Pivotante">Pivotante</option>
                        <option value="Max.ar">Max.Ar</option>
                    </select>

                    <select id="folhas" name="folhas" required>
                        <option value="" disabled selected>Selecione o Nº de Folhas</option>
                        <option value="1 Folha">1 Folha</option>
                        <option value="2 Folhas">2 Folhas</option>
                        <option value="3 Folhas">3 Folhas</option>
                        <option value="4 Folhas">4 Folhas</option>
                    </select>

                    <select id="material" name="material" required>
                        <option value="" disabled selected>Selecione o Material</option>
                        <?php
                        $materiais = buscarMateriais($conn);
                        foreach ($materiais as $material): ?>
                            <option value="<?php echo htmlspecialchars($material); ?>"><?php echo htmlspecialchars($material); ?></option>
                        <?php endforeach; ?>
                    </select>


                    <div class="dimensoes">
                        <input type="number" id="largura" name="largura" min="0" required placeholder="Largura (mm)">
                        <p class="multiplicador">x</p>
                        <input type="number" id="altura" name="altura" min="0" required placeholder="Altura (mm)">
                    </div>


                    <select id="cor" name="cor" required>
                        <option value="" disabled selected>Selecione a Cor</option>
                        <option value="Alumínio na cor Branca">Alumínio Branco</option>
                        <option value="Alumínio na cor Preta">Alumínio Preto</option>
                        <option value="Alumínio na cor Fosca">Alumínio Fosco</option>
                        <option value="Alumínio na cor Bronze">Alumínio Bronze</option>
                    </select>

                    <select id="espessura" name="espessura" required>
                        <option value="" disabled selected>Selecione a Espessura do Vidro</option>
                        <option value="4">4 mm</option>
                        <option value="6">6 mm</option>
                        <option value="8">8 mm</option>
                        <option value="10">10 mm</option>
                    </select>

                    <select id="tipo_vidro" name="tipo_vidro" required>
                        <option value="" disabled selected>Selecione o Tipo do Vidro</option>
                        <option value="Vidro Comum Incolor">Vidro Incolor Comum</option>
                        <option value="Vidro Comum Fume">Vidro Fume Comum</option>
                        <option value="Vidro Comum Verde">Vidro Verde Comum</option>
                        <option value="Vidro Temperado Incolor">Vidro Incolor Temperado</option>
                        <option value="Vidro Temperado Fume">Vidro Fume Temperado</option>
                        <option value="Vidro Temperado Verde">Vidro Verde Temperado</option>
                        <option value="Vidro Mine Boreal">Vidro Mine Boreal</option>
                    </select>

                    <input type="number" id="quantidade" name="quantidade" min="1" required placeholder="Informe a Quantidade">

                    <select id="imagem" name="imagem" required>
                        <option value="" disabled selected>Selecione a Imagem</option>
                        <?php
                        $dir = 'imagens_predefinidas/';
                        $images = glob($dir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
                        foreach ($images as $image) {
                            $filename = basename($image);
                            echo "<option value='$filename'>" . htmlspecialchars($filename) . "</option>";
                        }
                        ?>
                    </select>

                    <button type="submit">Adicionar Item ao Orçamento</button>
                </form>
            </div>
        </div>

        <div class="result-container">
    <div class="empresa">
        <div class="empresa logo-info-container">
            <?php if ($empresa): ?>
                <img src="<?php echo htmlspecialchars($empresa['logotipo_empresa']); ?>" alt="Logomarca" class="logo">
                <table class="tabela-empresa">
                    <tr>
                        <td>Nome da Empresa:</td>
                        <td><?php echo htmlspecialchars($empresa['nome_empresa']); ?></td>
                    </tr>
                    <tr>
                        <td>CNPJ:</td>
                        <td><?php echo formatarCNPJ(htmlspecialchars($empresa['cnpj_empresa'])); ?></td>
                    </tr>
                    <tr>
                        <td>Endereço:</td>
                        <td><?php echo htmlspecialchars($empresa['endereco_empresa']); ?></td>
                    </tr>
                    <tr>
                        <td>Email:</td>
                        <td><?php echo htmlspecialchars($empresa['email_empresa']); ?></td>
                    </tr>
                    <tr>
                        <td>Telefone:</td>
                        <td><?php echo formatarTelefone(htmlspecialchars($empresa['telefone_empresa'])); ?></td>
                    </tr>
                </table>
            <?php else: ?>
                <p>Informações da empresa não encontradas.</p>
            <?php endif; ?>
        </div>
    </div>




    <div class="cliente-container">
    <div class="info-cliente">
        <?php if ($cliente): ?>
            <table border="1" class="cliente">
                <tr>
                    <th>Cliente</th>
                    <td><?php echo htmlspecialchars($cliente['nome_cliente']); ?></td>
                </tr>
                <tr>
                    <th>Endereço</th>
                    <td><?php echo htmlspecialchars($cliente['endereco_cliente']); ?></td>
                </tr>
                <tr>
                    <th>Telefone</th>
                    <td><?php echo formatarTelefone(htmlspecialchars($cliente['telefone_cliente'])); ?></td>
                </tr>
                <tr>
                    <th>CPF</th>
                    <td><?php echo formatarCPF(htmlspecialchars($cliente['cpf'])); ?></td>
                </tr>
                <tr>
                    <th>E-mail</th>
                    <td><?php echo htmlspecialchars($cliente['email_cliente']); ?></td>
                </tr>
            </table>
        <?php endif; ?>
    </div>
</div>


<div class="cliente-container">
    <div class="flex-container">
        <form class="cliente-form" method="GET" action="">
            <select id="cliente" name="id_cliente" required onchange="this.form.submit()">
                <option value="" disabled <?php echo !isset($id_cliente) ? 'selected' : ''; ?>>Selecione um Cliente</option>
                <?php foreach ($clientes as $cli): ?>
                    <option value="<?php echo htmlspecialchars($cli['id_cliente']); ?>" <?php echo (isset($id_cliente) && $id_cliente == $cli['id_cliente']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cli['nome_cliente']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>


        <div class="content">
        <?php 
    $total = 0;
    if (isset($_SESSION['orcamentos']) && count($_SESSION['orcamentos']) > 0) {
        foreach ($_SESSION['orcamentos'] as $orcamento) {
            
            $valor_total = isset($orcamento['valor_total']) ? floatval($orcamento['valor_total']) : 0;
            $total += $valor_total;
        }


        
        
        foreach ($_SESSION['orcamentos'] as $index => $orcamento) {
            $valor_unitario = isset($orcamento['valor_unitario']) ? floatval($orcamento['valor_unitario']) : 0;
            $valor_total = isset($orcamento['valor_total']) ? floatval($orcamento['valor_total']) : 0;
            $largura_mm = isset($orcamento['largura_mm']) ? floatval($orcamento['largura_mm']) : 0;
            $altura_mm = isset($orcamento['altura_mm']) ? floatval($orcamento['altura_mm']) : 0;
            $quantidade = isset($orcamento['quantidade']) ? intval($orcamento['quantidade']) : 0;
            $espessura = isset($orcamento['espessura']) ? floatval($orcamento['espessura']) : 0;

            $imagemUrl = 'imagens_predefinidas/' . htmlspecialchars($orcamento['imagem']);
            echo "<div class='result'>";
            echo "<div class='image-container'>";
            echo "<img src='$imagemUrl' alt='Imagem do Orçamento'>";
            echo "</div>";
            echo "<div class='details'>";
            echo "<table>";
            echo "<tr><th>Descrição</th><td>" . htmlspecialchars($orcamento['descricao']) . "</td></tr>";
            echo "<tr><th>Medidas</th><td>" . number_format($largura_mm, 0, ',', '.') . " x " . number_format($altura_mm, 0, ',', '.') . "</td></tr>";
            echo "<tr><th>Quantidade</th><td>" . htmlspecialchars($quantidade) . "</td></tr>";
            echo "<tr><th>Preço Unitário</th><td>R$ " . number_format($valor_unitario, 2, ',', '.') . "</td></tr>";
            echo "<tr><th>Preço Total</th><td>R$ " . number_format($valor_total, 2, ',', '.') . "</td></tr>";
            echo "<tr><th>Cor do Perfil</th><td>" . htmlspecialchars($orcamento['cor']) . "</td></tr>";
            echo "<tr><th>Espessura do Vidro</th><td>" . number_format($espessura, 0, ',', '.') . " mm</td></tr>";
            echo "<tr><th>Tipo/Cor do Vidro</th><td>" . htmlspecialchars($orcamento['tipo_vidro']) . "</td></tr>";
            echo "</table>";
            echo "</div>"; 
            echo "<div class='actions'>";
            echo "<form method='post' action='' onsubmit='return confirmDelete()'>";
            echo "<input type='hidden' name='delete_index' value='$index'>";
            echo "<button type='submit' class='delete-button'>Excluir</button>";
            echo "</form>";
            echo "</div>"; 
            echo "</div>"; 
            }
                echo "<div class='total'>Valor Total: R$ " . number_format($total, 2, ',', '.') . "</div>";
            } else {
                echo "";
            }
            ?> 
            <div class="finalizar-container">
            <form method="POST" action="gerar-pdf.php">
                        <button type="submit">Gerar PDF</button>
                </form>
            </div>
        </div>
    </div> 
    <script>
        function confirmFinalizar() {
            return confirm("Você realmente deseja finalizar o orçamento?");
        }
    </script>

    <script> 
        function confirmDelete() {
            return confirm('Você tem certeza de que deseja excluir este item do orçamento?');
        }
    </script>
</body>
</html>
