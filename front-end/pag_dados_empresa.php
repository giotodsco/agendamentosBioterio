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
    <link rel="stylesheet" href="front-end-style/style_pag_dados_empresa.css">
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
                            <div class="data-label">Email Corporativo</div>
                            <div class="data-value"><?php echo htmlspecialchars($empresa['email']); ?></div>
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

    <script src="front-end-javascript\js_pag_dados_empresa.js"></script>
</body>
</html>