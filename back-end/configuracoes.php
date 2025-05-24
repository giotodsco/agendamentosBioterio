<?php
// acexx/back-end/configuracoes.php
session_start();
require_once 'functions.php';

// Verificar se o usuário está logado e é admin
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || $_SESSION['tipo_usuario'] !== 'admin') {
    header("Location: pag_adm.php");
    exit();
}

// Função para obter configuração
function obterConfiguracao($chave) {
    try {
        $conexao = conectarBanco();
        $stmt = $conexao->prepare("SELECT valor FROM configuracoes WHERE chave = ?");
        $stmt->execute([$chave]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ? $resultado['valor'] : null;
    } catch (PDOException $e) {
        return null;
    }
}

// Função para definir configuração
function definirConfiguracao($chave, $valor, $descricao = null) {
    try {
        $conexao = conectarBanco();
        $stmt = $conexao->prepare("
            INSERT INTO configuracoes (chave, valor, descricao) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE valor = VALUES(valor), descricao = VALUES(descricao)
        ");
        $stmt->execute([$chave, $valor, $descricao]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Processar alterações de configuração
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao'])) {
    $acao = $_POST['acao'];
    
    if ($acao === 'toggle_agendamento_automatico') {
        $valor_atual = obterConfiguracao('agendamento_automatico');
        $novo_valor = ($valor_atual === '1') ? '0' : '1';
        
        if (definirConfiguracao('agendamento_automatico', $novo_valor, 'Define se agendamentos individuais são aprovados automaticamente (1) ou ficam pendentes (0)')) {
            $mensagem_sucesso = "Modo de agendamento alterado com sucesso!";
        } else {
            $mensagem_erro = "Erro ao alterar configuração.";
        }
    }
}

// Obter configurações atuais
$agendamento_automatico = obterConfiguracao('agendamento_automatico') === '1';

// Buscar estatísticas
try {
    $conexao = conectarBanco();
    
    // Contar agendamentos pendentes
    $stmt = $conexao->prepare("SELECT COUNT(*) as total FROM agendamentos WHERE status = 'pendente'");
    $stmt->execute();
    $agendamentos_pendentes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Contar agendamentos hoje
    $stmt = $conexao->prepare("SELECT COUNT(*) as total FROM agendamentos WHERE data_agendamento = CURDATE() AND status = 'confirmado'");
    $stmt->execute();
    $agendamentos_hoje = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Contar total de agendamentos
    $stmt = $conexao->prepare("SELECT COUNT(*) as total FROM agendamentos");
    $stmt->execute();
    $total_agendamentos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Contar agendamentos de empresa pendentes
    $stmt = $conexao->prepare("SELECT COUNT(*) as total FROM agendamentos WHERE tipo_agendamento = 'empresa' AND status = 'pendente'");
    $stmt->execute();
    $empresas_pendentes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
} catch (PDOException $e) {
    $agendamentos_pendentes = 0;
    $agendamentos_hoje = 0;
    $total_agendamentos = 0;
    $empresas_pendentes = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações do Sistema - Biotério FSA</title>
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
            padding: 15px 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        .header h1 {
            color: white;
            font-size: 24px;
        }

        .btn-back {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid white;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back:hover {
            background-color: rgba(255, 255, 255, 0.3);
            color: white;
        }

        .content {
            background-color: rgb(225, 225, 228);
            border-radius: 20px;
            box-shadow: 5px 5px 50px rgba(90, 90, 90, 0.392);
            padding: 30px;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: rgba(64, 122, 53, 0.1);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border-left: 4px solid rgba(64, 122, 53, 0.819);
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: rgba(64, 122, 53, 0.819);
        }

        .stat-label {
            font-size: 14px;
            color: rgb(100, 100, 100);
            margin-top: 5px;
        }

        /* ATUALIZADO: Melhor destaque para o controle principal */
        .main-control {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 3px solid #ffc107;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(255, 193, 7, 0.3);
        }

        .main-control h2 {
            color: #856404;
            font-size: 24px;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .main-control p {
            color: #856404;
            font-size: 16px;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        /* ATUALIZADO: Toggle switch maior e mais visível */
        .toggle-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin: 25px 0;
        }

        .toggle-switch {
            position: relative;
            width: 80px;
            height: 40px;
            background-color: #dc3545;
            border-radius: 40px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .toggle-switch.active {
            background-color: #28a745;
        }

        .toggle-slider {
            position: absolute;
            top: 4px;
            left: 4px;
            width: 32px;
            height: 32px;
            background-color: white;
            border-radius: 50%;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .toggle-switch.active .toggle-slider {
            transform: translateX(40px);
        }

        .toggle-labels {
            display: flex;
            align-items: center;
            gap: 30px;
            font-size: 18px;
            font-weight: bold;
        }

        .toggle-label {
            padding: 10px 20px;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .toggle-label.active {
            background-color: rgba(64, 122, 53, 0.2);
            color: rgba(64, 122, 53, 0.819);
        }

        .toggle-label.inactive {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .config-section {
            background-color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            color: rgba(64, 122, 53, 0.819);
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: bold;
            margin-top: 15px;
        }

        .status-automatic {
            background-color: #d4edda;
            color: #155724;
            border: 2px solid #28a745;
        }

        .status-manual {
            background-color: #fff3cd;
            color: #856404;
            border: 2px solid #ffc107;
        }

        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            font-family: Georgia, 'Times New Roman', Times, serif;
            font-weight: bold;
        }

        .btn-primary {
            background-color: rgba(64, 122, 53, 0.819);
            color: white;
        }

        .btn-primary:hover {
            background-color: rgba(44, 81, 36, 0.819);
            color: white;
        }

        .btn-secondary {
            background-color: rgb(200, 200, 200);
            color: rgb(60, 59, 59);
        }

        .btn-secondary:hover {
            background-color: rgb(180, 180, 180);
            color: rgb(60, 59, 59);
        }

        .info-box {
            background-color: #e7f3ff;
            padding: 20px;
            border-radius: 15px;
            border-left: 4px solid #2196f3;
            margin-top: 20px;
        }

        .info-box h4 {
            color: #1976d2;
            margin-bottom: 10px;
        }

        .info-box ul {
            margin-left: 20px;
            line-height: 1.6;
        }

        .info-box li {
            margin-bottom: 8px;
            color: #1976d2;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .toggle-container {
                flex-direction: column;
                gap: 15px;
            }
            
            .actions {
                flex-direction: column;
                align-items: center;
            }

            .main-control {
                padding: 20px;
            }

            .toggle-labels {
                gap: 15px;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fa-solid fa-cog"></i> Configurações do Sistema</h1>
        <a href="pag_agendamentos_adm.php" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Voltar
        </a>
    </div>

    <div class="content">
        <?php if (isset($mensagem_sucesso)): ?>
            <div class="message success"><?php echo htmlspecialchars($mensagem_sucesso); ?></div>
        <?php endif; ?>
        
        <?php if (isset($mensagem_erro)): ?>
            <div class="message error"><?php echo htmlspecialchars($mensagem_erro); ?></div>
        <?php endif; ?>

        <!-- ATUALIZADO: Controle principal em destaque -->
        <div class="main-control">
            <h2><i class="fa-solid fa-toggle-on"></i> Modo de Agendamento</h2>
            <p>Controle como os agendamentos individuais são processados no sistema</p>

            <div class="toggle-container">
                <div class="toggle-labels">
                    <span class="toggle-label <?php echo !$agendamento_automatico ? 'active' : 'inactive'; ?>">
                        <i class="fa-solid fa-user-check"></i> Manual
                    </span>
                    
                    <form method="POST" style="margin: 0;">
                        <input type="hidden" name="acao" value="toggle_agendamento_automatico">
                        <div class="toggle-switch <?php echo $agendamento_automatico ? 'active' : ''; ?>" onclick="this.parentElement.submit()">
                            <div class="toggle-slider">
                                <?php echo $agendamento_automatico ? '<i class="fa-solid fa-check"></i>' : '<i class="fa-solid fa-times"></i>'; ?>
                            </div>
                        </div>
                    </form>
                    
                    <span class="toggle-label <?php echo $agendamento_automatico ? 'active' : 'inactive'; ?>">
                        <i class="fa-solid fa-magic"></i> Automático
                    </span>
                </div>
            </div>

            <div class="status-indicator <?php echo $agendamento_automatico ? 'status-automatic' : 'status-manual'; ?>">
                <i class="fa-solid fa-<?php echo $agendamento_automatico ? 'check-circle' : 'clock'; ?>"></i>
                Status Atual: <?php echo $agendamento_automatico ? 'Automático' : 'Manual'; ?>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_agendamentos; ?></div>
                <div class="stat-label">Total de Agendamentos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $agendamentos_pendentes; ?></div>
                <div class="stat-label">Agendamentos Pendentes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $agendamentos_hoje; ?></div>
                <div class="stat-label">Agendamentos Hoje</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $empresas_pendentes; ?></div>
                <div class="stat-label">Empresas Pendentes</div>
            </div>
        </div>

        <div class="config-section">
            <div class="section-title">
                <i class="fa-solid fa-info-circle"></i>
                Como Funciona o Sistema
            </div>

            <div class="info-box">
                <h4><i class="fa-solid fa-lightbulb"></i> Entenda os Modos:</h4>
                <ul>
                    <li><strong>Modo Automático:</strong> Agendamentos individuais são confirmados imediatamente após o cadastro</li>
                    <li><strong>Modo Manual:</strong> Todos os agendamentos individuais ficam pendentes e precisam ser aprovados por você</li>
                    <li><strong>Empresas:</strong> Agendamentos de empresas/instituições sempre ficam pendentes, independente desta configuração</li>
                    <li><strong>Limite:</strong> Máximo de 10 agendamentos por dia (segunda a sexta-feira)</li>
                </ul>
            </div>
        </div>

        <div class="actions">
            <a href="pag_agendamentos_adm.php" class="btn btn-primary">
                <i class="fa-solid fa-calendar-check"></i>
                Gerenciar Agendamentos
            </a>
            <a href="pag_agendamentos_operador.php" class="btn btn-secondary">
                <i class="fa-solid fa-chart-line"></i>
                Relatórios
            </a>
        </div>
    </div>
</body>
</html>