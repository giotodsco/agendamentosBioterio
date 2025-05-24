<?php
session_start();
require_once 'functions.php';

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: pag_adm.php");
    exit();
}

// Processar ações de confirmar/negar/remover/cancelar agendamento
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao'])) {
    $acao = $_POST['acao'];
    $agendamento_id = $_POST['agendamento_id'] ?? '';
    
    if (!empty($agendamento_id)) {
        try {
            $conexao = conectarBanco();
            
            if ($acao === 'confirmar') {
                $stmt = $conexao->prepare("UPDATE agendamentos SET status = 'confirmado' WHERE id = :id");
                $stmt->bindParam(':id', $agendamento_id);
                $stmt->execute();
                $mensagem = "Agendamento confirmado com sucesso!";
                
            } elseif ($acao === 'negar') {
                $stmt = $conexao->prepare("UPDATE agendamentos SET status = 'negado' WHERE id = :id");
                $stmt->bindParam(':id', $agendamento_id);
                $stmt->execute();
                $mensagem = "Agendamento negado com sucesso!";
                
            } elseif ($acao === 'cancelar') {
                $stmt = $conexao->prepare("UPDATE agendamentos SET status = 'cancelado', data_cancelamento = NOW() WHERE id = :id");
                $stmt->bindParam(':id', $agendamento_id);
                $stmt->execute();
                $mensagem = "Agendamento cancelado com sucesso!";
                
            } elseif ($acao === 'remover') {
                $stmt = $conexao->prepare("DELETE FROM agendamentos WHERE id = :id");
                $stmt->bindParam(':id', $agendamento_id);
                $stmt->execute();
                $mensagem = "Agendamento removido com sucesso!";
            }
        } catch (PDOException $e) {
            $mensagem = "Erro ao processar ação: " . $e->getMessage();
        }
    }
}

try {
    $conexao = conectarBanco();
    $agendamentos = buscarAgendamentosCompletos();
} catch (PDOException $e) {
    $mensagem = "Erro ao carregar agendamentos: " . $e->getMessage();
    $agendamentos = [];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biotério - Administração de Agendamentos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            padding: 20px 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 30px;
        }

        .header h1 {
            color: white;
            font-size: 28px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .btn-header {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid rgba(255,255,255,0.3);
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-header:hover {
            background: white;
            color: #2c3e50;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }

        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }

        .dashboard-header {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border-left: 6px solid #3498db;
        }

        .welcome-text {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .welcome-subtitle {
            color: #7f8c8d;
            font-size: 16px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-left: 4px solid #e74c3c;
        }

        .stat-card:nth-child(1) { border-left-color: #3498db; }
        .stat-card:nth-child(2) { border-left-color: #f39c12; }
        .stat-card:nth-child(3) { border-left-color: #27ae60; }
        .stat-card:nth-child(4) { border-left-color: #9b59b6; }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .stat-number {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #2c3e50;
        }

        .stat-label {
            font-size: 14px;
            color: #7f8c8d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .controls-section {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .controls-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 22px;
            font-weight: 700;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-config {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
        }

        .btn-config:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.3);
            color: white;
        }

        .table-section {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .table-header {
            background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-title {
            font-size: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-container {
            max-height: 600px;
            overflow-y: auto;
            position: relative;
        }

        /* Barra de rolagem estilizada */
        .table-container::-webkit-scrollbar {
            width: 12px;
        }

        .table-container::-webkit-scrollbar-track {
            background: #ecf0f1;
            border-radius: 6px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .table-container::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #2980b9 0%, #34495e 100%);
        }

        .appointments-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .appointments-table th {
            background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
            color: white;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
            z-index: 10;
            border-bottom: 3px solid #3498db;
        }

        .appointments-table td {
            padding: 15px 12px;
            border-bottom: 1px solid #ecf0f1;
            vertical-align: middle;
            font-size: 14px;
        }

        .appointments-table tr {
            transition: all 0.3s ease;
        }

        .appointments-table tr:hover {
            background: linear-gradient(135deg, #ebf3fd 0%, #f8f9fa 100%);
            transform: scale(1.01);
        }

        .empresa-row {
            background: linear-gradient(135deg, #fff3cd 0%, rgba(255, 243, 205, 0.3) 100%);
            border-left: 4px solid #f39c12;
        }

        .empresa-row:hover {
            background: linear-gradient(135deg, #ffeaa7 0%, #fff3cd 100%);
        }

        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            min-width: 80px;
            display: inline-block;
        }

        .status-pendente {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(243, 156, 18, 0.4);
        }

        .status-confirmado {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.4);
        }

        .status-negado {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.4);
        }

        .status-cancelado {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(149, 165, 166, 0.4);
        }

        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            font-size: 11px;
            font-weight: 600;
            margin: 2px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-confirmar {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
        }

        .btn-confirmar:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(39, 174, 96, 0.4);
        }

        .btn-negar {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
        }

        .btn-negar:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(243, 156, 18, 0.4);
        }

        .btn-cancelar {
            background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);
            color: white;
        }

        .btn-cancelar:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(230, 126, 34, 0.4);
        }

        .btn-remover {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }

        .btn-remover:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
        }

        .user-type {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .user-logado {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
        }

        .user-anonimo {
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
            color: white;
        }

        .user-empresa {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
        }

        .empresa-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .empresa-nome {
            font-weight: 700;
            color: #e67e22;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .empresa-pessoas {
            background: linear-gradient(135deg, #f39c12 0%, rgba(243, 156, 18, 0.2) 100%);
            color: #e67e22;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .message {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
            padding: 15px 25px;
            border-radius: 15px;
            margin-bottom: 20px;
            font-weight: 600;
            box-shadow: 0 8px 25px rgba(46, 204, 113, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .no-data {
            text-align: center;
            padding: 80px 20px;
            color: #7f8c8d;
        }

        .no-data i {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .no-data h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #34495e;
        }

        /* Pop-up personalizado */
        .custom-popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            backdrop-filter: blur(5px);
        }

        .custom-popup {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 25px;
            padding: 40px;
            max-width: 450px;
            width: 90%;
            text-align: center;
            box-shadow: 0 25px 60px rgba(0,0,0,0.3);
            animation: popupSlideIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes popupSlideIn {
            from {
                opacity: 0;
                transform: scale(0.7) translateY(-50px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .popup-icon {
            font-size: 60px;
            margin-bottom: 20px;
            color: #f39c12;
        }

        .popup-title {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .popup-message {
            font-size: 16px;
            color: #7f8c8d;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .popup-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .popup-btn {
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .popup-btn-confirm {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
        }

        .popup-btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(39, 174, 96, 0.4);
        }

        .popup-btn-cancel {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }

        .popup-btn-cancel:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(231, 76, 60, 0.4);
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 15px;
            }
            
            .header-content {
                flex-direction: column;
                gap: 15px;
                padding: 0 20px;
            }
            
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            
            .controls-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .table-container {
                max-height: 500px;
            }
            
            .appointments-table th,
            .appointments-table td {
                padding: 10px 8px;
                font-size: 12px;
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
        <div class="header-content">
            <h1>
                <i class="fa-solid fa-shield-halved"></i>
                Painel Administrativo
            </h1>
            <div class="header-actions">
                <a href="configuracoes.php" class="btn-header">
                    <i class="fa-solid fa-cog"></i>
                    Configurações
                </a>
                <a href="logout.php" class="btn-header">
                    <i class="fa-solid fa-sign-out-alt"></i>
                    Sair
                </a>
            </div>
        </div>
    </div>

    <div class="main-container">
        <?php if (isset($mensagem)): ?>
            <div class="message">
                <i class="fa-solid fa-check-circle"></i>
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-header">
            <div class="welcome-text">
                <i class="fa-solid fa-calendar-check"></i>
                Administração de Agendamentos
            </div>
            <div class="welcome-subtitle">
                Gerencie todos os agendamentos do sistema do Biotério FSA
            </div>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($agendamentos); ?></div>
                <div class="stat-label">Total Agendamentos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['status'] === 'pendente')); ?></div>
                <div class="stat-label">Aguardando Aprovação</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['status'] === 'confirmado')); ?></div>
                <div class="stat-label">Confirmados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['tipo_agendamento'] === 'empresa')); ?></div>
                <div class="stat-label">Agendamentos Empresariais</div>
            </div>
        </div>

        <?php if (count($agendamentos) > 0): ?>
            <div class="table-section">
                <div class="table-header">
                    <div class="table-title">
                        <i class="fa-solid fa-list"></i>
                        Lista de Agendamentos
                    </div>
                    <div style="color: rgba(255,255,255,0.8); font-size: 14px;">
                        Total: <?php echo count($agendamentos); ?> registros
                    </div>
                </div>
                <div class="table-container">
                    <table class="appointments-table">
                        <thead>
                            <tr>
                                <th><i class="fa-solid fa-hashtag"></i> ID</th>
                                <th><i class="fa-solid fa-user"></i> Nome/Empresa</th>
                                <th><i class="fa-solid fa-envelope"></i> Email</th>
                                <th><i class="fa-solid fa-id-card"></i> CPF/CNPJ</th>
                                <th><i class="fa-solid fa-calendar"></i> Data</th>
                                <th><i class="fa-solid fa-clock"></i> Hora</th>
                                <th><i class="fa-solid fa-info-circle"></i> Status</th>
                                <th><i class="fa-solid fa-tag"></i> Tipo</th>
                                <th><i class="fa-solid fa-users"></i> Pessoas</th>
                                <th><i class="fa-solid fa-calendar-plus"></i> Criado</th>
                                <th><i class="fa-solid fa-cogs"></i> Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($agendamentos as $agendamento): 
                                $isEmpresa = $agendamento['tipo_agendamento'] === 'empresa';
                                $rowClass = $isEmpresa ? 'empresa-row' : '';
                            ?>
                            <tr class="<?php echo $rowClass; ?>">
                                <td><strong>#<?php echo $agendamento['id']; ?></strong></td>
                                <td>
                                    <?php if ($isEmpresa): ?>
                                        <div class="empresa-info">
                                            <div class="empresa-nome">
                                                <i class="fa-solid fa-building"></i>
                                                <?php echo htmlspecialchars($agendamento['empresa_nome'] ?? $agendamento['nome']); ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <i class="fa-solid fa-user"></i>
                                            <?php echo htmlspecialchars($agendamento['nome']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($agendamento['email']); ?></td>
                                <td><code><?php echo htmlspecialchars($agendamento['cpf']); ?></code></td>
                                <td><strong><?php echo date('d/m/Y', strtotime($agendamento['data_agendamento'])); ?></strong></td>
                                <td><strong><?php echo date('H:i', strtotime($agendamento['hora_agendamento'])); ?></strong></td>
                                <td>
                                    <span class="status status-<?php echo $agendamento['status']; ?>">
                                        <?php echo ucfirst($agendamento['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($isEmpresa): ?>
                                        <span class="user-type user-empresa">
                                            <i class="fa-solid fa-building"></i> Empresa
                                        </span>
                                    <?php elseif ($agendamento['usuario_id']): ?>
                                        <span class="user-type user-logado">
                                            <i class="fa-solid fa-user-check"></i> Usuário
                                        </span>
                                    <?php else: ?>
                                        <span class="user-type user-anonimo">
                                            <i class="fa-solid fa-user-secret"></i> Anônimo
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isEmpresa): ?>
                                        <span class="empresa-pessoas">
                                            <i class="fa-solid fa-users"></i> <?php echo $agendamento['quantidade_pessoas'] ?? 1; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="empresa-pessoas" style="background: linear-gradient(135deg, #3498db 0%, rgba(52, 152, 219, 0.2) 100%); color: #2980b9;">
                                            <i class="fa-solid fa-user"></i> 1
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m H:i', strtotime($agendamento['data_criacao'])); ?></td>
                                <td>
                                    <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                        <?php if ($agendamento['status'] === 'pendente'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                                <button type="submit" name="acao" value="confirmar" class="btn-action btn-confirmar" title="Confirmar agendamento">
                                                    <i class="fa-solid fa-check"></i>
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                                <button type="submit" name="acao" value="negar" class="btn-action btn-negar" title="Negar agendamento">
                                                    <i class="fa-solid fa-times"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($agendamento['status'] === 'confirmado'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                                <button type="button" name="acao" value="cancelar" class="btn-action btn-cancelar" title="Cancelar agendamento" 
                                                        onclick="showCustomConfirm('Tem certeza que deseja cancelar este agendamento?', () => { this.type='submit'; this.click(); })">
                                                    <i class="fa-solid fa-ban"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                            <button type="button" name="acao" value="remover" class="btn-action btn-remover" title="Remover agendamento permanentemente"
                                                    onclick="showCustomConfirm('Tem certeza que deseja remover este agendamento? Esta ação não pode ser desfeita!', () => { this.type='submit'; this.click(); })">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="table-section">
                <div class="no-data">
                    <i class="fa-solid fa-calendar-times"></i>
                    <h3>Nenhum agendamento encontrado</h3>
                    <p>Não há agendamentos cadastrados no sistema no momento.</p>
                </div>
            </div>
        <?php endif; ?>
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

        // Adicionar efeitos visuais
        document.addEventListener('DOMContentLoaded', function() {
            // Animar cards de estatísticas
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(30px)';
                    card.style.transition = 'all 0.6s ease';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 100);
            });
        });
    </script>
</body>
</html>