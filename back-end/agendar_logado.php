<?php
// acexx/back-end/agendar_logado.php
session_start();
require_once 'functions.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: ../front-end/pag_login_usuario.php?login_required=true");
    exit();
}

// Função para verificar se agendamento deve ser automático
function isAgendamentoAutomatico() {
    try {
        $conexao = conectarBanco();
        $stmt = $conexao->prepare("SELECT valor FROM configuracoes WHERE chave = 'agendamento_automatico'");
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ? ($resultado['valor'] === '1') : true; // Default: automático
    } catch (PDOException $e) {
        return true; // Default: automático se erro
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data_agendamento = $_POST['data_agendamento'] ?? '';
    $hora_agendamento = $_POST['hora_agendamento'] ?? '';
    $usuario_id = $_SESSION['usuario_id'];

    // Validações básicas
    if (empty($data_agendamento) || empty($hora_agendamento)) {
        header("Location: ../front-end/pag_agendar_logado.php?erro=" . urlencode("Preencha todos os campos."));
        exit();
    }

    // Validação de data: Não permitir datas passadas
    $data_atual = date('Y-m-d');
    if ($data_agendamento < $data_atual) {
        header("Location: ../front-end/pag_agendar_logado.php?erro=" . urlencode("Não é possível agendar para uma data passada."));
        exit();
    }

    // Validação de dia útil (segunda a sexta)
    if (!isDiaUtil($data_agendamento)) {
        header("Location: ../front-end/pag_agendar_logado.php?erro=" . urlencode("Agendamentos só são permitidos de segunda a sexta-feira."));
        exit();
    }

    // Validação da hora (10:00 às 18:00, intervalos de 30 min)
    $horariosValidos = gerarHorariosDisponiveis();
    if (!in_array($hora_agendamento, $horariosValidos)) {
        header("Location: ../front-end/pag_agendar_logado.php?erro=" . urlencode("Horário inválido. Escolha um horário entre 10:00 e 18:00."));
        exit();
    }

    // Validação para não permitir agendamento em horário já passou no dia atual
    if ($data_agendamento === $data_atual) {
        $hora_atual = date('H:i');
        if ($hora_agendamento <= $hora_atual) {
            header("Location: ../front-end/pag_agendar_logado.php?erro=" . urlencode("Não é possível agendar para um horário já passado."));
            exit();
        }
    }

    try {
        $conexao = conectarBanco();

        // Busca informações do usuário
        $stmt_user = $conexao->prepare("SELECT nome, email, cpf FROM usuarios WHERE id = ?");
        $stmt_user->execute([$usuario_id]);
        $usuario = $stmt_user->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            header("Location: ../front-end/pag_agendar_logado.php?erro=" . urlencode("Erro ao buscar dados do usuário."));
            exit();
        }

        // Verificar se o email do usuário já tem agendamento confirmado
        $stmt_check_email = $conexao->prepare("
            SELECT COUNT(*) FROM agendamentos 
            WHERE email = ? AND status = 'confirmado'
        ");
        $stmt_check_email->execute([$usuario['email']]);
        $email_exists = $stmt_check_email->fetchColumn();

        if ($email_exists > 0) {
            header("Location: ../front-end/pag_agendar_logado.php?erro=" . urlencode("Você já possui um agendamento confirmado ativo. Cancele o agendamento anterior para fazer um novo."));
            exit();
        }

        // Verificar se o CPF do usuário já tem agendamento confirmado
        $stmt_check_cpf_geral = $conexao->prepare("
            SELECT COUNT(*) FROM agendamentos 
            WHERE cpf = ? AND status = 'confirmado'
        ");
        $stmt_check_cpf_geral->execute([$usuario['cpf']]);
        $cpf_exists = $stmt_check_cpf_geral->fetchColumn();

        if ($cpf_exists > 0) {
            header("Location: ../front-end/pag_agendar_logado.php?erro=" . urlencode("Seu CPF já possui um agendamento confirmado ativo. Cancele o agendamento anterior para fazer um novo."));
            exit();
        }

        // Verificar se a data ainda está disponível (não bloqueada por atingir limite de 10)
        if (!dataDisponivel($data_agendamento)) {
            header("Location: ../front-end/pag_agendar_logado.php?erro=" . urlencode("Esta data não está mais disponível para agendamentos (limite de 10 visitas atingido)."));
            exit();
        }

        // Verifica se o usuário já tem um agendamento confirmado para a mesma data
        $stmt_check_user = $conexao->prepare("
            SELECT COUNT(*) FROM agendamentos 
            WHERE usuario_id = ? AND data_agendamento = ? AND status = 'confirmado'
        ");
        $stmt_check_user->execute([$usuario_id, $data_agendamento]);
        $conflito_user = $stmt_check_user->fetchColumn();

        if ($conflito_user > 0) {
            header("Location: ../front-end/pag_agendar_logado.php?erro=" . urlencode("Você já possui um agendamento confirmado para esta data."));
            exit();
        }

        // NOVO: Verificar se agendamento deve ser automático ou manual
        $agendamento_automatico = isAgendamentoAutomatico();
        $status_inicial = $agendamento_automatico ? 'confirmado' : 'pendente';

        // Insere o agendamento
        $stmt = $conexao->prepare("
            INSERT INTO agendamentos (nome, email, cpf, data_agendamento, hora_agendamento, status, usuario_id, tipo_agendamento, quantidade_pessoas) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'individual', 1)
        ");
        $stmt->execute([
            $usuario['nome'], 
            $usuario['email'], 
            $usuario['cpf'], 
            $data_agendamento, 
            $hora_agendamento, 
            $status_inicial,
            $usuario_id
        ]);

        // Se for automático, verificar limite de 10 agendamentos
        if ($agendamento_automatico) {
            $total_agendamentos = contarAgendamentosData($data_agendamento);
            if ($total_agendamentos >= 10) {
                $stmt_update = $conexao->prepare("
                    INSERT INTO controle_diario (data_agendamento, total_agendamentos, bloqueado) 
                    VALUES (?, ?, 1)
                    ON DUPLICATE KEY UPDATE 
                    total_agendamentos = ?, bloqueado = 1
                ");
                $stmt_update->execute([$data_agendamento, $total_agendamentos, $total_agendamentos]);
            }
        }

        // Redireciona para página de sucesso com parâmetro do status
        if ($agendamento_automatico) {
            header("Location: ../front-end/pag_sucesso_agendamento.php");
        } else {
            header("Location: ../front-end/pag_sucesso_agendamento.php?pendente=true");
        }
        exit();

    } catch (PDOException $e) {
        error_log("Erro ao agendar: " . $e->getMessage());
        header("Location: ../front-end/pag_agendar_logado.php?erro=" . urlencode("Erro ao agendar. Tente novamente."));
        exit();
    }
} else {
    header("Location: ../front-end/pag_agendar_logado.php");
    exit();
}