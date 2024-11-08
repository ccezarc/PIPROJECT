<?php
function buscarCliente($conn, $id_cliente, $id_usuario) {
    $cliente = null;

    try {
        $stmt = $conn->prepare("SELECT * FROM clientes WHERE id_cliente = :id_cliente AND id_usuario = :id_usuario");
        $stmt->bindParam(':id_cliente', $id_cliente);
        $stmt->bindParam(':id_usuario', $id_usuario);
        $stmt->execute();
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar cliente: " . $e->getMessage());
    }

    return $cliente;
}
?>
