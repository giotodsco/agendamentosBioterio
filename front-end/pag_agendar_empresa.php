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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <title>Biotério - Agendamento Empresa</title>
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
            display: flex;
            flex-direction: column;
        }

        .header {
            background-color: rgba(64, 122, 53, 0.9);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .btn-back {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 10px 18px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back:hover {
            background-color: rgba(255, 255, 255, 0.3);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .company-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .company-info span {
            color: white;
            font-size: 16px;
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

        .main-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background-color: rgb(225, 225, 228);
            width: 100%;
            max-width: 700px;
            border-radius: 20px;
            box-shadow: 5px 5px 50px rgba(90, 90, 90, 0.392);
            overflow: hidden;
        }

        .form-header {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: white;
            padding: 25px;
            text-align: center;
        }

        .form-header h1 {
            color: white;
            font-size: 28px;
            margin-bottom: 10px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }

        .form-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
        }

        .form-container {
            padding: 30px;
        }

        .company-welcome {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, rgba(255, 193, 7, 0.05) 100%);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            border-left: 5px solid #ffc107;
        }

        .company-welcome h3 {
            color: #856404;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .company-welcome p {
            font-size: 14px;
            color: #856404;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 16px;
            color: rgb(60, 59, 59);
            font-weight: bold;
        }

        select, input[type="number"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 10px;
            background-color: rgb(240, 240, 240);
            font-size: 16px;
            font-family: Georgia, 'Times New Roman', Times, serif;
            transition: border-color 0.3s;
            cursor: pointer;
        }

        select:focus, input:focus {
            outline: none;
            border-color: #ffc107;
            background-color: rgb(250, 250, 250);
        }

        .horarios-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .horario-item {
            position: relative;
        }

        .horario-radio {
            display: none;
        }

        .horario-label {
            display: block;
            padding: 12px;
            text-align: center;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            font-weight: bold;
            background-color: white;
        }

        .horario-label:hover {
            border-color: #ffc107;
            background-color: rgba(255, 193, 7, 0.1);
        }

        .horario-radio:checked + .horario-label {
            background-color: #ffc107;
            color: white;
            border-color: #ffc107;
        }

        .quantity-info {
            background-color: rgba(255, 193, 7, 0.1);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #ffc107;
        }

        .quantity-info h4 {
            color: #856404;
            margin-bottom: 10px;
        }

        .quantity-info p {
            font-size: 14px;
            margin-bottom: 5px;
            color: #856404;
        }

        .pending-notice {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            border-left: 5px solid #ffc107;
            text-align: center;
        }

        .pending-notice h4 {
            color: #856404;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .pending-notice p {
            color: #856404;
            font-size: 14px;
            line-height: 1.5;
        }

        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-family: Georgia, 'Times New Roman', Times, serif;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            font-weight: bold;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #e0a800 0%, #d39e00 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 193, 7, 0.3);
        }

        .btn-primary:disabled {
            background-color: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-secondary {
            background-color: rgb(200, 200, 200);
            color: rgb(60, 59, 59);
        }

        .btn-secondary:hover {
            background-color: rgb(180, 180, 180);
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            font-weight: bold;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #ffc107;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        /* Pop-up personalizado */
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
            color: #dc3545;
        }

        .popup-icon.warning {
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

        .popup-btn-primary {
            background-color: #ffc107;
            color: white;
        }

        .popup-btn-primary:hover {
            background-color: #e0a800;
        }

        .popup-btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .popup-btn-secondary:hover {
            background-color: #5a6268;
        }

        @media (max-width: 768px) {
            .header {
                padding: 10px 15px;
                flex-direction: column;
                gap: 15px;
            }

            .header-left {
                width: 100%;
                justify-content: space-between;
            }
            
            .main-container {
                padding: 10px;
            }
            
            .container {
                margin: 0;
            }
            
            .form-container {
                padding: 20px;
            }
            
            .horarios-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .action-buttons {
                flex-direction: column;
            }

            .popup-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Pop-up personalizado -->
    <div class="custom-popup-overlay" id="popup-overlay">
        <div class="custom-popup">
            <div class="popup-icon" id="popup-icon">
                <i class="fa-solid fa-exclamation-triangle"></i>
            </div>
            <div class="popup-title" id="popup-title">Atenção</div>
            <div class="popup-message" id="popup-message">Mensagem do sistema</div>
            <div class="popup-buttons" id="popup-buttons">
                <button class="popup-btn popup-btn-primary" onclick="closePopup()">OK</button>
            </div>
        </div>
    </div>

    <div class="header">
        <div class="header-left">
            <a href="pag_inicial.html" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i> 
                <span>Voltar ao Início</span>
            </a>
            <div class="company-info">
                <i class="fa-solid fa-building"></i>
                <span><?php echo htmlspecialchars($_SESSION['empresa_nome']); ?></span>
            </div>
        </div>
        <div>
            <a href="#" class="btn-logout" onclick="showLogoutConfirm(event)">
                <i class="fa-solid fa-sign-out-alt"></i> Sair
            </a>
            <form id="logout-form" action="../back-end/auth_empresa.php" method="POST" style="display: none;">
                <input type="hidden" name="acao" value="logout_empresa">
            </form>
        </div>
    </div>

    <div class="main-container">
        <div class="container">
            <div class="form-header">
                <h1><i class="fa-solid fa-building"></i> Agendamento Empresarial</h1>
                <p>Sistema de Agendamento para Empresas/Instituições</p>
            </div>
            
            <div class="form-container">
                <div id="error-message" class="alert alert-danger" style="display: none;"></div>

                <div class="company-welcome">
                    <h3><i class="fa-solid fa-handshake"></i> Agendamento Institucional</h3>
                    <p>Sua empresa pode agendar visitas para grupos. Preencha os dados abaixo e aguarde nossa confirmação.</p>
                </div>

                <div class="pending-notice">
                    <h4><i class="fa-solid fa-clock"></i> Aprovação Necessária</h4>
                    <p>Todos os agendamentos de empresas passam por análise e aprovação da administração antes da confirmação.</p>
                </div>
                
                <div class="quantity-info">
                    <h4><i class="fa-solid fa-users"></i> Informações sobre Quantidade</h4>
                    <p><strong>Mínimo:</strong> 1 pessoa</p>
                    <p><strong>Máximo:</strong> 20 pessoas por agendamento</p>
                    <p><strong>Horário:</strong> Segunda a Sexta, das 10:00 às 18:00</p>
                </div>

                <form id="agendamento-form" method="POST" action="../back-end/agendar_empresa.php">
                    <div class="form-group">
                        <label for="data_agendamento">
                            <i class="fa-solid fa-calendar"></i> Escolha a Data:
                        </label>
                        <select id="data_agendamento" name="data_agendamento" required>
                            <option value="">Selecione uma data...</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fa-solid fa-clock"></i> Escolha o Horário:
                        </label>
                        <div class="horarios-grid" id="horarios-container">
                            <!-- Horários serão carregados via JavaScript -->
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="quantidade_pessoas">
                            <i class="fa-solid fa-users"></i> Quantidade de Pessoas:
                        </label>
                        <input type="number" id="quantidade_pessoas" name="quantidade_pessoas" 
                               min="1" max="20" value="1" required>
                    </div>

                    <div class="loading" id="loading">
                        <div class="spinner"></div>
                        <p>Processando agendamento...</p>
                    </div>

                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary" id="btn-agendar" disabled>
                            <i class="fa-solid fa-paper-plane"></i> Solicitar Agendamento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('agendamento-form');
            const dataSelect = document.getElementById('data_agendamento');
            const horariosContainer = document.getElementById('horarios-container');
            const btnAgendar = document.getElementById('btn-agendar');
            const loading = document.getElementById('loading');

            // Horários disponíveis (10:00 às 18:00, de 30 em 30 min)
            const horariosDisponiveis = [
                '10:00', '10:30', '11:00', '11:30', '12:00', '12:30',
                '13:00', '13:30', '14:00', '14:30', '15:00', '15:30',
                '16:00', '16:30', '17:00', '17:30', '18:00'
            ];

            // Função para gerar datas disponíveis (próximos 30 dias úteis)
            function gerarDatasDisponiveis() {
                const hoje = new Date();
                const datas = [];
                let diasAdicionados = 0;
                let dataAtual = new Date(hoje);

                while (diasAdicionados < 30) {
                    dataAtual.setDate(dataAtual.getDate() + 1);
                    
                    // Verifica se é dia útil (1=segunda, 5=sexta)
                    const diaSemana = dataAtual.getDay();
                    if (diaSemana >= 1 && diaSemana <= 5) {
                        const ano = dataAtual.getFullYear();
                        const mes = String(dataAtual.getMonth() + 1).padStart(2, '0');
                        const dia = String(dataAtual.getDate()).padStart(2, '0');
                        const dataFormatada = `${ano}-${mes}-${dia}`;
                        const dataExibicao = `${dia}/${mes}/${ano}`;
                        
                        const nomeDia = dataAtual.toLocaleDateString('pt-BR', { weekday: 'long' });
                        
                        datas.push({
                            valor: dataFormatada,
                            texto: `${dataExibicao} - ${nomeDia.charAt(0).toUpperCase() + nomeDia.slice(1)}`
                        });
                        
                        diasAdicionados++;
                    }
                }

                return datas;
            }

            // Carregar datas disponíveis
            function carregarDatas() {
                const datas = gerarDatasDisponiveis();
                
                dataSelect.innerHTML = '<option value="">Selecione uma data...</option>';
                
                datas.forEach(data => {
                    const option = document.createElement('option');
                    option.value = data.valor;
                    option.textContent = data.texto;
                    dataSelect.appendChild(option);
                });
            }

            // Carregar horários
            function carregarHorarios() {
                horariosContainer.innerHTML = '';
                
                horariosDisponiveis.forEach(horario => {
                    const div = document.createElement('div');
                    div.className = 'horario-item';
                    
                    const input = document.createElement('input');
                    input.type = 'radio';
                    input.name = 'hora_agendamento';
                    input.value = horario;
                    input.id = `horario-${horario.replace(':', '')}`;
                    input.className = 'horario-radio';
                    input.required = true;
                    
                    const label = document.createElement('label');
                    label.htmlFor = input.id;
                    label.className = 'horario-label';
                    label.textContent = horario;
                    
                    div.appendChild(input);
                    div.appendChild(label);
                    horariosContainer.appendChild(div);
                    
                    input.addEventListener('change', verificarFormCompleto);
                });
            }

            // Verificar se o formulário está completo
            function verificarFormCompleto() {
                const data = dataSelect.value;
                const horarioSelecionado = document.querySelector('input[name="hora_agendamento"]:checked');
                const quantidade = document.getElementById('quantidade_pessoas').value;
                
                const formCompleto = data && horarioSelecionado && quantidade >= 1 && quantidade <= 20;
                btnAgendar.disabled = !formCompleto;
            }

            // Event listeners
            dataSelect.addEventListener('change', verificarFormCompleto);
            document.getElementById('quantidade_pessoas').addEventListener('input', verificarFormCompleto);

            // Validação antes do envio
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const data = dataSelect.value;
                const horarioSelecionado = document.querySelector('input[name="hora_agendamento"]:checked');
                const quantidade = parseInt(document.getElementById('quantidade_pessoas').value);
                
                if (!data || !horarioSelecionado) {
                    showPopup('Erro', 'Por favor, selecione uma data e horário.', 'error');
                    return false;
                }

                if (quantidade < 1 || quantidade > 20) {
                    showPopup('Erro', 'A quantidade de pessoas deve estar entre 1 e 20.', 'error');
                    return false;
                }

                // Mostrar loading
                loading.style.display = 'block';
                btnAgendar.disabled = true;

                // Enviar formulário
                setTimeout(() => {
                    form.submit();
                }, 1000);
            });

            // Verificar mensagens da URL
            const urlParams = new URLSearchParams(window.location.search);
            const erro = urlParams.get('erro');

            if (erro) {
                showPopup('Erro', decodeURIComponent(erro), 'error');
                // Limpar URL
                window.history.replaceState({}, document.title, window.location.pathname);
            }

            // Inicializar
            carregarDatas();
            carregarHorarios();
        });

        // Sistema de pop-up personalizado
        function showPopup(title, message, type = 'info', buttons = null) {
            const overlay = document.getElementById('popup-overlay');
            const titleElement = document.getElementById('popup-title');
            const messageElement = document.getElementById('popup-message');
            const iconElement = document.getElementById('popup-icon');
            const buttonsContainer = document.getElementById('popup-buttons');
            
            titleElement.textContent = title;
            messageElement.textContent = message;
            
            // Configurar ícone baseado no tipo
            let iconClass = 'fa-info-circle';
            let iconColorClass = '';
            
            switch(type) {
                case 'error':
                    iconClass = 'fa-exclamation-triangle';
                    iconColorClass = '';
                    break;
                case 'warning':
                    iconClass = 'fa-exclamation-triangle';
                    iconColorClass = 'warning';
                    break;
                default:
                    iconClass = 'fa-info-circle';
                    iconColorClass = '';
            }
            
            iconElement.innerHTML = `<i class="fa-solid ${iconClass}"></i>`;
            iconElement.className = `popup-icon ${iconColorClass}`;
            
            // Configurar botões
            if (buttons) {
                buttonsContainer.innerHTML = '';
                buttons.forEach(button => {
                    const btn = document.createElement('button');
                    btn.className = `popup-btn ${button.class || 'popup-btn-primary'}`;
                    btn.textContent = button.text;
                    btn.onclick = button.action;
                    buttonsContainer.appendChild(btn);
                });
            } else {
                buttonsContainer.innerHTML = '<button class="popup-btn popup-btn-primary" onclick="closePopup()">OK</button>';
            }
            
            overlay.style.display = 'flex';
        }

        function closePopup() {
            document.getElementById('popup-overlay').style.display = 'none';
        }

        // Função para confirmar logout
        function showLogoutConfirm(event) {
            event.preventDefault();
            
            showPopup(
                'Confirmar Saída', 
                'Tem certeza que deseja sair?', 
                'warning',
                [
                    {
                        text: 'Sim, Sair',
                        class: 'popup-btn-primary',
                        action: function() {
                            closePopup();
                            document.getElementById('logout-form').submit();
                        }
                    },
                    {
                        text: 'Cancelar',
                        class: 'popup-btn-secondary',
                        action: closePopup
                    }
                ]
            );
        }
    </script>
</body>
</html>