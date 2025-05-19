<?php
require_once '../back-end/functions.php';

$agendamentos = [];
$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';

    if (empty($email)) {
        $mensagem = "Por favor, insira um email para buscar os agendamentos.";
    } else {
        try {
            $conexao = conectarBanco();
            $stmt = $conexao->prepare("SELECT nome, email, data_agendamento, hora_agendamento, status FROM agendamentos WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($agendamentos) == 0) {
                $mensagem = "Nenhum agendamento encontrado para o email: " . htmlspecialchars($email);
            }
        } catch (PDOException $e) {
            $mensagem = "Erro ao buscar agendamentos: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar Agendamentos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        * {
            color: rgb(60, 59, 59);
            font-family: Georgia, 'Times New Roman', Times, serif;
            box-sizing: border-box;
        }

        body {
            overflow: hidden;
            margin: 0; 
            padding: 0; 
        }

        #email {
            padding: 10px;
            border-radius: 10px;
            width: 75%;
            border: none;
        }
        #busca{
            width: 60px;
            padding: 10px;
            background-color: rgba(64, 122, 53, 0.819);
            color: white;
            text-decoration: none; 
            margin-left: 26px;
            border: none;
            border-radius: 10px;
        }
        #div_geral_color {
            background: radial-gradient(circle, rgba(173,199,205,1) 0%, rgba(169,189,165,1) 31%, rgba(64, 122, 53, 0.819) 85%);
            height: 100vh;
            display: flex; 
            justify-content: center; 
            align-items: center; 
        }
        #div_geral_centro {
            display: flex;
            height: 100%; 
            justify-content: center;
            align-items: center;
            width: 100%;
        }
        #div_info_agendar {
            width: 60%;
            background-color: rgb(225, 225, 228);
            height: 80%;
            border-radius: 20px;
            box-shadow: 5px 5px 50px rgba(90, 90, 90, 0.392);
            overflow-y: auto;
            padding: 20px;
            box-sizing: border-box;
            max-width: 900px;
        }
        h1 {
            color: rgb(55, 75, 51);
            font-size: 32px;
            padding: 24px;
            text-align: center;
            font-weight: 700;
        }
        #agendamentos {
            margin-top: 20px;
            text-align: left;
            width: 100%;
            border-collapse: collapse;
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
            vertical-align: middle;
        }
        #agendamentos th {
            background-color: rgba(64, 122, 53, 0.819);
            color: white;
        }
        #agendamentos tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .btn-acao {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            margin: 2px;
            white-space: nowrap; 
            display: inline-block; 
        }
        .btn-remover {
            background-color: #dc3545;
            color: white;
        }
        .btn-remover:hover {
            background-color: #c82333;
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
        .status-pendente {
            color: #ffc107;
            font-weight: bold;
        }
        .status-confirmado {
            color: #28a745;
            font-weight: bold;
        }
        .status-negado {
            color: #dc3545;
            font-weight: bold;
        }
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
            background-color: rgba(64, 122, 53, 0.819);
            color: white;
            text-decoration: none;
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
        .alerta.aviso { 
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        @media (max-width: 992px) { 
            #div_info_agendar {
                width: 80%;
                height: 85%;
            }
        }
        @media (max-width: 768px) {
            #div_info_agendar {
                width: 95%;
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
            #agendamentos td form {
                display: block;
                margin-right: 0;
                margin-bottom: 5px;
            }
            .btn-acao {
                width: 100%;
                box-sizing: border-box; 
            }
        }
        @media (max-width: 480px) {
            h1 {
                font-size: 24px;
            }
            #agendamentos th, #agendamentos td {
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
            <div id="div_info_agendar">
                <h1>Buscar Agendamentos por Email</h1>

                <form method="POST">
                    <label for="email">Digite o Email:</label>
                    <input type="email" id="email" name="email" required>
                    <button type="submit" id ="busca">Buscar</button>
                </form>

                <?php if ($mensagem): ?>
                    <p class="alerta erro"><?php echo htmlspecialchars($mensagem); ?></p>
                <?php endif; ?>

                <?php if ($agendamentos): ?>
                    <div style="overflow-x:auto;">
                        <table id="agendamentos">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Dia e Hora</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($agendamentos as $agendamento): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($agendamento['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($agendamento['email']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($agendamento['data_agendamento'] . ' ' . $agendamento['hora_agendamento'])); ?></td>
                                    <td>
                                        <?php
                                        $status = htmlspecialchars(ucfirst($agendamento['status']));
                                        if ($status == 'Pendente') {
                                            echo "<span style='color: #ffc107; font-weight: bold;'>$status</span>";
                                        } elseif ($status == 'Negado') {
                                            echo "<span style='color: #dc3545; font-weight: bold;'>$status</span>";
                                        } elseif ($status == 'Confirmado') {
                                            echo "<span style='color: #28a745; font-weight: bold;'>$status</span>";
                                        } else {
                                            echo $status;
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <a href="pag_inicial.html" class="agendar_voltar">Voltar para a Página Inicial</a>
            </div>
        </div>
    </div>
</body>
</html>