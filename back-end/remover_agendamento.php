<?php
// acexx/back-end/remover_agendamento.php
session_start();
require_once 'functions.php'; // Inclui a função de conexão com o banco

// Verifica se o funcionário está logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: ../front-end/pag_adm.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_agendamento = $_POST['id'] ?? null;

    if ($id_agendamento === null || !is_numeric($id_agendamento)) {
        header("Location: pag_agendamentos_adm.php?status=erro&mensagem=" . urlencode("ID de agendamento inválido."));
        exit();
    }

    try {
        $conexao = conectarBanco();

        $stmt = $conexao->prepare("DELETE FROM agendamentos WHERE id = :id");
        $stmt->bindParam(':id', $id_agendamento, PDO::PARAM_INT); // PDO::PARAM_INT para garantir que é um inteiro
        $stmt->execute();

        // Verifica se alguma linha foi afetada (se o agendamento foi realmente removido)
        if ($stmt->rowCount() > 0) {
            header("Location: pag_agendamentos_adm.php?status=sucesso&mensagem=" . urlencode("Agendamento removido com sucesso!"));
        } else {
            header("Location: pag_agendamentos_adm.php?status=erro&mensagem=" . urlencode("Agendamento não encontrado ou já removido."));
        }
        exit();

    } catch (PDOException $e) {
        error_log("Erro ao remover agendamento: " . $e->getMessage()); // Loga o erro para depuração
        header("Location: pag_agendamentos_adm.php?status=erro&mensagem=" . urlencode("Erro ao remover agendamento. Tente novamente."));
        exit();
    }
} else {
    // Redireciona se a requisição não for POST
    header("Location: pag_agendamentos_adm.php");
    exit();
}
?>