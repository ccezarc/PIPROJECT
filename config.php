<?php
$host = 'localhost';
$dbname = 'db-esquadrias';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    echo "Erro de conexão: " . htmlspecialchars($e->getMessage());
    exit();
}
?>
