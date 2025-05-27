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

// Processar remoção de agendamentos concluídos
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao']) && $_POST['acao'] === 'remover_concluido') {
    $agendamento_id = $_POST['agendamento_id'] ?? '';
    
    if (!empty($agendamento_id)) {
        try {
            $conexao = conectarBanco();
            
            // Verificar se o agendamento está concluído
            $stmt = $conexao->prepare("SELECT status, data_agendamento FROM agendamentos WHERE id = ?");
            $stmt->execute([$agendamento_id]);
            $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($agendamento && $agendamento['status'] === 'confirmado' && $agendamento['data_agendamento'] < date('Y-m-d')) {
                // Remover o agendamento concluído
                $stmt = $conexao->prepare("DELETE FROM agendamentos WHERE id = ?");
                $stmt->execute([$agendamento_id]);
                
                if ($stmt->rowCount() > 0) {
                    $mensagem_sucesso = "Agendamento concluído removido com sucesso.";
                } else {
                    $mensagem_erro = "Erro ao remover agendamento.";
                }
            } else {
                $mensagem_erro = "Apenas agendamentos concluídos podem ser removidos.";
            }
        } catch (PDOException $e) {
            $mensagem_erro = "Erro ao remover agendamento: " . $e->getMessage();
        }
    }
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
    
    $filtros = [
        'data_inicio' => $filtro_data_inicio,
        'data_fim' => $filtro_data_fim,
        'status' => $filtro_status
    ];
    
    // CORREÇÃO: Operador NÃO deve ver agendamentos pendentes
    // Se não há filtro específico de status, excluir pendentes automaticamente
    if (empty($filtro_status)) {
        $filtros['status_excluir'] = 'pendente';
    } elseif ($filtro_status === 'pendente') {
        // Se operador tentar filtrar por pendente, redirecionar sem esse filtro
        $url_redirect = $_SERVER['PHP_SELF'] . '?';
        $params = $_GET;
        unset($params['status']);
        $url_redirect .= http_build_query($params);
        header("Location: $url_redirect");
        exit();
    }
    
    $agendamentos = buscarAgendamentosCompletos($filtros);
    
    // Separar agendamentos por status temporal
    $agendamentos_atuais = [];
    $agendamentos_concluidos = [];
    
    foreach ($agendamentos as $agendamento) {
        // Determinar se está concluído (confirmado e data passou)
        if ($agendamento['status'] === 'confirmado' && $agendamento['data_agendamento'] < $hoje) {
            $agendamento['status_display'] = 'concluido';
            $agendamentos_concluidos[] = $agendamento;
        } else {
            $agendamento['status_display'] = $agendamento['status'];
            $agendamentos_atuais[] = $agendamento;
        }
    }
    
    // Reorganizar: atuais primeiro, concluídos por último
    $agendamentos = array_merge($agendamentos_atuais, $agendamentos_concluidos);
    
    // Organizar agendamentos por data
    $agendamentos_por_data = [];
    foreach ($agendamentos as $agendamento) {
        $data = $agendamento['data_agendamento'];
        if (!isset($agendamentos_por_data[$data])) {
            $agendamentos_por_data[$data] = [];
        }
        $agendamentos_por_data[$data][] = $agendamento;
    }
    
    // Ordenar datas: futuras/hoje primeiro, passadas por último
    $datas_futuras = [];
    $datas_passadas = [];
    
    foreach ($agendamentos_por_data as $data => $agends) {
        if ($data >= $hoje) {
            $datas_futuras[$data] = $agends;
        } else {
            $datas_passadas[$data] = $agends;
        }
    }
    
    // Ordenar futuras crescente, passadas decrescente
    ksort($datas_futuras);
    krsort($datas_passadas);
    
    // Reorganizar: futuras primeiro, passadas depois
    $agendamentos_por_data = array_merge($datas_futuras, $datas_passadas);
    
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
            flex-shrink: 0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
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

        /* NOVO: Aviso para operador sobre pendentes */
        .operador-info {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border: 2px solid #2196f3;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            text-align: center;
        }

        .operador-info h4 {
            color: #1976d2;
            margin-bottom: 10px;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .operador-info p {
            color: #1976d2;
            font-size: 14px;
            line-height: 1.5;
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid transparent;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
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
            background-color: rgba(64, 122, 53, 0.819);
            color: white;
        }

        .btn-primary:hover {
            background-color: rgba(44, 81, 36, 0.819);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(64, 122, 53, 0.3);
        }

        .btn-success {
            background-color: #28a745;
            color: white;
        }

        .btn-success:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3);
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.3);
        }

        .btn-warning {
            background-color: #ffc107;
            color: #333;
        }

        .btn-warning:hover {
            background-color: #e0a800;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 193, 7, 0.3);
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.3);
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

        .actions {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            flex-shrink: 0;
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
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.2);
            flex-shrink: 0;
        }

        .export-specific-date h4 {
            color: #856404;
            margin-bottom: 15px;
            font-size: 18px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .export-date-form {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
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

        /* NOVO: Seção de dias passados com aparência diferente */
        .day-section.past-day {
            opacity: 0.8;
            border-left-color: #6c757d;
        }

        .day-section.past-day .day-header {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
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
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
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

        /* NOVO: Cards verde claro para usuários logados */
        .appointment-card.user-logado-card {
            background: linear-gradient(135deg, #e8f5e8 0%, #f0f8f0 100%);
            border-left-color: #28a745;
            border-color: #28a745;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.1);
        }

        .appointment-card.user-logado-card:hover {
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.2);
            border-color: #1e7e34;
        }

        /* NOVO: Cards cinza para agendamentos concluídos */
        .appointment-card.concluido-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-color: #6c757d;
            opacity: 0.85;
        }

        .appointment-card.concluido-card:hover {
            border-color: #5a6268;
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.2);
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

        .appointment-id.concluido {
            background-color: rgba(108, 117, 125, 0.2);
            color: #6c757d;
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
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .status-confirmado {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 2px solid #28a745;
        }

        .status-cancelado {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 2px solid #dc3545;
        }

        .status-negado {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 2px solid #dc3545;
        }

        /* NOVO: Status concluído em cinza */
        .status-concluido {
            background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
            color: #495057;
            border: 2px solid #6c757d;
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

        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-today {
            background-color: #28a745;
            color: white;
        }

        .badge-future {
            background-color: #17a2b8;
            color: white;
        }

        .badge-past {
            background-color: #6c757d;
            color: white;
        }

        /* NOVO: Botão de remoção para concluídos */
        .btn-remove-completed {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-remove-completed:hover {
            background: linear-gradient(135deg, #5a6268 0%, #495057 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
        }

        /* NOVO: Aviso sobre PDF */
        .pdf-warning {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }

        .pdf-warning h5 {
            color: #856404;
            margin-bottom: 10px;
            font-size: 16px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .pdf-warning p {
            color: #856404;
            font-size: 14px;
            margin-bottom: 10px;
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
            max-width: 500px;
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
            color: #ffc107;
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
                padding: 10px 15px;
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
        <h1><i class="fa-solid fa-chart-line"></i> Painel do Operador</h1>
        <div class="user-info">
            <span><i class="fa-solid fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?> (<?php echo ucfirst($_SESSION['tipo_usuario']); ?>)</span>
            <a href="logout.php" class="btn-logout">
                <i class="fa-solid fa-sign-out-alt"></i> Sair
            </a>
        </div>
    </div>

    <div class="content">
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

        <div class="page-title">
            <h2><i class="fa-solid fa-calendar-alt"></i> Relatórios e Agendamentos</h2>
            <p>Visualize e analise todos os agendamentos confirmados e processados do sistema</p>
        </div>

        <!-- NOVO: Aviso sobre política de acesso para operadores -->
        <div class="operador-info">
            <h4><i class="fa-solid fa-info-circle"></i> Informação para Operadores</h4>
            <p>Como operador, você visualiza apenas agendamentos <strong>confirmados</strong>, <strong>cancelados</strong>, <strong>negados</strong> e <strong>concluídos</strong>. Agendamentos pendentes são visíveis apenas para administradores.</p>
        </div>

        <?php if (count(array_filter($agendamentos, fn($a) => isset($a['status_display']) && $a['status_display'] === 'concluido')) > 0): ?>
        <!-- NOVO: Aviso sobre agendamentos concluídos -->
        <div class="pdf-warning">
            <h5><i class="fa-solid fa-exclamation-triangle"></i> Agendamentos Concluídos Detectados</h5>
            <p>Há agendamentos concluídos na lista. <strong>Recomendamos salvar um PDF dos dados antes de removê-los</strong>, pois a remoção é permanente.</p>
            <button type="button" class="btn btn-warning" onclick="exportarPDF()">
                <i class="fa-solid fa-file-pdf"></i> Salvar PDF Antes de Remover
            </button>
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
                            <option value="">Todos os Status Visíveis</option>
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

        <!-- Exportação por data específica -->
        <div class="export-specific-date">
            <h4><i class="fa-solid fa-calendar-day"></i> Exportar Agendamentos de uma Data Específica</h4>
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
                <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['status'] === 'confirmado' && $a['data_agendamento'] >= date('Y-m-d'))); ?></div>
                <div class="stat-label"><i class="fa-solid fa-check-circle"></i> Confirmados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => isset($a['status_display']) && $a['status_display'] === 'concluido')); ?></div>
                <div class="stat-label"><i class="fa-solid fa-flag-checkered"></i> Concluídos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['status'] === 'cancelado')); ?></div>
                <div class="stat-label"><i class="fa-solid fa-times-circle"></i> Cancelados</div>
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
                                    <i class="fa-solid fa-building"></i> <?php echo count(array_filter($agendamentos_do_dia, fn($a) => $a['tipo_agendamento'] === 'empresa')); ?> empresa<?php echo count(array_filter($agendamentos_do_dia, fn($a) => $a['tipo_agendamento'] === 'empresa')) != 1 ? 's' : ''; ?>
                                </div>
                                <?php if ($is_past): ?>
                                <div class="day-count">
                                    <i class="fa-solid fa-flag-checkered"></i> <?php echo count(array_filter($agendamentos_do_dia, fn($a) => isset($a['status_display']) && $a['status_display'] === 'concluido')); ?> concluído<?php echo count(array_filter($agendamentos_do_dia, fn($a) => isset($a['status_display']) && $a['status_display'] === 'concluido')) != 1 ? 's' : ''; ?>
                                </div>
                                <?php endif; ?>
                                <button type="button" class="btn btn-warning" onclick="exportarDataEspecificaDireta('<?php echo $data; ?>', 'pdf')" style="font-size: 12px; padding: 6px 12px;">
                                    <i class="fa-solid fa-download"></i> PDF
                                </button>
                            </div>
                        </div>
                        
                        <div class="appointments-grid">
                            <?php foreach ($agendamentos_do_dia as $agendamento): 
                                $isEmpresa = $agendamento['tipo_agendamento'] === 'empresa';
                                $isConcluido = isset($agendamento['status_display']) && $agendamento['status_display'] === 'concluido';
                                
                                // NOVO: Definir classe baseada no tipo de usuário e status
                                $cardClass = '';
                                if ($isConcluido) {
                                    $cardClass = 'concluido-card';  // Cinza para concluídos
                                } elseif (!$isEmpresa && $agendamento['usuario_id']) {
                                    $cardClass = 'user-logado-card';  // Verde claro para usuários logados
                                } elseif ($isEmpresa) {
                                    $cardClass = 'empresa-card';      // Amarelo para empresas
                                }
                                
                                // Badge para data
                                $badge_class = '';
                                $badge_text = '';
                                if ($is_today) {
                                    $badge_class = 'badge-today';
                                    $badge_text = 'HOJE';
                                } elseif ($is_past) {
                                    $badge_class = 'badge-past';
                                    $badge_text = 'PASSADO';
                                } else {
                                    $badge_class = 'badge-future';
                                    $badge_text = 'FUTURO';
                                }
                            ?>
                            <div class="appointment-card <?php echo $cardClass; ?>">
                                <div class="appointment-header">
                                    <div class="appointment-id <?php echo $isEmpresa ? 'empresa' : ''; ?> <?php echo $isConcluido ? 'concluido' : ''; ?>">
                                        <i class="fa-solid fa-hashtag"></i> <?php echo $agendamento['id']; ?>
                                        <?php if ($isConcluido): ?>
                                            <span style="color: #6c757d; font-size: 10px; font-weight: bold;">CONCLUÍDO</span>
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
                                    <?php if ($isConcluido): ?>
                                    <p><i class="fa-solid fa-flag-checkered"></i> Concluído: <?php echo date('d/m/Y', strtotime($agendamento['data_agendamento'])); ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card-footer">
                                    <div>
                                        <span class="status status-<?php echo $isConcluido ? 'concluido' : $agendamento['status']; ?>">
                                            <?php if ($isConcluido): ?>
                                                <i class="fa-solid fa-flag-checkered"></i> CONCLUÍDO
                                            <?php elseif ($agendamento['status'] === 'confirmado'): ?>
                                                <i class="fa-solid fa-check-circle"></i> CONFIRMADO
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

                                        <?php if ($isConcluido): ?>
                                            <form method="POST" style="display: inline;" action="" id="form-remover-concluido-<?php echo $agendamento['id']; ?>">
                                                <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                                <input type="hidden" name="acao" value="remover_concluido">
                                                <button type="button" class="btn-remove-completed"
                                                        onclick="showCustomConfirm('⚠️ ATENÇÃO: Tem certeza que deseja remover este agendamento concluído?\n\nRecomendamos salvar um PDF antes da remoção, pois esta ação é PERMANENTE e não pode ser desfeita!', () => { document.getElementById('form-remover-concluido-<?php echo $agendamento['id']; ?>').submit(); })">
                                                    <i class="fa-solid fa-trash"></i> Remover
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
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
                    <small>Tente ajustar os filtros para ver mais resultados ou aguarde novos agendamentos serem confirmados</small>
                </div>
            <?php endif; ?>
        </div>
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

        function exportarDataEspecifica(tipo) {
            const dataEspecifica = document.getElementById('data_especifica').value;
            if (!dataEspecifica) {
                alert('Por favor, selecione uma data primeiro.');
                return;
            }
            
            const params = new URLSearchParams();
            params.append('export', tipo);
            params.append('data_especifica', dataEspecifica);
            
            window.open('exportar_relatorio.php?' + params.toString(), '_blank');
        }

        function exportarDataEspecificaDireta(data, tipo) {
            const params = new URLSearchParams();
            params.append('export', tipo);
            params.append('data_especifica', data);
            
            window.open('exportar_relatorio.php?' + params.toString(), '_blank');
        }

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