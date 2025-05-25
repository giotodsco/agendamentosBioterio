<?php
session_start();
require_once '../back-end/functions.php';

// Verifica se a empresa está logada
if (!isset($_SESSION['empresa_logada']) || $_SESSION['empresa_logada'] !== true) {
    header("Location: pag_login_usuario.php?tab=empresa");
    exit();
}

// Buscar dados da empresa
try {
    $conexao = conectarBanco();
    
    // Dados básicos da empresa
    $stmt = $conexao->prepare("SELECT nome_instituicao, email, cnpj, ativo FROM empresas WHERE id = ?");
    $stmt->execute([$_SESSION['empresa_id']]);
    $empresa = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Estatísticas de agendamentos da empresa
    $stmt = $conexao->prepare("
        SELECT 
            COUNT(*) as total_agendamentos,
            COUNT(CASE WHEN status = 'confirmado' AND data_agendamento >= CURDATE() THEN 1 END) as proximas_visitas,
            COUNT(CASE WHEN status = 'cancelado' THEN 1 END) as cancelados,
            COUNT(CASE WHEN status = 'confirmado' AND data_agendamento < CURDATE() THEN 1 END) as visitas_concluidas,
            COUNT(CASE WHEN status = 'pendente' THEN 1 END) as aguardando_aprovacao,
            COUNT(CASE WHEN status = 'negado' THEN 1 END) as negados,
            SUM(CASE WHEN status IN ('confirmado', 'pendente') THEN quantidade_pessoas ELSE 0 END) as total_pessoas
        FROM agendamentos 
        WHERE empresa_id = ?
    ");
    $stmt->execute([$_SESSION['empresa_id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Último agendamento
    $stmt = $conexao->prepare("
        SELECT data_agendamento, hora_agendamento, status, quantidade_pessoas 
        FROM agendamentos 
        WHERE empresa_id = ? 
        ORDER BY data_criacao DESC 
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['empresa_id']]);
    $ultimo_agendamento = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $erro = "Erro ao carregar dados: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dados da Empresa - Biotério FSA</title>
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
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
            min-height: 0;
        }

        .content-container {
            background-color: rgb(225, 225, 228);
            width: 100%;
            max-width: 1200px;
            min-height: calc(100vh - 120px);
            border-radius: 15px;
            box-shadow: 5px 5px 50px rgba(90, 90, 90, 0.392);
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        /* Barra de rolagem personalizada */
        .content-container::-webkit-scrollbar {
            width: 8px;
        }

        .content-container::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 4px;
        }

        .content-container::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            border-radius: 4px;
        }

        .content-container::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #e0a800 0%, #d39e00 100%);
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

        .profile-header {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.15) 0%, rgba(255, 193, 7, 0.08) 100%);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            border-left: 5px solid #ffc107;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }

        .profile-info h2 {
            color: #856404;
            font-size: 24px;
            margin-bottom: 5px;
        }

        .profile-info p {
            color: #856404;
            font-size: 14px;
        }

        .data-section {
            background-color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            flex-shrink: 0;
        }

        .section-title {
            color: #856404;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .data-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .data-item {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, rgba(255, 193, 7, 0.05) 100%);
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #ffc107;
        }

        .data-item.email-item {
            grid-column: 1 / -1; /* Faz o email ocupar toda a largura */
        }

        .data-label {
            font-size: 14px;
            color: #856404;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .data-value {
            font-size: 16px;
            color: #856404;
            font-weight: bold;
            word-break: break-all; /* Quebra palavras longas */
            line-height: 1.4;
        }

        .data-value.email-value {
            word-break: break-all;
            overflow-wrap: break-word;
            font-size: 15px;
            background-color: rgba(255, 255, 255, 0.7);
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid rgba(255, 193, 7, 0.3);
            font-family: 'Courier New', monospace;
            user-select: all; /* Permite seleção fácil do texto */
            cursor: text;
            transition: all 0.3s;
        }

        .data-value.email-value:hover {
            background-color: rgba(255, 255, 255, 0.9);
            border-color: #ffc107;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 15px;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.15) 0%, rgba(255, 193, 7, 0.08) 100%);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            border-left: 4px solid #ffc107;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #856404;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 12px;
            color: #856404;
            font-weight: bold;
        }

        .last-appointment {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #ffc107;
        }

        .last-appointment h4 {
            color: #856404;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .last-appointment p {
            color: #856404;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .company-type-badge {
            background: linear-gradient(135deg, #e0a800 0%, #d39e00 100%);
            color: white;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
            flex-shrink: 0;
            padding-bottom: 20px;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            font-family: Georgia, 'Times New Roman', Times, serif;
            font-weight: bold;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #e0a800 0%, #d39e00 100%);
            color: white;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #5a6268 0%, #495057 100%);
            color: white;
            transform: translateY(-2px);
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid transparent;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
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

        .popup-btn-primary {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: white;
        }

        .popup-btn-primary:hover {
            background: linear-gradient(135deg, #e0a800 0%, #d39e00 100%);
        }

        .popup-btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .popup-btn-secondary:hover {
            background-color: #5a6268;
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
                min-height: calc(100vh - 140px);
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .data-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
                gap: 10px;
                margin-top: 20px;
            }
            
            .btn {
                width: 100%;
                max-width: 250px;
                justify-content: center;
            }

            .popup-buttons {
                flex-direction: column;
            }

            .data-value.email-value {
                font-size: 14px;
                word-break: break-all;
            }
        }

        @media (max-width: 480px) {
            .content-container {
                padding: 12px;
                min-height: calc(100vh - 160px);
            }

            .profile-header {
                padding: 20px 15px;
            }

            .data-section {
                padding: 20px 15px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .data-value.email-value {
                font-size: 13px;
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
            <div class="popup-message" id="popup-message">Tem certeza que deseja sair?</div>
            <div class="popup-buttons">
                <button class="popup-btn popup-btn-primary" id="popup-confirm">Confirmar</button>
                <button class="popup-btn popup-btn-secondary" id="popup-cancel">Cancelar</button>
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
        <div class="content-container">
            <h1><i class="fa-solid fa-building"></i> Dados da Empresa</h1>
            
            <?php if (isset($erro)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
            <?php endif; ?>

            <?php if (isset($empresa)): ?>
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($empresa['nome_instituicao'], 0, 1)); ?>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($empresa['nome_instituicao']); ?></h2>
                        <p>
                            <span class="company-type-badge">
                                <i class="fa-solid fa-building"></i>
                                Empresa Cadastrada
                            </span>
                        </p>
                    </div>
                </div>

                <div class="data-section">
                    <div class="section-title">
                        <i class="fa-solid fa-id-card"></i>
                        Informações Empresariais
                    </div>
                    <div class="data-grid">
                        <div class="data-item">
                            <div class="data-label">Nome da Instituição</div>
                            <div class="data-value"><?php echo htmlspecialchars($empresa['nome_instituicao']); ?></div>
                        </div>
                        <div class="data-item">
                            <div class="data-label">CNPJ</div>
                            <div class="data-value"><?php echo preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $empresa['cnpj']); ?></div>
                        </div>
                        <div class="data-item">
                            <div class="data-label">Status da Conta</div>
                            <div class="data-value">
                                <?php if ($empresa['ativo']): ?>
                                    <span style="color: #28a745; display: flex; align-items: center; gap: 6px;">
                                        <i class="fa-solid fa-check-circle"></i> Ativa
                                    </span>
                                <?php else: ?>
                                    <span style="color: #dc3545; display: flex; align-items: center; gap: 6px;">
                                        <i class="fa-solid fa-times-circle"></i> Inativa
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="data-item email-item">
                            <div class="data-label">Email Corporativo</div>
                            <div class="data-value email-value"><?php echo htmlspecialchars($empresa['email']); ?></div>
                        </div>
                    </div>
                </div>

                <div class="data-section">
                    <div class="section-title">
                        <i class="fa-solid fa-chart-bar"></i>
                        Estatísticas de Agendamentos
                    </div>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['total_agendamentos']; ?></div>
                            <div class="stat-label">Total de Solicitações</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['aguardando_aprovacao']; ?></div>
                            <div class="stat-label">Aguardando Aprovação</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['proximas_visitas']; ?></div>
                            <div class="stat-label">Visitas Confirmadas</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['visitas_concluidas']; ?></div>
                            <div class="stat-label">Visitas Realizadas</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['negados']; ?></div>
                            <div class="stat-label">Solicitações Negadas</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['total_pessoas'] ?? 0; ?></div>
                            <div class="stat-label">Total de Pessoas</div>
                        </div>
                    </div>
                </div>

                <?php if ($ultimo_agendamento): ?>
                <div class="data-section">
                    <div class="section-title">
                        <i class="fa-solid fa-clock"></i>
                        Última Solicitação
                    </div>
                    <div class="last-appointment">
                        <h4><i class="fa-solid fa-calendar"></i> Última solicitação enviada</h4>
                        <p><strong>Data:</strong> <?php echo date('d/m/Y', strtotime($ultimo_agendamento['data_agendamento'])); ?></p>
                        <p><strong>Horário:</strong> <?php echo date('H:i', strtotime($ultimo_agendamento['hora_agendamento'])); ?></p>
                        <p><strong>Pessoas:</strong> <?php echo $ultimo_agendamento['quantidade_pessoas'] ?? 1; ?></p>
                        <?php 
                        $statusUltimo = $ultimo_agendamento['status'];
                        if ($ultimo_agendamento['status'] === 'confirmado' && $ultimo_agendamento['data_agendamento'] < date('Y-m-d')) {
                            $statusUltimo = 'concluído';
                        }
                        ?>
                        <p><strong>Status:</strong> <?php echo ucfirst($statusUltimo); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="action-buttons">
                <a href="pag_agendar_empresa.php" class="btn btn-primary">
                    <i class="fa-solid fa-calendar-plus"></i>
                    Nova Solicitação
                </a>
                <a href="pag_meus_agendamentos_empresa.php" class="btn btn-secondary">
                    <i class="fa-solid fa-list"></i>
                    Meus Agendamentos
                </a>
                <a href="pag_inicial.html" class="btn btn-secondary">
                    <i class="fa-solid fa-home"></i>
                    Página Inicial
                </a>
            </div>
        </div>
    </div>

    <script>
        // Sistema de pop-up personalizado
        function showLogoutConfirm(event) {
            event.preventDefault();
            
            const overlay = document.getElementById('popup-overlay');
            const confirmBtn = document.getElementById('popup-confirm');
            const cancelBtn = document.getElementById('popup-cancel');
            
            overlay.style.display = 'flex';
            
            confirmBtn.onclick = function() {
                overlay.style.display = 'none';
                document.getElementById('logout-form').submit();
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