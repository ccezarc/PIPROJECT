<?php
session_start(); 


unset($_SESSION['orcamentos']);
unset($_SESSION['id_cliente']);


header("Location: area-usuario.php");
exit();
?>




