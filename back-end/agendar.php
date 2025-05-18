<?php
// acexx/back-end/agendar.php
require_once 'functions.php'; // Inclui a função de conexão com o banco

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'] ?? '';
    $origem = $_POST['origem'] ?? '';
    // Garante que 'e_aluno' seja 1 (true) ou 0 (false) para o banco de dados BOOLEAN/TINYINT
    $e_aluno = (isset($_POST['e_aluno']) && $_POST['e_aluno'] == 'sim') ? 1 : 0;
    $data_agendamento = $_POST['data_agendamento'] ?? '';
    $hora_agendamento = $_POST['hora_agendamento'] ?? '';

    // --- Início das Validações ---

    // Validação básica de campos obrigatórios
    if (empty($nome) || empty($data_agendamento) || empty($hora_agendamento)) {
        header("Location: ../front-end/pag_agendar.html?status=erro&mensagem=" . urlencode("Preencha todos os campos obrigatórios."));
        exit();
    }

    // Validação de data: Não permitir datas passadas
    $data_atual = date('Y-m-d');
    if ($data_agendamento < $data_atual) {
        header("Location: ../front-end/pag_agendar.html?status=erro&mensagem=" . urlencode("Não é possível agendar para uma data passada."));
        exit();
    }

    // Validação de hora: Horário de funcionamento (ex: 08:00 às 17:00)
    $hora_minima = '08:00';
    $hora_maxima = '17:00';

    if ($hora_agendamento < $hora_minima || $hora_agendamento > $hora_maxima) {
        header("Location: ../front-end/pag_agendar.html?status=erro&mensagem=" . urlencode("O horário de agendamento deve ser entre {$hora_minima} e {$hora_maxima}."));
        exit();
    }

    // Opcional: Validação para impedir agendamento no mesmo dia em horário já passado
    $datetime_agendamento = new DateTime("{$data_agendamento} {$hora_agendamento}");
    $datetime_atual = new DateTime();

    if ($datetime_agendamento < $datetime_atual) {
        header("Location: ../front-end/pag_agendar.html?status=erro&mensagem=" . urlencode("Não é possível agendar para um horário já passado."));
        exit();
    }

    // Validação de conflito de agendamento (mais importante!)
    try {
        $conexao = conectarBanco();

        // Verifica se já existe um agendamento para a mesma data e hora
        $stmt_check = $conexao->prepare("SELECT COUNT(*) FROM agendamentos WHERE data_agendamento = :data AND hora_agendamento = :hora");
        $stmt_check->bindParam(':data', $data_agendamento);
        $stmt_check->bindParam(':hora', $hora_agendamento);
        $stmt_check->execute();
        $conflito = $stmt_check->fetchColumn();

        if ($conflito > 0) {
            header("Location: ../front-end/pag_agendar.html?status=erro&mensagem=" . urlencode("Já existe um agendamento para este dia e horário. Por favor, escolha outro."));
            exit();
        }

        // Se não há conflito, procede com a inserção
        $stmt = $conexao->prepare("INSERT INTO agendamentos (nome, origem, e_aluno, data_agendamento, hora_agendamento, status) VALUES (:nome, :origem, :e_aluno, :data_agendamento, :hora_agendamento, 'pendente')");
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':origem', $origem);
        $stmt->bindParam(':e_aluno', $e_aluno, PDO::PARAM_INT);
        $stmt->bindParam(':data_agendamento', $data_agendamento);
        $stmt->bindParam(':hora_agendamento', $hora_agendamento);
        $stmt->execute();

        header("Location: ../front-end/pag_inicial.html?status=sucesso&mensagem=" . urlencode("Agendamento realizado com sucesso!"));
        exit();
    } catch (PDOException $e) {
        error_log("Erro ao agendar: " . $e->getMessage()); // Para depuração, armazena o erro no log
        header("Location: ../front-end/pag_agendar.html?status=erro&mensagem=" . urlencode("Erro ao agendar. Tente novamente mais tarde."));
        exit();
    }
} else {
    // Redireciona se a requisição não for POST
    header("Location: ../front-end/pag_agendar.html");
    exit();
}
?>