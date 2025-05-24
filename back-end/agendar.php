<?php
// acexx/back-end/agendar.php - VERSÃO CORRIGIDA

require_once 'functions.php';

// NOVO: Função para verificar se agendamento deve ser automático
function isAgendamentoAutomatico() {
    try {
        $conexao = conectarBanco();
        $stmt = $conexao->prepare("SELECT valor FROM configuracoes WHERE chave = 'agendamento_automatico'");
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Se não existir a configuração, criar como automático por padrão
        if (!$resultado) {
            // Criar a configuração como automática por padrão
            $stmt_insert = $conexao->prepare("INSERT INTO configuracoes (chave, valor, descricao) VALUES ('agendamento_automatico', '1', 'Define se agendamentos individuais são aprovados automaticamente')");
            $stmt_insert->execute();
            return true;
        }
        
        return $resultado['valor'] === '1';
    } catch (PDOException $e) {
        // Em caso de erro, retornar false (modo manual) para segurança
        error_log("Erro ao verificar modo de agendamento: " . $e->getMessage());
        return false; // Modo manual por segurança
    }
}

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

        // CORRIGIDO: Verificar modo de agendamento
        $agendamento_automatico = isAgendamentoAutomatico();
        $status_inicial = $agendamento_automatico ? 'confirmado' : 'pendente';

        // NOVA VALIDAÇÃO: Verificar se email já existe em agendamentos confirmados OU pendentes
        $stmt_check_email = $conexao->prepare("
            SELECT COUNT(*) FROM agendamentos 
            WHERE email = ? AND status IN ('confirmado', 'pendente')
        ");
        $stmt_check_email->execute([$email]);
        $email_exists = $stmt_check_email->fetchColumn();

        if ($email_exists > 0) {
            header("Location: ../front-end/pag_agendar.html?status=erro&mensagem=" . urlencode("Este email já possui um agendamento ativo ou pendente. Cada email pode ter apenas um agendamento ativo."));
            exit();
        }

        // NOVA VALIDAÇÃO: Verificar se CPF já existe em agendamentos confirmados OU pendentes
        $stmt_check_cpf_geral = $conexao->prepare("
            SELECT COUNT(*) FROM agendamentos 
            WHERE cpf = ? AND status IN ('confirmado', 'pendente')
        ");
        $stmt_check_cpf_geral->execute([$cpf_limpo]);
        $cpf_exists = $stmt_check_cpf_geral->fetchColumn();

        if ($cpf_exists > 0) {
            header("Location: ../front-end/pag_agendar.html?status=erro&mensagem=" . urlencode("Este CPF já possui um agendamento ativo ou pendente. Cada CPF pode ter apenas um agendamento ativo."));
            exit();
        }

        // Verificar se a data ainda está disponível apenas se for automático
        if ($agendamento_automatico && !dataDisponivel($data_agendamento)) {
            header("Location: ../front-end/pag_agendar.html?status=erro&mensagem=" . urlencode("Esta data não está mais disponível para agendamentos (limite de 10 visitas atingido)."));
            exit();
        }

        // Verificar se já existe agendamento para o mesmo CPF na mesma data (validação adicional)
        $stmt_check_cpf = $conexao->prepare("
            SELECT COUNT(*) FROM agendamentos 
            WHERE cpf = ? AND data_agendamento = ? AND status IN ('confirmado', 'pendente')
        ");
        $stmt_check_cpf->execute([$cpf_limpo, $data_agendamento]);
        $conflito_cpf = $stmt_check_cpf->fetchColumn();

        if ($conflito_cpf > 0) {
            header("Location: ../front-end/pag_agendar.html?status=erro&mensagem=" . urlencode("Já existe um agendamento para este CPF na data selecionada."));
            exit();
        }

        // CORRIGIDO: Inserir agendamento com status baseado na configuração
        $stmt = $conexao->prepare("
            INSERT INTO agendamentos (nome, email, cpf, data_agendamento, hora_agendamento, status, tipo_agendamento, quantidade_pessoas, data_criacao) 
            VALUES (?, ?, ?, ?, ?, ?, 'individual', 1, NOW())
        ");
        $stmt->execute([$nome, $email, $cpf_limpo, $data_agendamento, $hora_agendamento, $status_inicial]);

        // Se for automático, verificar se atingiu o limite de 10 agendamentos para bloquear a data
        if ($agendamento_automatico) {
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
        }

        // CORRIGIDO: Redirecionar com mensagem baseada no modo
        if ($agendamento_automatico) {
            header("Location: ../front-end/pag_agendar.html?status=sucesso&mensagem=" . urlencode("Agendamento realizado com sucesso! Sua visita foi confirmada automaticamente."));
        } else {
            header("Location: ../front-end/pag_agendar.html?status=sucesso&mensagem=" . urlencode("Agendamento enviado com sucesso! Aguarde a aprovação da administração. Você receberá uma confirmação em breve."));
        }
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