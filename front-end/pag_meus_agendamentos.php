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

// Processar ações de agendamento
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao'])) {
    $acao = $_POST['acao'];
    $agendamento_id = $_POST['agendamento_id'] ?? '';
    
    if (!empty($agendamento_id) && ($acao === 'cancelar' || $acao === 'excluir')) {
        try {
            $conexao = conectarBanco();
            
            // Verificar se o agendamento pertence ao usuário
            $stmt = $conexao->prepare("
                SELECT status, data_agendamento FROM agendamentos 
                WHERE id = ? AND usuario_id = ?
            ");
            $stmt->execute([$agendamento_id, $_SESSION['usuario_id']]);
            $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$agendamento) {
                $mensagem_erro = "Agendamento não encontrado.";
            } elseif ($acao === 'cancelar') {
                // Permitir cancelamento de agendamentos confirmados
                if ($agendamento['status'] === 'cancelado') {
                    $mensagem_erro = "Agendamento já foi cancelado.";
                } elseif ($agendamento['status'] === 'negado') {
                    $mensagem_erro = "Não é possível cancelar um agendamento que foi negado.";
                } elseif ($agendamento['status'] === 'concluido') {
                    $mensagem_erro = "Não é possível cancelar um agendamento já concluído.";
                } else {
                    // Cancelar o agendamento
                    $stmt = $conexao->prepare("
                        UPDATE agendamentos 
                        SET status = 'cancelado', data_cancelamento = NOW() 
                        WHERE id = ? AND usuario_id = ?
                    ");
                    $stmt->execute([$agendamento_id, $_SESSION['usuario_id']]);
                    
                    if ($stmt->rowCount() > 0) {
                        // Enviar email de cancelamento
                        $resultadoEmail = enviarEmailAgendamentoCancelado($agendamento_id);
                        if (!$resultadoEmail['sucesso']) {
                            error_log("Falha ao enviar email de cancelamento para agendamento ID: $agendamento_id");
                        }
                        
                        $mensagem_sucesso = "Agendamento cancelado com sucesso.";
                    } else {
                        $mensagem_erro = "Erro ao cancelar agendamento.";
                    }
                }
            } elseif ($acao === 'excluir') {
                // Permitir exclusão de agendamentos cancelados, negados, concluídos ou confirmados do passado
                $isConcluido = ($agendamento['status'] === 'confirmado' && $agendamento['data_agendamento'] < date('Y-m-d')) || 
                               $agendamento['status'] === 'concluido';
                $statusPermitidos = ['cancelado', 'negado', 'concluido'];
                
                if (!in_array($agendamento['status'], $statusPermitidos) && !$isConcluido) {
                    $mensagem_erro = "Apenas agendamentos cancelados, negados ou concluídos podem ser excluídos.";
                } else {
                    // Excluir o agendamento
                    $stmt = $conexao->prepare("
                        DELETE FROM agendamentos 
                        WHERE id = ? AND usuario_id = ?
                    ");
                    $stmt->execute([$agendamento_id, $_SESSION['usuario_id']]);
                    
                    if ($stmt->rowCount() > 0) {
                        $mensagem_sucesso = "Agendamento excluído com sucesso.";
                    } else {
                        $mensagem_erro = "Erro ao excluir agendamento.";
                    }
                }
            }
        } catch (PDOException $e) {
            $mensagem_erro = "Erro ao processar solicitação: " . $e->getMessage();
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
    
    // Processar status de exibição para cada agendamento
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
    <title>Meus Agendamentos - Biotério FSA</title>
    <link rel="stylesheet" href="front-end-style/style_pag_meus_agendamentos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
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
        <div class="header-left">
            <a href="pag_inicial.html" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i> 
                <span>Voltar ao Início</span>
            </a>
            <div class="user-info">
                <i class="fa-solid fa-user"></i>
                <span>Olá, <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>!</span>
            </div>
        </div>
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
            <h1><i class="fa-solid fa-user-circle"></i> Meus Agendamentos</h1>
            
            <div class="welcome-message">
                <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong>, 
                aqui estão todos os seus agendamentos no Espaço Biodiversidade
            </div>

            <?php if (isset($mensagem_sucesso)): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-check-circle"></i>
                    <?php echo htmlspecialchars($mensagem_sucesso); ?>
                    <?php if (strpos($mensagem_sucesso, 'cancelad') !== false): ?>
                        <br><small><i class="fa-solid fa-envelope"></i> Um email de confirmação foi enviado para você.</small>
                    <?php endif; ?>
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
                        <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['status'] === 'confirmado' && $a['data_agendamento'] >= date('Y-m-d'))); ?></div>
                        <div class="stat-label">Próximas Visitas</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => 
                            isset($a['status_display']) && $a['status_display'] === 'concluido' || 
                            $a['status'] === 'concluido'
                        )); ?></div>
                        <div class="stat-label">Visitas Realizadas</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['status'] === 'cancelado')); ?></div>
                        <div class="stat-label">Cancelados</div>
                    </div>
                </div>

                <div class="appointments-container">
                    <div class="appointments-grid">
                        <?php foreach ($agendamentos as $agendamento): 
                            // Usar status_display para determinar estado real
                            $statusReal = isset($agendamento['status_display']) ? $agendamento['status_display'] : $agendamento['status'];
                            
                            $isConfirmado = $agendamento['status'] === 'confirmado' && $agendamento['data_agendamento'] >= date('Y-m-d');
                            $isConcluido = $statusReal === 'concluido' || $agendamento['status'] === 'concluido';
                            $isCancelado = $agendamento['status'] === 'cancelado';
                            $isNegado = $agendamento['status'] === 'negado';
                            
                            // Determinar classe do card
                            $cardClass = '';
                            if ($isConcluido) {
                                $cardClass = 'concluido-card';
                            } elseif ($isNegado) {
                                $cardClass = 'negado-card';
                            } elseif ($isCancelado) {
                                $cardClass = 'cancelado-card';
                            }
                            
                            // Status para exibição
                            $statusDisplay = $statusReal;
                            $statusClass = $statusReal;
                        ?>
                        <div class="appointment-card <?php echo $cardClass; ?>">
                            <div class="appointment-header">
                                <div class="appointment-id <?php echo $statusDisplay; ?>">
                                    <i class="fa-solid fa-hashtag"></i> <?php echo $agendamento['id']; ?>
                                    <?php if ($isConcluido): ?>
                                        <span style="color: #6c757d; font-size: 10px; font-weight: bold;">CONCLUÍDO</span>
                                    <?php elseif ($isNegado): ?>
                                        <span style="color: #dc3545; font-size: 10px; font-weight: bold;">NEGADO</span>
                                    <?php elseif ($isCancelado): ?>
                                        <span style="color: #ff9800; font-size: 10px; font-weight: bold;">CANCELADO</span>
                                    <?php elseif ($isConfirmado): ?>
                                        <span style="color: #28a745; font-size: 10px; font-weight: bold;">CONFIRMADO</span>
                                    <?php endif; ?>
                                </div>
                                <div class="appointment-status status-<?php echo $statusClass; ?>">
                                    <?php if ($isConcluido): ?>
                                        <i class="fa-solid fa-check-circle"></i> Concluído
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
                                <span style="font-size: 14px; color: #856404; opacity: 0.8;">
                                    (<?php echo diaSemanaPortugues($agendamento['data_agendamento']); ?>)
                                </span>
                            </div>
                            
                            <div class="appointment-time">
                                <i class="fa-solid fa-clock"></i>
                                <?php echo date('H:i', strtotime($agendamento['hora_agendamento'])); ?>
                            </div>
                            
                            <div class="appointment-info">
                                <p><i class="fa-solid fa-calendar-plus"></i> <strong>Agendado em:</strong> <?php echo date('d/m/Y H:i', strtotime($agendamento['data_criacao'])); ?></p>
                                
                                <?php if ($agendamento['data_cancelamento']): ?>
                                    <p><i class="fa-solid fa-calendar-times"></i> <strong>Cancelado em:</strong> <?php echo date('d/m/Y H:i', strtotime($agendamento['data_cancelamento'])); ?></p>
                                <?php elseif ($isConcluido): ?>
                                    <p><i class="fa-solid fa-check-double"></i> <strong>Visita realizada em:</strong> <?php echo date('d/m/Y', strtotime($agendamento['data_agendamento'])); ?></p>
                                <?php elseif ($isNegado): ?>
                                    <p><i class="fa-solid fa-times"></i> <strong>Status:</strong> Agendamento não aprovado</p>
                                <?php elseif ($isConfirmado): ?>
                                    <p><i class="fa-solid fa-check-circle"></i> <strong>Status:</strong> Agendamento confirmado - compareça no horário marcado</p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="appointment-actions">
                                <?php if ($isConfirmado): ?>
                                    <!-- Cancelar agendamentos confirmados -->
                                    <form method="POST" style="display: inline;" id="cancel-form-<?php echo $agendamento['id']; ?>">
                                        <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                        <input type="hidden" name="acao" value="cancelar">
                                        <button type="button" class="btn btn-danger" 
                                                onclick="showCustomConfirm('Tem certeza que deseja cancelar este agendamento? Você receberá um email de confirmação do cancelamento.', () => { document.getElementById('cancel-form-<?php echo $agendamento['id']; ?>').submit(); })">
                                            <i class="fa-solid fa-ban"></i> Cancelar Agendamento
                                        </button>
                                    </form>
                                <?php elseif ($isNegado || $isCancelado || $isConcluido): ?>
                                    <!-- Excluir agendamentos finalizados -->
                                    <form method="POST" style="display: inline;" id="delete-form-<?php echo $agendamento['id']; ?>">
                                        <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                        <input type="hidden" name="acao" value="excluir">
                                        <button type="button" class="btn btn-warning" 
                                                onclick="showCustomConfirm('Tem certeza que deseja excluir permanentemente este agendamento? Esta ação não pode ser desfeita!', () => { document.getElementById('delete-form-<?php echo $agendamento['id']; ?>').submit(); })">
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
                        <i class="fa-solid fa-calendar"></i><br>
                        Você ainda não possui agendamentos.<br>
                        Faça seu primeiro agendamento agora!
                    </div>
                </div>
            <?php endif; ?>

            <div class="action-buttons">
                <a href="pag_agendar_logado.php" class="btn btn-primary btn-lg">
                    <i class="fa-solid fa-plus"></i>
                    Novo Agendamento
                </a>
                <a href="pag_dados_usuario.php" class="btn btn-secondary btn-lg">
                    <i class="fa-solid fa-id-card"></i>
                    Meus Dados
                </a>
                <a href="pag_inicial.html" class="btn btn-secondary btn-lg">
                    <i class="fa-solid fa-home"></i>
                    Página Inicial
                </a>
            </div>
        </div>
    </div>
    <script src="front-end-javascript/js_pag_meus_agendamentos.js"></script>
</body>
</html>