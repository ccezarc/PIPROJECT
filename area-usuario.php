<?php
include 'config.php'; 

session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

if (isset($_SESSION['id_usuario'])) {
    $userId = $_SESSION['id_usuario'];

    try {
        $stmt = $conn->prepare("SELECT nome_usuario, logotipo_empresa, log_tipo FROM usuarios WHERE id_usuario = :id_usuario");
        $stmt->bindParam(':id_usuario', $userId);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $userName = htmlspecialchars($user['nome_usuario']);
            $userLogo = htmlspecialchars($user['logotipo_empresa']) ?: 'https://via.placeholder.com/45'; 
            $logTipo = $user['log_tipo']; 
        } else {
            $userName = 'Usuário';
            $userLogo = 'https://via.placeholder.com/45'; 
            $logTipo = 0; 
        }

        $stmt = $conn->prepare("SELECT id_cliente, nome_cliente FROM clientes WHERE id_usuario = :id_usuario");
        $stmt->bindParam(':id_usuario', $userId);
        $stmt->execute();
        $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar dados do usuário: " . $e->getMessage());
        $userName = 'Usuário';
        $userLogo = 'https://via.placeholder.com/45'; 
        $clientes = [];
        $logTipo = 0; 
    }
} else {
    $userName = 'Usuário';
    $userLogo = 'https://via.placeholder.com/45'; 
    $clientes = [];
    $logTipo = 0; 
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aluminnium</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="area-usuario.css">
</head>
<body>
    <div class="container">
        <header class="navbar">
            <h1>Área do Usuário</h1>
            <div class="profile-navbar" onclick="toggleUserMenu()">
                <img src="<?php echo $userLogo; ?>" alt="Usuário" style="width: 45px; height: 45px; border-radius: 50%;">
                <span><?php echo $userName; ?></span>
            </div>
            <?php if ($logTipo == 1): ?>
                <button onclick="window.location.href='area-adm.php'" class="btn">Administração</button>
            <?php endif; ?>
        </header>
        
        <aside class="sidebar">
            <img src="<?php echo $userLogo; ?>" alt="Usuário" class="img-side">
            <button class="btn" onclick="toggleDrawer('Orçamento', 'criar-orcamento.php', '#paginahistorico', 'Ao clicar em Acessar, você poderá utilizar nossa ferramenta para criar orçamentos. Lembre-se de cadastrar os dados da sua empresa em CONFIGURAÇÕES e também as informações dos seus clientes antes de prosseguir.')">Criar Orçamento</button>
            <button class="btn" onclick="toggleDrawer('Seus Clientes', 'clientes.php', '#paginahistorico','Ao clicar em Acessar, você será redirecionado para a página de cadastro de clientes, onde deverá registrar os dados dos seus clientes ANTES de utilizar as demais ferramentas.')">Seus Clientes</button>
            <button class="btn" onclick="toggleDrawer('Recibo', 'gerar-recibo.php', '#paginahistorico', 'Ao clicar em Acessar, você poderá utilizar nossa ferramenta para gerar recibos. Lembre-se de cadastrar os dados da sua empresa em CONFIGURAÇÕES e também as informações dos seus clientes antes de prosseguir.')">Criar Recibo</button>
            <button class="btn" onclick="toggleDrawer('Contrato', 'gerar-contrato.php','#paginahistorico', 'Ao clicar em Acessar, você poderá utilizar nossa ferramenta para criar contratos. Não se esqueça de cadastrar os dados da sua empresa em CONFIGURAÇÕES e também as informações dos seus clientes antes de prosseguir.')">Criar Contrato</button>
            <button class="btn" onclick="toggleDrawer('Historico', 'historico.php', '#paginahistorico', 'Ao clicar em Acessar, você será redirecionado para a página de Histórico, onde poderá visualizar os dados de Contratos e Recibos realizados anteriormente.')">Histórico</button>
            <button class="btn" onclick="window.location.href='perfil-usuario.php'">Configurações</button>
        </aside>
        
        <section class="content">
            <div class="box glass">
                <h2>Crie seu Orçamento</h2>
                <p>Lembre-se de antes de criar seu orçamento cadastrar todos os dados da sua empresa assim como dos seus clientes.</p>
            </div>
            <div class="box glass">
                <h2>Seus Clientes</h2>
                <ul>
                    <?php foreach ($clientes as $cliente): ?>
                        <li>
                            <div class="client-box" onclick="location.href='clientes.php?id=<?php echo $cliente['id_cliente']; ?>'">
                                <?php echo htmlspecialchars($cliente['nome_cliente']); ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>
    </div>

    <div class="drawer" id="drawer">
        <h2 id="drawer-title">Título da gaveta</h2>
        <p id="drawer-description">Descrição da gaveta</p>
        <button id="drawer-button" onclick="">Acessar</button>
    </div>

    <div class="user-menu" id="user-menu">
        <button onclick="window.location.href='perfil-usuario.php'">Perfil</button>
        <button onclick="window.location.href='logout.php'">Logout</button>
    </div>
    <footer>
        <p>&copy; 2024 Esquadrias de Alumínio. Todos os direitos reservados.</p>
        <p>Projeto Integrador - Faculdade OPET</p>
    </footer>
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
