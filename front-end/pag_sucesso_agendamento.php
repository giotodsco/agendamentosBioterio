<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: pag_login_usuario.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamento Realizado - Biotério FSA</title>
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
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .success-container {
            background-color: rgb(225, 225, 228);
            width: 90%;
            max-width: 600px;
            border-radius: 20px;
            box-shadow: 5px 5px 50px rgba(90, 90, 90, 0.392);
            padding: 40px;
            text-align: center;
        }

        .success-icon {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
            animation: bounce 1s ease-in-out;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }

        h1 {
            color: rgb(55, 75, 51);
            font-size: 32px;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .success-message {
            font-size: 20px;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .user-info {
            background-color: rgba(64, 122, 53, 0.1);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-left: 4px solid rgba(64, 122, 53, 0.819);
        }

        .user-info strong {
            color: rgba(64, 122, 53, 0.819);
        }

        .buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
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
            transition: background-color 0.3s;
            font-family: Georgia, 'Times New Roman', Times, serif;
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

        @media (max-width: 768px) {
            .success-container {
                width: 95%;
                padding: 30px 20px;
            }
            
            .buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 250px;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">
            <i class="fa-solid fa-check-circle"></i>
        </div>
        
        <h1>Agendamento Realizado com Sucesso!</h1>
        
        <div class="success-message">
            Seu agendamento foi confirmado automaticamente e já está ativo no sistema!
        </div>
        
        <div class="user-info">
            <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong>, 
            seu agendamento está confirmado.<br>
            Você pode visualizar e gerenciar todos os seus agendamentos na área "Meus Agendamentos".
        </div>
        
        <div class="buttons">
            <a href="pag_meus_agendamentos.php" class="btn btn-primary">
                <i class="fa-solid fa-calendar"></i>
                Meus Agendamentos
            </a>
            <a href="pag_agendar_logado.php" class="btn btn-secondary">
                <i class="fa-solid fa-plus"></i>
                Novo Agendamento
            </a>
            <a href="pag_inicial.html" class="btn btn-secondary">
                <i class="fa-solid fa-home"></i>
                Página Inicial
            </a>
        </div>
    </div>
</body>
</html>