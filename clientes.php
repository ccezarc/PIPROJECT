<?php
include 'config.php';
session_start();


if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['id_usuario'];
$userName = 'Usuário';


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


function validarTelefone($telefone) {
    $telefone = preg_replace('/\D/', '', $telefone);
    return (strlen($telefone) === 10 || strlen($telefone) === 11);
}


function formatarCPF($cpf) {
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}


function formatarTelefone($telefone) {
    $telefone = preg_replace('/\D/', '', $telefone);
    if (strlen($telefone) === 11) {
        return '('.substr($telefone, 0, 2).') '.substr($telefone, 2, 5).'-'.substr($telefone, 7);
    } elseif (strlen($telefone) === 10) {
        return '('.substr($telefone, 0, 2).') '.substr($telefone, 2, 4).'-'.substr($telefone, 6);
    }
    return $telefone;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_cliente'])) {
        $idCliente = $_POST['id_cliente'] ?? '';

        if (!empty($idCliente)) {
            try {
                $stmt = $conn->prepare("DELETE FROM clientes WHERE id_cliente = :id_cliente AND id_usuario = :id_usuario");
                $stmt->bindParam(':id_cliente', $idCliente);
                $stmt->bindParam(':id_usuario', $userId);
                $stmt->execute();

                $_SESSION['successMessage'] = "Cliente excluído com sucesso!";
            } catch (PDOException $e) {
                error_log("Erro ao excluir cliente: " . $e->getMessage());
                $_SESSION['errorMessage'] = "Erro ao excluir cliente: " . $e->getMessage(); 
            }
        }
    } else {
        $nome = $_POST['nome_cliente'] ?? '';
        $cpf = preg_replace('/\D/', '', $_POST['cpf_cliente'] ?? ''); 
        $endereco = $_POST['endereco_cliente'] ?? '';
        $telefone = $_POST['telefone_cliente'] ?? '';
        $email = $_POST['email_cliente'] ?? '';

        if (!empty($nome) && !empty($cpf)) {
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['errorMessage'] = "Email inválido.";
            } else {
                try {
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM clientes WHERE cpf = :cpf_cliente");
                    $stmt->bindParam(':cpf_cliente', $cpf);
                    $stmt->execute();
                    $cpfExists = $stmt->fetchColumn();

                    if ($cpfExists) {
                        $_SESSION['errorMessage'] = "CPF já cadastrado. Tente outro.";
                    } else {
                        $stmt = $conn->prepare("INSERT INTO clientes (nome_cliente, cpf, endereco_cliente, telefone_cliente, email_cliente, id_usuario) 
                            VALUES (:nome_cliente, :cpf_cliente, :endereco_cliente, :telefone_cliente, :email_cliente, :id_usuario)");
                        $stmt->bindParam(':nome_cliente', $nome);
                        $stmt->bindParam(':cpf_cliente', $cpf);
                        $stmt->bindParam(':endereco_cliente', $endereco);
                        $stmt->bindParam(':telefone_cliente', $telefone);
                        $stmt->bindParam(':email_cliente', $email);
                        $stmt->bindParam(':id_usuario', $userId);
                        $stmt->execute();
                        $_SESSION['successMessage'] = "Cliente cadastrado com sucesso!";
                    }
                } catch (PDOException $e) {
                    error_log("Erro ao cadastrar cliente: " . $e->getMessage());
                    $_SESSION['errorMessage'] = "Erro ao cadastrar cliente: " . $e->getMessage(); 
                }
            }
        } else {
            $_SESSION['errorMessage'] = "Nome e CPF são obrigatórios.";
        }
    }


    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}



$clientes = [];
try {
    $stmt = $conn->prepare("SELECT * FROM clientes WHERE id_usuario = :id_usuario");
    $stmt->bindParam(':id_usuario', $userId);
    $stmt->execute();
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar clientes: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aluminnium</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="clientes.css">
</head>
<body>
    <div class="container">

        <div class="navbar">
            <h1>Cadastro de Clientes</h1>
            <button class="btn-back" onclick="window.location.href='area-usuario.php'">Voltar</button>
        </div>

<?php
        $successMessage = $_SESSION['successMessage'] ?? '';
        $errorMessage = $_SESSION['errorMessage'] ?? '';
        unset($_SESSION['successMessage'], $_SESSION['errorMessage']); 
?>

<div class="sidebar">
    <div class="box">
        <h2>Cadastrar Cliente</h2>
        <form class="form" method="POST">

            <input type="text" name="nome_cliente" placeholder="Nome do Cliente (Completo)" required>

            <div class="input-group-wrapper">
                <label for="cpf_cliente">CPF</label>
                <div class="input-group">
                    <input type="text" id="cpf1" maxlength="3" oninput="moveToNext(this, 'cpf2')" autocomplete="off" required placeholder="xxx">
                    <span>.</span>
                    <input type="text" id="cpf2" maxlength="3" oninput="moveToNext(this, 'cpf3')" autocomplete="off" required placeholder="xxx">
                    <span>.</span>
                    <input type="text" id="cpf3" maxlength="3" oninput="moveToNext(this, 'cpf4')" autocomplete="off" required placeholder="xxx">
                    <span>-</span>
                    <input type="text" id="cpf4" maxlength="2" autocomplete="off" required placeholder="xx">
                    <input type="hidden" name="cpf_cliente" id="cpf_hidden">
                </div>
            </div>


            <div class="input-group-wrapper">
                <label for="telefone_cliente">TEL</label>
                <div class="input-group">
                <input type="text" id="tel1" maxlength="2" oninput="moveToNext(this, 'tel2')" autocomplete="off" required placeholder="(DDD)">
                    <input type="text" id="tel2" maxlength="5" oninput="moveToNext(this, 'tel3')" autocomplete="off" required placeholder="xxxxx">
                    <input type="text" id="tel3" maxlength="4" autocomplete="off" required placeholder="xxxx">
                    <input type="hidden" name="telefone_cliente" id="tel_hidden">
                </div>
            </div>

            <div class="input-group-wrapper">
                <div class="input-group">
                    <input type="text" id="rua" name="rua" placeholder="Rua" maxlength="60" required style="flex-grow: 1;">
                    <input type="text" id="numero" name="numero" placeholder="Nº" maxlength="6" required>
                </div>
            </div>


            <div class="input-group-wrapper">
                <div class="input-group">
                    <input type="text" id="cidade" name="cidade" placeholder="Cidade" maxlength="20" required>
                    <input type="text" id="estado" name="estado" placeholder="Estado" maxlength="2" required>
                </div>
                <input type="hidden" name="endereco_cliente" id="endereco_hidden">
            </div>

            <input type="email" name="email_cliente" placeholder="Email">
            <div class="form-box">
                <button type="submit" onclick="combineCPF(); combineTel(); combineEndereco();">Cadastrar</button>
            </div>
        </form>
    </div>
</div>




        <div class="content">
            <div class="table-box">
                <h2>Clientes Cadastrados</h2>
                <?php if (!empty($successMessage)) echo "<p style='color:green;'>$successMessage</p>"; ?>
                <?php if (!empty($errorMessage)) echo "<p style='color:red;'>$errorMessage</p>"; ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>Endereço</th>
                            <th>Telefone</th>
                            <th>Email</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cliente['nome_cliente']); ?></td>
                                <td><?php echo formatarCPF(htmlspecialchars($cliente['cpf'])); ?></td>
                                <td><?php echo htmlspecialchars($cliente['endereco_cliente']); ?></td>
                                <td><?php echo formatarTelefone(htmlspecialchars($cliente['telefone_cliente'])); ?></td>
                                <td><?php echo htmlspecialchars($cliente['email_cliente']); ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="id_cliente" value="<?php echo $cliente['id_cliente']; ?>">
                                        <button type="submit" name="delete_cliente" class="btn-delete" onclick="return confirm('Tem certeza que deseja excluir este cliente?');">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
    function formatarCPF(cpf) {
        return cpf.replace(/\D/g, '') 
                  .replace(/^(\d{3})(\d)/, '$1.$2') 
                  .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3') 
                  .replace(/\.(\d{3})(\d)/, '.$1-$2'); 
    }

    function formatarTelefone(telefone) {
        return telefone.replace(/\D/g, '') 
                       .replace(/^(\d{2})(\d)/, '($1) $2') 
                       .replace(/(\d)(\d{4})$/, '$1-$2'); 
    }

    document.addEventListener('DOMContentLoaded', function () {
        const cpfInput = document.querySelector('input[name="cpf_cliente"]');
        const telefoneInput = document.querySelector('input[name="telefone_cliente"]');

        cpfInput.addEventListener('input', function () {
            this.value = formatarCPF(this.value);
        });

        telefoneInput.addEventListener('input', function () {
            this.value = formatarTelefone(this.value);
        });
    });



function moveToNext(current, nextFieldId) {
    if (current.value.length == current.maxLength) {
        document.getElementById(nextFieldId).focus();
    }
}

function combineCPF() {
    const cpf1 = document.getElementById('cpf1').value;
    const cpf2 = document.getElementById('cpf2').value;
    const cpf3 = document.getElementById('cpf3').value;
    const cpf4 = document.getElementById('cpf4').value;
    const cpfFull = cpf1 + cpf2 + cpf3 + cpf4;
    document.getElementById('cpf_hidden').value = cpfFull;
}

function combineTel() {
    const tel1 = document.getElementById('tel1').value;
    const tel2 = document.getElementById('tel2').value;
    const tel3 = document.getElementById('tel3').value;
    const telFull = tel1 + tel2 + tel3;
    document.getElementById('tel_hidden').value = telFull;
}

function combineEndereco() {
    const rua = document.getElementById('rua').value;
    const numero = document.getElementById('numero').value;
    const estado = document.getElementById('estado').value;
    const cidade = document.getElementById('cidade').value;
    const enderecoFull = rua + ', ' + numero + ', ' + estado + ', ' + cidade;
    document.getElementById('endereco_hidden').value = enderecoFull;
}


</script>

</body>
</html>
