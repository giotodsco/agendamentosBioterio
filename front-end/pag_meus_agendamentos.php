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
                
                // Verificar se o agendamento pertence ao usuário e está cancelado OU concluído
                $stmt = $conexao->prepare("
                    SELECT status, data_agendamento FROM agendamentos 
                    WHERE id = ? AND usuario_id = ?
                ");
                $stmt->execute([$agendamento_id, $_SESSION['usuario_id']]);
                $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$agendamento) {
                    $mensagem_erro = "Agendamento não encontrado.";
                } else {
                    $isConcluido = $agendamento['status'] === 'confirmado' && $agendamento['data_agendamento'] < date('Y-m-d');
                    $isCancelado = $agendamento['status'] === 'cancelado';
                    
                    if (!$isCancelado && !$isConcluido) {
                        $mensagem_erro = "Apenas agendamentos cancelados ou concluídos podem ser excluídos.";
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
            background-color: rgba(64, 122, 53, 0.9);
            padding: 12px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            flex-shrink: 0;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-info span {
            color: white;
            font-size: 14px;
        }

        .user-info a {
            color: white;
            text-decoration: none;
            margin-left: 12px;
            padding: 4px 8px;
            background-color: rgba(255,255,255,0.2);
            border-radius: 4px;
            transition: all 0.3s;
            font-size: 12px;
        }

        .user-info a:hover {
            background-color: rgba(255,255,255,0.4);
            transform: translateY(-1px);
        }

        .btn-logout {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid white;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
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
            color: rgb(55, 75, 51);
            font-size: 24px;
            text-align: center;
            margin-bottom: 20px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .welcome-message {
            background-color: rgba(64, 122, 53, 0.1);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            border-left: 3px solid rgba(64, 122, 53, 0.819);
            flex-shrink: 0;
        }

        .welcome-message strong {
            color: rgba(64, 122, 53, 0.819);
        }

        .alert {
            padding: 8px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 14px;
            flex-shrink: 0;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
            flex-shrink: 0;
        }

        .stat-card {
            background-color: rgba(64, 122, 53, 0.1);
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            border-left: 3px solid rgba(64, 122, 53, 0.819);
        }

        .stat-number {
            font-size: 20px;
            font-weight: bold;
            color: rgba(64, 122, 53, 0.819);
        }

        .stat-label {
            font-size: 11px;
            color: rgb(100, 100, 100);
            margin-top: 4px;
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
            background: rgba(64, 122, 53, 0.6);
            border-radius: 4px;
        }

        .appointments-container::-webkit-scrollbar-thumb:hover {
            background: rgba(64, 122, 53, 0.8);
        }

        .no-appointments {
            text-align: center;
            padding: 40px 20px;
            color: rgb(100, 100, 100);
            font-size: 16px;
        }

        .appointments-grid {
            display: grid;
            gap: 15px;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        }

        .appointment-card {
            background-color: white;
            border-radius: 12px;
            padding: 15px;
            border-left: 4px solid rgba(64, 122, 53, 0.819);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .appointment-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .appointment-id {
            background-color: rgba(64, 122, 53, 0.1);
            color: rgba(64, 122, 53, 0.819);
            padding: 3px 6px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: bold;
        }

        .appointment-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
        }

        .status-confirmado {
            background-color: #d4edda;
            color: #155724;
        }

        .status-concluido {
            background-color: #d4edda;
            color: #155724;
        }

        .status-cancelado {
            background-color: #f8d7da;
            color: #721c24;
        }

        .completed-appointments {
            background-color: rgba(40, 167, 69, 0.05);
            border-left-color: #28a745;
        }

        .appointment-date {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-size: 16px;
            font-weight: bold;
            color: rgba(64, 122, 53, 0.819);
        }

        .appointment-time {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            font-size: 14px;
            color: rgb(60, 59, 59);
        }

        .appointment-info {
            font-size: 12px;
            color: rgb(100, 100, 100);
            margin-bottom: 12px;
        }

        .appointment-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.3s;
            font-family: Georgia, 'Times New Roman', Times, serif;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .btn-warning {
            background-color: #fd7e14;
            color: white;
        }

        .btn-warning:hover {
            background-color: #e56b03;
        }

        .btn-primary {
            background-color: rgba(64, 122, 53, 0.819);
            color: white;
        }

        .btn-primary:hover {
            background-color: rgba(44, 81, 36, 0.819);
            color: white;
        }

        .btn-secondary {
            background-color: rgb(200, 200, 200);
            color: rgb(60, 59, 59);
        }

        .btn-secondary:hover {
            background-color: rgb(180, 180, 180);
            color: rgb(60, 59, 59);
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
            flex-shrink: 0;
        }

        .btn-lg {
            padding: 10px 20px;
            font-size: 14px;
        }

        .upcoming-appointments {
            background-color: rgba(255, 193, 7, 0.1);
            border-left-color: #ffc107;
        }

        .past-appointments {
            opacity: 0.8;
            background-color: rgba(108, 117, 125, 0.05);
            border-left-color: #6c757d;
        }

        .cancelled-appointments {
            opacity: 0.9;
            background-color: rgba(220, 53, 69, 0.05);
            border-left-color: #dc3545;
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
            border-radius: 12px;
            padding: 25px;
            max-width: 350px;
            width: 90%;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
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
            font-size: 40px;
            margin-bottom: 15px;
            color: #ffc107;
        }

        .popup-title {
            font-size: 18px;
            font-weight: bold;
            color: rgb(55, 75, 51);
            margin-bottom: 12px;
        }

        .popup-message {
            font-size: 14px;
            color: rgb(60, 59, 59);
            margin-bottom: 20px;
            line-height: 1.4;
        }

        .popup-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .popup-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            font-family: Georgia, 'Times New Roman', Times, serif;
        }

        .popup-btn-confirm {
            background-color: rgba(64, 122, 53, 0.819);
            color: white;
        }

        .popup-btn-confirm:hover {
            background-color: rgba(44, 81, 36, 0.819);
        }

        .popup-btn-cancel {
            background-color: #dc3545;
            color: white;
        }

        .popup-btn-cancel:hover {
            background-color: #c82333;
        }

        @media (max-width: 768px) {
            .header {
                padding: 8px 15px;
                flex-direction: column;
                gap: 8px;
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
        <div class="user-info">
            <i class="fa-solid fa-user"></i>
            <span>Olá, <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>!</span>
            <a href="pag_dados_usuario.php">
                <i class="fa-solid fa-user-circle"></i> Meus Dados
            </a>
        </div>
        <div>
            <a href="../back-end/auth_usuario.php" class="btn-logout" 
               onclick="event.preventDefault(); showCustomConfirm('Tem certeza que deseja sair?', () => { document.getElementById('logout-form').submit(); })">
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
                aqui estão todos os seus agendamentos no Biotério.
            </div>

            <?php if (isset($mensagem_sucesso)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($mensagem_sucesso); ?></div>
            <?php endif; ?>
            
            <?php if (isset($mensagem_erro)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($mensagem_erro); ?></div>
            <?php endif; ?>

            <?php if (count($agendamentos) > 0): ?>
                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($agendamentos); ?></div>
                        <div class="stat-label">Total</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['status'] === 'confirmado' && $a['data_agendamento'] >= date('Y-m-d'))); ?></div>
                        <div class="stat-label">Próximos</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['status'] === 'confirmado' && $a['data_agendamento'] < date('Y-m-d'))); ?></div>
                        <div class="stat-label">Concluídos</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['status'] === 'cancelado')); ?></div>
                        <div class="stat-label">Cancelados</div>
                    </div>
                </div>

                <div class="appointments-container">
                    <div class="appointments-grid">
                        <?php foreach ($agendamentos as $agendamento): 
                            $isUpcoming = $agendamento['status'] === 'confirmado' && $agendamento['data_agendamento'] >= date('Y-m-d');
                            $isPast = $agendamento['data_agendamento'] < date('Y-m-d');
                            $isCancelled = $agendamento['status'] === 'cancelado';
                            $isConcluido = $agendamento['status'] === 'confirmado' && $agendamento['data_agendamento'] < date('Y-m-d');
                            
                            $cardClass = '';
                            if ($isCancelled) {
                                $cardClass = 'cancelled-appointments';
                            } elseif ($isConcluido) {
                                $cardClass = 'completed-appointments';
                            } elseif ($isUpcoming) {
                                $cardClass = 'upcoming-appointments';
                            } elseif ($isPast) {
                                $cardClass = 'past-appointments';
                            }
                            
                            // Determinar status display
                            $statusDisplay = $agendamento['status'];
                            $statusClass = $agendamento['status'];
                            if ($isConcluido) {
                                $statusDisplay = 'concluído';
                                $statusClass = 'concluido';
                            }
                        ?>
                        <div class="appointment-card <?php echo $cardClass; ?>">
                            <div class="appointment-header">
                                <div class="appointment-id">
                                    <i class="fa-solid fa-hashtag"></i> <?php echo $agendamento['id']; ?>
                                </div>
                                <div class="appointment-status status-<?php echo $statusClass; ?>">
                                    <?php if ($isConcluido): ?>
                                        <i class="fa-solid fa-check-circle"></i>
                                    <?php elseif ($agendamento['status'] === 'confirmado'): ?>
                                        <i class="fa-solid fa-clock"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-times-circle"></i>
                                    <?php endif; ?>
                                    <?php echo ucfirst($statusDisplay); ?>
                                </div>
                            </div>
                            
                            <div class="appointment-date">
                                <i class="fa-solid fa-calendar"></i>
                                <?php echo date('d/m/Y', strtotime($agendamento['data_agendamento'])); ?>
                                <span style="font-size: 12px; color: rgb(100, 100, 100);">
                                    (<?php echo diaSemanaPortugues($agendamento['data_agendamento']); ?>)
                                </span>
                            </div>
                            
                            <div class="appointment-time">
                                <i class="fa-solid fa-clock"></i>
                                <?php echo date('H:i', strtotime($agendamento['hora_agendamento'])); ?>
                            </div>
                            
                            <div class="appointment-info">
                                <strong>Agendado em:</strong> <?php echo date('d/m/Y H:i', strtotime($agendamento['data_criacao'])); ?><br>
                                <?php if ($agendamento['data_cancelamento']): ?>
                                    <strong>Cancelado em:</strong> <?php echo date('d/m/Y H:i', strtotime($agendamento['data_cancelamento'])); ?>
                                <?php elseif ($isConcluido): ?>
                                    <strong>Visita concluída em:</strong> <?php echo date('d/m/Y', strtotime($agendamento['data_agendamento'])); ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="appointment-actions">
                                <?php if ($agendamento['status'] === 'confirmado' && $agendamento['data_agendamento'] >= date('Y-m-d')): ?>
                                    <form method="POST" style="display: inline;" id="cancel-form-<?php echo $agendamento['id']; ?>">
                                        <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                        <input type="hidden" name="acao" value="cancelar">
                                        <button type="button" class="btn btn-warning" 
                                                onclick="showCustomConfirm('Tem certeza que deseja cancelar este agendamento?', () => { document.getElementById('cancel-form-<?php echo $agendamento['id']; ?>').submit(); })">
                                            <i class="fa-solid fa-ban"></i> Cancelar
                                        </button>
                                    </form>
                                <?php elseif ($agendamento['status'] === 'cancelado' || $isConcluido): ?>
                                    <form method="POST" style="display: inline;" id="delete-form-<?php echo $agendamento['id']; ?>">
                                        <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                        <input type="hidden" name="acao" value="excluir">
                                        <button type="button" class="btn btn-danger" 
                                                onclick="showCustomConfirm('Tem certeza que deseja excluir este agendamento? Esta ação não pode ser desfeita!', () => { document.getElementById('delete-form-<?php echo $agendamento['id']; ?>').submit(); })">
                                            <i class="fa-solid fa-trash"></i> Excluir
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
                        <i class="fa-solid fa-calendar-times" style="font-size: 50px; margin-bottom: 15px; color: rgb(150, 150, 150);"></i><br>
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