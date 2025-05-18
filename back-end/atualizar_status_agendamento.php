<?php
// acexx/back-end/atualizar_status_agendamento.php
session_start();
require_once 'functions.php'; // Inclui a função de conexão com o banco

// Verifica se o funcionário está logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: ../front-end/pag_adm.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_agendamento = $_POST['id'] ?? null;
    $novo_status = $_POST['status'] ?? null;

    // Validação básica para garantir que os dados necessários foram enviados
    if ($id_agendamento === null || !is_numeric($id_agendamento) || empty($novo_status)) {
        header("Location: pag_agendamentos_adm.php?status=erro&mensagem=" . urlencode("Dados inválidos para atualização."));
        exit();
    }

    // Valida se o status enviado é um dos valores permitidos para evitar injeção de dados inválidos
    $status_permitidos = ['confirmado', 'negado'];
    if (!in_array($novo_status, $status_permitidos)) {
        header("Location: pag_agendamentos_adm.php?status=erro&mensagem=" . urlencode("Status inválido."));
        exit();
    }

    try {
        $conexao = conectarBanco();

        // Prepara a consulta para atualizar apenas o status do agendamento específico
        $stmt = $conexao->prepare("UPDATE agendamentos SET status = :status WHERE id = :id");
        $stmt->bindParam(':status', $novo_status);
        $stmt->bindParam(':id', $id_agendamento, PDO::PARAM_INT); // PDO::PARAM_INT para garantir que é um inteiro
        $stmt->execute();

        // Verifica se alguma linha foi afetada (se o agendamento foi realmente atualizado)
        if ($stmt->rowCount() > 0) {
            header("Location: pag_agendamentos_adm.php?status=sucesso&mensagem=" . urlencode("Status do agendamento atualizado para " . ucfirst($novo_status) . " com sucesso!"));
        } else {
            // Isso pode acontecer se o ID não existe ou o status já era o mesmo
            header("Location: pag_agendamentos_adm.php?status=aviso&mensagem=" . urlencode("Agendamento não encontrado ou status já era " . ucfirst($novo_status) . "."));
        }
        exit();

    } catch (PDOException $e) {
        // Em caso de erro no banco de dados, loga o erro para depuração e exibe uma mensagem genérica ao usuário
        error_log("Erro ao atualizar status do agendamento: " . $e->getMessage());
        header("Location: pag_agendamentos_adm.php?status=erro&mensagem=" . urlencode("Erro ao atualizar o status do agendamento."));
        exit();
    }
} else {
    // Se a requisição não for POST (por exemplo, alguém tentou acessar diretamente via URL), redireciona de volta
    header("Location: pag_agendamentos_adm.php");
    exit();
}
?>