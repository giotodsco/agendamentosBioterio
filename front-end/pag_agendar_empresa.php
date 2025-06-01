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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="front-end-style/style_pag_agendar_empresa.css">
    <title>Biotério - Agendamento Empresa</title>
</head>
<body>
    <!-- Pop-up personalizado -->
    <div class="custom-popup-overlay" id="popup-overlay">
        <div class="custom-popup">
            <div class="popup-icon" id="popup-icon">
                <i class="fa-solid fa-exclamation-triangle"></i>
            </div>
            <div class="popup-title" id="popup-title">Atenção</div>
            <div class="popup-message" id="popup-message">Mensagem do sistema</div>
            <div class="popup-buttons" id="popup-buttons">
                <button class="popup-btn popup-btn-primary" onclick="closePopup()">OK</button>
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
            <a href="#" class="btn-logout" onclick="showLogoutConfirm(event)">
                <i class="fa-solid fa-sign-out-alt"></i> Sair
            </a>
            <form id="logout-form" action="../back-end/auth_empresa.php" method="POST" style="display: none;">
                <input type="hidden" name="acao" value="logout_empresa">
            </form>
        </div>
    </div>

    <div class="main-container">
        <div class="container">
            <div class="form-header">
                <h1><i class="fa-solid fa-building"></i> Agendamento Empresarial</h1>
                <p>Sistema de Agendamento para Empresas/Instituições</p>
            </div>
            
            <div class="form-container">
                <div id="error-message" class="alert alert-danger" style="display: none;"></div>
                <div id="success-message" class="alert alert-success" style="display: none;"></div>

                <div class="company-welcome">
                    <h3><i class="fa-solid fa-handshake"></i> Bem-vindo de volta!</h3>
                    <p>Seus dados já estão salvos. Selecione a data, horário e quantidade de pessoas para sua visita empresarial.</p>
                </div>

                <div class="pending-notice">
                    <h4><i class="fa-solid fa-clock"></i> Aprovação Necessária</h4>
                    <p>Todos os agendamentos de empresas passam por análise e aprovação da administração antes da confirmação.</p>
                </div>
                
                <div class="availability-info">
                    <h4><i class="fa-solid fa-info-circle"></i> Informações para Empresas</h4>
                    <p><strong>Horário empresarial:</strong> Segunda a Sexta, das 08:00 às 16:00</p>
                    <p><strong>Antecedência mínima:</strong> Agendamentos devem ser feitos com pelo menos 2 dias de antecedência</p>
                    <p><strong>Quantidade:</strong> De 1 a 45 pessoas por agendamento</p>
                    <p><strong>Aprovação:</strong> Todas as solicitações são analisadas individualmente</p>
                    <p><strong>Resposta:</strong> Confirmação em até 2 dias úteis pelo site</p>
                </div>

                <form id="agendamento-form" method="POST" action="../back-end/agendar_empresa.php">
                    <div class="form-group">
                        <label>
                            <i class="fa-solid fa-calendar"></i> Escolha a Data:
                        </label>
                        <div class="calendar-container">
                            <div class="calendar-header">
                                <button type="button" class="calendar-nav" id="prev-month">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </button>
                                <div class="calendar-month-year" id="month-year"></div>
                                <button type="button" class="calendar-nav" id="next-month">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </button>
                            </div>
                            <div class="calendar-grid" id="calendar-grid">
                                <!-- Calendário será gerado via JavaScript -->
                            </div>
                        </div>
                        <input type="hidden" id="data_agendamento" name="data_agendamento" required>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fa-solid fa-clock"></i> Escolha o Horário:
                        </label>
                        <div class="horarios-grid" id="horarios-container">
                            <!-- Horários serão carregados via JavaScript -->
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="quantidade_pessoas">
                            <i class="fa-solid fa-users"></i> Quantidade de Pessoas:
                        </label>
                        <input type="number" id="quantidade_pessoas" name="quantidade_pessoas" 
                               min="1" max="45" value="1" required>
                    </div>

                    <div class="loading" id="loading">
                        <div class="spinner"></div>
                        <p>Processando solicitação...</p>
                    </div>

                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary" id="btn-agendar" disabled>
                            <i class="fa-solid fa-calendar-check"></i> Confirmar Agendamento
                        </button>
                    </div>
                </form>

                <div class="action-buttons">
                    <a href="pag_inicial.html" class="btn btn-secondary">
                        <i class="fa-solid fa-home"></i> Página Inicial
                    </a>
                    <a href="pag_meus_agendamentos_empresa.php" class="btn btn-secondary">
                        <i class="fa-solid fa-list"></i> Meus Agendamentos
                    </a>
                    <a href="pag_dados_empresa.php" class="btn btn-secondary">
                        <i class="fa-solid fa-id-card"></i> Meus Dados
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="front-end-javascript/js_pag_agendar_empresa.js"></script>
</body>
</html>