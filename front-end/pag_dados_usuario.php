<?php
session_start();
require_once '../back-end/functions.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: pag_login_usuario.php?login_required=true");
    exit();
}

// Buscar dados do usuário
try {
    $conexao = conectarBanco();
    
    // Dados básicos do usuário
    $stmt = $conexao->prepare("SELECT nome, email, cpf FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Estatísticas de agendamentos
    $stmt = $conexao->prepare("
        SELECT 
            COUNT(*) as total_agendamentos,
            COUNT(CASE WHEN status = 'confirmado' AND data_agendamento >= CURDATE() THEN 1 END) as proximas_visitas,
            COUNT(CASE WHEN status = 'cancelado' THEN 1 END) as cancelados,
            COUNT(CASE WHEN status = 'confirmado' AND data_agendamento < CURDATE() THEN 1 END) as visitas_concluidas
        FROM agendamentos 
        WHERE usuario_id = ?
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Último agendamento
    $stmt = $conexao->prepare("
        SELECT data_agendamento, hora_agendamento, status 
        FROM agendamentos 
        WHERE usuario_id = ? 
        ORDER BY data_criacao DESC 
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
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
    <title>Meus Dados - Biotério FSA</title>
    <link rel="stylesheet" href="front-end-style/style_pag_dados_usuario.css">
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
            <div class="user-info">
                <i class="fa-solid fa-user"></i>
                <span>Olá, <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>!</span>
            </div>
        </div>
        <div>
            <a href="#" class="btn-logout" onclick="showLogoutConfirm(event)">
                <i class="fa-solid fa-sign-out-alt"></i> Sair
            </a>
            <form id="logout-form" action="../back-end/auth_usuario.php" method="POST" style="display: none;">
                <input type="hidden" name="acao" value="logout">
            </form>
        </div>
    </div>

    <div class="main-container">
        <div class="content-container">
            <h1><i class="fa-solid fa-user-circle"></i> Meus Dados</h1>
            
            <?php if (isset($erro)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
            <?php endif; ?>

            <?php if (isset($usuario)): ?>
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($usuario['nome'], 0, 1)); ?>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($usuario['nome']); ?></h2>
                        <p>Membro do Biotério FSA</p>
                    </div>
                </div>

                <div class="data-section">
                    <div class="section-title">
                        <i class="fa-solid fa-id-card"></i>
                        Informações Pessoais
                    </div>
                    <div class="data-grid">
                        <div class="data-item">
                            <div class="data-label">Nome Completo</div>
                            <div class="data-value"><?php echo htmlspecialchars($usuario['nome']); ?></div>
                        </div>
                        <div class="data-item">
                            <div class="data-label">CPF</div>
                            <div class="data-value"><?php echo preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $usuario['cpf']); ?></div>
                        </div>
                        <div class="data-item email-item">
                            <div class="data-label">Email</div>
                            <div class="data-value email-value"><?php echo htmlspecialchars($usuario['email']); ?></div>
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
                            <div class="stat-label">Total de Agendamentos</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['proximas_visitas']; ?></div>
                            <div class="stat-label">Próximas Visitas</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['visitas_concluidas']; ?></div>
                            <div class="stat-label">Visitas Concluídas</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['cancelados']; ?></div>
                            <div class="stat-label">Cancelados</div>
                        </div>
                    </div>
                </div>

                <?php if ($ultimo_agendamento): ?>
                <div class="data-section">
                    <div class="section-title">
                        <i class="fa-solid fa-clock"></i>
                        Último Agendamento
                    </div>
                    <div class="last-appointment">
                        <h4><i class="fa-solid fa-calendar"></i> Último agendamento realizado</h4>
                        <p><strong>Data:</strong> <?php echo date('d/m/Y', strtotime($ultimo_agendamento['data_agendamento'])); ?></p>
                        <p><strong>Horário:</strong> <?php echo date('H:i', strtotime($ultimo_agendamento['hora_agendamento'])); ?></p>
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
                <a href="pag_agendar_logado.php" class="btn btn-primary">
                    <i class="fa-solid fa-calendar-plus"></i>
                    Novo Agendamento
                </a>
                <a href="pag_meus_agendamentos.php" class="btn btn-secondary">
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

    <script src="front-end-javascript/js_pag_dados_usuario.js"></script>
</body>
</html>