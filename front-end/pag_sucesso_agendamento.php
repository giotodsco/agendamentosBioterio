<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: pag_login_usuario.php");
    exit();
}

// Verificar o status do agendamento
$status = $_GET['status'] ?? 'confirmado';
$isPendente = ($status === 'pendente');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isPendente ? 'Agendamento Solicitado' : 'Agendamento Realizado'; ?> - Biotério FSA</title>
    <link rel="stylesheet" href="front-end-style\style_pag_sucesso_agendamento.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    
</head>
<body>
    <div class="success-container">
        <?php if ($isPendente): ?>
            <!-- MODO PENDENTE -->
            <div class="success-icon pendente">
                <i class="fa-solid fa-clock"></i>
            </div>
            
            <h1>Agendamento Solicitado!</h1>
            
            <div class="success-message">
                Sua solicitação foi enviada e está aguardando aprovação da administração.
            </div>
            
            <div class="user-info pendente">
                <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong>, 
                seu agendamento foi recebido e está na fila de aprovação.<br>
                Você receberá uma confirmação por email quando for aprovado.
            </div>

            <div class="status-info">
                <h3><i class="fa-solid fa-hourglass-half"></i> Aguardando Aprovação</h3>
                <p><strong>Status:</strong> Pendente de análise pela administração<br>
                <strong>Prazo:</strong> Resposta em até 2 dias úteis<br>
                <strong>Notificação:</strong> Você receberá um email com a confirmação ou orientações adicionais</p>
            </div>
        <?php else: ?>
            <!-- MODO CONFIRMADO -->
            <div class="success-icon confirmado">
                <i class="fa-solid fa-check-circle"></i>
            </div>
            
            <h1>Agendamento Realizado com Sucesso!</h1>
            
            <div class="success-message">
                Seu agendamento foi confirmado automaticamente e já está ativo no sistema!
            </div>
            
            <div class="user-info confirmado">
                <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong>, 
                seu agendamento está confirmado.<br>
                Você pode visualizar e gerenciar todos os seus agendamentos na área "Meus Agendamentos".
            </div>
        <?php endif; ?>
        
        <div class="buttons">
            <a href="pag_meus_agendamentos.php" class="btn btn-primary">
                <i class="fa-solid fa-calendar"></i>
                Meus Agendamentos
            </a>
            
            <?php if ($isPendente): ?>
                <a href="pag_dados_usuario.php" class="btn btn-warning">
                    <i class="fa-solid fa-user"></i>
                    Meus Dados
                </a>
            <?php else: ?>
                <a href="pag_agendar_logado.php" class="btn btn-secondary">
                    <i class="fa-solid fa-plus"></i>
                    Novo Agendamento
                </a>
            <?php endif; ?>
            
            <a href="pag_inicial.html" class="btn btn-secondary">
                <i class="fa-solid fa-home"></i>
                Página Inicial
            </a>
        </div>
    </div>
</body>
</html>