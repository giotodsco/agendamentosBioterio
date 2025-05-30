<?php
session_start();
require_once '../back-end/functions.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: pag_login_usuario.php?login_required=true");
    exit();
}

// Converter dia da semana para português
function diaSemanaPortugues($data) {
    $diasIngles = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $diasPortugues = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
    
    $diaIngles = date('l', strtotime($data));
    return str_replace($diasIngles, $diasPortugues, $diaIngles);
}

// Processar cancelamento e exclusão de agendamento
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao'])) {
    $acao = $_POST['acao'];
    $agendamento_id = $_POST['agendamento_id'] ?? '';
    
    if (!empty($agendamento_id)) {
        if ($acao === 'cancelar') {
            $resultado = cancelarAgendamento($agendamento_id, $_SESSION['usuario_id']);
            if ($resultado['sucesso']) {
                $mensagem_sucesso = $resultado['mensagem'];
            } else {
                $mensagem_erro = $resultado['mensagem'];
            }
        } elseif ($acao === 'excluir') {
            try {
                $conexao = conectarBanco();
                
                // CORRIGIDO: Verificar se o agendamento pertence ao usuário e pode ser excluído
                $stmt = $conexao->prepare("
                    SELECT status, data_agendamento FROM agendamentos 
                    WHERE id = ? AND usuario_id = ?
                ");
                $stmt->execute([$agendamento_id, $_SESSION['usuario_id']]);
                $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$agendamento) {
                    $mensagem_erro = "Agendamento não encontrado.";
                } else {
                    // NOVA LÓGICA: Permitir exclusão de agendamentos cancelados, negados, concluídos ou confirmados do passado
                    $isConcluido = ($agendamento['status'] === 'confirmado' && $agendamento['data_agendamento'] < date('Y-m-d')) || 
                                   $agendamento['status'] === 'concluido';
                    $isCancelado = $agendamento['status'] === 'cancelado';
                    $isNegado = $agendamento['status'] === 'negado';
                    
                    if (!$isCancelado && !$isConcluido && !$isNegado) {
                        $mensagem_erro = "Apenas agendamentos cancelados, negados ou concluídos podem ser excluídos.";
                    } else {
                        // Excluir o agendamento
                        $stmt = $conexao->prepare("DELETE FROM agendamentos WHERE id = ? AND usuario_id = ?");
                        $stmt->execute([$agendamento_id, $_SESSION['usuario_id']]);
                        
                        if ($stmt->rowCount() > 0) {
                            $mensagem_sucesso = "Agendamento excluído com sucesso.";
                        } else {
                            $mensagem_erro = "Erro ao excluir agendamento.";
                        }
                    }
                }
            } catch (PDOException $e) {
                $mensagem_erro = "Erro ao excluir agendamento: " . $e->getMessage();
            }
        }
    }
}

$agendamentos = [];

try {
    $conexao = conectarBanco();
    $stmt = $conexao->prepare("
        SELECT * FROM agendamentos 
        WHERE usuario_id = ? 
        ORDER BY data_agendamento DESC, hora_agendamento ASC
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $agendamentos_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // CORRIGIDO: Processar status de exibição para cada agendamento
    $hoje = date('Y-m-d');
    foreach ($agendamentos_raw as $agendamento) {
        // Determinar status de exibição baseado na lógica do admin
        if ($agendamento['status'] === 'confirmado' && $agendamento['data_agendamento'] < $hoje) {
            $agendamento['status_display'] = 'concluido';
        } else {
            $agendamento['status_display'] = $agendamento['status'];
        }
        $agendamentos[] = $agendamento;
    }

} catch (PDOException $e) {
    $mensagem_erro = "Erro ao buscar agendamentos: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Agendamentos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        * {
            color: rgb(60, 59, 59);
            font-family: Georgia, 'Times New Roman', Times, serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: radial-gradient(circle, rgba(173,199,205,1) 0%, rgba(169,189,165,1) 31%, rgba(64, 122, 53, 0.819) 85%);
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, rgba(64, 122, 53, 0.9) 0%, rgba(44, 81, 36, 0.9) 100%);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            flex-shrink: 0;
            box-shadow: 0 4px 15px rgba(64, 122, 53, 0.3);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .btn-back {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 10px 18px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back:hover {
            background-color: rgba(255, 255, 255, 0.3);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-info span {
            color: white;
            font-size: 16px;
            background-color: rgba(255, 255, 255, 0.1);
            padding: 8px 15px;
            border-radius: 20px;
        }

        .btn-logout {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid white;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .btn-logout:hover {
            background-color: rgba(255, 255, 255, 0.3);
            color: white;
        }

        .main-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 15px;
            overflow: hidden;
        }

        .content-container {
            background-color: rgb(225, 225, 228);
            width: 100%;
            max-width: 1200px;
            height: 100%;
            border-radius: 15px;
            box-shadow: 5px 5px 50px rgba(90, 90, 90, 0.392);
            padding: 20px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        h1 {
            color: rgba(64, 122, 53, 0.819);
            font-size: 26px;
            text-align: center;
            margin-bottom: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            flex-shrink: 0;
        }

        .welcome-message {
            background: linear-gradient(135deg, rgba(64, 122, 53, 0.15) 0%, rgba(64, 122, 53, 0.08) 100%);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid rgba(64, 122, 53, 0.819);
            flex-shrink: 0;
        }

        .welcome-message strong {
            color: rgba(64, 122, 53, 0.819);
        }

        .alert {
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 14px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
            flex-shrink: 0;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(64, 122, 53, 0.15) 0%, rgba(64, 122, 53, 0.08) 100%);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            border-left: 4px solid rgba(64, 122, 53, 0.819);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: rgba(64, 122, 53, 0.819);
            margin-bottom: 6px;
        }

        .stat-label {
            font-size: 12px;
            color: rgba(64, 122, 53, 0.819);
            font-weight: bold;
            opacity: 0.8;
        }

        .appointments-container {
            flex: 1;
            overflow-y: auto;
            padding-right: 5px;
        }

        /* Barra de rolagem personalizada */
        .appointments-container::-webkit-scrollbar {
            width: 8px;
        }

        .appointments-container::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 4px;
        }

        .appointments-container::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, rgba(64, 122, 53, 0.819) 0%, rgba(44, 81, 36, 0.819) 100%);
            border-radius: 4px;
        }

        .appointments-container::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, rgba(44, 81, 36, 0.819) 0%, rgba(64, 122, 53, 0.819) 100%);
        }

        .no-appointments {
            text-align: center;
            padding: 60px 20px;
            color: rgba(64, 122, 53, 0.819);
            font-size: 18px;
            background: linear-gradient(135deg, rgba(64, 122, 53, 0.1) 0%, rgba(64, 122, 53, 0.05) 100%);
            border-radius: 15px;
            margin: 20px 0;
        }

        .no-appointments i {
            font-size: 60px;
            margin-bottom: 20px;
            opacity: 0.5;
            color: rgba(64, 122, 53, 0.819);
        }

        .appointments-grid {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        }

        .appointment-card {
            background: linear-gradient(135deg, #e8f5e8 0%, #f0f8f0 100%);
            border-radius: 15px;
            padding: 20px;
            border-left: 5px solid rgba(64, 122, 53, 0.819);
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(64, 122, 53, 0.1);
        }

        .appointment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(64, 122, 53, 0.2);
        }

        .appointment-card.pending-card {
            border-left-color: #ffc107;
            background: linear-gradient(135deg, #f6f8e8 0%, #f9fae8 100%);
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.1);
            animation: pendingGlow 3s infinite;
        }

        @keyframes pendingGlow {
            0%, 100% { border-left-color: #ffc107; }
            50% { border-left-color: #ff9800; }
        }

        .appointment-card.pending-card:hover {
            box-shadow: 0 8px 25px rgba(255, 193, 7, 0.2);
        }

        .appointment-card.concluido-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-left-color: #6c757d;
            opacity: 0.85;
        }

        .appointment-card.concluido-card:hover {
            border-left-color: #5a6268;
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.2);
        }

        .appointment-card.negado-card {
            background: linear-gradient(135deg, #ffebee 0%, #fce4ec 100%);
            border-left-color: #dc3545;
            opacity: 0.9;
        }

        .appointment-card.negado-card:hover {
            border-left-color: #c82333;
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.2);
        }

        .appointment-card.cancelado-card {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
            border-left-color: #ff9800;
            opacity: 0.9;
        }

        .appointment-card.cancelado-card:hover {
            border-left-color: #f57c00;
            box-shadow: 0 8px 25px rgba(255, 152, 0, 0.2);
        }

        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .appointment-id {
            background: linear-gradient(135deg, rgba(64, 122, 53, 0.2) 0%, rgba(64, 122, 53, 0.1) 100%);
            color: rgba(64, 122, 53, 0.819);
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .appointment-id.pendente {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.2) 0%, rgba(255, 193, 7, 0.1) 100%);
            color: #856404;
            animation: pulse 2s infinite;
        }

        .appointment-id.concluido {
            background: linear-gradient(135deg, rgba(108, 117, 125, 0.2) 0%, rgba(108, 117, 125, 0.1) 100%);
            color: #6c757d;
        }

        .appointment-id.negado {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.2) 0%, rgba(220, 53, 69, 0.1) 100%);
            color: #dc3545;
        }

        .appointment-id.cancelado {
            background: linear-gradient(135deg, rgba(255, 152, 0, 0.2) 0%, rgba(255, 152, 0, 0.1) 100%);
            color: #ff9800;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }

        .appointment-status {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .status-confirmado {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 2px solid #28a745;
        }

        .status-concluido {
            background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
            color: #495057;
            border: 2px solid #6c757d;
        }

        .status-cancelado {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
            color: #e65100;
            border: 2px solid #ff9800;
        }

        .status-pendente {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
            border: 2px solid #ffc107;
            animation: statusPulse 2s infinite;
        }

        @keyframes statusPulse {
            0% { transform: scale(1); box-shadow: 0 0 5px rgba(255, 193, 7, 0.4); }
            50% { transform: scale(1.05); box-shadow: 0 0 15px rgba(255, 193, 7, 0.6); }
            100% { transform: scale(1); box-shadow: 0 0 5px rgba(255, 193, 7, 0.4); }
        }

        .status-negado {
            background: linear-gradient(135deg, #ffebee 0%, #fce4ec 100%);
            color: #c62828;
            border: 2px solid #dc3545;
        }

        .appointment-date {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 18px;
            font-weight: bold;
            color: rgba(64, 122, 53, 0.819);
        }

        .appointment-time {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            font-size: 16px;
            color: rgba(64, 122, 53, 0.819);
        }

        .appointment-info {
            font-size: 13px;
            color: rgba(64, 122, 53, 0.819);
            margin-bottom: 15px;
            opacity: 0.8;
            line-height: 1.5;
        }

        .appointment-info p {
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .appointment-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
            font-family: Georgia, 'Times New Roman', Times, serif;
            font-weight: bold;
        }

        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.3);
        }

        .btn-warning {
            background: linear-gradient(135deg, #fd7e14 0%, #e56b03 100%);
            color: white;
        }

        .btn-warning:hover {
            background: linear-gradient(135deg, #e56b03 0%, #dc6002 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(253, 126, 20, 0.3);
        }

        .btn-primary {
            background: linear-gradient(135deg, rgba(64, 122, 53, 0.819) 0%, rgba(44, 81, 36, 0.819) 100%);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, rgba(44, 81, 36, 0.819) 0%, rgba(64, 122, 53, 0.819) 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(64, 122, 53, 0.3);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #5a6268 0%, #495057 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.3);
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
            flex-wrap: wrap;
            flex-shrink: 0;
        }

        .btn-lg {
            padding: 12px 25px;
            font-size: 16px;
        }

        /* Pop-up personalizado */
        .custom-popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 10000;
        }

        .custom-popup {
            background-color: rgb(225, 225, 228);
            border-radius: 15px;
            padding: 30px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: popupSlideIn 0.3s ease-out;
        }

        @keyframes popupSlideIn {
            from {
                opacity: 0;
                transform: scale(0.8) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .popup-icon {
            font-size: 50px;
            margin-bottom: 20px;
            color: rgba(64, 122, 53, 0.819);
        }

        .popup-title {
            font-size: 20px;
            font-weight: bold;
            color: rgba(64, 122, 53, 0.819);
            margin-bottom: 15px;
        }

        .popup-message {
            font-size: 16px;
            color: rgb(60, 59, 59);
            margin-bottom: 25px;
            line-height: 1.4;
        }

        .popup-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .popup-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            font-family: Georgia, 'Times New Roman', Times, serif;
        }

        .popup-btn-confirm {
            background: linear-gradient(135deg, rgba(64, 122, 53, 0.819) 0%, rgba(44, 81, 36, 0.819) 100%);
            color: white;
        }

        .popup-btn-confirm:hover {
            background: linear-gradient(135deg, rgba(44, 81, 36, 0.819) 0%, rgba(64, 122, 53, 0.819) 100%);
        }

        .popup-btn-cancel {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }

        .popup-btn-cancel:hover {
            background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
        }

        @media (max-width: 768px) {
            .header {
                padding: 10px 15px;
                flex-direction: column;
                gap: 15px;
            }

            .header-left {
                width: 100%;
                justify-content: space-between;
            }
            
            .main-container {
                padding: 8px;
            }

            .content-container {
                padding: 15px;
            }

            .appointments-grid {
                grid-template-columns: 1fr;
            }
            
            .stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn-lg {
                width: 100%;
                max-width: 250px;
                justify-content: center;
            }

            .appointment-actions {
                justify-content: center;
            }

            .popup-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Pop-up personalizado -->
    <div class="custom-popup-overlay" id="popup-overlay">
        <div class="custom-popup">
            <div class="popup-icon" id="popup-icon">
                <i class="fa-solid fa-exclamation-triangle"></i>
            </div>
            <div class="popup-title" id="popup-title">Confirmar Ação</div>
            <div class="popup-message" id="popup-message">Tem certeza que deseja realizar esta ação?</div>
            <div class="popup-buttons">
                <button class="popup-btn popup-btn-confirm" id="popup-confirm">Confirmar</button>
                <button class="popup-btn popup-btn-cancel" id="popup-cancel">Cancelar</button>
            </div>
        </div>
    </div>

    <div class="header">
        <div>
            <a href="#" class="btn-logout" onclick="showCustomConfirm('Tem certeza que deseja sair?', () => { document.getElementById('logout-form').submit(); })">
                <i class="fa-solid fa-sign-out-alt"></i> Sair
            </a>
            <form id="logout-form" action="../back-end/auth_usuario.php" method="POST" style="display: none;">
                <input type="hidden" name="acao" value="logout">
            </form>
        </div>
    </div>

    <div class="main-container">
        <div class="content-container">
            <h1><i class="fa-solid fa-calendar-check"></i> Meus Agendamentos</h1>
            
            <div class="welcome-message">
                <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong>, 
                aqui estão todas as suas solicitações de agendamento no Espaço Biodiversidade
            </div>

            <?php if (isset($mensagem_sucesso)): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-check-circle"></i>
                    <?php echo htmlspecialchars($mensagem_sucesso); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($mensagem_erro)): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($mensagem_erro); ?>
                </div>
            <?php endif; ?>

            <?php if (count($agendamentos) > 0): ?>
                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($agendamentos); ?></div>
                        <div class="stat-label">Total de Agendamentos</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['status'] === 'pendente')); ?></div>
                        <div class="stat-label">Aguardando Aprovação</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['status'] === 'confirmado' && $a['data_agendamento'] >= date('Y-m-d'))); ?></div>
                        <div class="stat-label">Próximos</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => 
                            isset($a['status_display']) && $a['status_display'] === 'concluido' || 
                            $a['status'] === 'concluido'
                        )); ?></div>
                        <div class="stat-label">Concluídos</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['status'] === 'cancelado')); ?></div>
                        <div class="stat-label">Cancelados</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['status'] === 'negado')); ?></div>
                        <div class="stat-label">Negados</div>
                    </div>
                </div>

                <div class="appointments-container">
                    <div class="appointments-grid">
                        <?php foreach ($agendamentos as $agendamento): 
                            // NOVA LÓGICA: Usar status_display para determinar estado real
                            $statusReal = isset($agendamento['status_display']) ? $agendamento['status_display'] : $agendamento['status'];
                            
                            $isPendente = $agendamento['status'] === 'pendente';
                            $isConfirmado = $agendamento['status'] === 'confirmado' && $agendamento['data_agendamento'] >= date('Y-m-d');
                            $isConcluido = $statusReal === 'concluido' || $agendamento['status'] === 'concluido';
                            $isCancelado = $agendamento['status'] === 'cancelado';
                            $isNegado = $agendamento['status'] === 'negado';
                            
                            // Determinar classe do card baseada no status real
                            $cardClass = '';
                            if ($isConcluido) {
                                $cardClass = 'concluido-card';
                            } elseif ($isPendente) {
                                $cardClass = 'pending-card';
                            } elseif ($isNegado) {
                                $cardClass = 'negado-card';
                            } elseif ($isCancelado) {
                                $cardClass = 'cancelado-card';
                            }
                            
                            // Determinar status para exibição
                            $statusDisplay = $statusReal;
                            $statusClass = $statusReal;
                        ?>
                        <div class="appointment-card <?php echo $cardClass; ?>">
                            <div class="appointment-header">
                                <div class="appointment-id <?php echo $statusDisplay; ?>">
                                    <i class="fa-solid fa-hashtag"></i> <?php echo $agendamento['id']; ?>
                                    <?php if ($isPendente): ?>
                                        <span style="color: #e67e22; font-size: 10px; font-weight: bold;">EM ANÁLISE</span>
                                    <?php elseif ($isConcluido): ?>
                                        <span style="color: #6c757d; font-size: 10px; font-weight: bold;">CONCLUÍDO</span>
                                    <?php elseif ($isNegado): ?>
                                        <span style="color: #dc3545; font-size: 10px; font-weight: bold;">NEGADO</span>
                                    <?php elseif ($isCancelado): ?>
                                        <span style="color: #ff9800; font-size: 10px; font-weight: bold;">CANCELADO</span>
                                    <?php endif; ?>
                                </div>
                                <div class="appointment-status status-<?php echo $statusClass; ?>">
                                    <?php if ($isPendente): ?>
                                        <i class="fa-solid fa-clock"></i> Pendente
                                    <?php elseif ($isConcluido): ?>
                                        <i class="fa-solid fa-flag-checkered"></i> Concluído
                                    <?php elseif ($isConfirmado): ?>
                                        <i class="fa-solid fa-calendar-check"></i> Confirmado
                                    <?php elseif ($isNegado): ?>
                                        <i class="fa-solid fa-times-circle"></i> Negado
                                    <?php elseif ($isCancelado): ?>
                                        <i class="fa-solid fa-ban"></i> Cancelado
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="appointment-date">
                                <i class="fa-solid fa-calendar"></i>
                                <?php echo date('d/m/Y', strtotime($agendamento['data_agendamento'])); ?>
                                <span style="font-size: 14px; color: rgba(64, 122, 53, 0.819); opacity: 0.8;">
                                    (<?php echo diaSemanaPortugues($agendamento['data_agendamento']); ?>)
                                </span>
                            </div>
                            
                            <div class="appointment-time">
                                <i class="fa-solid fa-clock"></i>
                                <?php echo date('H:i', strtotime($agendamento['hora_agendamento'])); ?>
                            </div>
                            
                            <div class="appointment-info">
                                <p><i class="fa-solid fa-calendar-plus"></i> <strong>Agendado em:</strong> <?php echo date('d/m/Y H:i', strtotime($agendamento['data_criacao'])); ?></p>
                                
                                <?php if ($isPendente): ?>
                                    <p><i class="fa-solid fa-hourglass-half"></i> <strong>Status:</strong> Aguardando aprovação da administração</p>
                                <?php elseif ($isConcluido): ?>
                                    <p><i class="fa-solid fa-flag-checkered"></i> <strong>Visita concluída em:</strong> <?php echo date('d/m/Y', strtotime($agendamento['data_agendamento'])); ?></p>
                                <?php elseif ($agendamento['data_cancelamento']): ?>
                                    <p><i class="fa-solid fa-calendar-times"></i> <strong>Cancelado em:</strong> <?php echo date('d/m/Y H:i', strtotime($agendamento['data_cancelamento'])); ?></p>
                                <?php elseif ($isNegado): ?>
                                    <p><i class="fa-solid fa-times"></i> <strong>Status:</strong> Solicitação não aprovada pela administração</p>
                                <?php elseif ($isConfirmado): ?>
                                    <p><i class="fa-solid fa-check-circle"></i> <strong>Status:</strong> Agendamento confirmado - compareça no horário marcado</p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="appointment-actions">
                                <?php if ($isConfirmado && !$isCancelado): ?>
                                    <!-- Cancelar agendamentos confirmados futuros -->
                                    <form method="POST" style="display: inline;" id="cancel-form-<?php echo $agendamento['id']; ?>">
                                        <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                        <input type="hidden" name="acao" value="cancelar">
                                        <button type="button" class="btn btn-warning" 
                                                onclick="showCustomConfirm('Tem certeza que deseja cancelar este agendamento?', () => { document.getElementById('cancel-form-<?php echo $agendamento['id']; ?>').submit(); })">
                                            <i class="fa-solid fa-ban"></i> Cancelar
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if ($isCancelado || $isConcluido || $isNegado): ?>
                                    <!-- Excluir agendamentos finalizados -->
                                    <form method="POST" style="display: inline;" id="delete-form-<?php echo $agendamento['id']; ?>">
                                        <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                        <input type="hidden" name="acao" value="excluir">
                                        <button type="button" class="btn btn-danger" 
                                                onclick="showCustomConfirm('Tem certeza que deseja excluir este agendamento? Esta ação não pode ser desfeita!', () => { document.getElementById('delete-form-<?php echo $agendamento['id']; ?>').submit(); })">
                                            <i class="fa-solid fa-trash"></i> Excluir
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if ($isPendente): ?>
                                    <!-- Para agendamentos pendentes, apenas mostrar que está em análise -->
                                    <span style="font-size: 11px; color: #856404; font-weight: bold; padding: 8px 12px; background: rgba(255, 193, 7, 0.1); border-radius: 8px;">
                                        <i class="fa-solid fa-hourglass-half"></i> Em análise administrativa
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="appointments-container">
                    <div class="no-appointments">
                        <i class="fa-solid fa-calendar-times"></i><br>
                        Você ainda não possui agendamentos.<br>
                        Faça seu primeiro agendamento agora!
                    </div>
                </div>
            <?php endif; ?>

            <div class="action-buttons">
                <a href="pag_agendar_logado.php" class="btn btn-primary btn-lg">
                    <i class="fa-solid fa-calendar-plus"></i>
                    Novo Agendamento
                </a>
                <a href="pag_dados_usuario.php" class="btn btn-secondary btn-lg">
                    <i class="fa-solid fa-user-circle"></i>
                    Meus Dados
                </a>
                <a href="pag_inicial.html" class="btn btn-secondary btn-lg">
                    <i class="fa-solid fa-home"></i>
                    Página Inicial
                </a>
            </div>
        </div>
    </div>

    <script>
        // Sistema de pop-up personalizado
        function showCustomConfirm(message, onConfirm) {
            const overlay = document.getElementById('popup-overlay');
            const messageElement = document.getElementById('popup-message');
            const confirmBtn = document.getElementById('popup-confirm');
            const cancelBtn = document.getElementById('popup-cancel');
            
            messageElement.textContent = message;
            overlay.style.display = 'flex';
            
            // Remover listeners anteriores
            confirmBtn.onclick = null;
            cancelBtn.onclick = null;
            
            // Adicionar novos listeners
            confirmBtn.onclick = function() {
                overlay.style.display = 'none';
                onConfirm();
            };
            
            cancelBtn.onclick = function() {
                overlay.style.display = 'none';
            };
            
            // Fechar com ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    overlay.style.display = 'none';
                }
            });
        }

        // Destacar agendamentos pendentes
        document.addEventListener('DOMContentLoaded', function() {
            // Scroll suave para o primeiro agendamento pendente
            const pendenteCard = document.querySelector('.pending-card');
            if (pendenteCard) {
                setTimeout(() => {
                    pendenteCard.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                }, 1000);
            }
        });
    </script>
</body>
</html>