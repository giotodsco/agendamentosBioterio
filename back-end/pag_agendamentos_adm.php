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
                $mensagem_sucesso = "Agendamento confirmado com sucesso!";
                
            } elseif ($acao === 'negar') {
                $stmt = $conexao->prepare("UPDATE agendamentos SET status = 'negado' WHERE id = :id");
                $stmt->bindParam(':id', $agendamento_id);
                $stmt->execute();
                $mensagem_sucesso = "Agendamento negado com sucesso!";
                
            } elseif ($acao === 'cancelar') {
                $stmt = $conexao->prepare("UPDATE agendamentos SET status = 'cancelado', data_cancelamento = NOW() WHERE id = :id");
                $stmt->bindParam(':id', $agendamento_id);
                $stmt->execute();
                $mensagem_sucesso = "Agendamento cancelado com sucesso!";
                
            } elseif ($acao === 'remover') {
                $stmt = $conexao->prepare("DELETE FROM agendamentos WHERE id = :id");
                $stmt->bindParam(':id', $agendamento_id);
                $stmt->execute();
                $mensagem_sucesso = "Agendamento removido com sucesso!";
            }
        } catch (PDOException $e) {
            $mensagem_erro = "Erro ao processar ação: " . $e->getMessage();
        }
    }
}

try {
    $conexao = conectarBanco();
    
    // Filtros
    $filtro_data_inicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-7 days'));
    $filtro_data_fim = $_GET['data_fim'] ?? date('Y-m-d', strtotime('+30 days'));
    $filtro_status = $_GET['status'] ?? '';
    
    $filtros = [
        'data_inicio' => $filtro_data_inicio,
        'data_fim' => $filtro_data_fim,
        'status' => $filtro_status
    ];
    
    $agendamentos = buscarAgendamentosCompletos($filtros);
    
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
    krsort($agendamentos_por_data); // Mais recente primeiro para admin
    
} catch (PDOException $e) {
    $mensagem_erro = "Erro ao carregar agendamentos: " . $e->getMessage();
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
    <title>Biotério - Painel Administrativo</title>
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
            background: linear-gradient(135deg, rgba(64, 122, 53, 0.9) 0%, rgba(44, 81, 36, 0.9) 100%);
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            flex-shrink: 0;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }

        .header h1 {
            color: white;
            font-size: 28px;
            font-weight: 700;
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
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-header:hover {
            background: white;
            color: rgba(64, 122, 53, 0.9);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }

        .btn-header.config {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.3) 0%, rgba(255, 193, 7, 0.2) 100%);
            border-color: #ffc107;
        }

        .btn-header.config:hover {
            background: #ffc107;
            color: #856404;
            border-color: #e0a800;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info span {
            color: white;
            font-size: 16px;
            background-color: rgba(255, 255, 255, 0.1);
            padding: 8px 15px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .content {
            background-color: rgb(225, 225, 228);
            border-radius: 15px 15px 0 0;
            box-shadow: 5px 5px 50px rgba(90, 90, 90, 0.392);
            padding: 25px;
            flex: 1;
            margin: 15px;
            margin-bottom: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .page-title {
            text-align: center;
            margin-bottom: 25px;
        }

        .page-title h2 {
            color: rgba(64, 122, 53, 0.819);
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .page-title p {
            color: rgb(100, 100, 100);
            font-size: 16px;
        }

        .admin-info {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 2px solid #ffc107;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            text-align: center;
        }

        .admin-info h4 {
            color: #856404;
            margin-bottom: 10px;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .admin-info p {
            color: #856404;
            font-size: 14px;
            line-height: 1.5;
        }

        .alerts {
            margin-bottom: 20px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            animation: slideIn 0.5s ease-out;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 2px solid #28a745;
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 2px solid #dc3545;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .filters {
            background: linear-gradient(135deg, rgba(64, 122, 53, 0.1) 0%, rgba(64, 122, 53, 0.05) 100%);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            border-left: 5px solid rgba(64, 122, 53, 0.819);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            flex-shrink: 0;
        }

        .filters h3 {
            color: rgba(64, 122, 53, 0.819);
            margin-bottom: 20px;
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .filter-group label {
            font-size: 14px;
            margin-bottom: 8px;
            color: rgb(60, 59, 59);
            font-weight: bold;
            display: block;
        }

        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            font-family: Georgia, 'Times New Roman', Times, serif;
            transition: all 0.3s;
            background-color: white;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: rgba(64, 122, 53, 0.819);
            box-shadow: 0 0 0 3px rgba(64, 122, 53, 0.1);
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
            background: linear-gradient(135deg, rgba(64, 122, 53, 0.819) 0%, rgba(44, 81, 36, 0.819) 100%);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, rgba(44, 81, 36, 0.819) 0%, rgba(64, 122, 53, 0.819) 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(64, 122, 53, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20a339 100%);
            color: white;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #20a339 0%, #1e7e34 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #333;
        }

        .btn-warning:hover {
            background: linear-gradient(135deg, #e0a800 0%, #d39e00 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 193, 7, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #5a6268 0%, #495057 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
            flex-shrink: 0;
        }

        .stat-card {
            background: linear-gradient(135deg, white 0%, #f8f9fa 100%);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            border-left: 5px solid rgba(64, 122, 53, 0.819);
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .stat-card:nth-child(2) { border-left-color: #ffc107; }
        .stat-card:nth-child(3) { border-left-color: #27ae60; }
        .stat-card:nth-child(4) { border-left-color: #9b59b6; }
        .stat-card:nth-child(5) { border-left-color: #e74c3c; }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: rgba(64, 122, 53, 0.819);
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 14px;
            color: rgb(100, 100, 100);
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .appointments-container {
            flex: 1;
            overflow-y: auto;
            padding-right: 5px;
        }

        /* Barra de rolagem personalizada */
        .appointments-container::-webkit-scrollbar {
            width: 10px;
        }

        .appointments-container::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 5px;
        }

        .appointments-container::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, rgba(64, 122, 53, 0.6) 0%, rgba(64, 122, 53, 0.8) 100%);
            border-radius: 5px;
            transition: all 0.3s;
        }

        .appointments-container::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, rgba(64, 122, 53, 0.8) 0%, rgba(64, 122, 53, 1) 100%);
        }

        .day-section {
            background-color: white;
            border-radius: 15px;
            margin-bottom: 25px;
            overflow: hidden;
            border-left: 6px solid rgba(64, 122, 53, 0.819);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }

        .day-section:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
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
            font-size: 22px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .day-date {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 5px;
        }

        .day-stats {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .day-count {
            background-color: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .appointments-grid {
            padding: 25px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 20px;
        }

        .appointment-card {
            background: linear-gradient(135deg, #f8f9fa 0%, white 100%);
            border-radius: 12px;
            padding: 20px;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
            position: relative;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .appointment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            border-color: rgba(64, 122, 53, 0.3);
        }

        .appointment-card.empresa-card {
            background: linear-gradient(135deg, #fff3cd 0%, rgba(255, 243, 205, 0.3) 100%);
            border-color: #ffc107;
        }

        .appointment-card.empresa-card:hover {
            border-color: #ff9800;
            box-shadow: 0 10px 30px rgba(255, 193, 7, 0.2);
        }

        .appointment-card.pendente-card {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border-color: #ffc107;
            animation: highlightPendente 3s infinite;
        }

        @keyframes highlightPendente {
            0%, 100% { border-color: #ffc107; }
            50% { border-color: #e67e22; }
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
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .appointment-id.empresa {
            background-color: rgba(255, 193, 7, 0.2);
            color: #856404;
        }

        .appointment-id.pendente {
            background-color: rgba(255, 193, 7, 0.3);
            color: #856404;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }

        .appointment-time {
            font-size: 20px;
            font-weight: bold;
            color: rgba(64, 122, 53, 0.819);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .appointment-info {
            margin-bottom: 15px;
        }

        .appointment-info h4 {
            color: rgb(60, 59, 59);
            margin-bottom: 8px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .appointment-info h4.empresa-name {
            color: #856404;
        }

        .appointment-info p {
            font-size: 14px;
            color: rgb(100, 100, 100);
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .empresa-details {
            background-color: rgba(255, 193, 7, 0.15);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #ffc107;
        }

        .pessoas-count {
            background-color: rgba(255, 193, 7, 0.2);
            color: #856404;
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .status {
            padding: 10px 16px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: bold;
            text-align: center;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            min-width: 120px;
            justify-content: center;
        }

        /* CORRIGIDO: Status pendente em amarelo */
        .status-pendente {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
            border: 2px solid #ffc107;
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);
            animation: statusPulse 2s infinite;
        }

        @keyframes statusPulse {
            0% { transform: scale(1); box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4); }
            50% { transform: scale(1.05); box-shadow: 0 6px 20px rgba(255, 193, 7, 0.6); }
            100% { transform: scale(1); box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4); }
        }

        .status-confirmado {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
            border: 2px solid #27ae60;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.4);
        }

        .status-cancelado {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
            color: white;
            border: 2px solid #7f8c8d;
            box-shadow: 0 4px 15px rgba(149, 165, 166, 0.4);
        }

        .status-negado {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            border: 2px solid #c0392b;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.4);
        }

        .user-type {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .user-logado {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            color: #1976d2;
            border: 2px solid #2196f3;
        }

        .user-anonimo {
            background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%);
            color: #7b1fa2;
            border: 2px solid #9c27b0;
        }

        .user-empresa {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
            border: 2px solid #ffc107;
        }

        .admin-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .btn-admin {
            padding: 8px 16px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-width: 110px;
            justify-content: center;
        }

        .btn-admin:hover {
            transform: translateY(-2px);
        }

        .btn-admin.confirmar {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
        }

        .btn-admin.confirmar:hover {
            box-shadow: 0 6px 20px rgba(39, 174, 96, 0.4);
        }

        .btn-admin.negar {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
        }

        .btn-admin.negar:hover {
            box-shadow: 0 6px 20px rgba(243, 156, 18, 0.4);
        }

        .btn-admin.cancelar {
            background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);
            color: white;
        }

        .btn-admin.cancelar:hover {
            box-shadow: 0 6px 20px rgba(230, 126, 34, 0.4);
        }

        .btn-admin.remover {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }

        .btn-admin.remover:hover {
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
        }

        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .today-highlight {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border-left-color: #ffc107;
        }

        .today-highlight .day-header {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
        }

        .past-day {
            opacity: 0.9;
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
            border: 2px solid rgba(64, 122, 53, 0.1);
        }

        @keyframes popupSlideIn {
            from {
                opacity: 0;
                transform: scale(0.7) translateY(-30px);
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
            color: rgba(64, 122, 53, 0.819);
            margin-bottom: 15px;
        }

        .popup-message {
            font-size: 16px;
            color: rgb(100, 100, 100);
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
            .header {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }
            
            .content {
                margin: 10px;
                padding: 20px;
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

            .day-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .day-stats {
                justify-content: center;
            }

            .admin-actions {
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
            <div class="popup-message" id="popup-message">Tem certeza que deseja realizar esta ação?</div>
            <div class="popup-buttons">
                <button class="popup-btn popup-btn-confirm" id="popup-confirm">Confirmar</button>
                <button class="popup-btn popup-btn-cancel" id="popup-cancel">Cancelar</button>
            </div>
        </div>
    </div>

    <div class="header">
        <h1>
            <i class="fa-solid fa-shield-halved"></i>
            Painel Administrativo
        </h1>
        <div class="header-actions">
            <div class="user-info">
                <span><i class="fa-solid fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?> (<?php echo ucfirst($_SESSION['tipo_usuario']); ?>)</span>
            </div>
            <?php if ($_SESSION['tipo_usuario'] === 'admin'): ?>
            <a href="configuracoes.php" class="btn-header config">
                <i class="fa-solid fa-cog"></i>
                Configurações
            </a>
            <?php endif; ?>
            <a href="../front-end/pag_inicial.html" class="btn-header">
                <i class="fa-solid fa-home"></i>
                Página Inicial
            </a>
            <a href="pag_adm.php" class="btn-header">
                <i class="fa-solid fa-sign-out-alt"></i>
                Sair
            </a>
        </div>
    </div>

    <div class="content">
        <div class="page-title">
            <h2><i class="fa-solid fa-calendar-check"></i> Administração de Agendamentos</h2>
            <p>Gerencie todos os agendamentos do sistema com controle total sobre aprovações e cancelamentos</p>
        </div>

        <div class="admin-info">
            <h4><i class="fa-solid fa-crown"></i> Painel Administrativo</h4>
            <p>Como administrador, você tem acesso completo: <strong>confirmar</strong>, <strong>negar</strong>, <strong>cancelar</strong> e <strong>remover</strong> agendamentos. Agendamentos pendentes em <strong style="color: #ffc107;">AMARELO</strong> requerem sua aprovação.</p>
        </div>

        <div class="alerts">
            <?php if (isset($mensagem_sucesso)): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-check-circle"></i>
                    <?php echo htmlspecialchars($mensagem_sucesso); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($mensagem_erro)): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($mensagem_erro); ?>
                </div>
            <?php endif; ?>
        </div>

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
                            <option value="pendente" <?php echo $filtro_status === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                            <option value="confirmado" <?php echo $filtro_status === 'confirmado' ? 'selected' : ''; ?>>Confirmado</option>
                            <option value="cancelado" <?php echo $filtro_status === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                            <option value="negado" <?php echo $filtro_status === 'negado' ? 'selected' : ''; ?>>Negado</option>
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

        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($agendamentos); ?></div>
                <div class="stat-label"><i class="fa-solid fa-calendar-check"></i> Total de Agendamentos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['status'] === 'pendente')); ?></div>
                <div class="stat-label"><i class="fa-solid fa-clock"></i> Aguardando Aprovação</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['status'] === 'confirmado')); ?></div>
                <div class="stat-label"><i class="fa-solid fa-check-circle"></i> Confirmados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['tipo_agendamento'] === 'empresa')); ?></div>
                <div class="stat-label"><i class="fa-solid fa-building"></i> Empresas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['status'] === 'negado')); ?></div>
                <div class="stat-label"><i class="fa-solid fa-ban"></i> Negados</div>
            </div>
        </div>

        <div class="appointments-container">
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
                                    <i class="fa-solid fa-clock"></i> <?php echo count(array_filter($agendamentos_do_dia, fn($a) => $a['status'] === 'pendente')); ?> pendente<?php echo count(array_filter($agendamentos_do_dia, fn($a) => $a['status'] === 'pendente')) != 1 ? 's' : ''; ?>
                                </div>
                                <div class="day-count">
                                    <i class="fa-solid fa-building"></i> <?php echo count(array_filter($agendamentos_do_dia, fn($a) => $a['tipo_agendamento'] === 'empresa')); ?> empresa<?php echo count(array_filter($agendamentos_do_dia, fn($a) => $a['tipo_agendamento'] === 'empresa')) != 1 ? 's' : ''; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="appointments-grid">
                            <?php foreach ($agendamentos_do_dia as $agendamento): 
                                $isEmpresa = $agendamento['tipo_agendamento'] === 'empresa';
                                $isPendente = $agendamento['status'] === 'pendente';
                                
                                $cardClass = '';
                                if ($isPendente) {
                                    $cardClass = 'pendente-card';
                                } elseif ($isEmpresa) {
                                    $cardClass = 'empresa-card';
                                }
                            ?>
                            <div class="appointment-card <?php echo $cardClass; ?>">
                                <div class="appointment-header">
                                    <div class="appointment-id <?php echo $isEmpresa ? 'empresa' : ''; ?> <?php echo $isPendente ? 'pendente' : ''; ?>">
                                        <i class="fa-solid fa-hashtag"></i> <?php echo $agendamento['id']; ?>
                                        <?php if ($isPendente): ?>
                                            <span style="color: #e67e22; font-weight: bold;">REQUER AÇÃO</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="appointment-time">
                                        <i class="fa-solid fa-clock"></i> <?php echo date('H:i', strtotime($agendamento['hora_agendamento'])); ?>
                                    </div>
                                </div>
                                
                                <div class="appointment-info">
                                    <?php if ($isEmpresa): ?>
                                        <h4 class="empresa-name">
                                            <i class="fa-solid fa-building"></i> 
                                            <?php echo htmlspecialchars($agendamento['empresa_nome'] ?? $agendamento['nome']); ?>
                                        </h4>
                                        <div class="empresa-details">
                                            <div class="pessoas-count">
                                                <i class="fa-solid fa-users"></i> 
                                                <?php echo $agendamento['quantidade_pessoas'] ?? 1; ?> pessoas
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <h4><i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($agendamento['nome']); ?></h4>
                                    <?php endif; ?>
                                    <p><i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($agendamento['email']); ?></p>
                                    <p><i class="fa-solid fa-id-card"></i> <?php echo htmlspecialchars($agendamento['cpf']); ?></p>
                                    <p><i class="fa-solid fa-calendar-plus"></i> Criado: <?php echo date('d/m/Y H:i', strtotime($agendamento['data_criacao'])); ?></p>
                                    <?php if ($agendamento['data_cancelamento']): ?>
                                    <p><i class="fa-solid fa-calendar-times"></i> Cancelado: <?php echo date('d/m/Y H:i', strtotime($agendamento['data_cancelamento'])); ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card-footer">
                                    <div>
                                        <span class="status status-<?php echo $agendamento['status']; ?>">
                                            <?php if ($agendamento['status'] === 'confirmado'): ?>
                                                <i class="fa-solid fa-check-circle"></i> CONFIRMADO
                                            <?php elseif ($agendamento['status'] === 'pendente'): ?>
                                                <i class="fa-solid fa-clock"></i> PENDENTE
                                            <?php elseif ($agendamento['status'] === 'negado'): ?>
                                                <i class="fa-solid fa-times-circle"></i> NEGADO
                                            <?php else: ?>
                                                <i class="fa-solid fa-ban"></i> CANCELADO
                                            <?php endif; ?>
                                        </span>
                                        
                                        <?php if ($isEmpresa): ?>
                                            <span class="user-type user-empresa">
                                                <i class="fa-solid fa-building"></i> Empresa
                                            </span>
                                        <?php elseif ($agendamento['usuario_id']): ?>
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

                                <!-- AÇÕES ADMINISTRATIVAS -->
                                <div class="admin-actions">
                                    <?php if ($agendamento['status'] === 'pendente'): ?>
                                        <form method="POST" style="display: inline;" action="">
                                            <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                            <input type="hidden" name="acao" value="confirmar">
                                            <button type="submit" class="btn-admin confirmar">
                                                <i class="fa-solid fa-check"></i> Confirmar
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;" action="">
                                            <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                            <input type="hidden" name="acao" value="negar">
                                            <button type="submit" class="btn-admin negar">
                                                <i class="fa-solid fa-times"></i> Negar
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($agendamento['status'] === 'confirmado'): ?>
                                        <form method="POST" style="display: inline;" action="" id="form-cancelar-<?php echo $agendamento['id']; ?>">
                                            <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                            <input type="hidden" name="acao" value="cancelar">
                                            <button type="button" class="btn-admin cancelar"
                                                    onclick="showCustomConfirm('Tem certeza que deseja cancelar este agendamento?', () => { document.getElementById('form-cancelar-<?php echo $agendamento['id']; ?>').submit(); })">
                                                <i class="fa-solid fa-ban"></i> Cancelar
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <form method="POST" style="display: inline;" action="" id="form-remover-<?php echo $agendamento['id']; ?>">
                                        <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                        <input type="hidden" name="acao" value="remover">
                                        <button type="button" class="btn-admin remover"
                                                onclick="showCustomConfirm('Tem certeza que deseja remover este agendamento? Esta ação não pode ser desfeita!', () => { document.getElementById('form-remover-<?php echo $agendamento['id']; ?>').submit(); })">
                                            <i class="fa-solid fa-trash"></i> Remover
                                        </button>
                                    </form>
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
                    <small>Não há agendamentos no período selecionado ou com os filtros aplicados</small>
                </div>
            <?php endif; ?>
        </div>
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

        // Destacar agendamentos pendentes ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            // Scroll suave para o primeiro agendamento pendente
            const pendenteCard = document.querySelector('.pendente-card');
            if (pendenteCard) {
                setTimeout(() => {
                    pendenteCard.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                }, 1000);
            }

            // Animar cards pendentes
            const pendenteCards = document.querySelectorAll('.pendente-card');
            pendenteCards.forEach(card => {
                card.style.position = 'relative';
                card.style.overflow = 'hidden';
                
                // Adicionar efeito de brilho
                const shine = document.createElement('div');
                shine.style.position = 'absolute';
                shine.style.top = '0';
                shine.style.left = '-100%';
                shine.style.width = '100%';
                shine.style.height = '100%';
                shine.style.background = 'linear-gradient(90deg, transparent, rgba(255, 193, 7, 0.3), transparent)';
                shine.style.animation = 'shine 3s infinite';
                shine.style.pointerEvents = 'none';
                card.appendChild(shine);
            });

            // Adicionar animação de brilho
            const style = document.createElement('style');
            style.textContent = `
                @keyframes shine {
                    0% { left: -100%; }
                    50% { left: 100%; }
                    100% { left: 100%; }
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>
</html>