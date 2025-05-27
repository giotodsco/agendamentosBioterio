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

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        .stat-card:nth-child(3) { border-left-color: #28a745; }
        .stat-card:nth-child(4) { border-left-color: #17a2b8; }

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

        .users-container {
            flex: 1;
            overflow-y: auto;
            padding-right: 5px;
        }

        /* Barra de rolagem personalizada */
        .users-container::-webkit-scrollbar {
            width: 10px;
        }

        .users-container::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 5px;
        }

        .users-container::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, rgba(64, 122, 53, 0.6) 0%, rgba(64, 122, 53, 0.8) 100%);
            border-radius: 5px;
            transition: all 0.3s;
        }

        .users-container::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, rgba(64, 122, 53, 0.8) 0%, rgba(64, 122, 53, 1) 100%);
        }

        .users-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }

        .user-card {
            background: linear-gradient(135deg, #f8f9fa 0%, white 100%);
            border-radius: 12px;
            padding: 20px;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
            position: relative;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .user-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            border-color: rgba(64, 122, 53, 0.3);
        }

        .user-card.empresa-card {
            background: linear-gradient(135deg, #fff3cd 0%, rgba(255, 243, 205, 0.3) 100%);
            border-color: #ffc107;
        }

        .user-card.empresa-card:hover {
            border-color: #ff9800;
            box-shadow: 0 10px 30px rgba(255, 193, 7, 0.2);
        }

        .user-card.inactive {
            opacity: 0.7;
            background: linear-gradient(135deg, #f8d7da 0%, rgba(248, 215, 218, 0.3) 100%);
            border-color: #dc3545;
        }

        .user-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
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

        .user-type.usuario {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            color: #1976d2;
            border: 2px solid #2196f3;
        }

        .user-type.empresa {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
            border: 2px solid #ffc107;
        }

        .user-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-ativo {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-inativo {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .user-info-section {
            margin-bottom: 15px;
        }

        .user-info-section h4 {
            color: rgb(60, 59, 59);
            margin-bottom: 8px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .user-info-section h4.empresa-name {
            color: #856404;
        }

        .user-info-section p {
            font-size: 14px;
            color: rgb(100, 100, 100);
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .agendamentos-stats {
            background-color: rgba(64, 122, 53, 0.05);
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }

        .agendamentos-stats h5 {
            color: rgba(64, 122, 53, 0.819);
            margin-bottom: 10px;
            font-size: 14px;
            font-weight: bold;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(70px, 1fr));
            gap: 6px;
        }

        .stat-item {
            text-align: center;
            padding: 8px 4px;
            background-color: white;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .stat-item .number {
            font-size: 16px;
            font-weight: bold;
            color: rgba(64, 122, 53, 0.819);
        }

        .stat-item .label {
            font-size: 9px;
            color: rgb(100, 100, 100);
            text-transform: uppercase;
            line-height: 1.2;
        }

        /* Cores específicas para cada tipo de status */
        .stat-item.confirmados .number { color: #17a2b8; }
        .stat-item.concluidos .number { color: #28a745; }
        .stat-item.pendentes .number { color: #ffc107; }
        .stat-item.cancelados .number { color: #6c757d; }
        .stat-item.negados .number { color: #dc3545; }

        .no-users {
            text-align: center;
            padding: 60px 20px;
            color: rgb(150, 150, 150);
            font-size: 18px;
            background-color: #f8f9fa;
            border-radius: 15px;
            margin: 20px 0;
        }

        .no-users i {
            font-size: 60px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 2px solid #dc3545;
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
            
            .users-grid {
                grid-template-columns: 1fr;
            }

            .stats-row {
                grid-template-columns: repeat(3, 1fr);
            }
        }
    </style>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Destacar usuários com mais agendamentos
            const userCards = document.querySelectorAll('.user-card');
            userCards.forEach(card => {
                const totalAgendamentos = parseInt(card.querySelector('.stat-item .number').textContent);
                if (totalAgendamentos >= 5) {
                    card.style.border = '2px solid #28a745';
                    card.style.boxShadow = '0 4px 15px rgba(40, 167, 69, 0.2)';
                }
            });

            // Auto-submit no filtro de nome após digitar
            const nomeInput = document.getElementById('nome');
            let timeout;
            
            nomeInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    if (this.value.length >= 3 || this.value.length === 0) {
                        this.form.submit();
                    }
                }, 500);
            });

            // Animação de entrada dos cards
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '0';
                        entry.target.style.transform = 'translateY(20px)';
                        entry.target.style.transition = 'all 0.5s ease';
                        
                        setTimeout(() => {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0)';
                        }, 100);
                        
                        observer.unobserve(entry.target);
                    }
                });
            });

            userCards.forEach(card => {
                observer.observe(card);
            });

            // Destacar usuários com muitos agendamentos concluídos
            userCards.forEach(card => {
                const concluidosElement = card.querySelector('.stat-item.concluidos .number');
                if (concluidosElement) {
                    const totalConcluidos = parseInt(concluidosElement.textContent);
                    if (totalConcluidos >= 3) {
                        concluidosElement.parentElement.style.background = 'linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%)';
                        concluidosElement.parentElement.style.border = '2px solid #28a745';
                    }
                }
            });
        });
    </script>
</body>
</html>