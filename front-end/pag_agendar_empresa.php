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
            padding: 20px;
            text-align: center;
        }

        .form-header h1 {
            color: white;
            font-size: 28px;
            margin-bottom: 10px;
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
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            border-left: 4px solid #ffc107;
        }

        .company-welcome h3 {
            color: #856404;
            margin-bottom: 10px;
        }

        .company-welcome p {
            font-size: 14px;
            color: #856404;
        }

        .pending-notice {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #ffc107;
            text-align: center;
        }

        .pending-notice h4 {
            color: #856404;
            margin-bottom: 8px;
            font-size: 16px;
        }

        .pending-notice p {
            color: #856404;
            font-size: 12px;
            line-height: 1.4;
        }

        .availability-info {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, rgba(255, 193, 7, 0.05) 100%);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #ffc107;
        }

        .availability-info h4 {
            color: #856404;
            margin-bottom: 10px;
        }

        .availability-info p {
            font-size: 14px;
            margin-bottom: 5px;
            color: #856404;
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

        /* NOVO: Estilos do calendário */
        .calendar-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 10px 0;
        }

        .calendar-nav {
            background: none;
            border: none;
            font-size: 24px;
            color: #ffc107;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .calendar-nav:hover {
            background-color: rgba(255, 193, 7, 0.1);
            transform: scale(1.1);
        }

        .calendar-nav:disabled {
            color: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .calendar-nav:disabled:hover {
            background: none;
        }

        .calendar-month-year {
            font-size: 20px;
            font-weight: bold;
            color: #856404;
            text-align: center;
            min-width: 200px;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
        }

        .calendar-day-header {
            text-align: center;
            font-weight: bold;
            color: #856404;
            padding: 10px 5px;
            font-size: 14px;
        }

        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s;
            position: relative;
            min-height: 45px;
        }

        .calendar-day.disabled {
            color: #ddd;
            cursor: not-allowed;
            background-color: #f8f9fa;
        }

        .calendar-day.available {
            color: #856404;
            background-color: rgba(255, 193, 7, 0.1);
            border: 2px solid transparent;
        }

        .calendar-day.available:hover {
            background-color: rgba(255, 193, 7, 0.2);
            border-color: #ffc107;
            transform: scale(1.05);
        }

        .calendar-day.selected {
            background-color: #ffc107;
            color: white;
            border-color: #e0a800;
            transform: scale(1.05);
        }

        .calendar-day.today {
            border: 2px solid #ffc107;
            font-weight: bold;
        }

        .calendar-day.today.available {
            background-color: rgba(255, 193, 7, 0.3);
        }

        .calendar-day.other-month {
            opacity: 0.3;
        }

        input[type="number"] {
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

        input:focus {
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

        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-family: Georgia, 'Times New Roman', Times, serif;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #e0a800 0%, #d39e00 100%);
        }

        .btn-primary:disabled {
            background-color: #ccc !important;
            cursor: not-allowed;
            color: #666;
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

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
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

        .popup-icon.success {
            color: #28a745;
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

            .calendar-grid {
                gap: 4px;
            }

            .calendar-day {
                min-height: 35px;
                font-size: 12px;
            }

            .calendar-month-year {
                font-size: 18px;
                min-width: 150px;
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
                <span>Olá, <?php echo htmlspecialchars($_SESSION['empresa_nome']); ?>!</span>
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
                <div id="success-message" class="alert alert-success" style="display: none;"></div>

                <div class="company-welcome">
                    <h3><i class="fa-solid fa-handshake"></i> Bem-vindo de volta!</h3>
                    <p>Seus dados já estão salvos. Selecione a data, horário e quantidade de pessoas para sua visita empresarial.</p>
                </div>

                <div class="pending-notice">
                    <h4><i class="fa-solid fa-clock"></i> Aprovação Necessária</h4>
                    <p>Todos os agendamentos de empresas passam por análise e aprovação da administração antes da confirmação.</p>
                </div>
                
                <div class="availability-info">
                    <h4><i class="fa-solid fa-info-circle"></i> Informações para Empresas</h4>
                    <p><strong>Horário empresarial:</strong> Segunda a Sexta, das 08:00 às 16:00</p>
                    <p><strong>Quantidade:</strong> De 1 a 45 pessoas por agendamento</p>
                    <p><strong>Aprovação:</strong> Todas as solicitações são analisadas individualmente.</p>
                    <p><strong>Resposta:</strong> Confirmação em até 2 dias úteis pelo site.</p>
                </div>

                <form id="agendamento-form" method="POST" action="../back-end/agendar_empresa.php">
                    <div class="form-group">
                        <label>
                            <i class="fa-solid fa-calendar"></i> Escolha a Data:
                        </label>
                        <div class="calendar-container">
                            <div class="calendar-header">
                                <button type="button" class="calendar-nav" id="prev-month">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </button>
                                <div class="calendar-month-year" id="month-year"></div>
                                <button type="button" class="calendar-nav" id="next-month">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </button>
                            </div>
                            <div class="calendar-grid" id="calendar-grid">
                                <!-- Calendário será gerado via JavaScript -->
                            </div>
                        </div>
                        <input type="hidden" id="data_agendamento" name="data_agendamento" required>
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
                               min="1" max="45" value="1" required>
                    </div>

                    <div class="loading" id="loading">
                        <div class="spinner"></div>
                        <p>Processando solicitação...</p>
                    </div>

                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary" id="btn-agendar" disabled>
                            <i class="fa-solid fa-calendar-check"></i> Confirmar Agendamento
                        </button>
                    </div>
                </form>

                <div class="action-buttons">
                    <a href="pag_inicial.html" class="btn btn-secondary">
                        <i class="fa-solid fa-home"></i> Página Inicial
                    </a>
                    <a href="pag_meus_agendamentos_empresa.php" class="btn btn-secondary">
                        <i class="fa-solid fa-list"></i> Meus Agendamentos
                    </a>
                    <a href="pag_dados_empresa.php" class="btn btn-secondary">
                        <i class="fa-solid fa-id-card"></i> Meus Dados
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        class CalendarScheduler {
            constructor() {
                this.currentDate = new Date();
                this.selectedDate = null;
                this.today = new Date();
                this.today.setHours(0, 0, 0, 0);
                
                // Horários para empresas (08:00 às 16:00)
                this.horariosDisponiveis = [
                    '08:00', '08:30', '09:00', '09:30', '10:00', '10:30',
                    '11:00', '11:30', '12:00', '12:30', '13:00', '13:30',
                    '14:00', '14:30', '15:00', '15:30', '16:00'
                ];

                this.initializeElements();
                this.bindEvents();
                this.renderCalendar();
                this.loadHorarios();
                this.checkFormValidity(); // Garantir que o botão comece desabilitado
            }

            initializeElements() {
                this.monthYearElement = document.getElementById('month-year');
                this.calendarGrid = document.getElementById('calendar-grid');
                this.prevMonthBtn = document.getElementById('prev-month');
                this.nextMonthBtn = document.getElementById('next-month');
                this.dataInput = document.getElementById('data_agendamento');
                this.horariosContainer = document.getElementById('horarios-container');
                this.btnAgendar = document.getElementById('btn-agendar');
                this.form = document.getElementById('agendamento-form');
                this.loading = document.getElementById('loading');
            }

            bindEvents() {
                this.prevMonthBtn.addEventListener('click', () => this.previousMonth());
                this.nextMonthBtn.addEventListener('click', () => this.nextMonth());
                this.form.addEventListener('submit', (e) => this.handleSubmit(e));
                document.getElementById('quantidade_pessoas').addEventListener('input', () => this.checkFormValidity());
            }

            previousMonth() {
                this.currentDate.setMonth(this.currentDate.getMonth() - 1);
                this.renderCalendar();
            }

            nextMonth() {
                this.currentDate.setMonth(this.currentDate.getMonth() + 1);
                this.renderCalendar();
            }

            isWeekday(date) {
                const day = date.getDay();
                return day >= 1 && day <= 5; // Segunda a sexta
            }

            isToday(date) {
                return date.getTime() === this.today.getTime();
            }

            isDateAvailable(date) {
                return date >= this.today && this.isWeekday(date);
            }

            formatDate(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            renderCalendar() {
                const year = this.currentDate.getFullYear();
                const month = this.currentDate.getMonth();

                // Atualizar cabeçalho
                const monthNames = [
                    'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                    'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
                ];
                this.monthYearElement.textContent = `${monthNames[month]} ${year}`;

                // Limpar grid
                this.calendarGrid.innerHTML = '';

                // Cabeçalhos dos dias da semana
                const dayHeaders = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
                dayHeaders.forEach(day => {
                    const headerElement = document.createElement('div');
                    headerElement.className = 'calendar-day-header';
                    headerElement.textContent = day;
                    this.calendarGrid.appendChild(headerElement);
                });

                // Primeiro dia do mês
                const firstDay = new Date(year, month, 1);
                const startDate = new Date(firstDay);
                startDate.setDate(startDate.getDate() - firstDay.getDay());

                // Gerar 42 dias (6 semanas)
                for (let i = 0; i < 42; i++) {
                    const date = new Date(startDate);
                    date.setDate(startDate.getDate() + i);

                    const dayElement = document.createElement('div');
                    dayElement.className = 'calendar-day';
                    dayElement.textContent = date.getDate();

                    // Adicionar classes baseadas no estado do dia
                    if (date.getMonth() !== month) {
                        dayElement.classList.add('other-month', 'disabled');
                    } else if (this.isToday(date)) {
                        dayElement.classList.add('today');
                        if (this.isDateAvailable(date)) {
                            dayElement.classList.add('available');
                        } else {
                            dayElement.classList.add('disabled');
                        }
                    } else if (this.isDateAvailable(date)) {
                        dayElement.classList.add('available');
                    } else {
                        dayElement.classList.add('disabled');
                    }

                    // Verificar se é o dia selecionado
                    if (this.selectedDate && date.getTime() === this.selectedDate.getTime()) {
                        dayElement.classList.add('selected');
                    }

                    // Adicionar evento de clique
                    if (dayElement.classList.contains('available')) {
                        dayElement.addEventListener('click', () => this.selectDate(date));
                    }

                    this.calendarGrid.appendChild(dayElement);
                }

                // Atualizar estado dos botões de navegação
                const currentMonth = new Date(this.today.getFullYear(), this.today.getMonth(), 1);
                const displayMonth = new Date(year, month, 1);
                this.prevMonthBtn.disabled = displayMonth <= currentMonth;
            }

            selectDate(date) {
                // Remover seleção anterior
                document.querySelectorAll('.calendar-day.selected').forEach(day => {
                    day.classList.remove('selected');
                });

                // Adicionar nova seleção
                this.selectedDate = new Date(date);
                const dayElements = document.querySelectorAll('.calendar-day');
                dayElements.forEach(dayElement => {
                    const dayNumber = parseInt(dayElement.textContent);
                    if (dayNumber === date.getDate() && 
                        !dayElement.classList.contains('other-month') &&
                        dayElement.classList.contains('available')) {
                        dayElement.classList.add('selected');
                    }
                });

                // Atualizar input hidden
                this.dataInput.value = this.formatDate(date);
                this.checkFormValidity();
            }

            loadHorarios() {
                this.horariosContainer.innerHTML = '';
                
                this.horariosDisponiveis.forEach(horario => {
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
                    this.horariosContainer.appendChild(div);
                    
                    input.addEventListener('change', () => this.checkFormValidity());
                });
            }

            checkFormValidity() {
                const data = this.dataInput.value;
                const horarioSelecionado = document.querySelector('input[name="hora_agendamento"]:checked');
                const quantidade = document.getElementById('quantidade_pessoas').value;
                
                const formCompleto = data && horarioSelecionado && quantidade >= 1 && quantidade <= 45;
                this.btnAgendar.disabled = !formCompleto;
            }

            handleSubmit(e) {
                e.preventDefault();
                
                const data = this.dataInput.value;
                const horarioSelecionado = document.querySelector('input[name="hora_agendamento"]:checked');
                const quantidade = parseInt(document.getElementById('quantidade_pessoas').value);
                
                if (!data || !horarioSelecionado) {
                    showPopup('Erro', 'Por favor, selecione uma data e horário.', 'error');
                    return false;
                }

                if (quantidade < 1 || quantidade > 45) {
                    showPopup('Erro', 'A quantidade de pessoas deve estar entre 1 e 45.', 'error');
                    return false;
                }

                // Mostrar loading
                this.loading.style.display = 'block';
                this.btnAgendar.disabled = true;

                // Enviar formulário
                setTimeout(() => {
                    this.form.submit();
                }, 1000);
            }
        }

        // Inicializar quando o DOM estiver carregado
        document.addEventListener('DOMContentLoaded', function() {
            const calendar = new CalendarScheduler();
            
            // Verificar mensagens da URL
            const urlParams = new URLSearchParams(window.location.search);
            const erro = urlParams.get('erro');

            if (erro) {
                showPopup('Erro', decodeURIComponent(erro), 'error');
                window.history.replaceState({}, document.title, window.location.pathname);
            }
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