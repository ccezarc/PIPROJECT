<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'config.php'; 

session_start();


if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

if (isset($_SESSION['id_usuario'])) {
    $userId = $_SESSION['id_usuario'];

    try {
      
        $stmt = $conn->prepare("SELECT nome_usuario, logotipo_empresa FROM usuarios WHERE id_usuario = :id_usuario");
        $stmt->bindParam(':id_usuario', $userId);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $userName = htmlspecialchars($user['nome_usuario']);
            $userLogo = htmlspecialchars($user['logotipo_empresa']) ?: 'https://via.placeholder.com/45'; 
        } else {
            $userName = 'Usuário';
            $userLogo = 'https://via.placeholder.com/45'; 
        }

        $stmt = $conn->prepare("SELECT id_cliente, nome_cliente FROM clientes WHERE id_usuario = :id_usuario");
        $stmt->bindParam(':id_usuario', $userId);
        $stmt->execute();
        $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        try {
            $stmtOrcamentos = $conn->prepare("SELECT * FROM historicos WHERE id_usuario = :id_usuario AND tipo_documento = 'orcamento'");
            $stmtOrcamentos->bindParam(':id_usuario', $userId);
            $stmtOrcamentos->execute();
            $historicoOrcamentos = $stmtOrcamentos->fetchAll(PDO::FETCH_ASSOC);
        
            $stmtRecibos = $conn->prepare("SELECT * FROM historicos WHERE id_usuario = :id_usuario AND tipo_documento = 'recibo'");
            $stmtRecibos->bindParam(':id_usuario', $userId);
            $stmtRecibos->execute();
            $historicoRecibos = $stmtRecibos->fetchAll(PDO::FETCH_ASSOC);
        
            $stmtContratos = $conn->prepare("SELECT * FROM historicos WHERE id_usuario = :id_usuario AND tipo_documento = 'contrato'");
            $stmtContratos->bindParam(':id_usuario', $userId);
            $stmtContratos->execute();
            $historicoContratos = $stmtContratos->fetchAll(PDO::FETCH_ASSOC);
        
        } catch (PDOException $e) {
            error_log("Erro ao buscar dados do histórico: " . $e->getMessage());
            $historicoOrcamentos = [];
            $historicoRecibos = [];
            $historicoContratos = [];
        }
    } catch (PDOException $e) {
        error_log("Erro ao buscar dados do usuário: " . $e->getMessage());
        $userName = 'Usuário';
        $userLogo = 'https://via.placeholder.com/45'; 
        $clientes = [];
    }
} else {
    $userName = 'Usuário';
    $userLogo = 'https://via.placeholder.com/45'; 
    $clientes = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aluminnium</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="historico.css">
</head>
<body>
    <div class="container">
        <header class="navbar">
            <h1>Históricos</h1>
            <button class="btn-back" onclick="window.location.href='area-usuario.php'">Voltar</button>
        </header>
        
        <aside class="sidebar">
        <img src="<?php echo $userLogo; ?>" alt="Usuário"  class = "img-side">
            <button class="btn" onclick="toggleDrawer('Orçamento', 'criar-orcamento.php', '#paginahistorico', 'Ao clicar em Acessar você poderá utilizar a nossa ferramenta para criação de orçamentos. Não esqueça de antes cadastrar os dados da sua Empresa em CONFIGURAÇÕES e tambem os dados dos seus clientes.')">Criar Orçamento</button>
            <button class="btn" onclick="toggleDrawer('Seus Clientes', 'clientes.php', '#paginahistorico','Ao clicar em Acessar você será redirecionado para a pagina de cadastro de clientes, onde você deverá cadastrar os dados dos seus clientes ANTES de utilizar as outras ferramentas.')">Seus Clientes</button>
            <button class="btn" onclick="toggleDrawer('Recibo', 'gerar-recibo.php', '#paginahistorico', 'Ao clicar em Acessar você poderá utilizar a nossa ferramenta para criação de recibos. Não esqueça de antes cadastrar os dados da sua Empresa em CONFIGURAÇÕES e tambem os dados dos seus clientes.')">Criar Recibo</button>
            <button class="btn" onclick="toggleDrawer('Contrato', 'gerar-contrato.php','#paginahistorico', 'Ao clicar em Acessar você poderá utilizar a nossa ferramenta para criação de contratos. Não esqueça de antes cadastrar os dados da sua Empresa em CONFIGURAÇÕES e tambem os dados dos seus clientes.')">Criar Contrato</button>
        </aside>
        
        <section class="content">
    <div class="main-content">
        <div class="div box glass recibos">
            <h2>Histórico de Recibos</h2>
            <?php if (!empty($historicoRecibos)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nome do Arquivo</th>
                            <th>Criado em</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($historicoRecibos as $recibo): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(basename($recibo['caminho_pdf'])); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($recibo['data_criacao_historico'])); ?></td>
                            <td>
                                <?php
                                    $caminhoRelativo = 'recibos/' . basename($recibo['caminho_pdf']);
                                ?>
                                <a href="<?php echo htmlspecialchars($caminhoRelativo); ?>" target="_blank">Ver Recibo</a> | 
                                <a href="<?php echo htmlspecialchars($caminhoRelativo); ?>" download="<?php echo htmlspecialchars($caminhoRelativo); ?>">Baixar Recibo</a> | 
                                <a href="excluir_historico.php?id=<?php echo $recibo['id_historico']; ?>&tipo_documento=recibo" onclick="return confirm('Tem certeza que deseja excluir este recibo?');">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Sem recibos disponíveis.</p>
            <?php endif; ?>
        </div>

        <div class="div box glass contratos">
            <h2>Histórico de Contratos</h2>
            <?php if (!empty($historicoContratos)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nome do Arquivo</th>
                            <th>Criado em</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($historicoContratos as $contrato): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(basename($contrato['caminho_pdf'])); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($contrato['data_criacao_historico'])); ?></td>
                            <td>
                                <?php
                                    $caminhoRelativo = 'contratos/' . basename($contrato['caminho_pdf']);
                                ?>
                                <a href="<?php echo htmlspecialchars($caminhoRelativo); ?>" target="_blank">Ver Contrato</a> | 
                                <a href="<?php echo htmlspecialchars($caminhoRelativo); ?>" download="<?php echo htmlspecialchars($caminhoRelativo); ?>">Baixar Contrato</a> | 
                                <a href="excluir_historico.php?id=<?php echo $contrato['id_historico']; ?>&tipo_documento=contrato" onclick="return confirm('Tem certeza que deseja excluir este contrato?');">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Sem contratos disponíveis.</p>
            <?php endif; ?>
        </div>
    </div>

</section>







    <div class="drawer" id="drawer">
        <h2 id="drawer-title">Título da gaveta</h2>
        <p id="drawer-description">Descrição da gaveta</p>
        <button id="drawer-button" onclick="">Acessar</button>
    </div>

    <div class="user-menu" id="user-menu">
        <button onclick="window.location.href='perfil-usuario.php'">Perfil</button>
        <button onclick="window.location.href='logout.php'">Logout</button>
        </div>


    <script>
    function toggleDrawer(title, pageUrl, historyUrl, description) {
        const drawer = document.getElementById('drawer');
        const drawerTitle = document.getElementById('drawer-title');
        const drawerDescription = document.getElementById('drawer-description');
        const drawerButton = document.getElementById('drawer-button');

        drawerTitle.textContent = title;
        drawerDescription.textContent = description;
        drawerButton.onclick = function() {
            window.location.href = pageUrl;
        };

        if (drawer.classList.contains('open')) {
            drawer.classList.remove('open');
        } else {
            drawer.classList.add('open');
        }
    }

    function toggleUserMenu() {
        const userMenu = document.getElementById('user-menu');
        userMenu.classList.toggle('open');
    }
    </script>
</body>
</html>
