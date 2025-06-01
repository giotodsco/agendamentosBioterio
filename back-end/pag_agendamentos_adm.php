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
                
                // NOVO: Enviar email de aprovação para empresas
                $resultadoEmail = enviarEmailAgendamentoAprovado($agendamento_id);
                if (!$resultadoEmail['sucesso']) {
                    error_log("Falha ao enviar email de aprovação para agendamento ID: $agendamento_id");
                }
                
                $mensagem_sucesso = "Agendamento confirmado com sucesso!";
                
            } elseif ($acao === 'negar') {
                // Capturar motivo da negação se fornecido
                $motivo = $_POST['motivo'] ?? '';
                
                $stmt = $conexao->prepare("UPDATE agendamentos SET status = 'negado' WHERE id = :id");
                $stmt->bindParam(':id', $agendamento_id);
                $stmt->execute();
                
                // NOVO: Enviar email de negação para empresas
                $resultadoEmail = enviarEmailAgendamentoNegado($agendamento_id, $motivo);
                if (!$resultadoEmail['sucesso']) {
                    error_log("Falha ao enviar email de negação para agendamento ID: $agendamento_id");
                }
                
                $mensagem_sucesso = "Agendamento negado com sucesso!";
                
            } elseif ($acao === 'concluir') {
                $stmt = $conexao->prepare("UPDATE agendamentos SET status = 'concluido' WHERE id = :id");
                $stmt->bindParam(':id', $agendamento_id);
                $stmt->execute();
                
                // NOVO: Enviar email de conclusão
                $resultadoEmail = enviarEmailAgendamentoConcluido($agendamento_id);
                if (!$resultadoEmail['sucesso']) {
                    error_log("Falha ao enviar email de conclusão para agendamento ID: $agendamento_id");
                }
                
                $mensagem_sucesso = "Agendamento concluído com sucesso!";
                
            } elseif ($acao === 'excluir') {
                // CORREÇÃO: Buscar dados do agendamento antes de excluir
                $stmt = $conexao->prepare("SELECT data_agendamento FROM agendamentos WHERE id = :id");
                $stmt->bindParam(':id', $agendamento_id);
                $stmt->execute();
                $agendamento_dados = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($agendamento_dados) {
                    $data_agendamento = $agendamento_dados['data_agendamento'];
                    
                    // Excluir o agendamento
                    $stmt = $conexao->prepare("DELETE FROM agendamentos WHERE id = :id");
                    $stmt->bindParam(':id', $agendamento_id);
                    $stmt->execute();
                    
                    if ($stmt->rowCount() > 0) {
                        // Verificar se ainda existem agendamentos nesta data
                        $stmt = $conexao->prepare("SELECT COUNT(*) as total FROM agendamentos WHERE data_agendamento = :data");
                        $stmt->bindParam(':data', $data_agendamento);
                        $stmt->execute();
                        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        // Retornar resposta JSON para AJAX
                        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                            header('Content-Type: application/json');
                            echo json_encode([
                                'success' => true,
                                'message' => 'Agendamento excluído com sucesso!',
                                'remaining_count' => (int)$resultado['total']
                            ]);
                            exit();
                        }
                        
                        $mensagem_sucesso = "Agendamento excluído permanentemente com sucesso!";
                        if ($resultado['total'] == 0) {
                            $mensagem_sucesso .= " A seção do dia foi removida pois não há mais agendamentos nesta data.";
                        }
                    } else {
                        $mensagem_erro = "Erro ao excluir agendamento.";
                    }
                } else {
                    $mensagem_erro = "Agendamento não encontrado.";
                }
                
            } elseif ($acao === 'cancelar') {
                $stmt = $conexao->prepare("UPDATE agendamentos SET status = 'cancelado', data_cancelamento = NOW() WHERE id = :id");
                $stmt->bindParam(':id', $agendamento_id);
                $stmt->execute();
                
                // NOVO: Enviar email de cancelamento
                $resultadoEmail = enviarEmailAgendamentoCancelado($agendamento_id);
                if (!$resultadoEmail['sucesso']) {
                    error_log("Falha ao enviar email de cancelamento para agendamento ID: $agendamento_id");
                }
                
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
    
    // Filtros para busca de agendamentos
    $where_conditions = [];
    $params = [];

    // Filtro por data
    if (!empty($filtro_data_inicio)) {
        $where_conditions[] = "a.data_agendamento >= :data_inicio";
        $params[':data_inicio'] = $filtro_data_inicio;
    }
    
    if (!empty($filtro_data_fim)) {
        $where_conditions[] = "a.data_agendamento <= :data_fim";
        $params[':data_fim'] = $filtro_data_fim;
    }

    // CORRIGIDO: Filtro por status - COM LÓGICA DE DATA
    if (!empty($filtro_status)) {
        $hoje = date('Y-m-d');
        
        if ($filtro_status === 'confirmado') {
            // Confirmado: apenas agendamentos confirmados que NÃO são do passado
            $where_conditions[] = "a.status = 'confirmado' AND a.data_agendamento >= :hoje_confirmado";
            $params[':hoje_confirmado'] = $hoje;
        } elseif ($filtro_status === 'concluido') {
            // Concluído: agendamentos 'concluido' OU 'confirmado' do passado
            $where_conditions[] = "(a.status = 'concluido' OR (a.status = 'confirmado' AND a.data_agendamento < :hoje_concluido))";
            $params[':hoje_concluido'] = $hoje;
        } else {
            // Outros status: busca normal
            $where_conditions[] = "a.status = :status";
            $params[':status'] = $filtro_status;
        }
    }

    // Montar a query
    $sql = "SELECT a.*, u.nome as usuario_nome, u.email as usuario_email,
                   e.nome_instituicao as empresa_nome
            FROM agendamentos a 
            LEFT JOIN usuarios u ON a.usuario_id = u.id
            LEFT JOIN empresas e ON a.empresa_id = e.id";
    
    if (!empty($where_conditions)) {
        $sql .= " WHERE " . implode(" AND ", $where_conditions);
    }
    
    $sql .= " ORDER BY a.data_agendamento, a.hora_agendamento";
    
    $stmt = $conexao->prepare($sql);
    $stmt->execute($params);
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // CORRIGIDO: Processar agendamentos e determinar status de exibição
    $hoje = date('Y-m-d');
    foreach ($agendamentos as &$agendamento) {
        // Determinar status de exibição
        if ($agendamento['status'] === 'confirmado' && $agendamento['data_agendamento'] < $hoje) {
            $agendamento['status_display'] = 'concluido';
        } else {
            $agendamento['status_display'] = $agendamento['status'];
        }
    }
    unset($agendamento); // Limpar referência
    
    // Organizar agendamentos por data
    $agendamentos_por_data = [];
    foreach ($agendamentos as $agendamento) {
        $data = $agendamento['data_agendamento'];
        if (!isset($agendamentos_por_data[$data])) {
            $agendamentos_por_data[$data] = [];
        }
        $agendamentos_por_data[$data][] = $agendamento;
    }
    
    // MODIFICAÇÃO: Organizar datas por proximidade (hoje primeiro, futuro próximo, depois passado)
    $datas_hoje = [];
    $datas_futuras = [];
    $datas_passadas = [];
    
    foreach ($agendamentos_por_data as $data => $agendamentos_do_dia) {
        if ($data === $hoje) {
            $datas_hoje[$data] = $agendamentos_do_dia;
        } elseif ($data > $hoje) {
            $datas_futuras[$data] = $agendamentos_do_dia;
        } else {
            $datas_passadas[$data] = $agendamentos_do_dia;
        }
    }
    
    // Ordenar futuras (mais próximas primeiro) e passadas (mais recentes primeiro)
    ksort($datas_futuras);
    krsort($datas_passadas);
    
    // Reorganizar: hoje + futuras + passadas
    $agendamentos_por_data = array_merge($datas_hoje, $datas_futuras, $datas_passadas);
    
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
    <link rel="stylesheet" href="back-end-style\style_pag_agendamentos_adm.css">
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
            <a href="pag_usuarios.php" class="btn-header config">
                <i class="fa-solid fa-users-cog"></i>
                Usuários
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
            <p>Como administrador, você tem acesso completo: <strong>confirmar</strong>, <strong>negar</strong>, <strong>cancelar</strong>, <strong>concluir</strong> e <strong style="color: #dc3545;">EXCLUIR PERMANENTEMENTE</strong> qualquer agendamento. Agendamentos pendentes em <strong style="color: #ffc107;">AMARELO</strong> requerem sua aprovação.</p>
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
                            <option value="confirmado" <?php echo $filtro_status === 'confirmado' ? 'selected' : ''; ?>>Confirmado (Ativo)</option>
                            <option value="concluido" <?php echo $filtro_status === 'concluido' ? 'selected' : ''; ?>>Concluído (Passado)</option>
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
                <div class="stat-number"><?php 
                    $hoje = date('Y-m-d');
                    echo count(array_filter($agendamentos, fn($a) => $a['status'] === 'confirmado' && $a['data_agendamento'] >= $hoje)); 
                ?></div>
                <div class="stat-label"><i class="fa-solid fa-check-circle"></i> Confirmados (Ativos)</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php 
                    echo count(array_filter($agendamentos, fn($a) => 
                        (isset($a['status_display']) && $a['status_display'] === 'concluido') ||
                        $a['status'] === 'concluido' || 
                        ($a['status'] === 'confirmado' && $a['data_agendamento'] < $hoje)
                    )); 
                ?></div>
                <div class="stat-label"><i class="fa-solid fa-check-double"></i> Concluídos (Passado)</div>
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
                    <div class="day-section <?php echo $day_class; ?>" id="day-section-<?php echo $data; ?>">
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
                                
                                <!-- BOTÃO EXCLUIR TODOS OS AGENDAMENTOS DO DIA -->
                                <div style="margin-top: 15px; padding-top: 15px; border-top: 2px solid #dc3545; text-align: center;">
                                    <button type="button" class="btn-admin-excluir-destaque" 
                                            onclick="excluirTodosAgendamentosDia('<?php echo $data; ?>')">
                                        <i class="fa-solid fa-trash-alt"></i> EXCLUIR TODOS AGENDAMENTOS DESTE DIA
                                    </button>
                                </div>
                            </div>
                            <div class="day-stats">
                                <div class="day-count">
                                    <i class="fa-solid fa-users"></i> 
                                    <span id="count-agendamentos-<?php echo $data; ?>"><?php echo count($agendamentos_do_dia); ?></span> 
                                    agendamento<?php echo count($agendamentos_do_dia) != 1 ? 's' : ''; ?>
                                </div>
                                <div class="day-count">
                                    <i class="fa-solid fa-clock"></i> <?php echo count(array_filter($agendamentos_do_dia, fn($a) => $a['status'] === 'pendente')); ?> pendente<?php echo count(array_filter($agendamentos_do_dia, fn($a) => $a['status'] === 'pendente')) != 1 ? 's' : ''; ?>
                                </div>
                                <div class="day-count">
                                    <i class="fa-solid fa-building"></i> <?php echo count(array_filter($agendamentos_do_dia, fn($a) => $a['tipo_agendamento'] === 'empresa')); ?> empresa<?php echo count(array_filter($agendamentos_do_dia, fn($a) => $a['tipo_agendamento'] === 'empresa')) != 1 ? 's' : ''; ?>
                                </div>
                                <div class="day-count">
                                    <i class="fa-solid fa-flag-checkered"></i> 
                                    <?php echo count(array_filter($agendamentos_do_dia, fn($a) => 
                                        (isset($a['status_display']) && $a['status_display'] === 'concluido') ||
                                        $a['status'] === 'concluido'
                                    )); ?> 
                                    concluído<?php echo count(array_filter($agendamentos_do_dia, fn($a) => 
                                        (isset($a['status_display']) && $a['status_display'] === 'concluido') ||
                                        $a['status'] === 'concluido'
                                    )) != 1 ? 's' : ''; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="appointments-grid" id="appointments-grid-<?php echo $data; ?>">
                            <?php foreach ($agendamentos_do_dia as $agendamento): 
                                $isEmpresa = $agendamento['tipo_agendamento'] === 'empresa';
                                $isPendente = $agendamento['status'] === 'pendente';
                                $isConcluido = (isset($agendamento['status_display']) && $agendamento['status_display'] === 'concluido') || 
                                               $agendamento['status'] === 'concluido';
                                
                                $cardClass = '';
                                if ($isPendente) {
                                    $cardClass = 'pendente-card';
                                } elseif ($isConcluido) {
                                    $cardClass = 'concluido-card';
                                } elseif ($isEmpresa) {
                                    $cardClass = 'empresa-card';
                                }
                            ?>
                            <div class="appointment-card <?php echo $cardClass; ?>" id="card-<?php echo $agendamento['id']; ?>">
                                <div class="appointment-header">
                                    <div class="appointment-id <?php echo $isEmpresa ? 'empresa' : ''; ?> <?php echo $isPendente ? 'pendente' : ''; ?> <?php echo $isConcluido ? 'concluido' : ''; ?>">
                                        <i class="fa-solid fa-hashtag"></i> <?php echo $agendamento['id']; ?>
                                        <?php if ($isPendente): ?>
                                            <span style="color: #e67e22; font-weight: bold;">REQUER AÇÃO</span>
                                        <?php elseif ($isConcluido): ?>
                                            <span style="color: #6c757d; font-weight: bold;">CONCLUÍDO</span>
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
                                        <span class="status status-<?php echo isset($agendamento['status_display']) ? $agendamento['status_display'] : $agendamento['status']; ?>">
                                            <?php if (isset($agendamento['status_display']) && $agendamento['status_display'] === 'concluido'): ?>
                                                <i class="fa-solid fa-check-double"></i> CONCLUÍDO
                                            <?php elseif ($agendamento['status'] === 'confirmado'): ?>
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
                                    <!-- BOTÃO EXCLUIR INDIVIDUAL - SEMPRE DISPONÍVEL -->
                                    <button type="button" class="btn-admin excluir"
                                            onclick="excluirAgendamentoIndividual(<?php echo $agendamento['id']; ?>, '<?php echo $data; ?>')">
                                        <i class="fa-solid fa-trash"></i> EXCLUIR
                                    </button>

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
                                    
                                    <?php if ($agendamento['status'] === 'confirmado' && !$isConcluido): ?>
                                        <form method="POST" style="display: inline;" action="">
                                            <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                            <input type="hidden" name="acao" value="concluir">
                                            <button type="submit" class="btn-admin concluir">
                                                <i class="fa-solid fa-check-double"></i> Concluir
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;" action="" id="form-cancelar-<?php echo $agendamento['id']; ?>">
                                            <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                            <input type="hidden" name="acao" value="cancelar">
                                            <button type="button" class="btn-admin cancelar"
                                                    onclick="showCustomConfirm('Tem certeza que deseja cancelar este agendamento?', () => { document.getElementById('form-cancelar-<?php echo $agendamento['id']; ?>').submit(); })">
                                                <i class="fa-solid fa-ban"></i> Cancelar
                                            </button>
                                        </form>
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
                    <small>Não há agendamentos no período selecionado ou com os filtros aplicados</small>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="back-end-javascript\js_pag_agendamentos_adm.js"></script>
</body>
</html>