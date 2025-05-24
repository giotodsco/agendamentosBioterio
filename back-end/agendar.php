<?php
// acexx/back-end/agendar.php

require_once 'functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $data_agendamento = $_POST['data_agendamento'] ?? '';
    $hora_agendamento = $_POST['hora_agendamento'] ?? '';

    // Validações básicas
    if (empty($nome) || empty($email) || empty($cpf) || empty($data_agendamento) || empty($hora_agendamento)) {
        header("Location: ../front-end/pag_agendar.html?status=erro&mensagem=" . urlencode("Preencha todos os campos obrigatórios."));
        exit();
    }

    // Validação do CPF
    if (!validarCPF($cpf)) {
        header("Location: ../front-end/pag_agendar.html?status=erro&mensagem=" . urlencode("CPF inválido. Por favor, digite um CPF válido."));
        exit();
    }

    // Limpa o CPF
    $cpf_limpo = preg_replace('/\D/', '', $cpf);

    // Validação de email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../front-end/pag_agendar.html?status=erro&mensagem=" . urlencode("Email inválido."));
        exit();
    }

    // Validação de data: não permitir datas passadas
    $data_atual = date('Y-m-d');
    if ($data_agendamento < $data_atual) {
        header("Location: ../front-end/pag_agendar.html?status=erro&mensagem=" . urlencode("Não é possível agendar para uma data passada."));
        exit();
    }

    // Validação de dia útil (segunda a sexta)
    if (!isDiaUtil($data_agendamento)) {
        header("Location: ../front-end/pag_agendar.html?status=erro&mensagem=" . urlencode("Agendamentos só são permitidos de segunda a sexta-feira."));
        exit();
    }

    // Validação da hora (10:00 às 18:00, intervalos de 30 min)
    $horariosValidos = gerarHorariosDisponiveis();
    if (!in_array($hora_agendamento, $horariosValidos)) {
        header("Location: ../front-end/pag_agendar.html?status=erro&mensagem=" . urlencode("Horário inválido. Escolha um horário entre 10:00 e 18:00."));
        exit();
    }

    // Validação para não permitir agendamento em horário já passou no dia atual
    if ($data_agendamento === $data_atual) {
        $hora_atual = date('H:i');
        if ($hora_agendamento <= $hora_atual) {
            header("Location: ../front-end/pag_agendar.html?status=erro&mensagem=" . urlencode("Não é possível agendar para um horário já passado."));
            exit();
        }
    }

    try {
        $conexao = conectarBanco();

        // NOVA VALIDAÇÃO: Verificar se email já existe em agendamentos confirmados
        $stmt_check_email = $conexao->prepare("
            SELECT COUNT(*) FROM agendamentos 
            WHERE email = ? AND status = 'confirmado'
        ");
        $stmt_check_email->execute([$email]);
        $email_exists = $stmt_check_email->fetchColumn();

        if ($email_exists > 0) {
            header("Location: ../front-end/pag_agendar.html?status=erro&mensagem=" . urlencode("Este email já possui um agendamento confirmado. Cada email pode ter apenas um agendamento ativo."));
            exit();
        }

        // NOVA VALIDAÇÃO: Verificar se CPF já existe em agendamentos confirmados
        $stmt_check_cpf_geral = $conexao->prepare("
            SELECT COUNT(*) FROM agendamentos 
            WHERE cpf = ? AND status = 'confirmado'
        ");
        $stmt_check_cpf_geral->execute([$cpf_limpo]);
        $cpf_exists = $stmt_check_cpf_geral->fetchColumn();

        if ($cpf_exists > 0) {
            header("Location: ../front-end/pag_agendar.html?status=erro&mensagem=" . urlencode("Este CPF já possui um agendamento confirmado. Cada CPF pode ter apenas um agendamento ativo."));
            exit();
        }

        // Verificar se a data ainda está disponível (não bloqueada por atingir limite de 10)
        if (!dataDisponivel($data_agendamento)) {
            header("Location: ../front-end/pag_agendar.html?status=erro&mensagem=" . urlencode("Esta data não está mais disponível para agendamentos (limite de 10 visitas atingido)."));
            exit();
        }

        // Verificar se já existe agendamento para o mesmo CPF na mesma data (validação adicional)
        $stmt_check_cpf = $conexao->prepare("
            SELECT COUNT(*) FROM agendamentos 
            WHERE cpf = ? AND data_agendamento = ? AND status = 'confirmado'
        ");
        $stmt_check_cpf->execute([$cpf_limpo, $data_agendamento]);
        $conflito_cpf = $stmt_check_cpf->fetchColumn();

        if ($conflito_cpf > 0) {
            header("Location: ../front-end/pag_agendar.html?status=erro&mensagem=" . urlencode("Já existe um agendamento para este CPF na data selecionada."));
            exit();
        }

        // Inserir agendamento (status direto confirmado conforme solicitado)
        $stmt = $conexao->prepare("
            INSERT INTO agendamentos (nome, email, cpf, data_agendamento, hora_agendamento, status) 
            VALUES (?, ?, ?, ?, ?, 'confirmado')
        ");
        $stmt->execute([$nome, $email, $cpf_limpo, $data_agendamento, $hora_agendamento]);

        // Verificar se atingiu o limite de 10 agendamentos para bloquear a data
        $total_agendamentos = contarAgendamentosData($data_agendamento);
        if ($total_agendamentos >= 10) {
            // Atualizar controle diário para bloquear a data
            $stmt_update = $conexao->prepare("
                INSERT INTO controle_diario (data_agendamento, total_agendamentos, bloqueado) 
                VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE 
                total_agendamentos = ?, bloqueado = 1
            ");
            $stmt_update->execute([$data_agendamento, $total_agendamentos, $total_agendamentos]);
        }

        // Redirecionar para página de sucesso
        header("Location: ../front-end/pag_agendar.html?status=sucesso&mensagem=" . urlencode("Agendamento realizado com sucesso! Sua visita foi confirmada automaticamente."));
        exit();

    } catch (PDOException $e) {
        error_log("Erro ao agendar: " . $e->getMessage());
        header("Location: ../front-end/pag_agendar.html?status=erro&mensagem=" . urlencode("Erro interno do sistema. Tente novamente mais tarde."));
        exit();
    }
} else {
    header("Location: ../front-end/pag_agendar.html");
    exit();
}
?>