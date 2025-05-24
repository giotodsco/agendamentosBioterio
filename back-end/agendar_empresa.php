<?php
// acexx/back-end/agendar_empresa.php
session_start();
require_once 'functions.php';

// Verifica se a empresa está logada
if (!isset($_SESSION['empresa_logada']) || $_SESSION['empresa_logada'] !== true) {
    header("Location: ../front-end/pag_login_usuario.php?tab=empresa");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data_agendamento = $_POST['data_agendamento'] ?? '';
    $hora_agendamento = $_POST['hora_agendamento'] ?? '';
    $quantidade_pessoas = intval($_POST['quantidade_pessoas'] ?? 1);
    $empresa_id = $_SESSION['empresa_id'];

    // Validações básicas
    if (empty($data_agendamento) || empty($hora_agendamento) || $quantidade_pessoas < 1) {
        header("Location: ../front-end/pag_agendar_empresa.php?erro=" . urlencode("Preencha todos os campos corretamente."));
        exit();
    }

    // Validação de quantidade (1 a 20 pessoas)
    if ($quantidade_pessoas < 1 || $quantidade_pessoas > 20) {
        header("Location: ../front-end/pag_agendar_empresa.php?erro=" . urlencode("A quantidade de pessoas deve estar entre 1 e 20."));
        exit();
    }

    // Validação de data: Não permitir datas passadas
    $data_atual = date('Y-m-d');
    if ($data_agendamento < $data_atual) {
        header("Location: ../front-end/pag_agendar_empresa.php?erro=" . urlencode("Não é possível agendar para uma data passada."));
        exit();
    }

    // Validação de dia útil (segunda a sexta)
    if (!isDiaUtil($data_agendamento)) {
        header("Location: ../front-end/pag_agendar_empresa.php?erro=" . urlencode("Agendamentos só são permitidos de segunda a sexta-feira."));
        exit();
    }

    // Validação da hora (10:00 às 18:00, intervalos de 30 min)
    $horariosValidos = gerarHorariosDisponiveis();
    if (!in_array($hora_agendamento, $horariosValidos)) {
        header("Location: ../front-end/pag_agendar_empresa.php?erro=" . urlencode("Horário inválido. Escolha um horário entre 10:00 e 18:00."));
        exit();
    }

    // Validação para não permitir agendamento em horário já passou no dia atual
    if ($data_agendamento === $data_atual) {
        $hora_atual = date('H:i');
        if ($hora_agendamento <= $hora_atual) {
            header("Location: ../front-end/pag_agendar_empresa.php?erro=" . urlencode("Não é possível agendar para um horário já passado."));
            exit();
        }
    }

    try {
        $conexao = conectarBanco();

        // Busca informações da empresa
        $stmt_empresa = $conexao->prepare("SELECT nome_instituicao, email, cnpj FROM empresas WHERE id = ?");
        $stmt_empresa->execute([$empresa_id]);
        $empresa = $stmt_empresa->fetch(PDO::FETCH_ASSOC);

        if (!$empresa) {
            header("Location: ../front-end/pag_agendar_empresa.php?erro=" . urlencode("Erro ao buscar dados da empresa."));
            exit();
        }

        // Verificar se a empresa já tem agendamento pendente ou confirmado para a mesma data
        $stmt_check = $conexao->prepare("
            SELECT COUNT(*) FROM agendamentos 
            WHERE empresa_id = ? AND data_agendamento = ? AND status IN ('pendente', 'confirmado')
        ");
        $stmt_check->execute([$empresa_id, $data_agendamento]);
        $conflito_empresa = $stmt_check->fetchColumn();

        if ($conflito_empresa > 0) {
            header("Location: ../front-end/pag_agendar_empresa.php?erro=" . urlencode("Sua empresa já possui um agendamento para esta data."));
            exit();
        }

        // Inserir agendamento (SEMPRE com status pendente para empresas)
        $stmt = $conexao->prepare("
            INSERT INTO agendamentos (nome, email, cpf, data_agendamento, hora_agendamento, status, tipo_agendamento, empresa_id, quantidade_pessoas) 
            VALUES (?, ?, ?, ?, ?, 'pendente', 'empresa', ?, ?)
        ");
        $stmt->execute([
            $empresa['nome_instituicao'], 
            $empresa['email'], 
            $empresa['cnpj'], 
            $data_agendamento, 
            $hora_agendamento, 
            $empresa_id,
            $quantidade_pessoas
        ]);

        // Redirecionar para página de sucesso
        header("Location: ../front-end/pag_sucesso_empresa.php");
        exit();

    } catch (PDOException $e) {
        error_log("Erro ao agendar empresa: " . $e->getMessage());
        header("Location: ../front-end/pag_agendar_empresa.php?erro=" . urlencode("Erro ao processar agendamento. Tente novamente."));
        exit();
    }
} else {
    header("Location: ../front-end/pag_agendar_empresa.php");
    exit();
}