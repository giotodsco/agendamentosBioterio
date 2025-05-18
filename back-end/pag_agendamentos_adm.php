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
    // Seleciona todos os agendamentos, incluindo a coluna 'status', ordenados por data e hora
    $stmt = $conexao->query("SELECT id, nome, email, e_aluno, data_agendamento, hora_agendamento, data_criacao, status FROM agendamentos ORDER BY data_agendamento DESC, hora_agendamento ASC");
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
            box-sizing: border-box; /* Adicionado para melhor controle de layout */
        }

        body {
            overflow: hidden;
            margin: 0; /* Garante que não haja margens extras */
            padding: 0; /* Garante que não haja padding extras */
        }

        /* Estilos do layout geral */
        #div_geral_color {
            background: radial-gradient(circle, rgba(173,199,205,1) 0%, rgba(169,189,165,1) 31%, rgba(64, 122, 53, 0.819) 85%);
            height: 100vh;
            display: flex; /* Adicionado para centralizar div_geral_centro */
            justify-content: center; /* Adicionado para centralizar div_geral_centro */
            align-items: center; /* Adicionado para centralizar div_geral_centro */
        }
        #div_geral_centro {
            display: flex;
            height: 100%; /* Ajustado para 100% da div_geral_color */
            justify-content: center;
            align-items: center;
            width: 100%; /* Para preencher a largura */
        }
        #div_info_agendamentos { /* Alterado de #div_info_agendar para refletir o uso */
            width: 60%;
            background-color: rgb(225, 225, 228);
            height: 80%;
            border-radius: 20px;
            box-shadow: 5px 5px 50px rgba(90, 90, 90, 0.392);
            overflow-y: auto; /* Permite rolagem se o conteúdo for maior que a altura */
            padding: 20px;
            box-sizing: border-box;
            max-width: 900px; /* Limite a largura para melhor leitura */
        }

        /* Estilos de título */
        #titulo_principal { /* Adicionado ID para corresponder ao HTML */
            color: rgb(55, 75, 51);
            font-size: 32px;
            padding: 24px;
            text-align: center;
            font-weight: 700;
        }

        /* Estilos da tabela de agendamentos */
        .tabela-container { /* Adicionado para envolver a tabela e gerenciar o overflow */
            overflow-x: auto; /* Permite rolagem horizontal em telas pequenas */
            margin-top: 20px;
        }

        table { /* Removido #agendamentos, apliquei direto em table */
            margin-top: 0; /* Ajustado para não ter margem duplicada */
            text-align: left;
            width: 100%; /* Tabela ocupa 100% do container */
            border-collapse: collapse;
        }

        table h2 { /* Removido #agendamentos, apliquei direto em table */
            color: rgb(55, 75, 51);
            font-size: 24px;
            margin-bottom: 15px;
        }

        table { /* Removido #agendamentos, apliquei direto em table */
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td { /* Removido #agendamentos, apliquei direto em th, td */
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
            font-size: 14px;
            vertical-align: middle; /* Alinha o conteúdo verticalmente */
        }

        th { /* Removido #agendamentos, apliquei direto em th */
            background-color: rgba(64, 122, 53, 0.819);
            color: white;
        }

        tr:nth-child(even) { /* Removido #agendamentos, apliquei direto em tr */
            background-color: #f2f2f2;
        }

        /* Estilos para botões de ação na tabela */
        .btn-acao {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            margin: 2px; /* Reduz margem para caber mais botões */
            white-space: nowrap; /* Evita que o texto do botão quebre a linha */
            display: inline-block; /* Permite que os botões fiquem lado a lado */
        }
        .btn-remover {
            background-color: #dc3545; /* Vermelho */
            color: white;
        }
        .btn-remover:hover {
            background-color: #c82333;
        }

        /* Novos estilos para os botões Confirmar e Negar */
        .btn-confirmar {
            background-color: #28a745; /* Verde */
            color: white;
        }
        .btn-confirmar:hover {
            background-color: #218838;
        }

        .btn-negar {
            background-color: #ffc107; /* Amarelo/Laranja */
            color: #333; /* Texto escuro para contraste */
        }
        .btn-negar:hover {
            background-color: #e0a800;
        }

        /* Estilos para o texto do status */
        .status-pendente {
            color: #ffc107; /* Amarelo/Laranja */
            font-weight: bold;
        }
        .status-confirmado {
            color: #28a745; /* Verde */
            font-weight: bold;
        }
        .status-negado {
            color: #dc3545; /* Vermelho */
            font-weight: bold;
        }


        /* Estilos para link "Voltar" */
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
            line-height: 40px; /* Centraliza verticalmente o texto */
            margin-left: auto;
            margin-right: auto;
            background-color: rgba(64, 122, 53, 0.819);
            color: white;
            text-decoration: none; /* Remove sublinhado do link */
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
        .mensagem-status { /* Alterado de .alerta para .mensagem-status para consistência com o PHP */
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
        }
        .mensagem-status.sucesso { /* Alterado de .alerta.sucesso */
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .mensagem-status.erro { /* Alterado de .alerta.erro */
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .mensagem-status.aviso { /* Adicionado para status 'aviso' do PHP */
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        /* Media Queries para responsividade */
        @media (max-width: 992px) { /* Adicionado para tablets */
            #div_info_agendamentos {
                width: 80%;
                height: 85%;
            }
        }
        @media (max-width: 768px) {
            #div_info_agendamentos {
                width: 95%;
                height: 90%;
                padding: 10px;
            }
            th, td {
                font-size: 12px;
                padding: 5px;
            }
            .agendar_voltar {
                width: 50%;
                font-size: 16px;
            }
            /* Ajuste para que os botões não quebrem o layout em telas menores */
            td form { /* Alvo direto o form dentro da célula */
                display: block; /* Cada botão em uma nova linha */
                margin-right: 0;
                margin-bottom: 5px; /* Espaço entre botões empilhados */
            }
            .btn-acao {
                width: 100%; /* Botões ocupam a largura total da célula */
                box-sizing: border-box; /* Garante que padding não aumente a largura */
            }
        }
        @media (max-width: 480px) {
            #titulo_principal { /* Ajuste para o título principal */
                font-size: 24px;
            }
            th, td {
                font-size: 10px;
                padding: 4px;
            }
            .agendar_voltar {
                width: 70%;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div id="div_geral_color">
        <div id="div_geral_centro">
            <div id="div_info_agendamentos">
                <p id="titulo_principal">Gerenciar Agendamentos</p>

                <?php if (isset($erro_banco)): ?>
                    <p class="mensagem-status erro">Erro ao carregar agendamentos. Por favor, tente novamente mais tarde.</p>
                <?php endif; ?>

                <?php if (isset($_GET['status']) && isset($_GET['mensagem'])): ?>
                    <div class="mensagem-status <?php echo htmlspecialchars($_GET['status']); ?>">
                        <?php echo htmlspecialchars(urldecode($_GET['mensagem'])); ?>
                    </div>
                <?php endif; ?>

                <?php if (count($agendamentos) > 0): ?>
                    <div class="tabela-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Email</th> <th>É Aluno?</th>
                                    <th>Data</th>
                                    <th>Hora</th>
                                    <th>Registro</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($agendamentos as $agendamento): ?>
                                    <?php
                                        $e_aluno_texto = $agendamento['e_aluno'] ? 'Sim' : 'Não';
                                        $status_classe = '';
                                        if ($agendamento['status'] == 'confirmado') {
                                            $status_classe = 'status-confirmado';
                                        } elseif ($agendamento['status'] == 'negado') {
                                            $status_classe = 'status-negado';
                                        } elseif ($agendamento['status'] == 'pendente') {
                                            $status_classe = 'status-pendente';
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($agendamento['id']); ?></td>
                                        <td><?php echo htmlspecialchars($agendamento['nome']); ?></td>
                                        <td><?php echo htmlspecialchars($agendamento['email']); ?></td> <td><?php echo htmlspecialchars($e_aluno_texto); ?></td>
                                        <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($agendamento['data_agendamento']))); ?></td>
                                        <td><?php echo htmlspecialchars(date('H:i', strtotime($agendamento['hora_agendamento']))); ?></td>
                                        <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($agendamento['data_criacao']))); ?></td>
                                        <td class="<?php echo $status_classe; ?>">
                                            <?php echo ucfirst(htmlspecialchars($agendamento['status'])); ?>
                                        </td>
                                        <td class="acoes">
                                            <form action="atualizar_status_agendamento.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($agendamento['id']); ?>">
                                                <?php if ($agendamento['status'] == 'pendente' || $agendamento['status'] == 'negado'): ?>
                                                    <input type="hidden" name="status" value="confirmado">
                                                    <button type="submit" class="btn-acao btn-confirmar">Confirmar</button>
                                                <?php endif; ?>
                                                <?php if ($agendamento['status'] == 'pendente' || $agendamento['status'] == 'confirmado'): ?>
                                                    <input type="hidden" name="status" value="negado">
                                                    <button type="submit" class="btn-acao btn-negar">Negar</button>
                                                <?php endif; ?>
                                            </form>
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