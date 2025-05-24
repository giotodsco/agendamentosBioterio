<?php
session_start();
require_once 'functions.php';

// Verificar se o usuário está logado e é operador ou admin
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: pag_adm.php");
    exit();
}

if (!in_array($_SESSION['tipo_usuario'], ['operador', 'admin'])) {
    header("Location: ../front-end/pag_inicial.html");
    exit();
}

// Buscar agendamentos
try {
    $conexao = conectarBanco();
    
    // Data de hoje para referência
    $hoje = date('Y-m-d');
    
    // Filtros
    $filtro_data_inicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-7 days'));
    $filtro_data_fim = $_GET['data_fim'] ?? date('Y-m-d', strtotime('+30 days'));
    $filtro_status = $_GET['status'] ?? '';
    
    $sql = "SELECT a.*, u.nome as usuario_nome 
            FROM agendamentos a 
            LEFT JOIN usuarios u ON a.usuario_id = u.id 
            WHERE a.data_agendamento BETWEEN ? AND ? ";
    $params = [$filtro_data_inicio, $filtro_data_fim];
    
    if ($filtro_status) {
        $sql .= " AND a.status = ?";
        $params[] = $filtro_status;
    }
    
    $sql .= " ORDER BY a.data_agendamento ASC, a.hora_agendamento ASC";
    
    $stmt = $conexao->prepare($sql);
    $stmt->execute($params);
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organizar agendamentos por data
    $agendamentos_por_data = [];
    foreach ($agendamentos as $agendamento) {
        $data = $agendamento['data_agendamento'];
        if (!isset($agendamentos_por_data[$data])) {
            $agendamentos_por_data[$data] = [];
        }
        $agendamentos_por_data[$data][] = $agendamento;
    }
    
    // Ordenar datas
    ksort($agendamentos_por_data);
    
} catch (PDOException $e) {
    $mensagem_erro = "Erro ao buscar agendamentos: " . $e->getMessage();
    $agendamentos = [];
    $agendamentos_por_data = [];
}

// Função para formatar data em português
function formatarDataPorExtensor($data) {
    $timestamp = strtotime($data);
    $dias_semana = [
        'Sunday' => 'Domingo',
        'Monday' => 'Segunda-feira', 
        'Tuesday' => 'Terça-feira',
        'Wednesday' => 'Quarta-feira',
        'Thursday' => 'Quinta-feira',
        'Friday' => 'Sexta-feira',
        'Saturday' => 'Sábado'
    ];
    $meses = [
        'January' => 'Janeiro', 'February' => 'Fevereiro', 'March' => 'Março',
        'April' => 'Abril', 'May' => 'Maio', 'June' => 'Junho',
        'July' => 'Julho', 'August' => 'Agosto', 'September' => 'Setembro',
        'October' => 'Outubro', 'November' => 'Novembro', 'December' => 'Dezembro'
    ];
    
    $dia_semana = $dias_semana[date('l', $timestamp)];
    $dia = date('d', $timestamp);
    $mes = $meses[date('F', $timestamp)];
    $ano = date('Y', $timestamp);
    
    return "$dia_semana, $dia de $mes de $ano";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biotério - Área do Operador</title>
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
            padding: 20px;
        }

        .header {
            background-color: rgba(64, 122, 53, 0.9);
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .header h1 {
            color: white;
            font-size: 28px;
            font-weight: 700;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info span {
            color: white;
            font-size: 16px;
            background-color: rgba(255, 255, 255, 0.1);
            padding: 8px 15px;
            border-radius: 20px;
        }

        .btn-logout {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid white;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s;
        }

        .btn-logout:hover {
            background-color: white;
            color: rgba(64, 122, 53, 0.9);
            transform: translateY(-2px);
        }

        .content {
            background-color: rgb(225, 225, 228);
            border-radius: 20px;
            box-shadow: 5px 5px 50px rgba(90, 90, 90, 0.392);
            padding: 30px;
            max-height: 85vh;
            overflow-y: auto;
        }

        .filters {
            background: linear-gradient(135deg, rgba(64, 122, 53, 0.1) 0%, rgba(64, 122, 53, 0.05) 100%);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            border-left: 5px solid rgba(64, 122, 53, 0.819);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .filters h3 {
            color: rgba(64, 122, 53, 0.819);
            margin-bottom: 20px;
            font-size: 20px;
            font-weight: 700;
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-size: 14px;
            margin-bottom: 8px;
            color: rgb(60, 59, 59);
            font-weight: bold;
        }

        .filter-group input,
        .filter-group select {
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            font-family: Georgia, 'Times New Roman', Times, serif;
            transition: border-color 0.3s;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: rgba(64, 122, 53, 0.819);
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-family: Georgia, 'Times New Roman', Times, serif;
            font-weight: bold;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background-color: rgba(64, 122, 53, 0.819);
            color: white;
        }

        .btn-primary:hover {
            background-color: rgba(44, 81, 36, 0.819);
            transform: translateY(-2px);
        }

        .btn-success {
            background-color: #28a745;
            color: white;
        }

        .btn-success:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }

        .btn-warning {
            background-color: #ffc107;
            color: #333;
        }

        .btn-warning:hover {
            background-color: #e0a800;
            transform: translateY(-2px);
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(64, 122, 53, 0.1) 0%, rgba(64, 122, 53, 0.05) 100%);
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            border-left: 5px solid rgba(64, 122, 53, 0.819);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: rgba(64, 122, 53, 0.819);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: rgb(100, 100, 100);
            font-weight: bold;
        }

        .actions {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .actions-left {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .export-specific-date {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            border-left: 5px solid #ffc107;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .export-specific-date h4 {
            color: #856404;
            margin-bottom: 15px;
            font-size: 18px;
            font-weight: bold;
        }

        .export-date-form {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }

        .day-section {
            background-color: white;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border-left: 6px solid rgba(64, 122, 53, 0.819);
        }

        .day-header {
            background: linear-gradient(135deg, rgba(64, 122, 53, 0.819) 0%, rgba(44, 81, 36, 0.819) 100%);
            color: white;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .day-title {
            font-size: 20px;
            font-weight: bold;
        }

        .day-date {
            font-size: 14px;
            opacity: 0.9;
        }

        .day-stats {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .day-count {
            background-color: rgba(255, 255, 255, 0.2);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }

        .appointments-grid {
            padding: 25px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }

        .appointment-card {
            background-color: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
            position: relative;
        }

        .appointment-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-color: rgba(64, 122, 53, 0.3);
        }

        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .appointment-id {
            background-color: rgba(64, 122, 53, 0.1);
            color: rgba(64, 122, 53, 0.819);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .appointment-time {
            font-size: 18px;
            font-weight: bold;
            color: rgba(64, 122, 53, 0.819);
        }

        .appointment-info {
            margin-bottom: 15px;
        }

        .appointment-info h4 {
            color: rgb(60, 59, 59);
            margin-bottom: 8px;
            font-size: 16px;
        }

        .appointment-info p {
            font-size: 14px;
            color: rgb(100, 100, 100);
            margin-bottom: 4px;
        }

        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
        }

        .status-confirmado {
            background-color: #d4edda;
            color: #155724;
        }

        .status-cancelado {
            background-color: #f8d7da;
            color: #721c24;
        }

        .user-type {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
        }

        .user-logado {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        .user-anonimo {
            background-color: #f3e5f5;
            color: #7b1fa2;
        }

        .no-appointments {
            text-align: center;
            padding: 60px 20px;
            color: rgb(150, 150, 150);
            font-size: 18px;
            background-color: #f8f9fa;
            border-radius: 15px;
            margin: 20px 0;
        }

        .no-appointments i {
            font-size: 60px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .today-highlight {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border-left-color: #ffc107;
        }

        .today-highlight .day-header {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
        }

        .past-day {
            opacity: 0.7;
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

        .popup-icon.error {
            color: #dc3545;
        }

        .popup-icon.success {
            color: #28a745;
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
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .filter-row {
                grid-template-columns: 1fr;
            }
            
            .stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .appointments-grid {
                grid-template-columns: 1fr;
                padding: 20px;
            }

            .actions {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }

            .actions-left {
                justify-content: center;
            }

            .day-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .day-stats {
                justify-content: center;
            }

            .export-date-form {
                flex-direction: column;
                align-items: stretch;
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
                <i class="fa-solid fa-info-circle"></i>
            </div>
            <div class="popup-title" id="popup-title">Informação</div>
            <div class="popup-message" id="popup-message">Mensagem do sistema</div>
            <div class="popup-buttons" id="popup-buttons">
                <button class="popup-btn popup-btn-primary" id="popup-btn-primary" onclick="closePopup()">OK</button>
            </div>
        </div>
    </div>

    <div class="header">
        <h1><i class="fa-solid fa-chart-line"></i> Painel do Operador</h1>
        <div class="user-info">
            <span><i class="fa-solid fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?> (<?php echo ucfirst($_SESSION['tipo_usuario']); ?>)</span>
            <!-- CORRIGIDO: Botão de sair agora faz logout corretamente -->
            <a href="logout.php" class="btn-logout">
                <i class="fa-solid fa-sign-out-alt"></i> Sair
            </a>
        </div>
    </div>

    <div class="content">
        <?php if (isset($mensagem_erro)): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($mensagem_erro); ?>
            </div>
        <?php endif; ?>

        <div class="filters">
            <h3><i class="fa-solid fa-filter"></i> Filtros e Controles</h3>
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="data_inicio"><i class="fa-solid fa-calendar-alt"></i> Data Início:</label>
                        <input type="date" id="data_inicio" name="data_inicio" value="<?php echo htmlspecialchars($filtro_data_inicio); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="data_fim"><i class="fa-solid fa-calendar-alt"></i> Data Fim:</label>
                        <input type="date" id="data_fim" name="data_fim" value="<?php echo htmlspecialchars($filtro_data_fim); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="status"><i class="fa-solid fa-tags"></i> Status:</label>
                        <select id="status" name="status">
                            <option value="">Todos os Status</option>
                            <option value="confirmado" <?php echo $filtro_status === 'confirmado' ? 'selected' : ''; ?>>Confirmado</option>
                            <option value="cancelado" <?php echo $filtro_status === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-search"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Exportação por data específica -->
        <div class="export-specific-date">
            <h4><i class="fa-solid fa-calendar-day"></i> Exportar Agendamentos de uma Data Específica</h4>
            <p style="margin-bottom: 15px; color: #856404;">Exemplo: Ver todos os agendamentos do dia 10/06/2025</p>
            <div class="export-date-form">
                <div class="filter-group" style="flex: 1; min-width: 200px;">
                    <label for="data_especifica">Selecione a Data:</label>
                    <input type="date" id="data_especifica" name="data_especifica" style="border-color: #ffc107;">
                </div>
                <div class="filter-group">
                    <button type="button" class="btn btn-warning" onclick="exportarDataEspecifica('pdf')">
                        <i class="fa-solid fa-file-pdf"></i> PDF da Data
                    </button>
                </div>
                <div class="filter-group">
                    <button type="button" class="btn btn-warning" onclick="exportarDataEspecifica('excel')">
                        <i class="fa-solid fa-file-excel"></i> Excel da Data
                    </button>
                </div>
            </div>
        </div>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($agendamentos); ?></div>
                <div class="stat-label"><i class="fa-solid fa-calendar-check"></i> Total de Agendamentos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['status'] === 'confirmado')); ?></div>
                <div class="stat-label"><i class="fa-solid fa-check-circle"></i> Confirmados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['status'] === 'cancelado')); ?></div>
                <div class="stat-label"><i class="fa-solid fa-times-circle"></i> Cancelados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['usuario_id'] !== null)); ?></div>
                <div class="stat-label"><i class="fa-solid fa-users"></i> Usuários Cadastrados</div>
            </div>
        </div>

        <div class="actions">
            <div class="actions-left">
                <button type="button" class="btn btn-success" onclick="exportarPDF()">
                    <i class="fa-solid fa-file-pdf"></i> Exportar PDF
                </button>
                <button type="button" class="btn btn-secondary" onclick="exportarExcel()">
                    <i class="fa-solid fa-file-excel"></i> Exportar Excel
                </button>
            </div>
            <div>
                <a href="../front-end/pag_inicial.html" class="btn btn-secondary">
                    <i class="fa-solid fa-home"></i> Página Inicial
                </a>
            </div>
        </div>

        <?php if (count($agendamentos_por_data) > 0): ?>
            <?php foreach ($agendamentos_por_data as $data => $agendamentos_do_dia): ?>
                <?php 
                $hoje = date('Y-m-d');
                $is_today = ($data === $hoje);
                $is_past = ($data < $hoje);
                $day_class = $is_today ? 'today-highlight' : ($is_past ? 'past-day' : '');
                ?>
                <div class="day-section <?php echo $day_class; ?>">
                    <div class="day-header">
                        <div>
                            <div class="day-title">
                                <?php if ($is_today): ?>
                                    <i class="fa-solid fa-star"></i> HOJE
                                <?php elseif ($is_past): ?>
                                    <i class="fa-solid fa-history"></i> <?php echo date('d/m/Y', strtotime($data)); ?>
                                <?php else: ?>
                                    <i class="fa-solid fa-calendar-day"></i> <?php echo date('d/m/Y', strtotime($data)); ?>
                                <?php endif; ?>
                            </div>
                            <div class="day-date"><?php echo formatarDataPorExtensor($data); ?></div>
                        </div>
                        <div class="day-stats">
                            <div class="day-count">
                                <i class="fa-solid fa-users"></i> <?php echo count($agendamentos_do_dia); ?> agendamento<?php echo count($agendamentos_do_dia) != 1 ? 's' : ''; ?>
                            </div>
                            <div class="day-count">
                                <i class="fa-solid fa-check"></i> <?php echo count(array_filter($agendamentos_do_dia, fn($a) => $a['status'] === 'confirmado')); ?> confirmado<?php echo count(array_filter($agendamentos_do_dia, fn($a) => $a['status'] === 'confirmado')) != 1 ? 's' : ''; ?>
                            </div>
                            <button type="button" class="btn btn-warning" onclick="exportarDataEspecificaDireta('<?php echo $data; ?>', 'pdf')" style="font-size: 12px; padding: 6px 12px;">
                                <i class="fa-solid fa-download"></i> PDF desta data
                            </button>
                        </div>
                    </div>
                    
                    <div class="appointments-grid">
                        <?php foreach ($agendamentos_do_dia as $agendamento): ?>
                        <div class="appointment-card">
                            <div class="appointment-header">
                                <div class="appointment-id">
                                    <i class="fa-solid fa-hashtag"></i> <?php echo $agendamento['id']; ?>
                                </div>
                                <div class="appointment-time">
                                    <i class="fa-solid fa-clock"></i> <?php echo date('H:i', strtotime($agendamento['hora_agendamento'])); ?>
                                </div>
                            </div>
                            
                            <div class="appointment-info">
                                <h4><i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($agendamento['nome']); ?></h4>
                                <p><i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($agendamento['email']); ?></p>
                                <p><i class="fa-solid fa-id-card"></i> <?php echo htmlspecialchars($agendamento['cpf']); ?></p>
                                <p><i class="fa-solid fa-calendar-plus"></i> Criado em: <?php echo date('d/m/Y H:i', strtotime($agendamento['data_criacao'])); ?></p>
                                <?php if ($agendamento['data_cancelamento']): ?>
                                <p><i class="fa-solid fa-calendar-times"></i> Cancelado em: <?php echo date('d/m/Y H:i', strtotime($agendamento['data_cancelamento'])); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span class="status status-<?php echo $agendamento['status']; ?>">
                                    <?php if ($agendamento['status'] === 'confirmado'): ?>
                                        <i class="fa-solid fa-check-circle"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-times-circle"></i>
                                    <?php endif; ?>
                                    <?php echo ucfirst($agendamento['status']); ?>
                                </span>
                                
                                <?php if ($agendamento['usuario_id']): ?>
                                    <span class="user-type user-logado">
                                        <i class="fa-solid fa-user-check"></i> Usuário Cadastrado
                                    </span>
                                <?php else: ?>
                                    <span class="user-type user-anonimo">
                                        <i class="fa-solid fa-user-secret"></i> Usuário Anônimo
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-appointments">
                <i class="fa-solid fa-calendar-times"></i><br>
                <strong>Nenhum agendamento encontrado</strong><br>
                <small>Tente ajustar os filtros para ver mais resultados</small>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function exportarPDF() {
            const filtros = new URLSearchParams(window.location.search);
            filtros.append('export', 'pdf');
            window.open('exportar_relatorio.php?' + filtros.toString(), '_blank');
        }

        function exportarExcel() {
            const filtros = new URLSearchParams(window.location.search);
            filtros.append('export', 'excel');
            window.open('exportar_relatorio.php?' + filtros.toString(), '_blank');
        }

        // Exportar data específica
        function exportarDataEspecifica(tipo) {
            const dataEspecifica = document.getElementById('data_especifica').value;
            if (!dataEspecifica) {
                showPopup('Erro', 'Por favor, selecione uma data primeiro.', 'error');
                return;
            }
            
            const params = new URLSearchParams();
            params.append('export', tipo);
            params.append('data_especifica', dataEspecifica);
            
            window.open('exportar_relatorio.php?' + params.toString(), '_blank');
        }

        // Exportar data específica diretamente
        function exportarDataEspecificaDireta(data, tipo) {
            const params = new URLSearchParams();
            params.append('export', tipo);
            params.append('data_especifica', data);
            
            window.open('exportar_relatorio.php?' + params.toString(), '_blank');
        }

        // Sistema de pop-up personalizado
        function showPopup(title, message, type = 'info', buttons = null) {
            const overlay = document.getElementById('popup-overlay');
            const titleElement = document.getElementById('popup-title');
            const messageElement = document.getElementById('popup-message');
            const iconElement = document.getElementById('popup-icon');
            const buttonsContainer = document.getElementById('popup-buttons');
            
            titleElement.textContent = title;
            messageElement.textContent = message;
            
            // Configurar ícone baseado no tipo
            let iconClass = 'fa-info-circle';
            let iconColorClass = '';
            
            switch(type) {
                case 'error':
                    iconClass = 'fa-exclamation-triangle';
                    iconColorClass = 'error';
                    break;
                case 'success':
                    iconClass = 'fa-check-circle';
                    iconColorClass = 'success';
                    break;
                case 'warning':
                    iconClass = 'fa-exclamation-triangle';
                    iconColorClass = '';
                    break;
                default:
                    iconClass = 'fa-info-circle';
                    iconColorClass = '';
            }
            
            iconElement.innerHTML = `<i class="fa-solid ${iconClass}"></i>`;
            iconElement.className = `popup-icon ${iconColorClass}`;
            
            // Configurar botões
            if (buttons) {
                buttonsContainer.innerHTML = '';
                buttons.forEach(button => {
                    const btn = document.createElement('button');
                    btn.className = `popup-btn ${button.class || 'popup-btn-primary'}`;
                    btn.textContent = button.text;
                    btn.onclick = button.action;
                    buttonsContainer.appendChild(btn);
                });
            } else {
                buttonsContainer.innerHTML = '<button class="popup-btn popup-btn-primary" onclick="closePopup()">OK</button>';
            }
            
            overlay.style.display = 'flex';
        }

        function closePopup() {
            document.getElementById('popup-overlay').style.display = 'none';
        }

        // Scroll suave para o dia atual
        document.addEventListener('DOMContentLoaded', function() {
            const todaySection = document.querySelector('.today-highlight');
            if (todaySection) {
                setTimeout(() => {
                    todaySection.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'start' 
                    });
                }, 500);
            }

            // Definir datas padrão para o filtro se não especificadas
            const dataInicio = document.getElementById('data_inicio');
            const dataFim = document.getElementById('data_fim');
            
            // Se não há filtros, definir período padrão
            const urlParams = new URLSearchParams(window.location.search);
            if (!urlParams.has('data_inicio') && !urlParams.has('data_fim')) {
                const hoje = new Date();
                const semanaPassada = new Date(hoje.getTime() - 7 * 24 * 60 * 60 * 1000);
                const proximoMes = new Date(hoje.getTime() + 30 * 24 * 60 * 60 * 1000);
                
                dataInicio.value = semanaPassada.toISOString().split('T')[0];
                dataFim.value = proximoMes.toISOString().split('T')[0];
            }
        });
    </script>
</body>
</html>