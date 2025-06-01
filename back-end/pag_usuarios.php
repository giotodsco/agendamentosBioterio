<?php
session_start();
require_once 'functions.php';

// Verificar se o usuário está logado e tem permissão (admin ou operador)
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: pag_adm.php");
    exit();
}

if (!in_array($_SESSION['tipo_usuario'], ['admin', 'operador'])) {
    header("Location: ../front-end/pag_inicial.html");
    exit();
}

try {
    $conexao = conectarBanco();
    
    // Filtros
    $filtro_nome = $_GET['nome'] ?? '';
    $filtro_tipo = $_GET['tipo'] ?? ''; // 'usuario', 'empresa', ou ''
    $filtro_status = $_GET['status'] ?? ''; // 'ativo', 'inativo', ou ''
    
    // Buscar usuários comuns
    $sql_usuarios = "
        SELECT 
            u.id,
            u.nome,
            u.email,
            u.cpf,
            u.data_criacao,
            'usuario' as tipo_conta,
            1 as ativo,
            (SELECT COUNT(*) FROM agendamentos a WHERE a.usuario_id = u.id) as total_agendamentos,
            (SELECT COUNT(*) FROM agendamentos a WHERE a.usuario_id = u.id AND a.status = 'confirmado' AND a.data_agendamento >= CURDATE()) as agendamentos_confirmados,
            (SELECT COUNT(*) FROM agendamentos a WHERE a.usuario_id = u.id AND (a.status = 'concluido' OR (a.status = 'confirmado' AND a.data_agendamento < CURDATE()))) as agendamentos_concluidos,
            (SELECT COUNT(*) FROM agendamentos a WHERE a.usuario_id = u.id AND a.status = 'pendente') as agendamentos_pendentes,
            (SELECT COUNT(*) FROM agendamentos a WHERE a.usuario_id = u.id AND a.status = 'cancelado') as agendamentos_cancelados,
            (SELECT COUNT(*) FROM agendamentos a WHERE a.usuario_id = u.id AND a.status = 'negado') as agendamentos_negados,
            (SELECT MAX(a.data_criacao) FROM agendamentos a WHERE a.usuario_id = u.id) as ultimo_agendamento
        FROM usuarios u
        WHERE 1=1
    ";
    
    // Buscar empresas
    $sql_empresas = "
        SELECT 
            e.id,
            e.nome_instituicao as nome,
            e.email,
            e.cnpj as cpf,
            e.data_criacao,
            'empresa' as tipo_conta,
            e.ativo,
            (SELECT COUNT(*) FROM agendamentos a WHERE a.empresa_id = e.id) as total_agendamentos,
            (SELECT COUNT(*) FROM agendamentos a WHERE a.empresa_id = e.id AND a.status = 'confirmado' AND a.data_agendamento >= CURDATE()) as agendamentos_confirmados,
            (SELECT COUNT(*) FROM agendamentos a WHERE a.empresa_id = e.id AND (a.status = 'concluido' OR (a.status = 'confirmado' AND a.data_agendamento < CURDATE()))) as agendamentos_concluidos,
            (SELECT COUNT(*) FROM agendamentos a WHERE a.empresa_id = e.id AND a.status = 'pendente') as agendamentos_pendentes,
            (SELECT COUNT(*) FROM agendamentos a WHERE a.empresa_id = e.id AND a.status = 'cancelado') as agendamentos_cancelados,
            (SELECT COUNT(*) FROM agendamentos a WHERE a.empresa_id = e.id AND a.status = 'negado') as agendamentos_negados,
            (SELECT MAX(a.data_criacao) FROM agendamentos a WHERE a.empresa_id = e.id) as ultimo_agendamento
        FROM empresas e
        WHERE 1=1
    ";
    
    $params_usuarios = [];
    $params_empresas = [];
    
    // Aplicar filtros
    if (!empty($filtro_nome)) {
        $sql_usuarios .= " AND u.nome LIKE ?";
        $sql_empresas .= " AND e.nome_instituicao LIKE ?";
        $params_usuarios[] = "%$filtro_nome%";
        $params_empresas[] = "%$filtro_nome%";
    }
    
    if (!empty($filtro_status)) {
        if ($filtro_status === 'ativo') {
            $sql_empresas .= " AND e.ativo = 1";
        } elseif ($filtro_status === 'inativo') {
            $sql_empresas .= " AND e.ativo = 0";
        }
    }
    
    $usuarios = [];
    
    // Executar consultas baseado no filtro de tipo
    if (empty($filtro_tipo) || $filtro_tipo === 'usuario') {
        $stmt = $conexao->prepare($sql_usuarios . " ORDER BY u.data_criacao DESC");
        $stmt->execute($params_usuarios);
        $usuarios_comuns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $usuarios = array_merge($usuarios, $usuarios_comuns);
    }
    
    if (empty($filtro_tipo) || $filtro_tipo === 'empresa') {
        $stmt = $conexao->prepare($sql_empresas . " ORDER BY e.data_criacao DESC");
        $stmt->execute($params_empresas);
        $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $usuarios = array_merge($usuarios, $empresas);
    }
    
    // Ordenar por data de criação (mais recente primeiro)
    usort($usuarios, function($a, $b) {
        return strtotime($b['data_criacao']) - strtotime($a['data_criacao']);
    });
    
    // Estatísticas gerais
    $total_usuarios = count(array_filter($usuarios, fn($u) => $u['tipo_conta'] === 'usuario'));
    $total_empresas = count(array_filter($usuarios, fn($u) => $u['tipo_conta'] === 'empresa'));
    $empresas_ativas = count(array_filter($usuarios, fn($u) => $u['tipo_conta'] === 'empresa' && $u['ativo'] == 1));
    $usuarios_com_agendamentos = count(array_filter($usuarios, fn($u) => $u['total_agendamentos'] > 0));
    
} catch (PDOException $e) {
    $mensagem_erro = "Erro ao buscar usuários: " . $e->getMessage();
    $usuarios = [];
}

// Função para formatar CPF/CNPJ
function formatarDocumento($documento, $tipo) {
    if ($tipo === 'empresa') {
        // Formatar CNPJ
        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $documento);
    } else {
        // Formatar CPF
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $documento);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biotério - Usuários Cadastrados</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="back-end-style\style_pag_usuarios.css">
</head>
<body>
    <div class="header">
        <h1>
            <i class="fa-solid fa-users"></i>
            Usuários Cadastrados
        </h1>
        <div class="header-actions">
            <div class="user-info">
                <span><i class="fa-solid fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?> (<?php echo ucfirst($_SESSION['tipo_usuario']); ?>)</span>
            </div>
            <?php if ($_SESSION['tipo_usuario'] === 'admin'): ?>
            <a href="pag_agendamentos_adm.php" class="btn-header">
                <i class="fa-solid fa-calendar-check"></i>
                Agendamentos
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
            <h2><i class="fa-solid fa-address-book"></i> Gerenciamento de Usuários</h2>
            <p>Visualize todos os usuários cadastrados no sistema e suas estatísticas de agendamentos</p>
        </div>

        <?php if (isset($mensagem_erro)): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($mensagem_erro); ?>
            </div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_usuarios; ?></div>
                <div class="stat-label"><i class="fa-solid fa-user"></i> Pessoas Físicas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_empresas; ?></div>
                <div class="stat-label"><i class="fa-solid fa-building"></i> Empresas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $empresas_ativas; ?></div>
                <div class="stat-label"><i class="fa-solid fa-check-circle"></i> Empresas Ativas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $usuarios_com_agendamentos; ?></div>
                <div class="stat-label"><i class="fa-solid fa-calendar-check"></i> Com Agendamentos</div>
            </div>
        </div>

        <div class="filters">
            <h3><i class="fa-solid fa-filter"></i> Filtros de Pesquisa</h3>
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="nome"><i class="fa-solid fa-search"></i> Nome/Instituição:</label>
                        <input type="text" id="nome" name="nome" placeholder="Pesquisar por nome..." value="<?php echo htmlspecialchars($filtro_nome); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="tipo"><i class="fa-solid fa-filter"></i> Tipo de Conta:</label>
                        <select id="tipo" name="tipo">
                            <option value="">Todos os Tipos</option>
                            <option value="usuario" <?php echo $filtro_tipo === 'usuario' ? 'selected' : ''; ?>>Pessoa Física</option>
                            <option value="empresa" <?php echo $filtro_tipo === 'empresa' ? 'selected' : ''; ?>>Empresa</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="status"><i class="fa-solid fa-toggle-on"></i> Status:</label>
                        <select id="status" name="status">
                            <option value="">Todos os Status</option>
                            <option value="ativo" <?php echo $filtro_status === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                            <option value="inativo" <?php echo $filtro_status === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-search"></i> Pesquisar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="users-container">
            <?php if (count($usuarios) > 0): ?>
                <div class="users-grid">
                    <?php foreach ($usuarios as $usuario): 
                        $isEmpresa = $usuario['tipo_conta'] === 'empresa';
                        $isAtivo = $usuario['ativo'] == 1;
                        
                        $cardClass = '';
                        if (!$isAtivo) {
                            $cardClass = 'inactive';
                        } elseif ($isEmpresa) {
                            $cardClass = 'empresa-card';
                        }
                    ?>
                    <div class="user-card <?php echo $cardClass; ?>">
                        <div class="user-header">
                            <div class="user-type <?php echo $isEmpresa ? 'empresa' : 'usuario'; ?>">
                                <?php if ($isEmpresa): ?>
                                    <i class="fa-solid fa-building"></i> Empresa
                                <?php else: ?>
                                    <i class="fa-solid fa-user"></i> Pessoa Física
                                <?php endif; ?>
                            </div>
                            <?php if ($isEmpresa): ?>
                                <div class="user-status <?php echo $isAtivo ? 'status-ativo' : 'status-inativo'; ?>">
                                    <?php echo $isAtivo ? 'Ativo' : 'Inativo'; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="user-info-section">
                            <h4 <?php echo $isEmpresa ? 'class="empresa-name"' : ''; ?>>
                                <?php if ($isEmpresa): ?>
                                    <i class="fa-solid fa-building"></i>
                                <?php else: ?>
                                    <i class="fa-solid fa-user"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($usuario['nome']); ?>
                            </h4>
                            <p><i class="fa-solid fa-calendar-plus"></i> Cadastrado em: <?php echo date('d/m/Y H:i', strtotime($usuario['data_criacao'])); ?></p>
                            <?php if ($usuario['ultimo_agendamento']): ?>
                            <p><i class="fa-solid fa-clock"></i> Último agendamento: <?php echo date('d/m/Y H:i', strtotime($usuario['ultimo_agendamento'])); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="agendamentos-stats">
                            <h5><i class="fa-solid fa-chart-bar"></i> Estatísticas de Agendamentos</h5>
                            <div class="stats-row">
                                <div class="stat-item">
                                    <div class="number"><?php echo $usuario['total_agendamentos']; ?></div>
                                    <div class="label">Total</div>
                                </div>
                                <div class="stat-item confirmados">
                                    <div class="number"><?php echo $usuario['agendamentos_confirmados']; ?></div>
                                    <div class="label">Confirmados</div>
                                </div>
                                <div class="stat-item concluidos">
                                    <div class="number"><?php echo $usuario['agendamentos_concluidos']; ?></div>
                                    <div class="label">Concluídos</div>
                                </div>
                                <div class="stat-item pendentes">
                                    <div class="number"><?php echo $usuario['agendamentos_pendentes']; ?></div>
                                    <div class="label">Pendentes</div>
                                </div>
                                <div class="stat-item cancelados">
                                    <div class="number"><?php echo $usuario['agendamentos_cancelados']; ?></div>
                                    <div class="label">Cancelados</div>
                                </div>
                                <div class="stat-item negados">
                                    <div class="number"><?php echo $usuario['agendamentos_negados']; ?></div>
                                    <div class="label">Negados</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-users">
                    <i class="fa-solid fa-users-slash"></i><br>
                    <strong>Nenhum usuário encontrado</strong><br>
                    <small>Não há usuários cadastrados com os filtros aplicados</small>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="back-end-javascript\js_pag_usuarios.js"></script>
</body>
</html>