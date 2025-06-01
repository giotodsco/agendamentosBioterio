class CalendarScheduler {
  constructor() {
    this.currentDate = new Date();
    this.selectedDate = null;
    this.today = new Date();
    this.today.setHours(0, 0, 0, 0);

    // Horários para empresas (08:00 às 16:00)
    this.horariosDisponiveis = [
      "08:00",
      "08:30",
      "09:00",
      "09:30",
      "10:00",
      "10:30",
      "11:00",
      "11:30",
      "12:00",
      "12:30",
      "13:00",
      "13:30",
      "14:00",
      "14:30",
      "15:00",
      "15:30",
      "16:00",
    ];

    this.initializeElements();
    this.bindEvents();
    this.renderCalendar();
    this.loadHorarios();
    this.checkFormValidity(); // Garantir que o botão comece desabilitado
  }

  initializeElements() {
    this.monthYearElement = document.getElementById("month-year");
    this.calendarGrid = document.getElementById("calendar-grid");
    this.prevMonthBtn = document.getElementById("prev-month");
    this.nextMonthBtn = document.getElementById("next-month");
    this.dataInput = document.getElementById("data_agendamento");
    this.horariosContainer = document.getElementById("horarios-container");
    this.btnAgendar = document.getElementById("btn-agendar");
    this.form = document.getElementById("agendamento-form");
    this.loading = document.getElementById("loading");
  }

  bindEvents() {
    this.prevMonthBtn.addEventListener("click", () => this.previousMonth());
    this.nextMonthBtn.addEventListener("click", () => this.nextMonth());
    this.form.addEventListener("submit", (e) => this.handleSubmit(e));
    document
      .getElementById("quantidade_pessoas")
      .addEventListener("input", () => this.checkFormValidity());
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
    // Data mínima: hoje + 2 dias
    const minDate = new Date(this.today);
    minDate.setDate(minDate.getDate() + 2);

    return date >= minDate && this.isWeekday(date);
  }

  formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
  }

  renderCalendar() {
    const year = this.currentDate.getFullYear();
    const month = this.currentDate.getMonth();

    // Atualizar cabeçalho
    const monthNames = [
      "Janeiro",
      "Fevereiro",
      "Março",
      "Abril",
      "Maio",
      "Junho",
      "Julho",
      "Agosto",
      "Setembro",
      "Outubro",
      "Novembro",
      "Dezembro",
    ];
    this.monthYearElement.textContent = `${monthNames[month]} ${year}`;

    // Limpar grid
    this.calendarGrid.innerHTML = "";

    // Cabeçalhos dos dias da semana
    const dayHeaders = ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sáb"];
    dayHeaders.forEach((day) => {
      const headerElement = document.createElement("div");
      headerElement.className = "calendar-day-header";
      headerElement.textContent = day;
      this.calendarGrid.appendChild(headerElement);
    });

    // Primeiro dia do mês
    const firstDay = new Date(year, month, 1);
    const startDate = new Date(firstDay);
    startDate.setDate(startDate.getDate() - firstDay.getDay());

    // Data mínima para agendamento (hoje + 2 dias)
    const minDate = new Date(this.today);
    minDate.setDate(minDate.getDate() + 2);

    // Gerar 42 dias (6 semanas)
    for (let i = 0; i < 42; i++) {
      const date = new Date(startDate);
      date.setDate(startDate.getDate() + i);

      const dayElement = document.createElement("div");
      dayElement.className = "calendar-day";
      dayElement.textContent = date.getDate();

      // Adicionar classes baseadas no estado do dia
      if (date.getMonth() !== month) {
        dayElement.classList.add("other-month", "disabled");
      } else if (this.isToday(date)) {
        dayElement.classList.add("today", "disabled");
      } else if (this.isDateAvailable(date)) {
        dayElement.classList.add("available");
        // Destacar data mínima disponível
        if (date.getTime() === minDate.getTime() && this.isWeekday(date)) {
          dayElement.classList.add("min-date");
        }
      } else {
        dayElement.classList.add("disabled");
      }

      // Verificar se é o dia selecionado
      if (this.selectedDate && date.getTime() === this.selectedDate.getTime()) {
        dayElement.classList.add("selected");
      }

      // Adicionar evento de clique
      if (dayElement.classList.contains("available")) {
        dayElement.addEventListener("click", () => this.selectDate(date));
      }

      this.calendarGrid.appendChild(dayElement);
    }

    // Atualizar estado dos botões de navegação
    const currentMonth = new Date(
      this.today.getFullYear(),
      this.today.getMonth(),
      1
    );
    const displayMonth = new Date(year, month, 1);
    this.prevMonthBtn.disabled = displayMonth <= currentMonth;
  }

  selectDate(date) {
    // Remover seleção anterior
    document.querySelectorAll(".calendar-day.selected").forEach((day) => {
      day.classList.remove("selected");
    });

    // Adicionar nova seleção
    this.selectedDate = new Date(date);
    const dayElements = document.querySelectorAll(".calendar-day");
    dayElements.forEach((dayElement) => {
      const dayNumber = parseInt(dayElement.textContent);
      if (
        dayNumber === date.getDate() &&
        !dayElement.classList.contains("other-month") &&
        dayElement.classList.contains("available")
      ) {
        dayElement.classList.add("selected");
      }
    });

    // Atualizar input hidden
    this.dataInput.value = this.formatDate(date);
    this.checkFormValidity();
  }

  loadHorarios() {
    this.horariosContainer.innerHTML = "";

    this.horariosDisponiveis.forEach((horario) => {
      const div = document.createElement("div");
      div.className = "horario-item";

      const input = document.createElement("input");
      input.type = "radio";
      input.name = "hora_agendamento";
      input.value = horario;
      input.id = `horario-${horario.replace(":", "")}`;
      input.className = "horario-radio";
      input.required = true;

      const label = document.createElement("label");
      label.htmlFor = input.id;
      label.className = "horario-label";
      label.textContent = horario;

      div.appendChild(input);
      div.appendChild(label);
      this.horariosContainer.appendChild(div);

      input.addEventListener("change", () => this.checkFormValidity());
    });
  }

  checkFormValidity() {
    const data = this.dataInput.value;
    const horarioSelecionado = document.querySelector(
      'input[name="hora_agendamento"]:checked'
    );
    const quantidade = document.getElementById("quantidade_pessoas").value;

    const formCompleto =
      data && horarioSelecionado && quantidade >= 1 && quantidade <= 45;
    this.btnAgendar.disabled = !formCompleto;
  }

  handleSubmit(e) {
    e.preventDefault();

    const data = this.dataInput.value;
    const horarioSelecionado = document.querySelector(
      'input[name="hora_agendamento"]:checked'
    );
    const quantidade = parseInt(
      document.getElementById("quantidade_pessoas").value
    );

    if (!data || !horarioSelecionado) {
      showPopup("Erro", "Por favor, selecione uma data e horário.", "error");
      return false;
    }

    if (quantidade < 1 || quantidade > 45) {
      showPopup(
        "Erro",
        "A quantidade de pessoas deve estar entre 1 e 45.",
        "error"
      );
      return false;
    }

    // Mostrar loading
    this.loading.style.display = "block";
    this.btnAgendar.disabled = true;

    // Enviar formulário
    setTimeout(() => {
      this.form.submit();
    }, 1000);
  }
}

// Inicializar quando o DOM estiver carregado
document.addEventListener("DOMContentLoaded", function () {
  const calendar = new CalendarScheduler();

  // Verificar mensagens da URL
  const urlParams = new URLSearchParams(window.location.search);
  const erro = urlParams.get("erro");

  if (erro) {
    showPopup("Erro", decodeURIComponent(erro), "error");
    window.history.replaceState({}, document.title, window.location.pathname);
  }
});

// Sistema de pop-up personalizado
function showPopup(title, message, type = "info", buttons = null) {
  const overlay = document.getElementById("popup-overlay");
  const titleElement = document.getElementById("popup-title");
  const messageElement = document.getElementById("popup-message");
  const iconElement = document.getElementById("popup-icon");
  const buttonsContainer = document.getElementById("popup-buttons");

  titleElement.textContent = title;
  messageElement.textContent = message;

  let iconClass = "fa-info-circle";
  let iconColorClass = "";

  switch (type) {
    case "error":
      iconClass = "fa-exclamation-triangle";
      iconColorClass = "";
      break;
    case "warning":
      iconClass = "fa-exclamation-triangle";
      iconColorClass = "warning";
      break;
    default:
      iconClass = "fa-info-circle";
      iconColorClass = "";
  }

  iconElement.innerHTML = `<i class="fa-solid ${iconClass}"></i>`;
  iconElement.className = `popup-icon ${iconColorClass}`;

  if (buttons) {
    buttonsContainer.innerHTML = "";
    buttons.forEach((button) => {
      const btn = document.createElement("button");
      btn.className = `popup-btn ${button.class || "popup-btn-primary"}`;
      btn.textContent = button.text;
      btn.onclick = button.action;
      buttonsContainer.appendChild(btn);
    });
  } else {
    buttonsContainer.innerHTML =
      '<button class="popup-btn popup-btn-primary" onclick="closePopup()">OK</button>';
  }

  overlay.style.display = "flex";
}

function closePopup() {
  document.getElementById("popup-overlay").style.display = "none";
}

function showLogoutConfirm(event) {
  event.preventDefault();

  showPopup("Confirmar Saída", "Tem certeza que deseja sair?", "warning", [
    {
      text: "Sim, Sair",
      class: "popup-btn-primary",
      action: function () {
        closePopup();
        document.getElementById("logout-form").submit();
      },
    },
    {
      text: "Cancelar",
      class: "popup-btn-secondary",
      action: closePopup,
    },
  ]);
}
