<?php
session_start();

// Verifica se a empresa está logada
if (!isset($_SESSION['empresa_logada']) || $_SESSION['empresa_logada'] !== true) {
    header("Location: pag_login_usuario.php?tab=empresa");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitação Enviada - Biotério FSA</title>
    <link rel="stylesheet" href="front-end-style\style_pag_sucesso_empresa.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
    </style>
</head>
<body>
    <div class="success-container">
        <div class="header-section">
            <div class="success-icon">
                <i class="fa-solid fa-paper-plane"></i>
            </div>
            <h1>Solicitação Enviada com Sucesso!</h1>
            <p class="success-message">
                Sua solicitação de agendamento empresarial foi recebida e está sendo analisada pela nossa equipe.
            </p>
        </div>
        
        <div class="content-area">
            <div class="company-info">
                <p><strong><?php echo htmlspecialchars($_SESSION['empresa_nome']); ?></strong>, 
                agradecemos seu interesse em visitar nosso biotério. Sua solicitação está na fila de aprovação e será analisada com todo cuidado pela nossa equipe especializada.</p>
            </div>

            <div class="pending-notice">
                <h3>
                    <i class="fa-solid fa-clock"></i>
                    Aguardando Aprovação
                </h3>
                
                <div class="status-grid">
                    <div class="status-item">
                        <strong>Status:</strong>
                        <p>Pendente de análise</p>
                    </div>
                    <div class="status-item">
                        <strong>Prazo:</strong>
                        <p>Até 2 dias úteis</p>
                    </div>
                    <div class="status-item">
                        <strong>Notificação:</strong>
                        <p>Resposta por email</p>
                    </div>
                </div>
            </div>

            <div class="next-steps">
                <h4><i class="fa-solid fa-list-check"></i> Próximos Passos</h4>
                <ul>
                    <li>Nossa equipe analisará sua solicitação considerando a disponibilidade da data e horário solicitados</li>
                    <li>Verificaremos se todos os requisitos para a visita empresarial estão atendidos</li>
                    <li>Você receberá um email com a confirmação ou sugestão de nova data caso necessário</li>
                    <li>Em caso de aprovação, receberá instruções detalhadas para a realização da visita</li>
                </ul>
            </div>
            
            <div class="buttons">
                <a href="pag_agendar_empresa.php" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i>
                    Nova Solicitação
                </a>
                <a href="pag_inicial.html" class="btn btn-secondary">
                    <i class="fa-solid fa-home"></i>
                    Página Inicial
                </a>
            </div>

            <div class="contact-info">
                <h4><i class="fa-solid fa-info-circle"></i> Precisa de Ajuda?</h4>
                <p>Em caso de dúvidas ou necessidade de informações adicionais, entre em contato conosco através dos canais oficiais da FSA. Nossa equipe está sempre disponível para auxiliar sua empresa no processo de agendamento.</p>
            </div>
        </div>
    </div>
</body>
</html>