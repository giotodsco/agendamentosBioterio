<?php
session_start();
require_once '../back-end/functions.php';

// Verifica se a empresa está logada
if (!isset($_SESSION['empresa_logada']) || $_SESSION['empresa_logada'] !== true) {
    header("Location: pag_login_usuario.php?tab=empresa");
    exit();
}

// Converter dia da semana para português
function diaSemanaPortugues($data) {
    $diasIngles = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $diasPortugues = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
    
    $diaIngles = date('l', strtotime($data));
    return str_replace($diasIngles, $diasPortugues, $diaIngles);
}

// Processar cancelamento de agendamento de empresa
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao'])) {
    $acao = $_POST['acao'];
    $agendamento_id = $_POST['agendamento_id'] ?? '';
    
    if (!empty($agendamento_id) && $acao === 'cancelar') {
        try {
            $conexao = conectarBanco();
            
            // Verificar se o agendamento pertence à empresa
            $stmt = $conexao->prepare("
                SELECT status, data_agendamento FROM agendamentos 
                WHERE id = ? AND empresa_id = ?
            ");
            $stmt->execute([$agendamento_id, $_SESSION['empresa_id']]);
            $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$agendamento) {
                $mensagem_erro = "Agendamento não encontrado.";
            } elseif ($agendamento['status'] === 'cancelado') {
                $mensagem_erro = "Agendamento já foi cancelado.";
            } else {
                // Cancelar o agendamento
                $stmt = $conexao->prepare("
                    UPDATE agendamentos 
                    SET status = 'cancelado', data_cancelamento = NOW() 
                    WHERE id = ? AND empresa_id = ?
                ");
                $stmt->execute([$agendamento_id, $_SESSION['empresa_id']]);
                
                if ($stmt->rowCount() > 0) {
                    $mensagem_sucesso = "Agendamento cancelado com sucesso.";
                } else {
                    $mensagem_erro = "Erro ao cancelar agendamento.";
                }
            }
        } catch (PDOException $e) {
            $mensagem_erro = "Erro ao cancelar agendamento: " . $e->getMessage();
        }
    }
}

$agendamentos = [];

try {
    $conexao = conectarBanco();
    $stmt = $conexao->prepare("
        SELECT * FROM agendamentos 
        WHERE empresa_id = ? 
        ORDER BY data_agendamento DESC, hora_agendamento ASC
    ");
    $stmt->execute([$_SESSION['empresa_id']]);
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $mensagem_erro = "Erro ao buscar agendamentos: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Agendamentos - Empresa</title>
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
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            flex-shrink: 0;
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
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

        .company-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .company-info span {
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
            color: #856404;
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
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.15) 0%, rgba(255, 193, 7, 0.08) 100%);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid #ffc107;
            flex-shrink: 0;
        }

        .welcome-message strong {
            color: #856404;
        }

        .alert {
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 14px;
            flex-shrink: 0;
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
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.15) 0%, rgba(255, 193, 7, 0.08) 100%);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            border-left: 4px solid #ffc107;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #856404;
            margin-bottom: 6px;
        }

        .stat-label {
            font-size: 12px;
            color: #856404;
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
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            border-radius: 4px;
        }

        .appointments-container::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #e0a800 0%, #d39e00 100%);
        }

        .no-appointments {
            text-align: center;
            padding: 60px 20px;
            color: #856404;
            font-size: 18px;
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, rgba(255, 193, 7, 0.05) 100%);
            border-radius: 15px;
            margin: 20px 0;
        }

        .no-appointments i {
            font-size: 60px;
            margin-bottom: 20px;
            opacity: 0.5;
            color: #ffc107;
        }

        .appointments-grid {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        }

        .appointment-card {
            background: linear-gradient(135deg, #ffffff 0%, #fffbf0 100%);
            border-radius: 15px;
            padding: 20px;
            border-left: 5px solid #ffc107;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.1);
        }

        .appointment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(255, 193, 7, 0.2);
        }

        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .appointment-id {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.2) 0%, rgba(255, 193, 7, 0.1) 100%);
            color: #856404;
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 6px;
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

        .status-cancelado {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 2px solid #dc3545;
        }

        .status-pendente {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
            border: 2px solid #ffc107;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }

        .status-negado {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 2px solid #dc3545;
        }

        .appointment-date {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 18px;
            font-weight: bold;
            color: #856404;
        }

        .appointment-time {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            font-size: 16px;
            color: #856404;
        }

        .appointment-info {
            font-size: 13px;
            color: #856404;
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

        .appointment-details {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, rgba(255, 193, 7, 0.05) 100%);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 3px solid #ffc107;
        }

        .pessoas-info {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: bold;
            color: #856404;
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

        .btn-primary {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #e0a800 0%, #d39e00 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 193, 7, 0.3);
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
            color: #ffc107;
        }

        .popup-title {
            font-size: 20px;
            font-weight: bold;
            color: #856404;
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
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: white;
        }

        .popup-btn-confirm:hover {
            background: linear-gradient(135deg, #e0a800 0%, #d39e00 100%);
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
            <div class="popup-message" id="popup-message">Tem certeza que deseja cancelar este agendamento?</div>
            <div class="popup-buttons">
                <button class="popup-btn popup-btn-confirm" id="popup-confirm">Confirmar</button>
                <button class="popup-btn popup-btn-cancel" id="popup-cancel">Cancelar</button>
            </div>
        </div>
    </div>

    <div class="header">
        <div class="header-left">
            <a href="pag_inicial.html" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i> 
                <span>Voltar ao Início</span>
            </a>
            <div class="company-info">
                <i class="fa-solid fa-building"></i>
                <span>Olá, <?php echo htmlspecialchars($_SESSION['empresa_nome']); ?>!</span>
            </div>
        </div>
        <div>
            <a href="#" class="btn-logout" onclick="showCustomConfirm('Tem certeza que deseja sair?', () => { document.getElementById('logout-form').submit(); })">
                <i class="fa-solid fa-sign-out-alt"></i> Sair
            </a>
            <form id="logout-form" action="../back-end/auth_empresa.php" method="POST" style="display: none;">
                <input type="hidden" name="acao" value="logout_empresa">
            </form>
        </div>
    </div>

    <div class="main-container">
        <div class="content-container">
            <h1><i class="fa-solid fa-building"></i> Meus Agendamentos Empresariais</h1>
            
            <div class="welcome-message">
                <strong><?php echo htmlspecialchars($_SESSION['empresa_nome']); ?></strong>, 
                aqui estão todas as suas solicitações de agendamento no Biotério.
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
                        <div class="stat-label">Total de Solicitações</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['status'] === 'pendente')); ?></div>
                        <div class="stat-label">Aguardando Análise</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['status'] === 'confirmado')); ?></div>
                        <div class="stat-label">Confirmados</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['status'] === 'negado')); ?></div>
                        <div class="stat-label">Negados</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo array_sum(array_map(fn($a) => $a['quantidade_pessoas'] ?? 1, $agendamentos)); ?></div>
                        <div class="stat-label">Total de Pessoas</div>
                    </div>
                </div>

                <div class="appointments-container">
                    <div class="appointments-grid">
                        <?php foreach ($agendamentos as $agendamento): 
                            $isPendente = $agendamento['status'] === 'pendente';
                            $isConfirmado = $agendamento['status'] === 'confirmado';
                            $isNegado = $agendamento['status'] === 'negado';
                            $isCancelado = $agendamento['status'] === 'cancelado';
                            $isUpcoming = $isConfirmado && $agendamento['data_agendamento'] >= date('Y-m-d');
                            $isConcluido = $isConfirmado && $agendamento['data_agendamento'] < date('Y-m-d');
                            
                            // Determinar status display
                            $statusDisplay = $agendamento['status'];
                            $statusClass = $agendamento['status'];
                            if ($isConcluido) {
                                $statusDisplay = 'concluído';
                                $statusClass = 'confirmado';
                            }
                        ?>
                        <div class="appointment-card">
                            <div class="appointment-header">
                                <div class="appointment-id">
                                    <i class="fa-solid fa-hashtag"></i> <?php echo $agendamento['id']; ?>
                                </div>
                                <div class="appointment-status status-<?php echo $statusClass; ?>">
                                    <?php if ($isPendente): ?>
                                        <i class="fa-solid fa-clock"></i> Em Análise
                                    <?php elseif ($isConcluido): ?>
                                        <i class="fa-solid fa-check-circle"></i> Concluído
                                    <?php elseif ($isConfirmado): ?>
                                        <i class="fa-solid fa-calendar-check"></i> Confirmado
                                    <?php elseif ($isNegado): ?>
                                        <i class="fa-solid fa-times-circle"></i> Negado
                                    <?php else: ?>
                                        <i class="fa-solid fa-ban"></i> Cancelado
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="appointment-date">
                                <i class="fa-solid fa-calendar"></i>
                                <?php echo date('d/m/Y', strtotime($agendamento['data_agendamento'])); ?>
                                <span style="font-size: 14px; color: #856404; opacity: 0.8;">
                                    (<?php echo diaSemanaPortugues($agendamento['data_agendamento']); ?>)
                                </span>
                            </div>
                            
                            <div class="appointment-time">
                                <i class="fa-solid fa-clock"></i>
                                <?php echo date('H:i', strtotime($agendamento['hora_agendamento'])); ?>
                            </div>

                            <div class="appointment-details">
                                <div class="pessoas-info">
                                    <i class="fa-solid fa-users"></i>
                                    <?php echo $agendamento['quantidade_pessoas'] ?? 1; ?> pessoa<?php echo ($agendamento['quantidade_pessoas'] ?? 1) != 1 ? 's' : ''; ?>
                                </div>
                            </div>
                            
                            <div class="appointment-info">
                                <p><i class="fa-solid fa-calendar-plus"></i> <strong>Solicitado em:</strong> <?php echo date('d/m/Y H:i', strtotime($agendamento['data_criacao'])); ?></p>
                                
                                <?php if ($isPendente): ?>
                                    <p><i class="fa-solid fa-hourglass-half"></i> <strong>Status:</strong> Aguardando análise da administração</p>
                                <?php elseif ($agendamento['data_cancelamento']): ?>
                                    <p><i class="fa-solid fa-calendar-times"></i> <strong>Cancelado em:</strong> <?php echo date('d/m/Y H:i', strtotime($agendamento['data_cancelamento'])); ?></p>
                                <?php elseif ($isConcluido): ?>
                                    <p><i class="fa-solid fa-check-double"></i> <strong>Visita realizada em:</strong> <?php echo date('d/m/Y', strtotime($agendamento['data_agendamento'])); ?></p>
                                <?php elseif ($isNegado): ?>
                                    <p><i class="fa-solid fa-times"></i> <strong>Status:</strong> Solicitação não aprovada pela administração</p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="appointment-actions">
                                <?php if ($isUpcoming && !$isCancelado): ?>
                                    <form method="POST" style="display: inline;" id="cancel-form-<?php echo $agendamento['id']; ?>">
                                        <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                        <input type="hidden" name="acao" value="cancelar">
                                        <button type="button" class="btn btn-danger" 
                                                onclick="showCustomConfirm('Tem certeza que deseja cancelar este agendamento?', () => { document.getElementById('cancel-form-<?php echo $agendamento['id']; ?>').submit(); })">
                                            <i class="fa-solid fa-ban"></i> Cancelar
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="appointments-container">
                    <div class="no-appointments">
                        <i class="fa-solid fa-building"></i><br>
                        Sua empresa ainda não possui agendamentos.<br>
                        Faça sua primeira solicitação agora!
                    </div>
                </div>
            <?php endif; ?>

            <div class="action-buttons">
                <a href="pag_agendar_empresa.php" class="btn btn-primary btn-lg">
                    <i class="fa-solid fa-plus"></i>
                    Nova Solicitação
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
    </script>
</body>
</html>