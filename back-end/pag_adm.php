<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="back-end-style\style_pag_adm.css">
    <title>Biotério - Login Funcionário</title>
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

    <script src="back-end-javascript\js_pag_adm.js"></script>
</body>
</html>