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
            
            // Verificar se o agendamento está concluído (confirmado e data passou)
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

// CORRIGIDO: Buscar agendamentos com lógica melhorada
try {
    $conexao = conectarBanco();
    
    // Data de hoje para referência
    $hoje = date('Y-m-d');
    
    // Filtros
    $filtro_data_inicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-7 days'));
    $filtro_data_fim = $_GET['data_fim'] ?? date('Y-m-d', strtotime('+30 days'));
    $filtro_status = $_GET['status'] ?? '';
    
    // CORRIGIDO: Para operadores, sempre buscar confirmados
    $filtros = [
        'data_inicio' => $filtro_data_inicio,
        'data_fim' => $filtro_data_fim,
    ];
    
    // Para operadores, sempre buscar apenas confirmados
    if ($_SESSION['tipo_usuario'] === 'operador') {
        $filtros['status'] = 'confirmado';
    } else {
        // Para admins, usar filtro selecionado
        if (!empty($filtro_status)) {
            if ($filtro_status === 'concluido') {
                $filtros['status_especial'] = 'concluido';
            } else {
                $filtros['status'] = $filtro_status;
            }
        }
    }
    
    $agendamentos = buscarAgendamentosCompletos($filtros);
    
    // CORRIGIDO: Processar agendamentos e determinar status de exibição
    $agendamentos_processados = [];
    
    foreach ($agendamentos as $agendamento) {
        // Determinar se está concluído (confirmado e data passou)
        if ($agendamento['status'] === 'confirmado' && $agendamento['data_agendamento'] < $hoje) {
            $agendamento['status_display'] = 'concluido';
        } else {
            $agendamento['status_display'] = $agendamento['status'];
        }
        
        // Para operadores: filtrar baseado no status_display
        if ($_SESSION['tipo_usuario'] === 'operador') {
            // Aplicar filtro de status se especificado
            if (!empty($filtro_status)) {
                if ($filtro_status === 'concluido') {
                    // Mostrar apenas concluídos (confirmados com data passada)
                    if ($agendamento['status_display'] === 'concluido') {
                        $agendamentos_processados[] = $agendamento;
                    }
                } else if ($filtro_status === 'confirmado') {
                    // Mostrar apenas confirmados futuros/hoje
                    if ($agendamento['status'] === 'confirmado' && $agendamento['data_agendamento'] >= $hoje) {
                        $agendamentos_processados[] = $agendamento;
                    }
                }
            } else {
                // Sem filtro específico - mostrar todos os confirmados (incluindo concluídos)
                $agendamentos_processados[] = $agendamento;
            }
        } else {
            // Para admins: lógica original
            if (!empty($filtro_status)) {
                if ($filtro_status === 'concluido') {
                    if ($agendamento['status_display'] === 'concluido') {
                        $agendamentos_processados[] = $agendamento;
                    }
                } else {
                    if ($agendamento['status'] === $filtro_status && $agendamento['status_display'] !== 'concluido') {
                        $agendamentos_processados[] = $agendamento;
                    }
                }
            } else {
                $agendamentos_processados[] = $agendamento;
            }
        }
    }
    
    $agendamentos = $agendamentos_processados;
    
    // Separar agendamentos atuais e concluídos para ordenação
    $agendamentos_atuais = [];
    $agendamentos_concluidos = [];
    
    foreach ($agendamentos as $agendamento) {
        if (isset($agendamento['status_display']) && $agendamento['status_display'] === 'concluido') {
            $agendamentos_concluidos[] = $agendamento;
        } else {
            $agendamentos_atuais[] = $agendamento;
        }
    }
    
    // Reorganizar: atuais primeiro, concluídos por último (a menos que esteja filtrando só concluídos)
    if ($filtro_status === 'concluido') {
        $agendamentos = $agendamentos_concluidos;
    } else {
        $agendamentos = array_merge($agendamentos_atuais, $agendamentos_concluidos);
    }
    
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
    
    // Reorganizar: futuras primeiro, passadas depois (exceto se filtrando só concluídos)
    if ($filtro_status === 'concluido') {
        // Para concluídos, mostrar mais recentes primeiro
        $agendamentos_por_data = array_merge($datas_passadas, $datas_futuras);
    } else {
        $agendamentos_por_data = array_merge($datas_futuras, $datas_passadas);
    }
    
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
    <link rel="stylesheet" href="back-end-style\style_pag_agendamentos_operador.css">
    <style>
        
    </style>
</head>
<body>
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
            <p>Visualize agendamentos confirmados e concluídos do sistema</p>
        </div>

        <div class="operador-info">
            <h4><i class="fa-solid fa-shield-alt"></i> Acesso Restrito do Operador</h4>
            <p>Você tem acesso apenas a <strong>agendamentos confirmados</strong> (futuros/hoje) e <strong>concluídos</strong> (passados). Agendamentos pendentes, cancelados e negados são visíveis apenas para administradores.</p>
        </div>

        <?php if (count(array_filter($agendamentos, fn($a) => isset($a['status_display']) && $a['status_display'] === 'concluido')) > 0 && $filtro_status !== 'concluido'): ?>
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
                            <?php if ($_SESSION['tipo_usuario'] === 'operador'): ?>
                                <option value="">Todos Disponíveis</option>
                                <option value="confirmado" <?php echo $filtro_status === 'confirmado' ? 'selected' : ''; ?>>Confirmado (Futuro/Hoje)</option>
                                <option value="concluido" <?php echo $filtro_status === 'concluido' ? 'selected' : ''; ?>>Concluído (Passado)</option>
                            <?php else: ?>
                                <option value="">Todos os Status</option>
                                <option value="pendente" <?php echo $filtro_status === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                                <option value="confirmado" <?php echo $filtro_status === 'confirmado' ? 'selected' : ''; ?>>Confirmado</option>
                                <option value="cancelado" <?php echo $filtro_status === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                <option value="negado" <?php echo $filtro_status === 'negado' ? 'selected' : ''; ?>>Negado</option>
                                <option value="concluido" <?php echo $filtro_status === 'concluido' ? 'selected' : ''; ?>>Concluído</option>
                            <?php endif; ?>
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
                <div class="stat-label"><i class="fa-solid fa-calendar-check"></i> Total Visível</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => isset($a['status_display']) && $a['status_display'] === 'concluido')); ?></div>
                <div class="stat-label"><i class="fa-solid fa-flag-checkered"></i> Concluídos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['tipo_agendamento'] === 'empresa')); ?></div>
                <div class="stat-label"><i class="fa-solid fa-building"></i> Empresas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo array_sum(array_map(fn($a) => $a['quantidade_pessoas'] ?? 1, $agendamentos)); ?></div>
                <div class="stat-label"><i class="fa-solid fa-users"></i> Total Pessoas</div>
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
                <?php if ($filtro_status === 'concluido'): ?>
                <button type="button" class="btn btn-warning" onclick="filtrarSemConcluidos()">
                    <i class="fa-solid fa-eye"></i> Ver Sem Concluídos
                </button>
                <?php endif; ?>
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
                                <?php else: ?>
                                <div class="day-count">
                                    <i class="fa-solid fa-check-circle"></i> <?php echo count(array_filter($agendamentos_do_dia, fn($a) => $a['status'] === 'confirmado')); ?> confirmado<?php echo count(array_filter($agendamentos_do_dia, fn($a) => $a['status'] === 'confirmado')) != 1 ? 's' : ''; ?>
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
                                
                                // Definir classe baseada no tipo de usuário e status
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
                                    <?php if ($isConcluido): ?>
                                    <p><i class="fa-solid fa-flag-checkered"></i> Concluído: <?php echo date('d/m/Y', strtotime($agendamento['data_agendamento'])); ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card-footer">
                                    <div>
                                        <span class="status status-<?php echo isset($agendamento['status_display']) ? $agendamento['status_display'] : $agendamento['status']; ?>">
                                            <?php if (isset($agendamento['status_display']) && $agendamento['status_display'] === 'concluido'): ?>
                                                <i class="fa-solid fa-flag-checkered"></i> CONCLUÍDO
                                            <?php else: ?>
                                                <i class="fa-solid fa-check-circle"></i> CONFIRMADO
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

                                        <?php if (isset($agendamento['status_display']) && $agendamento['status_display'] === 'concluido'): ?>
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
                    <small>
                        <?php if ($_SESSION['tipo_usuario'] === 'operador'): ?>
                            <?php if (!empty($filtro_status)): ?>
                                Nenhum agendamento "<?php echo ucfirst($filtro_status); ?>" foi encontrado no período selecionado.
                            <?php else: ?>
                                Nenhum agendamento confirmado ou concluído foi encontrado no período selecionado.
                            <?php endif; ?>
                            <br><em>Como operador, você só tem acesso a agendamentos confirmados e concluídos.</em>
                        <?php else: ?>
                            <?php if (!empty($filtro_status)): ?>
                                Nenhum agendamento com status "<?php echo ucfirst($filtro_status); ?>" foi encontrado no período selecionado.
                            <?php else: ?>
                                Tente ajustar os filtros para ver mais resultados ou aguarde novos agendamentos.
                            <?php endif; ?>
                        <?php endif; ?>
                    </small>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="back-end-javascript\js_pag_agendamentos_operador.js"></script>
</body>
</html>