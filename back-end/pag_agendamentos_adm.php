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
                $mensagem = "Agendamento confirmado com sucesso!";
                
            } elseif ($acao === 'negar') {
                $stmt = $conexao->prepare("UPDATE agendamentos SET status = 'negado' WHERE id = :id");
                $stmt->bindParam(':id', $agendamento_id);
                $stmt->execute();
                $mensagem = "Agendamento negado com sucesso!";
                
            } elseif ($acao === 'cancelar') {
                // NOVO: Cancelar agendamento
                $stmt = $conexao->prepare("UPDATE agendamentos SET status = 'cancelado', data_cancelamento = NOW() WHERE id = :id");
                $stmt->bindParam(':id', $agendamento_id);
                $stmt->execute();
                $mensagem = "Agendamento cancelado com sucesso!";
                
            } elseif ($acao === 'remover') {
                $stmt = $conexao->prepare("DELETE FROM agendamentos WHERE id = :id");
                $stmt->bindParam(':id', $agendamento_id);
                $stmt->execute();
                $mensagem = "Agendamento removido com sucesso!";
            }
        } catch (PDOException $e) {
            $mensagem = "Erro ao processar ação: " . $e->getMessage();
        }
    }
}

try {
    $conexao = conectarBanco();
    $stmt = $conexao->query("
        SELECT a.*, u.nome as usuario_nome 
        FROM agendamentos a 
        LEFT JOIN usuarios u ON a.usuario_id = u.id 
        ORDER BY a.data_agendamento DESC, a.hora_agendamento ASC
    ");
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensagem = "Erro ao carregar agendamentos: " . $e->getMessage();
    $agendamentos = [];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biotério - Administração de Agendamentos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
            border-radius: 20px;
            box-shadow: 5px 5px 50px rgba(90, 90, 90, 0.392);
            padding: 30px;
            max-height: 80vh;
            overflow-y: auto;
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

        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .stat-card {
            background-color: rgba(64, 122, 53, 0.1);
            padding: 20px;
            border-radius: 10px;
            flex: 1;
            min-width: 200px;
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

        .table-container {
            overflow-x: auto;
        }

        .appointments-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .appointments-table th,
        .appointments-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            vertical-align: middle;
        }

        .appointments-table th {
            background-color: rgba(64, 122, 53, 0.819);
            color: white;
            font-weight: bold;
            position: sticky;
            top: 0;
        }

        .appointments-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .appointments-table tr:hover {
            background-color: #f5f5f5;
        }

        .status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
            white-space: nowrap;
        }

        .status-pendente {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-confirmado {
            background-color: #d4edda;
            color: #155724;
        }

        .status-negado {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-cancelado {
            background-color: #f8d7da;
            color: #721c24;
        }

        .btn-action {
            padding: 4px 8px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
            margin: 1px;
            white-space: nowrap;
        }

        .btn-confirmar {
            background-color: #28a745;
            color: white;
        }

        .btn-confirmar:hover {
            background-color: #218838;
        }

        .btn-negar {
            background-color: #ffc107;
            color: #333;
        }

        .btn-negar:hover {
            background-color: #e0a800;
        }

        .btn-cancelar {
            background-color: #fd7e14;
            color: white;
        }

        .btn-cancelar:hover {
            background-color: #e56b03;
        }

        .btn-remover {
            background-color: #dc3545;
            color: white;
        }

        .btn-remover:hover {
            background-color: #c82333;
        }

        .user-type {
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 10px;
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

        /* NOVO: Estilos para pop-up personalizado */
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

        .popup-btn-confirm {
            background-color: rgba(64, 122, 53, 0.819);
            color: white;
        }

        .popup-btn-confirm:hover {
            background-color: rgba(44, 81, 36, 0.819);
        }

        .popup-btn-cancel {
            background-color: #dc3545;
            color: white;
        }

        .popup-btn-cancel:hover {
            background-color: #c82333;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .stats {
                flex-direction: column;
            }
            
            .appointments-table {
                font-size: 12px;
            }
            
            .appointments-table th,
            .appointments-table td {
                padding: 4px;
            }

            .custom-popup {
                padding: 20px;
                margin: 20px;
            }

            .popup-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- NOVO: Pop-up personalizado -->
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
        <h1><i class="fa-solid fa-calendar-check"></i> Administração de Agendamentos</h1>
        <!-- CORRIGIDO: Botão de sair agora faz logout corretamente -->
        <a href="logout.php" class="btn-logout">
            <i class="fa-solid fa-sign-out-alt"></i> Sair
        </a>
    </div>

    <div class="content">
        <?php if (isset($mensagem)): ?>
            <div class="message success"><?php echo htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>

        <!-- Botão para configurações -->
        <div style="margin-bottom: 20px;">
            <a href="configuracoes.php" class="btn-action" style="background-color: rgba(64, 122, 53, 0.819); color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; font-size: 14px;">
                <i class="fa-solid fa-cog"></i> Configurações
            </a>
        </div>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($agendamentos); ?></div>
                <div class="stat-label">Total de Agendamentos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['status'] === 'pendente')); ?></div>
                <div class="stat-label">Pendentes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['status'] === 'confirmado')); ?></div>
                <div class="stat-label">Confirmados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($agendamentos, fn($a) => $a['usuario_id'] !== null)); ?></div>
                <div class="stat-label">Usuários Logados</div>
            </div>
        </div>

        <?php if (count($agendamentos) > 0): ?>
            <div class="table-container">
                <table class="appointments-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>CPF</th>
                            <th>Data</th>
                            <th>Hora</th>
                            <th>Status</th>
                            <th>Tipo</th>
                            <th>Criado em</th>
                            <th>Ações</th>
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
                                <span class="status status-<?php echo $agendamento['status']; ?>">
                                    <?php echo ucfirst($agendamento['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($agendamento['usuario_id']): ?>
                                    <span class="user-type user-logado">Usuário</span>
                                <?php else: ?>
                                    <span class="user-type user-anonimo">Anônimo</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($agendamento['data_criacao'])); ?></td>
                            <td>
                                <?php if ($agendamento['status'] === 'pendente'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                        <button type="submit" name="acao" value="confirmar" class="btn-action btn-confirmar" title="Confirmar agendamento">
                                            <i class="fa-solid fa-check"></i> Confirmar
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                        <button type="submit" name="acao" value="negar" class="btn-action btn-negar" title="Negar agendamento">
                                            <i class="fa-solid fa-times"></i> Negar
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if ($agendamento['status'] === 'confirmado'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                        <button type="button" name="acao" value="cancelar" class="btn-action btn-cancelar" title="Cancelar agendamento" 
                                                onclick="showCustomConfirm('Tem certeza que deseja cancelar este agendamento?', () => { this.type='submit'; this.click(); })">
                                            <i class="fa-solid fa-ban"></i> Cancelar
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                    <button type="button" name="acao" value="remover" class="btn-action btn-remover" title="Remover agendamento permanentemente"
                                            onclick="showCustomConfirm('Tem certeza que deseja remover este agendamento? Esta ação não pode ser desfeita!', () => { this.type='submit'; this.click(); })">
                                        <i class="fa-solid fa-trash"></i> Remover
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: rgb(100, 100, 100);">
                <i class="fa-solid fa-calendar-times" style="font-size: 50px; margin-bottom: 15px;"></i><br>
                Nenhum agendamento encontrado.
            </div>
        <?php endif; ?>
    </div>

    <script>
        // NOVO: Sistema de pop-up personalizado
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
    </script>
</body>
</html>