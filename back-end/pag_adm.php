<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <title>Biotério - Login Funcionário</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #333;
        }

        .login-container {
            background-color: white;
            width: 100%;
            max-width: 400px;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border: 1px solid #ddd;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #2c3e50;
            font-size: 24px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .header p {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .login-info {
            background-color: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 25px;
            border-left: 4px solid #3498db;
        }

        .login-info h4 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 14px;
            font-weight: 600;
        }

        .login-info p {
            font-size: 12px;
            margin-bottom: 5px;
            color: #7f8c8d;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            margin-bottom: 5px;
            color: #2c3e50;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #bdc3c7;
            border-radius: 5px;
            font-size: 14px;
            background-color: #fff;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-weight: 500;
        }

        .btn-login:hover {
            background-color: #2980b9;
        }

        .btn-login:active {
            background-color: #21618c;
        }

        .message {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            font-size: 14px;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .help-text {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 20px;
            text-align: center;
        }

        .help-text a {
            color: #3498db;
            text-decoration: none;
        }

        .help-text a:hover {
            text-decoration: underline;
        }

        .voltar {
            margin-top: 20px;
            text-align: center;
        }

        .voltar a {
            color: #7f8c8d;
            text-decoration: none;
            font-size: 14px;
        }

        .voltar a:hover {
            text-decoration: underline;
            color: #2c3e50;
        }

        /* Pop-up personalizado */
        .popup-overlay {
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

        .popup {
            background-color: white;
            border-radius: 10px;
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
            color: #e74c3c;
        }

        .popup-title {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .popup-message {
            font-size: 16px;
            color: #7f8c8d;
            margin-bottom: 25px;
            line-height: 1.4;
        }

        .popup-button {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            background-color: #3498db;
            color: white;
        }

        .popup-button:hover {
            background-color: #2980b9;
        }

        @media (max-width: 768px) {
            .login-container {
                margin: 20px;
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Pop-up personalizado -->
    <div class="popup-overlay" id="popup-overlay">
        <div class="popup">
            <div class="popup-icon">
                <i class="fa-solid fa-exclamation-triangle"></i>
            </div>
            <div class="popup-title" id="popup-title">Erro de Login</div>
            <div class="popup-message" id="popup-message">RA ou senha incorretos!</div>
            <button class="popup-button" onclick="closePopup()">
                <i class="fa-solid fa-check"></i> OK
            </button>
        </div>
    </div>

    <div class="login-container">
        <div class="header">
            <h1><i class="fa-solid fa-user-tie"></i> Área do Funcionário</h1>
            <p>Sistema de Gerenciamento do Biotério FSA</p>
        </div>

        <div class="login-info">
            <h4><i class="fa-solid fa-info-circle"></i> Tipos de Acesso</h4>
            <p><strong>Administrador:</strong> Acesso total ao sistema</p>
            <p><strong>Operador:</strong> Visualização e relatórios</p>
        </div>

        <?php if (isset($_GET['erro_login'])): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    showPopup();
                });
            </script>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="ra">
                    <i class="fa-solid fa-id-badge"></i> RA:
                </label>
                <input type="text" id="ra" name="ra" placeholder="Digite seu RA" required autocomplete="username">
            </div>

            <div class="form-group">
                <label for="senha">
                    <i class="fa-solid fa-lock"></i> Senha:
                </label>
                <input type="password" id="senha" name="senha" placeholder="Digite sua senha" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn-login">
                <i class="fa-solid fa-sign-in-alt"></i> Entrar
            </button>
        </form>

        <div class="help-text">
            <p>Precisa de ajuda? <a href="#"><i class="fa-solid fa-question-circle"></i> Contate o suporte</a></p>
        </div>

        <div class="voltar">
            <a href="../front-end/pag_inicial.html">
                <i class="fa-solid fa-arrow-left"></i> Voltar à Página Inicial
            </a>
        </div>
    </div>

    <script>
        function showPopup() {
            document.getElementById('popup-overlay').style.display = 'flex';
        }

        function closePopup() {
            document.getElementById('popup-overlay').style.display = 'none';
            // Limpar URL para remover parâmetro de erro
            if (window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        }

        // Fechar popup com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePopup();
            }
        });
    </script>
</body>
</html>