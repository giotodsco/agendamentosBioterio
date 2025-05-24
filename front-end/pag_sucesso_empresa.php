<?php
session_start();

// Verifica se a empresa está logada
if (!isset($_SESSION['empresa_logada']) || $_SESSION['empresa_logada'] !== true) {
    header("Location: pag_login_usuario.php?tab=empresa");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitação Enviada - Biotério FSA</title>
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
            max-width: 650px;
            border-radius: 20px;
            box-shadow: 5px 5px 50px rgba(90, 90, 90, 0.392);
            padding: 40px;
            text-align: center;
        }

        .success-icon {
            font-size: 80px;
            color: #ffc107;
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

        .company-info {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, rgba(255, 193, 7, 0.05) 100%);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            border-left: 5px solid #ffc107;
        }

        .company-info strong {
            color: #856404;
        }

        .pending-notice {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            border-left: 5px solid #ffc107;
        }

        .pending-notice h3 {
            color: #856404;
            font-size: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .pending-notice p {
            color: #856404;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .next-steps {
            background-color: rgba(64, 122, 53, 0.1);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            border-left: 5px solid rgba(64, 122, 53, 0.819);
        }

        .next-steps h4 {
            color: rgba(64, 122, 53, 0.819);
            margin-bottom: 15px;
            font-size: 18px;
        }

        .next-steps ul {
            text-align: left;
            margin-left: 20px;
        }

        .next-steps li {
            margin-bottom: 8px;
            color: rgb(60, 59, 59);
            line-height: 1.4;
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
            transition: all 0.3s;
            font-family: Georgia, 'Times New Roman', Times, serif;
            font-weight: bold;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #e0a800 0%, #d39e00 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 193, 7, 0.3);
        }

        .btn-secondary {
            background-color: rgb(200, 200, 200);
            color: rgb(60, 59, 59);
        }

        .btn-secondary:hover {
            background-color: rgb(180, 180, 180);
            color: rgb(60, 59, 59);
            transform: translateY(-2px);
        }

        .contact-info {
            background-color: #e7f3ff;
            padding: 20px;
            border-radius: 15px;
            margin-top: 25px;
            border-left: 5px solid #2196f3;
        }

        .contact-info h4 {
            color: #1976d2;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .contact-info p {
            color: #1976d2;
            font-size: 14px;
            line-height: 1.5;
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
                max-width: 280px;
                justify-content: center;
            }

            h1 {
                font-size: 28px;
            }

            .success-message {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">
            <i class="fa-solid fa-paper-plane"></i>
        </div>
        
        <h1>Solicitação Enviada com Sucesso!</h1>
        
        <div class="success-message">
            Sua solicitação de agendamento empresarial foi recebida e está sendo analisada pela nossa equipe.
        </div>
        
        <div class="company-info">
            <strong><?php echo htmlspecialchars($_SESSION['empresa_nome']); ?></strong>, 
            agradecemos seu interesse em visitar nosso biotério.<br>
            Sua solicitação está na fila de aprovação.
        </div>

        <div class="pending-notice">
            <h3>
                <i class="fa-solid fa-clock"></i>
                Aguardando Aprovação
            </h3>
            <p><strong>Status:</strong> Pendente de análise pela administração</p>
            <p><strong>Prazo:</strong> Resposta em até 2 dias úteis</p>
            <p><strong>Notificação:</strong> Você será notificado por email sobre a decisão</p>
        </div>

        <div class="next-steps">
            <h4><i class="fa-solid fa-list-check"></i> Próximos Passos:</h4>
            <ul>
                <li>Nossa equipe analisará sua solicitação considerando a disponibilidade</li>
                <li>Verificaremos se a data e horário solicitados estão disponíveis</li>
                <li>Você receberá um email com a confirmação ou sugestão de nova data</li>
                <li>Em caso de aprovação, receberá instruções detalhadas para a visita</li>
            </ul>
        </div>
        
        <div class="buttons">
            <a href="pag_agendar_empresa.php" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i>
                Nova Solicitação
            </a>
            <a href="pag_inicial.html" class="btn btn-secondary">
                <i class="fa-solid fa-home"></i>
                Página Inicial
            </a>
        </div>

        <div class="contact-info">
            <h4><i class="fa-solid fa-info-circle"></i> Precisa de Ajuda?</h4>
            <p>Em caso de dúvidas, entre em contato conosco através dos canais oficiais da FSA ou aguarde nossa resposta por email.</p>
        </div>
    </div>
</body>
</html>