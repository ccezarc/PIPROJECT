<?php
include 'config.php';
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

// Obtém os dados do usuário
$userId = $_SESSION['id_usuario'];
$stmt = $conn->prepare("SELECT log_tipo FROM usuarios WHERE id_usuario = :id_usuario");
$stmt->bindParam(':id_usuario', $userId);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['log_tipo'] != 1) {
    header("Location: index.php"); 
    exit();
}


$consultaProdutos = $conn->prepare("SELECT * FROM produtos ORDER BY id_produto ASC");
$consultaProdutos->execute();
$produtos = $consultaProdutos->fetchAll(PDO::FETCH_ASSOC);


$consultaUsuarios = $conn->prepare("SELECT * FROM usuarios ORDER BY id_usuario ASC");
$consultaUsuarios->execute();
$usuarios = $consultaUsuarios->fetchAll(PDO::FETCH_ASSOC);


if (isset($_GET['delete_id'])) {
    $deleteId = intval($_GET['delete_id']);
    $deleteStmt = $conn->prepare("DELETE FROM usuarios WHERE id_usuario = :id_usuario");
    $deleteStmt->bindParam(':id_usuario', $deleteId);
    $deleteStmt->execute();

    header("Location: area-adm.php"); 
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área de Administrador</title>
    <link rel="stylesheet" href="area-adm.css">
</head>

<body>
    <div class="container-up">
        <p>Gerenciar Produtos e Usuários</p>
        <button onclick="window.location.href='logout.php'" type="button" class="logout-button">Sair</button>
    </div>

    <div class="container-principal">
        <div class="container-itens">
            <h2>Produtos</h2>
            <?php foreach ($produtos as $produto): ?>
                <div class="item">
                    <p><strong>ID:</strong> <?php echo htmlspecialchars($produto['id_produto']); ?></p>
                    <p><strong>Descrição:</strong> <?php echo htmlspecialchars($produto['descricao']); ?></p>
                    <?php if ($produto['tipo_categoria'] == 'vidro' || $produto['tipo_categoria'] == 'cor'): ?>
                        <p><strong>Valor:</strong> R$ <?php echo htmlspecialchars(number_format($produto['valor_extra'], 2, ',', '.')); ?></p>
                    <?php else: ?>
                        <p><strong>Valor:</strong> R$ <?php echo htmlspecialchars(number_format($produto['valor'], 2, ',', '.')); ?></p>
                    <?php endif; ?>
                    <p><strong>Categoria:</strong> <?php echo htmlspecialchars($produto['tipo_categoria']); ?></p>
                    <form action="area-adm.php" method="GET">
                        <input type="hidden" name="id" value="<?php echo $produto['id_produto']; ?>">
                        <button type="submit" class="edit-button">Editar</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>


        <div class="container-alteracao">
            <h2>Alterações de Produtos</h2>
            <?php if (isset($_GET['id'])): ?>
                <?php
                $id = intval($_GET['id']);
                $consulta = $conn->prepare("SELECT * FROM produtos WHERE id_produto = :id");
                $consulta->bindValue(":id", $id, PDO::PARAM_INT);
                $consulta->execute();
                $produto = $consulta->fetch(PDO::FETCH_ASSOC);

                if ($produto):
                ?>
                    <form action="update-produto.php" method="POST">
                        <input type="hidden" name="id_produto" value="<?php echo htmlspecialchars($produto['id_produto']); ?>">

                        <div class="form-group">
                            <label for="descricao">Descrição:</label>
                            <p id="descricao" class="descricao-text"><?php echo htmlspecialchars($produto['descricao']); ?></p>
                        </div>

                        <div class="form-group">
                            <label for="valor">Valor:</label>
                            <?php if ($produto['tipo_categoria'] == 'vidro' || $produto['tipo_categoria'] == 'cor'): ?>
                                <input type="number" id="valor_extra" name="valor_extra" step="0.01" required value="<?php echo htmlspecialchars($produto['valor_extra']); ?>">
                            <?php else: ?>
                                <input type="number" id="valor" name="valor" step="0.01" required value="<?php echo htmlspecialchars($produto['valor']); ?>">
                            <?php endif; ?>
                        </div>

                        <div class="form-group">

                        </div>

                        <button type="submit" class="update-button">Salvar</button>
                    </form>
                <?php else: ?>
                    <p>Produto não encontrado!</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>


        <div class="container-usuarios">
            <h2>Usuários</h2>
            <?php foreach ($usuarios as $usuario): ?>
                <div class="item">
                    <p><strong>ID:</strong> <?php echo htmlspecialchars($usuario['id_usuario']); ?></p>
                    <p><strong>Nome:</strong> <?php echo htmlspecialchars($usuario['nome_usuario']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($usuario['email_usuario']); ?></p>
                    <p><strong>Tipo de Log:</strong> <?php echo htmlspecialchars($usuario['log_tipo']); ?></p>
                    <form action="area-adm.php" method="GET" style="display:inline;">
                        <input type="hidden" name="user_id" value="<?php echo $usuario['id_usuario']; ?>">
                        <button type="submit" class="edit-button">Editar</button>
                    </form>
                    <form action="area-adm.php" method="GET" style="display:inline;">
                        <input type="hidden" name="delete_id" value="<?php echo $usuario['id_usuario']; ?>">
                        <button type="submit" class="edit-button" onclick="return confirm('Tem certeza que deseja excluir este usuário?');">Excluir</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>


        <div class="container-alteracao-usuarios">
            <h2>Alterações de Usuários</h2>
            <?php if (isset($_GET['user_id'])): ?>
                <?php
                $user_id = intval($_GET['user_id']);
                $consulta = $conn->prepare("SELECT * FROM usuarios WHERE id_usuario = :user_id");
                $consulta->bindValue(":user_id", $user_id, PDO::PARAM_INT);
                $consulta->execute();
                $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

                if ($usuario):
                ?>
                    <form action="update-usuario.php" method="POST">
                        <input type="hidden" name="id_usuario" value="<?php echo htmlspecialchars($usuario['id_usuario']); ?>">

                        <div class="form-group">
                            <label for="nome_usuario">Nome:</label>
                            <input type="text" id="nome_usuario" name="nome_usuario" required value="<?php echo htmlspecialchars($usuario['nome_usuario']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="email_usuario">Email:</label>
                            <input type="email" id="email_usuario" name="email_usuario" required value="<?php echo htmlspecialchars($usuario['email_usuario']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="log_tipo">Tipo de Log:</label>
                            <select id="log_tipo" name="log_tipo" required>
                                <option value="1" <?php echo ($usuario['log_tipo'] == 1) ? 'selected' : ''; ?>>Administrador</option>
                                <option value="0" <?php echo ($usuario['log_tipo'] == 0) ? 'selected' : ''; ?>>Usuário</option>
                            </select>
                        </div>

                        <button type="submit" class="update-button">Salvar</button>
                    </form>
                <?php else: ?>
                    <p>Usuário não encontrado!</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>
