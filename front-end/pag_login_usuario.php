<?php
session_start();
// Recuperar dados salvos na sessão para manter no formulário
$cadastro_dados = $_SESSION['cadastro_dados'] ?? [];
// Limpar da sessão após usar
if (isset($_SESSION['cadastro_dados'])) {
    unset($_SESSION['cadastro_dados']);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <title>Biotério - Login de Usuário</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Georgia, 'Times New Roman', Times, serif;
            background: radial-gradient(circle, rgba(173,199,205,1) 0%, rgba(169,189,165,1) 31%, rgba(64, 122, 53, 0.819) 85%);
            min-height: 100vh;
            overflow-x: hidden;
            padding: 10px;
        }

        /* MELHORADO: Container principal com melhor controle de altura */
        .login-container {
            display: flex;
            max-width: 1100px;
            width: 100%;
            margin: 0 auto;
            background-color: rgb(225, 225, 228);
            border-radius: 20px;
            box-shadow: 5px 5px 50px rgba(90, 90, 90, 0.392);
            overflow: hidden;
            min-height: calc(100vh - 20px);
            max-height: calc(100vh - 20px);
        }

        /* MELHORADO: Seção de imagem com altura responsiva */
        .login-image {
            flex: 1;
            min-height: 300px;
            background-image:
                radial-gradient(circle, rgba(121, 125, 125, 0.43) 0%, rgba(101, 113, 98, 0.626) 31%, rgba(64,122,53,0.36) 85%),
                url('https://www.fsa.br/wp-content/uploads/2019/02/d79abec1-2674-42b2-9873-431fbdaa9007.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            border-radius: 1px 50px 50px 1px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .image-overlay {
            background-color: rgba(64, 122, 53, 0.15);
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            color: white;
            backdrop-filter: blur(5px);
            max-width: 90%;
        }

        .image-overlay h2 {
            color: white;
            font-size: 24px;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
        }

        .image-overlay p {
            color: rgba(255, 255, 255, 0.95);
            font-size: 14px;
            line-height: 1.6;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);
        }

        /* MELHORADO: Seção de formulário com scroll controlado */
        .login-form-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: calc(100vh - 20px);
            max-height: calc(100vh - 20px);
            overflow: hidden;
        }

        /* MELHORADO: Header fixo para manter título sempre visível */
        .form-header {
            background: linear-gradient(135deg, rgba(64, 122, 53, 0.1) 0%, rgba(64, 122, 53, 0.05) 100%);
            padding: 20px 30px;
            border-bottom: 1px solid rgba(64, 122, 53, 0.1);
            flex-shrink: 0;
        }

        .main-title {
            color: rgb(55, 75, 51);
            font-size: 28px;
            text-align: center;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .subtitle {
            color: rgb(100, 100, 100);
            font-size: 14px;
            text-align: center;
        }

        /* MELHORADO: Área de conteúdo com scroll suave */
        .form-content {
            flex: 1;
            overflow-y: auto;
            padding: 20px 30px 30px;
        }

        /* MELHORADO: Barra de rolagem personalizada */
        .form-content::-webkit-scrollbar {
            width: 8px;
        }

        .form-content::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 4px;
        }

        .form-content::-webkit-scrollbar-thumb {
            background: rgba(64, 122, 53, 0.3);
            border-radius: 4px;
            transition: all 0.3s;
        }

        .form-content::-webkit-scrollbar-thumb:hover {
            background: rgba(64, 122, 53, 0.6);
        }

        /* MELHORADO: Tabs mais compactas */
        .tabs {
            display: flex;
            margin-bottom: 25px;
            background-color: rgb(200, 200, 200);
            border-radius: 10px;
            overflow: hidden;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .tab {
            flex: 1;
            padding: 12px 8px;
            text-align: center;
            cursor: pointer;
            border: none;
            background-color: transparent;
            font-size: 14px;
            font-family: Georgia, 'Times New Roman', Times, serif;
            transition: all 0.3s;
            font-weight: bold;
            color: rgb(60, 59, 59);
        }

        .tab.active {
            background-color: rgba(64, 122, 53, 0.819);
            color: white;
        }

        .tab:hover:not(.active) {
            background-color: rgb(180, 180, 180);
        }

        .tab.empresa-tab {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, rgba(255, 193, 7, 0.05) 100%);
            transition: all 0.3s;
        }

        .tab.empresa-tab:hover {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.2) 0%, rgba(255, 193, 7, 0.1) 100%);
        }

        .tab.empresa-tab.active {
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
            color: white;
        }

        .form-section {
            display: none;
        }

        .form-section.active {
            display: block;
        }

        /* MELHORADO: Seções com melhor espaçamento */
        .section-title {
            color: rgba(64, 122, 53, 0.819);
            font-size: 22px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 700;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            color: rgb(60, 59, 59);
            font-weight: bold;
        }

        .input-container {
            position: relative;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #ddd;
            border-radius: 8px;
            background-color: rgb(240, 240, 240);
            font-size: 14px;
            font-family: Georgia, 'Times New Roman', Times, serif;
            transition: all 0.3s;
        }

        input:focus {
            outline: none;
            background-color: rgb(250, 250, 250);
            border-color: rgba(64, 122, 53, 0.819);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(64, 122, 53, 0.15);
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(64, 122, 53, 0.6);
            font-size: 16px;
        }

        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-family: Georgia, 'Times New Roman', Times, serif;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
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

        .btn-empresa {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: white;
        }

        .btn-empresa:hover {
            background: linear-gradient(135deg, #e0a800 0%, #d39e00 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 193, 7, 0.3);
        }

        .btn-secondary {
            background-color: rgb(200, 200, 200);
            color: rgb(60, 59, 59);
            font-size: 13px;
            padding: 8px 15px;
        }

        .btn-secondary:hover {
            background-color: rgb(180, 180, 180);
            transform: translateY(-1px);
        }

        /* MELHORADO: Cards informativos mais compactos */
        .welcome-info {
            background: linear-gradient(135deg, rgba(64, 122, 53, 0.1) 0%, rgba(64, 122, 53, 0.05) 100%);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid rgba(64, 122, 53, 0.819);
        }

        .welcome-info h3 {
            color: rgba(64, 122, 53, 0.819);
            margin-bottom: 8px;
            font-size: 16px;
        }

        .welcome-info p {
            color: rgb(60, 59, 59);
            font-size: 13px;
            line-height: 1.4;
        }

        .empresa-section {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, rgba(255, 193, 7, 0.05) 100%);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #ffc107;
        }

        .empresa-section h3 {
            color: #856404;
            margin-bottom: 8px;
            font-size: 16px;
        }

        .empresa-section p {
            color: #856404;
            font-size: 13px;
            line-height: 1.4;
        }

        .login-unificado-info {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #2196f3;
            text-align: center;
        }

        .login-unificado-info h4 {
            color: #1976d2;
            margin-bottom: 6px;
            font-size: 14px;
        }

        .login-unificado-info p {
            color: #1976d2;
            font-size: 12px;
            line-height: 1.3;
        }

        /* MELHORADO: Links de cadastro mais organizados */
        .cadastro-links {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .cadastro-links p {
            text-align: center;
            margin: 15px 0;
            color: rgb(100, 100, 100);
            font-size: 13px;
        }

        .cadastro-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .link {
            text-align: center;
            margin-top: 15px;
        }

        .link a {
            color: rgba(64, 122, 53, 0.819);
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
        }

        .link a:hover {
            text-decoration: underline;
            color: rgba(44, 81, 36, 0.819);
            transform: translateX(-3px);
        }

        /* Pop-up personalizado */
        .custom-popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            backdrop-filter: blur(3px);
        }

        .custom-popup {
            background-color: rgb(225, 225, 228);
            border-radius: 15px;
            padding: 30px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
            animation: popupSlideIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 2px solid rgba(64, 122, 53, 0.1);
        }

        @keyframes popupSlideIn {
            from {
                opacity: 0;
                transform: scale(0.7) translateY(-30px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .popup-icon {
            font-size: 50px;
            margin-bottom: 15px;
            color: #dc3545;
        }

        .popup-icon.success {
            color: #28a745;
        }

        .popup-icon.warning {
            color: #ffc107;
        }

        .popup-icon.info {
            color: #17a2b8;
        }

        .popup-title {
            font-size: 20px;
            font-weight: bold;
            color: rgb(55, 75, 51);
            margin-bottom: 12px;
        }

        .popup-message {
            font-size: 14px;
            color: rgb(60, 59, 59);
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .popup-btn {
            padding: 10px 25px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            font-family: Georgia, 'Times New Roman', Times, serif;
            background-color: rgba(64, 122, 53, 0.819);
            color: white;
        }

        .popup-btn:hover {
            background-color: rgba(44, 81, 36, 0.819);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(64, 122, 53, 0.3);
        }

        /* RESPONSIVO MELHORADO */
        @media (max-width: 1024px) {
            .login-container {
                flex-direction: column;
                max-width: 600px;
                min-height: auto;
                max-height: none;
            }
            
            .login-image {
                min-height: 200px;
                border-radius: 50px 50px 1px 1px;
            }
            
            .login-form-section {
                height: auto;
                max-height: none;
            }

            .form-content {
                max-height: 70vh;
            }
            
            .image-overlay h2 {
                font-size: 20px;
            }

            .image-overlay p {
                font-size: 13px;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 5px;
            }

            .login-container {
                min-height: calc(100vh - 10px);
                border-radius: 15px;
            }
            
            .form-header,
            .form-content {
                padding: 15px 20px;
            }
            
            .main-title {
                font-size: 24px;
            }
            
            .image-overlay {
                padding: 20px;
            }

            .tabs {
                margin-bottom: 20px;
            }

            .tab {
                font-size: 12px;
                padding: 10px 6px;
            }

            .cadastro-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn-secondary {
                width: 100%;
                max-width: 200px;
            }

            .custom-popup {
                padding: 20px;
                margin: 20px;
            }
        }

        @media (max-width: 480px) {
            .form-header,
            .form-content {
                padding: 12px 15px;
            }

            .main-title {
                font-size: 20px;
            }

            .section-title {
                font-size: 18px;
            }

            input[type="text"],
            input[type="email"],
            input[type="password"] {
                padding: 10px 12px 10px 38px;
                font-size: 14px;
            }

            .btn {
                padding: 10px;
                font-size: 14px;
            }
        }

        /* MELHORAMENTOS VISUAIS EXTRAS */
        .form-section {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .input-container:focus-within .input-icon {
            color: rgba(64, 122, 53, 0.819);
            transform: translateY(-50%) scale(1.1);
        }

        /* Loading state para botões */
        .btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none !important;
        }

        .btn.loading::after {
            content: '';
            width: 16px;
            height: 16px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 8px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Pop-up personalizado -->
    <div class="custom-popup-overlay" id="popup-overlay">
        <div class="custom-popup">
            <div class="popup-icon" id="popup-icon">
                <i class="fa-solid fa-check-circle"></i>
            </div>
            <div class="popup-title" id="popup-title">Sucesso!</div>
            <div class="popup-message" id="popup-message">Operação realizada com sucesso!</div>
            <button class="popup-btn" id="popup-btn" onclick="closePopup()">OK</button>
        </div>
    </div>

    <div class="login-container">
        <div class="login-image">
            <div class="image-overlay">
                <h2><i class="fa-solid fa-seedling"></i> Espaço de Biodiversidade FSA</h2>
                <p>Seu portal de acesso ao sistema de agendamentos. Faça parte da nossa comunidade científica e contribua para o avanço da pesquisa!</p>
            </div>
        </div>
        
        <div class="login-form-section">
            <!-- MELHORADO: Header fixo -->
            <div class="form-header">
                <h1 class="main-title">Área de Acesso</h1>
                <p class="subtitle">Sistema de Agendamento do Espaço de Biodiversidade FSA</p>
            </div>

            <!-- MELHORADO: Conteúdo com scroll -->
            <div class="form-content">
                <div class="tabs">
                    <button class="tab active" onclick="showTab('login')">
                        <i class="fa-solid fa-sign-in-alt"></i> Login
                    </button>
                    <button class="tab" onclick="showTab('cadastro')">
                        <i class="fa-solid fa-user-plus"></i> Pessoa Física
                    </button>
                    <button class="tab empresa-tab" onclick="showTab('empresa')">
                        <i class="fa-solid fa-building"></i> Empresa
                    </button>
                </div>
                
                <!-- Seção de Login UNIFICADO -->
                <div id="login-section" class="form-section active">
                    <h2 class="section-title">Bem-vindo de volta!</h2>
                    
                    <div class="login-unificado-info">
                        <h4><i class="fa-solid fa-info-circle"></i> Login Unificado</h4>
                        <p>Pessoas físicas: Digite seu <strong>email</strong><br>
                        Empresas: Digite seu <strong>email</strong> ou <strong>CNPJ</strong></p>
                    </div>
                    
                    <div class="welcome-info">
                        <h3><i class="fa-solid fa-key"></i> Acesse sua conta</h3>
                        <p>Entre com seus dados para acessar o sistema de agendamentos e gerenciar suas visitas ao espaço de biodiversidade</p>
                    </div>
                    
                    <form action="../back-end/auth_unificado.php" method="POST" id="login-form">
                        <input type="hidden" name="acao" value="login">
                        <?php if (isset($_GET['redirect_to'])): ?>
                            <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($_GET['redirect_to']); ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="login_field">
                                <i class="fa-solid fa-user"></i> Email ou CNPJ:
                            </label>
                            <div class="input-container">
                                <i class="fa-solid fa-user input-icon"></i>
                                <input type="text" id="login_field" name="login" placeholder="Digite seu email ou CNPJ" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="senha_login">
                                <i class="fa-solid fa-lock"></i> Senha:
                            </label>
                            <div class="input-container">
                                <i class="fa-solid fa-lock input-icon"></i>
                                <input type="password" id="senha_login" name="senha" placeholder="Digite sua senha" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-sign-in-alt"></i>
                            Entrar
                        </button>
                    </form>
                    
                    <div class="cadastro-links">
                        <p>Ainda não tem conta?</p>
                        <div class="cadastro-buttons">
                            <button type="button" onclick="showTab('cadastro')" class="btn btn-secondary">
                                <i class="fa-solid fa-user-plus"></i> Cadastrar Pessoa Física
                            </button>
                            <button type="button" onclick="showTab('empresa')" class="btn btn-secondary" style="background: linear-gradient(135deg, rgba(255, 193, 7, 0.8) 0%, rgba(255, 193, 7, 0.6) 100%); color: #856404;">
                                <i class="fa-solid fa-building"></i> Cadastrar Empresa
                            </button>
                        </div>
                    </div>

                    <div class="link">
                        <a href="pag_inicial.html">
                            <i class="fa-solid fa-arrow-left"></i> Voltar à Página Inicial
                        </a>
                    </div>
                </div>
                
                <!-- Seção de Cadastro Pessoa Física -->
                <div id="cadastro-section" class="form-section">
                    <h2 class="section-title">Cadastro Pessoa Física</h2>
                    
                    <div class="welcome-info">
                        <h3><i class="fa-solid fa-user-plus"></i> Cadastro Individual</h3>
                        <p>Crie sua conta pessoal para ter acesso ao sistema de agendamentos do espaço de biodiversidade</p>
                    </div>
                    
                    <form action="../back-end/auth_unificado.php" method="POST" id="cadastro-form">
                        <input type="hidden" name="acao" value="cadastro">
                        
                        <div class="form-group">
                            <label for="nome_cadastro">
                                <i class="fa-solid fa-user"></i> Nome Completo:
                            </label>
                            <div class="input-container">
                                <i class="fa-solid fa-user input-icon"></i>
                                <input type="text" id="nome_cadastro" name="nome" placeholder="Digite seu nome completo" value="<?php echo htmlspecialchars($cadastro_dados['nome'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email_cadastro">
                                <i class="fa-solid fa-envelope"></i> Email:
                            </label>
                            <div class="input-container">
                                <i class="fa-solid fa-envelope input-icon"></i>
                                <input type="email" id="email_cadastro" name="email" placeholder="Digite seu email" value="<?php echo htmlspecialchars($cadastro_dados['email'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="cpf_cadastro">
                                <i class="fa-solid fa-id-card"></i> CPF:
                            </label>
                            <div class="input-container">
                                <i class="fa-solid fa-id-card input-icon"></i>
                                <input type="text" id="cpf_cadastro" name="cpf" placeholder="000.000.000-00" maxlength="14" value="<?php echo htmlspecialchars($cadastro_dados['cpf'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="senha_cadastro">
                                <i class="fa-solid fa-lock"></i> Senha:
                            </label>
                            <div class="input-container">
                                <i class="fa-solid fa-lock input-icon"></i>
                                <input type="password" id="senha_cadastro" name="senha" placeholder="Mínimo 6 caracteres" minlength="6" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirmar_senha">
                                <i class="fa-solid fa-lock"></i> Confirmar Senha:
                            </label>
                            <div class="input-container">
                                <i class="fa-solid fa-lock input-icon"></i>
                                <input type="password" id="confirmar_senha" name="confirmar_senha" placeholder="Digite a senha novamente" minlength="6" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-user-plus"></i>
                            Criar Conta
                        </button>
                    </form>
                    
                    <div class="link">
                        <a href="pag_inicial.html">
                            <i class="fa-solid fa-arrow-left"></i> Voltar à Página Inicial
                        </a>
                    </div>
                </div>

                <!-- Seção de Cadastro Empresa -->
                <div id="empresa-section" class="form-section">
                    <h2 class="section-title">Cadastro Empresa</h2>
                    
                    <div class="empresa-section">
                        <h3><i class="fa-solid fa-building"></i> Cadastro Institucional</h3>
                        <p>Registre sua empresa ou instituição para ter acesso ao sistema de agendamentos com condições especiais para grupos.</p>
                    </div>
                    
                    <form action="../back-end/auth_unificado.php" method="POST" id="empresa-form">
                        <input type="hidden" name="acao" value="cadastro_empresa">
                        
                        <div class="form-group">
                            <label for="nome_instituicao">
                                <i class="fa-solid fa-building"></i> Nome da Instituição:
                            </label>
                            <div class="input-container">
                                <i class="fa-solid fa-building input-icon"></i>
                                <input type="text" id="nome_instituicao" name="nome_instituicao" placeholder="Digite o nome da empresa/instituição" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="cnpj_empresa">
                                <i class="fa-solid fa-file-alt"></i> CNPJ:
                            </label>
                            <div class="input-container">
                                <i class="fa-solid fa-file-alt input-icon"></i>
                                <input type="text" id="cnpj_empresa" name="cnpj" placeholder="00.000.000/0000-00" maxlength="18" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email_empresa">
                                <i class="fa-solid fa-envelope"></i> Email Corporativo:
                            </label>
                            <div class="input-container">
                                <i class="fa-solid fa-envelope input-icon"></i>
                                <input type="email" id="email_empresa" name="email" placeholder="Digite o email da empresa" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="senha_empresa">
                                <i class="fa-solid fa-lock"></i> Senha:
                            </label>
                            <div class="input-container">
                                <i class="fa-solid fa-lock input-icon"></i>
                                <input type="password" id="senha_empresa" name="senha" placeholder="Mínimo 6 caracteres" minlength="6" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirmar_senha_empresa">
                                <i class="fa-solid fa-lock"></i> Confirmar Senha:
                            </label>
                            <div class="input-container">
                                <i class="fa-solid fa-lock input-icon"></i>
                                <input type="password" id="confirmar_senha_empresa" name="confirmar_senha" placeholder="Digite a senha novamente" minlength="6" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-empresa">
                            <i class="fa-solid fa-building"></i>
                            Cadastrar Empresa
                        </button>
                    </form>
                    
                    <div class="link">
                        <a href="pag_inicial.html">
                            <i class="fa-solid fa-arrow-left"></i> Voltar à Página Inicial
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Validação melhorada de CPF
        function validarCPF(cpf) {
            cpf = cpf.replace(/\D/g, '');
            
            // Verificações básicas
            if (cpf.length !== 11) return false;
            if (/^(\d)\1{10}$/.test(cpf)) return false;
            
            // Primeiro dígito verificador
            let soma = 0;
            for (let i = 0; i < 9; i++) {
                soma += parseInt(cpf.charAt(i)) * (10 - i);
            }
            let resto = 11 - (soma % 11);
            let dv1 = resto < 2 ? 0 : resto;
            
            if (parseInt(cpf.charAt(9)) !== dv1) return false;
            
            // Segundo dígito verificador
            soma = 0;
            for (let i = 0; i < 10; i++) {
                soma += parseInt(cpf.charAt(i)) * (11 - i);
            }
            resto = 11 - (soma % 11);
            let dv2 = resto < 2 ? 0 : resto;
            
            return parseInt(cpf.charAt(10)) === dv2;
        }

        // Validação melhorada de CNPJ
        function validarCNPJ(cnpj) {
            cnpj = cnpj.replace(/\D/g, '');
            
            if (cnpj.length !== 14) return false;
            if (/^(\d)\1{13}$/.test(cnpj)) return false;
            
            let tamanho = cnpj.length - 2;
            let numeros = cnpj.substring(0, tamanho);
            let digitos = cnpj.substring(tamanho);
            let soma = 0;
            let pos = tamanho - 7;
            
            for (let i = tamanho; i >= 1; i--) {
                soma += numeros.charAt(tamanho - i) * pos--;
                if (pos < 2) pos = 9;
            }
            
            let resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
            if (resultado !== parseInt(digitos.charAt(0))) return false;
            
            tamanho = tamanho + 1;
            numeros = cnpj.substring(0, tamanho);
            soma = 0;
            pos = tamanho - 7;
            
            for (let i = tamanho; i >= 1; i--) {
                soma += numeros.charAt(tamanho - i) * pos--;
                if (pos < 2) pos = 9;
            }
            
            resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
            return resultado === parseInt(digitos.charAt(1));
        }

        function formatCPF(cpf) {
            cpf = cpf.replace(/\D/g, '');
            cpf = cpf.replace(/(\d{3})(\d)/, '$1.$2');
            cpf = cpf.replace(/(\d{3})(\d)/, '$1.$2');
            cpf = cpf.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            return cpf;
        }

        function formatCNPJ(cnpj) {
            cnpj = cnpj.replace(/\D/g, '');
            cnpj = cnpj.replace(/(\d{2})(\d)/, '$1.$2');
            cnpj = cnpj.replace(/(\d{3})(\d)/, '$1.$2');
            cnpj = cnpj.replace(/(\d{3})(\d)/, '$1/$2');
            cnpj = cnpj.replace(/(\d{4})(\d{1,2})$/, '$1-$2');
            return cnpj;
        }

        function showTab(tabName) {
            // Remover classes ativas
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.form-section').forEach(section => section.classList.remove('active'));
            
            // Adicionar classe ativa
            const tabs = document.querySelectorAll('.tab');
            if (tabName === 'login') {
                tabs[0].classList.add('active');
                document.getElementById('login-section').classList.add('active');
            } else if (tabName === 'cadastro') {
                tabs[1].classList.add('active');
                document.getElementById('cadastro-section').classList.add('active');
            } else if (tabName === 'empresa') {
                tabs[2].classList.add('active');
                document.getElementById('empresa-section').classList.add('active');
            }

            // Scroll para o topo do conteúdo
            document.querySelector('.form-content').scrollTop = 0;
        }

        function showPopup(title, message, type = 'success') {
            const overlay = document.getElementById('popup-overlay');
            const titleElement = document.getElementById('popup-title');
            const messageElement = document.getElementById('popup-message');
            const iconElement = document.getElementById('popup-icon');
            
            titleElement.textContent = title;
            messageElement.textContent = message;
            
            let iconClass = 'fa-check-circle';
            let iconColorClass = 'success';
            
            switch(type) {
                case 'error':
                    iconClass = 'fa-exclamation-triangle';
                    iconColorClass = '';
                    break;
                case 'warning':
                    iconClass = 'fa-exclamation-triangle';
                    iconColorClass = 'warning';
                    break;
                case 'info':
                    iconClass = 'fa-info-circle';
                    iconColorClass = 'info';
                    break;
            }
            
            iconElement.innerHTML = `<i class="fa-solid ${iconClass}"></i>`;
            iconElement.className = `popup-icon ${iconColorClass}`;
            
            overlay.style.display = 'flex';
        }

        function closePopup() {
            document.getElementById('popup-overlay').style.display = 'none';
        }

        // MELHORADO: Loading state para botões
        function setButtonLoading(button, loading = true) {
            if (loading) {
                button.disabled = true;
                button.classList.add('loading');
            } else {
                button.disabled = false;
                button.classList.remove('loading');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Abrir tab específico se especificado na URL
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab');
            
            if (tab === 'cadastro') {
                showTab('cadastro');
            } else if (tab === 'empresa') {
                showTab('empresa');
            }

            // Verificar mensagens de erro e sucesso via URL
            <?php if (isset($_GET['erro_login'])): ?>
                showPopup('Erro de Login', 'Email/CNPJ ou senha incorretos!', 'error');
            <?php endif; ?>
            
            <?php if (isset($_GET['cadastro_sucesso'])): ?>
                showPopup('Cadastro Realizado!', 'Cadastro realizado com sucesso! Faça login.', 'success');
            <?php endif; ?>
            
            <?php if (isset($_GET['login_required'])): ?>
                showPopup('Login Necessário', 'Você precisa fazer login para agendar!', 'warning');
            <?php endif; ?>

            <?php if (isset($_GET['erro_cadastro'])): ?>
                showPopup('Erro no Cadastro', '<?php echo addslashes($_GET['erro_cadastro']); ?>', 'error');
                showTab('cadastro');
            <?php endif; ?>

            <?php if (isset($_GET['erro_empresa'])): ?>
                showPopup('Erro no Cadastro', '<?php echo addslashes($_GET['erro_empresa']); ?>', 'error');
                showTab('empresa');
            <?php endif; ?>

            // Formatação automática de CPF
            const cpfInput = document.getElementById('cpf_cadastro');
            if (cpfInput) {
                cpfInput.addEventListener('input', function(e) {
                    e.target.value = formatCPF(e.target.value);
                });
            }

            // Formatação automática de CNPJ
            const cnpjInput = document.getElementById('cnpj_empresa');
            if (cnpjInput) {
                cnpjInput.addEventListener('input', function(e) {
                    e.target.value = formatCNPJ(e.target.value);
                });
            }

            // Formulário de login com loading
            const loginForm = document.getElementById('login-form');
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    const loginValue = document.getElementById('login_field').value.trim();
                    const senhaValue = document.getElementById('senha_login').value;
                    const submitBtn = this.querySelector('button[type="submit"]');
                    
                    if (!loginValue || !senhaValue) {
                        e.preventDefault();
                        showPopup('Campos Obrigatórios', 'Preencha email/CNPJ e senha.', 'error');
                        return;
                    }
                    
                    // Adicionar loading
                    setButtonLoading(submitBtn, true);
                });
            }

            // Validação em tempo real para formulários
            const cadastroForm = document.getElementById('cadastro-form');
            if (cadastroForm) {
                cadastroForm.addEventListener('submit', function(e) {
                    const cpf = document.getElementById('cpf_cadastro').value;
                    const senha = document.getElementById('senha_cadastro').value;
                    const confirmarSenha = document.getElementById('confirmar_senha').value;
                    const submitBtn = this.querySelector('button[type="submit"]');
                    
                    if (!validarCPF(cpf)) {
                        e.preventDefault();
                        showPopup('CPF Inválido', 'Por favor, digite um CPF válido.', 'error');
                        return;
                    }
                    
                    if (senha !== confirmarSenha) {
                        e.preventDefault();
                        showPopup('Senhas Diferentes', 'As senhas não coincidem.', 'error');
                        return;
                    }

                    setButtonLoading(submitBtn, true);
                });
            }

            const empresaForm = document.getElementById('empresa-form');
            if (empresaForm) {
                empresaForm.addEventListener('submit', function(e) {
                    const cnpj = document.getElementById('cnpj_empresa').value;
                    const senha = document.getElementById('senha_empresa').value;
                    const confirmarSenha = document.getElementById('confirmar_senha_empresa').value;
                    const submitBtn = this.querySelector('button[type="submit"]');
                    
                    if (!validarCNPJ(cnpj)) {
                        e.preventDefault();
                        showPopup('CNPJ Inválido', 'Por favor, digite um CNPJ válido.', 'error');
                        return;
                    }
                    
                    if (senha !== confirmarSenha) {
                        e.preventDefault();
                        showPopup('Senhas Diferentes', 'As senhas não coincidem.', 'error');
                        return;
                    }

                    setButtonLoading(submitBtn, true);
                });
            }
        });
    </script>
</body>
</html>