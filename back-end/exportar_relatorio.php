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
    
    // CORREÇÃO: Se é operador, excluir agendamentos pendentes automaticamente
    if ($_SESSION['tipo_usuario'] === 'operador') {
        $sql .= " AND a.status != 'pendente'";
    }
    
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
    
    // CORREÇÃO: Se operador tentar filtrar por pendente, ignorar o filtro
    if ($filtro_status && !($_SESSION['tipo_usuario'] === 'operador' && $filtro_status === 'pendente')) {
        $sql .= " AND a.status = ?";
        $params[] = $filtro_status;
    }
    
    $sql .= " ORDER BY a.data_agendamento DESC, a.hora_agendamento ASC";
    
    $stmt = $conexao->prepare($sql);
    $stmt->execute($params);
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // NOVO: Determinar status de exibição (incluindo concluído)
    $hoje = date('Y-m-d');
    foreach ($agendamentos as &$agendamento) {
        if ($agendamento['status'] === 'confirmado' && $agendamento['data_agendamento'] < $hoje) {
            $agendamento['status_display'] = 'concluido';
        } else {
            $agendamento['status_display'] = $agendamento['status'];
        }
    }
    
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
    
    // Cabeçalhos ATUALIZADOS
    fputcsv($output, [
        'ID',
        'Nome',
        'Email', 
        'CPF/CNPJ',
        'Data do Agendamento',
        'Horário',
        'Quantidade de Pessoas',
        'Status',
        'Tipo de Usuário',
        'Data de Criação',
        'Data de Cancelamento'
    ], ';');
    
    // Dados
    foreach ($agendamentos as $agendamento) {
        // NOVO: Determinar tipo de usuário
        $tipoUsuario = '';
        if ($agendamento['tipo_agendamento'] === 'empresa') {
            $tipoUsuario = 'Empresa';
        } elseif ($agendamento['usuario_id']) {
            $tipoUsuario = 'Usuário Logado';
        } else {
            $tipoUsuario = 'Anônimo';
        }
        
        // NOVO: Status para exibição (incluindo concluído)
        $statusExibicao = '';
        if ($agendamento['status_display'] === 'concluido') {
            $statusExibicao = 'Concluído';
        } else {
            $statusExibicao = ucfirst($agendamento['status']);
        }
        
        fputcsv($output, [
            $agendamento['id'],
            $agendamento['nome'],
            $agendamento['email'],
            $agendamento['cpf'],
            date('d/m/Y', strtotime($agendamento['data_agendamento'])),
            date('H:i', strtotime($agendamento['hora_agendamento'])),
            $agendamento['quantidade_pessoas'] ?? 1,
            $statusExibicao,
            $tipoUsuario,
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
    $tipo_usuario = ucfirst($_SESSION['tipo_usuario']);
    
    // NOVA FUNCIONALIDADE: Título específico para data específica
    if ($filtro_data_especifica) {
        $data_formatada = date('d/m/Y', strtotime($filtro_data_especifica));
        $titulo = 'Agendamentos do dia ' . $data_formatada . ' - Biotério FSA';
    }
    
    // CORREÇÃO: Adicionar nota se for operador
    $nota_operador = '';
    if ($_SESSION['tipo_usuario'] === 'operador') {
        $nota_operador = '<div class="operator-note">
            <p><strong>Nota:</strong> Como operador, este relatório exclui agendamentos pendentes. Agendamentos pendentes são visíveis apenas para administradores.</p>
        </div>';
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
            
            .operator-note {
                background-color: #e3f2fd;
                border: 2px solid #2196f3;
                border-radius: 5px;
                padding: 15px;
                margin-bottom: 20px;
                text-align: center;
            }
            
            .operator-note p {
                margin: 0;
                color: #1976d2;
                font-weight: bold;
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
            
            .status-negado {
                background-color: #f8d7da;
                color: #721c24;
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 10px;
            }
            
            /* NOVO: Status concluído */
            .status-concluido {
                background-color: #e9ecef;
                color: #495057;
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
            
            /* NOVO: Destaque para empresas */
            .empresa-row {
                background-color: #fff3cd !important;
                border-left: 4px solid #ffc107;
            }
            
            .tipo-empresa {
                background-color: #ffc107;
                color: #856404;
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 10px;
                font-weight: bold;
            }
            
            .tipo-usuario {
                background-color: #e3f2fd;
                color: #1976d2;
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 10px;
                font-weight: bold;
            }
            
            .tipo-anonimo {
                background-color: #f3e5f5;
                color: #7b1fa2;
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 10px;
                font-weight: bold;
            }
            
            /* NOVO: Destaque para quantidade de pessoas */
            .qtd-pessoas {
                background-color: #e8f5e8;
                color: #2e7d32;
                padding: 4px 8px;
                border-radius: 4px;
                font-weight: bold;
                display: inline-block;
                min-width: 25px;
                text-align: center;
            }
            
            .qtd-multiplas {
                background-color: #fff3cd;
                color: #856404;
                border: 2px solid #ffc107;
            }
            
            /* NOVO: Destaque para agendamentos concluídos */
            .concluido-row {
                background-color: #f8f9fa !important;
                opacity: 0.85;
                border-left: 4px solid #6c757d;
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
            <p><strong>Gerado por:</strong> <?php echo htmlspecialchars($usuario_gerador); ?> (<?php echo $tipo_usuario; ?>)</p>
        </div>
        
        <?php echo $nota_operador; ?>
        
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
            <?php if ($filtro_status && !($_SESSION['tipo_usuario'] === 'operador' && $filtro_status === 'pendente')): ?>
                <p><strong>Status:</strong> <?php echo ucfirst($filtro_status); ?></p>
            <?php endif; ?>
            <?php if ($_SESSION['tipo_usuario'] === 'operador'): ?>
                <p><strong>Política de Acesso:</strong> Agendamentos pendentes excluídos (acesso apenas para administradores)</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="stats">
            <div class="stat-item">
                <div class="stat-number"><?php echo count($agendamentos); ?></div>
                <div class="stat-label">Total de Agendamentos</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo array_sum(array_map(fn($a) => $a['quantidade_pessoas'] ?? 1, $agendamentos)); ?></div>
                <div class="stat-label">Total de Pessoas</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['status'] === 'confirmado' && $a['data_agendamento'] >= date('Y-m-d'))); ?></div>
                <div class="stat-label">Confirmados</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => isset($a['status_display']) && $a['status_display'] === 'concluido')); ?></div>
                <div class="stat-label">Concluídos</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['status'] === 'cancelado')); ?></div>
                <div class="stat-label">Cancelados</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['status'] === 'negado')); ?></div>
                <div class="stat-label">Negados</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['tipo_agendamento'] === 'empresa')); ?></div>
                <div class="stat-label">Empresas</div>
            </div>
        </div>
        
        <?php if (count($agendamentos) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>CPF/CNPJ</th>
                        <th>Data</th>
                        <th>Horário</th>
                        <th>Qtd. Pessoas</th>
                        <th>Status</th>
                        <th>Tipo</th>
                        <th>Criado em</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($agendamentos as $agendamento): 
                        $isEmpresa = $agendamento['tipo_agendamento'] === 'empresa';
                        $isConcluido = isset($agendamento['status_display']) && $agendamento['status_display'] === 'concluido';
                        
                        $rowClass = '';
                        if ($isConcluido) {
                            $rowClass = 'concluido-row';
                        } elseif ($isEmpresa) {
                            $rowClass = 'empresa-row';
                        }
                        
                        // Determinar tipo de usuário
                        $tipoUsuario = '';
                        $tipoClass = '';
                        if ($isEmpresa) {
                            $tipoUsuario = 'Empresa';
                            $tipoClass = 'tipo-empresa';
                        } elseif ($agendamento['usuario_id']) {
                            $tipoUsuario = 'Usuário Logado';
                            $tipoClass = 'tipo-usuario';
                        } else {
                            $tipoUsuario = 'Anônimo';
                            $tipoClass = 'tipo-anonimo';
                        }
                        
                        // Status para exibição
                        $statusExibicao = '';
                        $statusClass = '';
                        if ($isConcluido) {
                            $statusExibicao = 'Concluído';
                            $statusClass = 'status-concluido';
                        } else {
                            $statusExibicao = ucfirst($agendamento['status']);
                            $statusClass = 'status-' . $agendamento['status'];
                        }
                    ?>
                    <tr class="<?php echo $rowClass; ?>">
                        <td><?php echo $agendamento['id']; ?></td>
                        <td><?php echo htmlspecialchars($agendamento['nome']); ?></td>
                        <td><?php echo htmlspecialchars($agendamento['email']); ?></td>
                        <td><?php echo htmlspecialchars($agendamento['cpf']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($agendamento['data_agendamento'])); ?></td>
                        <td><?php echo date('H:i', strtotime($agendamento['hora_agendamento'])); ?></td>
                        <td style="text-align: center;">
                            <?php 
                            $qtdPessoas = $agendamento['quantidade_pessoas'] ?? 1;
                            $qtdClass = $qtdPessoas > 1 ? 'qtd-pessoas qtd-multiplas' : 'qtd-pessoas';
                            ?>
                            <span class="<?php echo $qtdClass; ?>">
                                <?php echo $qtdPessoas; ?>
                            </span>
                        </td>
                        <td>
                            <span class="<?php echo $statusClass; ?>">
                                <?php echo $statusExibicao; ?>
                            </span>
                        </td>
                        <td>
                            <span class="<?php echo $tipoClass; ?>">
                                <?php echo $tipoUsuario; ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($agendamento['data_criacao'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; padding: 40px; color: #666;">
                Nenhum agendamento encontrado com os filtros aplicados.
                <?php if ($_SESSION['tipo_usuario'] === 'operador'): ?>
                <br><small>Lembre-se: agendamentos pendentes não são exibidos para operadores.</small>
                <?php endif; ?>
            </p>
        <?php endif; ?>
        
        <div class="footer">
            <p>Relatório gerado pelo Sistema de Agendamento do Biotério FSA</p>
            <p>Este documento contém informações confidenciais e deve ser tratado com sigilo.</p>
            <?php if ($_SESSION['tipo_usuario'] === 'operador'): ?>
            <p><strong>Política de Acesso:</strong> Relatório gerado por operador - agendamentos pendentes não incluídos.</p>
            <?php endif; ?>
            <?php if (count(array_filter($agendamentos, fn($a) => isset($a['status_display']) && $a['status_display'] === 'concluido')) > 0): ?>
            <p><strong>Nota:</strong> Este relatório inclui agendamentos concluídos (confirmados com data passada).</p>
            <?php endif; ?>
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