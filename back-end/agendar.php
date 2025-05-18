<?php
// acexx/back-end/agendar.php

require_once 'functions.php'; // Inclui a função de conexão com o banco

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? ''; // Nova linha para capturar o email
    // Garante que 'e_aluno' seja 1 (true) ou 0 (false) para o banco de dados BOOLEAN/TINYINT
    $e_aluno = (isset($_POST['e_aluno']) && $_POST['e_aluno'] == 'sim') ? 1 : 0;
    $data_agendamento = $_POST['data_agendamento'] ?? '';
    $hora_agendamento = $_POST['hora_agendamento'] ?? '';

    // --- Início das Validações ---

    // Validação básica de campos obrigatórios
    if (empty($nome) || empty($email) || empty($data_agendamento) || empty($hora_agendamento)) { // Adicionado $email
        header("Location: ../front-end/pag_agendar.html?status=erro&mensagem=" . urlencode("Preencha todos os campos obrigatórios."));
        exit();
    }

    // Validação de data: Não permitir datas passadas
    $data_atual = date('Y-m-d');
    if ($data_agendamento < $data_atual) {
        header("Location: ../front-end/pag_agendar.html?status=erro&mensagem=" . urlencode("Não é possível agendar para uma data passada."));
        exit();
    }

    // Validação de hora: Verificar se a hora está dentro do intervalo permitido (08:00 - 17:00)
    $hora_minima = "08:00";
    $hora_maxima = "17:00";
    if ($hora_agendamento < $hora_minima || $hora_agendamento > $hora_maxima) {
        header("Location: ../front-end/pag_agendar.html?status=erro&mensagem=" . urlencode("Horário de agendamento fora do expediente (08:00 - 17:00)."));
        exit();
    }

    // Validação de data e hora para agendamentos no mesmo dia:
    // Se a data selecionada for hoje, a hora do agendamento não pode ser no passado.
    if ($data_agendamento == $data_atual) {
        $hora_atual = date('H:i');
        // Adiciona uma margem de 30 minutos para evitar agendamentos muito próximos
        $hora_limite_minima_hoje = date('H:i', strtotime('+30 minutes'));
        if ($hora_agendamento < $hora_limite_minima_hoje) {
            header("Location: ../front-end/pag_agendar.html?status=erro&mensagem=" . urlencode("Para hoje, o agendamento deve ser pelo menos 30 minutos após a hora atual."));
            exit();
        }
    }

    // --- Fim das Validações ---

    try {
        $conexao = conectarBanco();

        // Verifica se já existe um agendamento para a mesma data e hora (conflito)
        $stmt_check = $conexao->prepare("SELECT COUNT(*) FROM agendamentos WHERE data_agendamento = :data_agendamento AND hora_agendamento = :hora_agendamento AND status != 'negado'");
        $stmt_check->bindParam(':data_agendamento', $data_agendamento);
        $stmt_check->bindParam(':hora_agendamento', $hora_agendamento);
        $stmt_check->execute();
        $conflito = $stmt_check->fetchColumn();

        if ($conflito > 0) {
            header("Location: ../front-end/pag_agendar.html?status=erro&mensagem=" . urlencode("Já existe um agendamento para este dia e horário. Por favor, escolha outro."));
            exit();
        }

        // Se não há conflito, procede com a inserção
        $stmt = $conexao->prepare("INSERT INTO agendamentos (nome, email, e_aluno, data_agendamento, hora_agendamento, status) VALUES (:nome, :email, :e_aluno, :data_agendamento, :hora_agendamento, 'pendente')");
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':email', $email); // Novo bind para o email
        $stmt->bindParam(':e_aluno', $e_aluno, PDO::PARAM_INT);
        $stmt->bindParam(':data_agendamento', $data_agendamento);
        $stmt->bindParam(':hora_agendamento', $hora_agendamento);
        $stmt->execute();

        header("Location: ../front-end/pag_inicial.html?status=sucesso&mensagem=" . urlencode("Agendamento realizado com sucesso!"));
        exit();
    } catch (PDOException $e) {
        error_log("Erro ao agendar: " . $e->getMessage()); // Para depuração, armazena o erro no log
        header("Location: ../front-end/pag_agendar.html?status=erro&mensagem=" . urlencode("Ocorreu um erro ao agendar. Tente novamente mais tarde."));
        exit();
    }
} else {
    // Se a requisição não for POST, redireciona para a página de agendamento
    header("Location: ../front-end/pag_agendar.html");
    exit();
}
?>