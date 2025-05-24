<?php
// acexx/back-end/exportar_relatorio.php
session_start();
require_once 'functions.php';

// Verificar se o usuário está logado e tem permissão (operador ou admin)
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: pag_adm.php");
    exit();
}

if (!in_array($_SESSION['tipo_usuario'], ['operador', 'admin'])) {
    header("Location: ../front-end/pag_inicial.html");
    exit();
}

$export_type = $_GET['export'] ?? '';
if (!in_array($export_type, ['pdf', 'excel'])) {
    header("Location: pag_agendamentos_operador.php");
    exit();
}

// Filtros
$filtro_data_inicio = $_GET['data_inicio'] ?? '';
$filtro_data_fim = $_GET['data_fim'] ?? '';
$filtro_status = $_GET['status'] ?? '';
$filtro_data_especifica = $_GET['data_especifica'] ?? ''; // NOVA FUNCIONALIDADE

try {
    $conexao = conectarBanco();
    
    $sql = "SELECT a.*, u.nome as usuario_nome FROM agendamentos a LEFT JOIN usuarios u ON a.usuario_id = u.id WHERE 1=1 ";
    $params = [];
    
    // NOVA FUNCIONALIDADE: Filtro por data específica tem prioridade
    if ($filtro_data_especifica) {
        $sql .= " AND a.data_agendamento = ?";
        $params[] = $filtro_data_especifica;
    } else {
        // Filtros originais apenas se não houver data específica
        if ($filtro_data_inicio) {
            $sql .= " AND a.data_agendamento >= ?";
            $params[] = $filtro_data_inicio;
        }
        
        if ($filtro_data_fim) {
            $sql .= " AND a.data_agendamento <= ?";
            $params[] = $filtro_data_fim;
        }
    }
    
    if ($filtro_status) {
        $sql .= " AND a.status = ?";
        $params[] = $filtro_status;
    }
    
    $sql .= " ORDER BY a.data_agendamento DESC, a.hora_agendamento ASC";
    
    $stmt = $conexao->prepare($sql);
    $stmt->execute($params);
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Erro ao buscar dados: " . $e->getMessage());
}

if ($export_type === 'excel') {
    // Exportar para Excel (CSV)
    $filename = 'relatorio_agendamentos_' . date('Y-m-d_H-i-s') . '.csv';
    
    // NOVA FUNCIONALIDADE: Nome específico para data específica
    if ($filtro_data_especifica) {
        $filename = 'agendamentos_' . date('d-m-Y', strtotime($filtro_data_especifica)) . '_' . date('H-i-s') . '.csv';
    }
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Saída para o navegador
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8 (para Excel reconhecer acentos)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Cabeçalhos
    fputcsv($output, [
        'ID',
        'Nome',
        'Email', 
        'CPF',
        'Data do Agendamento',
        'Horário',
        'Status',
        'Tipo de Usuário',
        'Data de Criação',
        'Data de Cancelamento'
    ], ';');
    
    // Dados
    foreach ($agendamentos as $agendamento) {
        fputcsv($output, [
            $agendamento['id'],
            $agendamento['nome'],
            $agendamento['email'],
            $agendamento['cpf'],
            date('d/m/Y', strtotime($agendamento['data_agendamento'])),
            date('H:i', strtotime($agendamento['hora_agendamento'])),
            ucfirst($agendamento['status']),
            $agendamento['usuario_id'] ? 'Usuário Logado' : 'Anônimo',
            date('d/m/Y H:i', strtotime($agendamento['data_criacao'])),
            $agendamento['data_cancelamento'] ? date('d/m/Y H:i', strtotime($agendamento['data_cancelamento'])) : ''
        ], ';');
    }
    
    fclose($output);
    exit();
    
} elseif ($export_type === 'pdf') {
    // Exportar para PDF (HTML simples que pode ser salvo como PDF pelo navegador)
    $titulo = 'Relatório de Agendamentos - Biotério FSA';
    $data_geracao = date('d/m/Y H:i:s');
    $usuario_gerador = $_SESSION['usuario_nome'];
    
    // NOVA FUNCIONALIDADE: Título específico para data específica
    if ($filtro_data_especifica) {
        $data_formatada = date('d/m/Y', strtotime($filtro_data_especifica));
        $titulo = 'Agendamentos do dia ' . $data_formatada . ' - Biotério FSA';
    }
    
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $titulo; ?></title>
        <style>
            body {
                font-family: Arial, sans-serif;
                font-size: 12px;
                margin: 20px;
                color: #333;
            }
            
            .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 2px solid #407a35;
                padding-bottom: 20px;
            }
            
            .header h1 {
                color: #407a35;
                margin-bottom: 10px;
                font-size: 24px;
            }
            
            .header p {
                margin: 5px 0;
                color: #666;
            }
            
            .filters {
                background-color: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                margin-bottom: 20px;
            }
            
            .filters h3 {
                margin-top: 0;
                color: #407a35;
            }
            
            .stats {
                display: flex;
                justify-content: space-around;
                margin-bottom: 30px;
                text-align: center;
            }
            
            .stat-item {
                background-color: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                border-left: 4px solid #407a35;
            }
            
            .stat-number {
                font-size: 24px;
                font-weight: bold;
                color: #407a35;
            }
            
            .stat-label {
                font-size: 12px;
                color: #666;
            }
            
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            
            th, td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
                font-size: 11px;
            }
            
            th {
                background-color: #407a35;
                color: white;
                font-weight: bold;
            }
            
            tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            
            .status-confirmado {
                background-color: #d4edda;
                color: #155724;
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 10px;
            }
            
            .status-cancelado {
                background-color: #f8d7da;
                color: #721c24;
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 10px;
            }
            
            .footer {
                margin-top: 30px;
                text-align: center;
                font-size: 10px;
                color: #666;
                border-top: 1px solid #ddd;
                padding-top: 15px;
            }
            
            /* NOVO: Destaque para relatório de data específica */
            .date-highlight {
                background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
                border: 2px solid #ffc107;
                padding: 15px;
                border-radius: 10px;
                margin-bottom: 20px;
                text-align: center;
            }
            
            .date-highlight h2 {
                color: #856404;
                margin-bottom: 10px;
                font-size: 20px;
            }
            
            @media print {
                body { margin: 0; }
                .no-print { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1><?php echo $titulo; ?></h1>
            <p><strong>Data de Geração:</strong> <?php echo $data_geracao; ?></p>
            <p><strong>Gerado por:</strong> <?php echo htmlspecialchars($usuario_gerador); ?> (<?php echo ucfirst($_SESSION['tipo_usuario']); ?>)</p>
        </div>
        
        <?php if ($filtro_data_especifica): ?>
        <div class="date-highlight">
            <h2>📅 Relatório do Dia <?php echo date('d/m/Y', strtotime($filtro_data_especifica)); ?></h2>
            <p>Este relatório contém apenas os agendamentos do dia selecionado</p>
        </div>
        <?php endif; ?>
        
        <?php if ($filtro_data_inicio || $filtro_data_fim || $filtro_status): ?>
        <div class="filters">
            <h3>Filtros Aplicados:</h3>
            <?php if ($filtro_data_especifica): ?>
                <p><strong>Data Específica:</strong> <?php echo date('d/m/Y', strtotime($filtro_data_especifica)); ?></p>
            <?php else: ?>
                <?php if ($filtro_data_inicio): ?>
                    <p><strong>Data Início:</strong> <?php echo date('d/m/Y', strtotime($filtro_data_inicio)); ?></p>
                <?php endif; ?>
                <?php if ($filtro_data_fim): ?>
                    <p><strong>Data Fim:</strong> <?php echo date('d/m/Y', strtotime($filtro_data_fim)); ?></p>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($filtro_status): ?>
                <p><strong>Status:</strong> <?php echo ucfirst($filtro_status); ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="stats">
            <div class="stat-item">
                <div class="stat-number"><?php echo count($agendamentos); ?></div>
                <div class="stat-label">Total de Agendamentos</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['status'] === 'confirmado')); ?></div>
                <div class="stat-label">Confirmados</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['status'] === 'cancelado')); ?></div>
                <div class="stat-label">Cancelados</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['usuario_id'] !== null)); ?></div>
                <div class="stat-label">Usuários Logados</div>
            </div>
        </div>
        
        <?php if (count($agendamentos) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>CPF</th>
                        <th>Data</th>
                        <th>Horário</th>
                        <th>Status</th>
                        <th>Tipo</th>
                        <th>Criado em</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($agendamentos as $agendamento): ?>
                    <tr>
                        <td><?php echo $agendamento['id']; ?></td>
                        <td><?php echo htmlspecialchars($agendamento['nome']); ?></td>
                        <td><?php echo htmlspecialchars($agendamento['email']); ?></td>
                        <td><?php echo htmlspecialchars($agendamento['cpf']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($agendamento['data_agendamento'])); ?></td>
                        <td><?php echo date('H:i', strtotime($agendamento['hora_agendamento'])); ?></td>
                        <td>
                            <span class="status-<?php echo $agendamento['status']; ?>">
                                <?php echo ucfirst($agendamento['status']); ?>
                            </span>
                        </td>
                        <td><?php echo $agendamento['usuario_id'] ? 'Usuário' : 'Anônimo'; ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($agendamento['data_criacao'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; padding: 40px; color: #666;">
                Nenhum agendamento encontrado com os filtros aplicados.
            </p>
        <?php endif; ?>
        
        <div class="footer">
            <p>Relatório gerado pelo Sistema de Agendamento do Biotério FSA</p>
            <p>Este documento contém informações confidenciais e deve ser tratado com sigilo.</p>
        </div>
        
        <div class="no-print" style="text-align: center; margin-top: 20px;">
            <button onclick="window.print()" style="padding: 10px 20px; background-color: #407a35; color: white; border: none; border-radius: 5px; cursor: pointer;">
                Imprimir / Salvar como PDF
            </button>
            <button onclick="window.close()" style="padding: 10px 20px; background-color: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
                Fechar
            </button>
        </div>
    </body>
    </html>
    <?php
    exit();
}
?>