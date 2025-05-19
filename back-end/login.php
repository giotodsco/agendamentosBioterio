<?php
// acexx/back-end/login.php
session_start(); // Inicia a sessão para armazenar informações do usuário

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ra = $_POST['ra'] ?? '';
    $senha = $_POST['senha'] ?? '';


    $usuario_valido = "760691";
    $senha_valida = "gwW5jKsX1";

    if ($ra === $usuario_valido && $senha === $senha_valida) {
        $_SESSION['logado'] = true;
        header("Location: ../back-end/pag_agendamentos_adm.php"); // Redireciona para a área de agendamentos
        exit();
    } else {
        header("Location: ../front-end/pag_adm.php?erro_login=true"); // Redireciona de volta para o login com erro
        exit();
    }
} else {
    header("Location: ../front-end/pag_adm.php"); // Redireciona se não for POST
    exit();
}
?>