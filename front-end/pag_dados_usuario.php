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
            background-color: rgba(64, 122, 53, 0.9);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info span {
            color: white;
            font-size: 16px;
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
            padding: 20px;
        }

        .content-container {
            background-color: rgb(225, 225, 228);
            width: 95%;
            max-width: 800px;
            border-radius: 20px;
            box-shadow: 5px 5px 50px rgba(90, 90, 90, 0.392);
            padding: 30px;
            max-height: 80vh;
            overflow-y: auto;
        }

        h1 {
            color: rgb(55, 75, 51);
            font-size: 28px;
            text-align: center;
            margin-bottom: 30px;
            font-weight: 700;
        }

        .profile-header {
            background: linear-gradient(135deg, rgba(64, 122, 53, 0.1) 0%, rgba(64, 122, 53, 0.05) 100%);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            border-left: 5px solid rgba(64, 122, 53, 0.819);
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(64, 122, 53, 0.819) 0%, rgba(44, 81, 36, 0.819) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }

        .profile-info h2 {
            color: rgba(64, 122, 53, 0.819);
            font-size: 24px;
            margin-bottom: 5px;
        }

        .profile-info p {
            color: rgb(100, 100, 100);
            font-size: 14px;
        }

        .data-section {
            background-color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            color: rgba(64, 122, 53, 0.819);
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .data-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .data-item {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid rgba(64, 122, 53, 0.819);
        }

        .data-label {
            font-size: 14px;
            color: rgb(100, 100, 100);
            margin-bottom: 5px;
            font-weight: bold;
        }

        .data-value {
            font-size: 16px;
            color: rgb(60, 59, 59);
            font-weight: bold;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(64, 122, 53, 0.1) 0%, rgba(64, 122, 53, 0.05) 100%);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            border-left: 4px solid rgba(64, 122, 53, 0.819);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: rgba(64, 122, 53, 0.819);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 12px;
            color: rgb(100, 100, 100);
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

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
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
            background-color: rgba(64, 122, 53, 0.819);
            color: white;
        }

        .btn-primary:hover {
            background-color: rgba(44, 81, 36, 0.819);
            color: white;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: rgb(200, 200, 200);
            color: rgb(60, 59, 59);
        }

        .btn-secondary:hover {
            background-color: rgb(180, 180, 180);
            color: rgb(60, 59, 59);
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
            color: rgb(55, 75, 51);
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
            background-color: rgba(64, 122, 53, 0.819);
            color: white;
        }

        .popup-btn-primary:hover {
            background-color: rgba(44, 81, 36, 0.819);
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
                gap: 10px;
            }
            
            .content-container {
                width: 100%;
                padding: 20px 15px;
                max-height: none;
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .data-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 250px;
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
            <div class="popup-message" id="popup-message">Tem certeza que deseja sair?</div>
            <div class="popup-buttons">
                <button class="popup-btn popup-btn-primary" id="popup-confirm">Confirmar</button>
                <button class="popup-btn popup-btn-secondary" id="popup-cancel">Cancelar</button>
            </div>
        </div>
    </div>

    <div class="header">
        <div class="user-info">
            <i class="fa-solid fa-user"></i>
            <span>Olá, <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>!</span>
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
                            <div class="data-label">Email</div>
                            <div class="data-value"><?php echo htmlspecialchars($usuario['email']); ?></div>
                        </div>
                        <div class="data-item">
                            <div class="data-label">CPF</div>
                            <div class="data-value"><?php echo preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $usuario['cpf']); ?></div>
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