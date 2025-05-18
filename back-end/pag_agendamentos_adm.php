<?php
// acexx/back-end/pag_agendamentos_adm.php
session_start();
require_once 'functions.php';

// Verifica se o funcionário está logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: ../front-end/pag_adm.php");
    exit();
}

try {
    $conexao = conectarBanco();
    // Seleciona todos os agendamentos, ordenados por data e hora
    $stmt = $conexao->query("SELECT id, nome, origem, e_aluno, data_agendamento, hora_agendamento, data_criacao FROM agendamentos ORDER BY data_agendamento DESC, hora_agendamento ASC");
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro ao buscar agendamentos: " . $e->getMessage()); // Loga o erro no servidor
    $agendamentos = []; // Garante que $agendamentos seja um array vazio em caso de erro
    $erro_banco = true; // Flag para exibir mensagem de erro
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biotério - Agendamentos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <style>
        /* acexx/front-end/Estilos/style_adm_agendamentos.css - Conteúdo consolidado */

        /* Estilos globais */
        * {
            color: rgb(60, 59, 59);
            font-family: Georgia, 'Times New Roman', Times, serif;
        }

        body {
            overflow: hidden;
        }

        /* Estilos do layout geral */
        #div_geral_color {
            background: radial-gradient(circle, rgba(173,199,205,1) 0%, rgba(169,189,165,1) 31%, rgba(64, 122, 53, 0.819) 85%);
            height: 100vh;
        }
        #div_geral_centro {
            display: flex;
            height: 100vh;
            justify-content: center;
            align-items: center;
        }
        #div_info_agendar {
            width: 60%;
            background-color: rgb(225, 225, 228);
            height:80%;
            border-radius: 20px;
            box-shadow: 5px 5px 50px rgba(90, 90, 90, 0.392);
            overflow-y: auto;
            padding: 20px;
            box-sizing: border-box;
        }

        /* Estilos de título */
        h1 {
            color: rgb(55, 75, 51);
            font-size: 32px;
            padding: 24px;
            text-align: center;
            font-weight: 700;
        }

        /* Estilos da tabela de agendamentos */
        #agendamentos {
            margin-top: 20px;
            text-align: left;
        }

        #agendamentos h2 {
            color: rgb(55, 75, 51);
            font-size: 24px;
            margin-bottom: 15px;
        }

        #agendamentos table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        #agendamentos th, #agendamentos td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
            font-size: 14px;
        }

        #agendamentos th {
            background-color: rgba(64, 122, 53, 0.819);
            color: white;
        }

        #agendamentos tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        /* Estilos para botões de ação na tabela */
        .btn-acao {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            margin: 2px;
            white-space: nowrap;
        }
        .btn-remover {
            background-color: #dc3545; /* Vermelho */
            color: white;
        }
        .btn-remover:hover {
            background-color: #c82333;
        }

        /* Estilos para link "Voltar" e botão "Agendar" (se aplicável aqui) */
        .agendar_voltar {
            display: block;
            width: 30%;
            height: 40px;
            text-align: center;
            font-family: Georgia, 'Times New Roman', Times, serif;
            border-radius: 10px;
            border: none;
            font-size: 20px;
            cursor:pointer;
            margin-top: 20px;
            line-height: 40px;
            margin-left: auto;
            margin-right: auto;
            background-color: rgba(64, 122, 53, 0.819); /* Cor para o botão Voltar */
            color: white;
        }
        .agendar_voltar:hover {
            background-color: rgba(44, 81, 36, 0.819);
            color: white;
        }
        .agendar_voltar:active {
            background-color: rgba(35, 65, 29, 0.819);
            color: white;
        }
        a {
            text-decoration: none;
            color: inherit;
        }

        /* Estilos para mensagens de alerta */
        .alerta {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
        }
        .alerta.sucesso {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alerta.erro {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Media Queries para responsividade */
        @media (max-width: 768px) {
            #div_info_agendar {
                width: 90%;
                height: 90%;
                padding: 10px;
            }
            #agendamentos th, #agendamentos td {
                font-size: 12px;
                padding: 5px;
            }
            .agendar_voltar {
                width: 50%;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div id="div_geral_color">
        <div id="div_geral_centro">
            <div id="div_info_agendar">
                <h1>Agendamentos Cadastrados</h1>

                <?php
                // Exibe mensagens de status (sucesso/erro)
                if (isset($_GET['status']) && isset($_GET['mensagem'])) {
                    $status = $_GET['status'];
                    $mensagem = htmlspecialchars(urldecode($_GET['mensagem']));
                    $classe_alerta = ($status == 'sucesso') ? 'sucesso' : 'erro';
                    echo "<p class='alerta {$classe_alerta}'>{$mensagem}</p>";
                }
                ?>
                <?php if (isset($erro_banco)): ?>
                    <p class="alerta erro">Ocorreu um erro ao carregar os agendamentos. Tente novamente mais tarde.</p>
                <?php elseif (count($agendamentos) > 0): ?>
                    <div style="overflow-x:auto;"> <table id="agendamentos">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Origem</th>
                                    <th>É Aluno?</th>
                                    <th>Data</th>
                                    <th>Hora</th>
                                    <th>Registro</th>
                                    <th>Ações</th> </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($agendamentos as $agendamento): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($agendamento['id']); ?></td>
                                        <td><?php echo htmlspecialchars($agendamento['nome']); ?></td>
                                        <td><?php echo htmlspecialchars($agendamento['origem']); ?></td>
                                        <td><?php echo htmlspecialchars($agendamento['e_aluno'] ? 'Sim' : 'Não'); ?></td>
                                        <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($agendamento['data_agendamento']))); ?></td>
                                        <td><?php echo htmlspecialchars(date('H:i', strtotime($agendamento['hora_agendamento']))); ?></td>
                                        <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($agendamento['data_criacao']))); ?></td>
                                        <td>
                                            <form action="remover_agendamento.php" method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja remover este agendamento? Esta ação é irreversível.');">
                                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($agendamento['id']); ?>">
                                                <button type="submit" class="btn-acao btn-remover">Remover</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>Nenhum agendamento cadastrado ainda.</p>
                <?php endif; ?>

                <a href="../front-end/pag_inicial.html" class="agendar_voltar">Voltar para a Página Inicial</a>
            </div>
        </div>
    </div>
    <script>
        // Script para remover os parâmetros de URL após exibir a mensagem (para URL limpa)
        document.addEventListener('DOMContentLoaded', function() {
            const url = new URL(window.location.href);
            if (url.searchParams.has('status') || url.searchParams.has('mensagem')) {
                url.searchParams.delete('status');
                url.searchParams.delete('mensagem');
                history.replaceState({}, document.title, url.pathname);
            }
        });
    </script>
</body>
</html>