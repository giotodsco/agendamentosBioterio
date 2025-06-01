let isUserLoggedIn = false;
let currentUserName = "";
let tipoConta = "";

// ATUALIZADO: Nova função para clique na área do usuário
function handleUserClick() {
  if (isUserLoggedIn) {
    // Se logado, verificar tipo de conta
    if (tipoConta === "empresa") {
      // Empresa logada - ir para página de dados da empresa
      window.location.href = "pag_dados_empresa.php";
    } else {
      // Usuário comum logado - ir para página de dados do usuário
      window.location.href = "pag_dados_usuario.php";
    }
  } else {
    // Se não logado, ir para login/cadastro
    window.location.href = "pag_login_usuario.php";
  }
}

// Sistema atualizado - usuários comuns + empresas + visitantes
function verificarStatusUsuario() {
  fetch("../back-end/verificar_login_ajax.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.logado) {
        isUserLoggedIn = true;
        currentUserName = data.usuario_nome;
        tipoConta = data.tipo_conta;

        if (data.tipo_conta === "empresa") {
          mostrarEmpresaLogada(data.usuario_nome);
        } else {
          mostrarUsuarioLogado(data.usuario_nome);
        }
      } else {
        isUserLoggedIn = false;
        mostrarVisitante();
      }
    })
    .catch((error) => {
      console.log("Erro ao verificar login, mostrando visitante");
      isUserLoggedIn = false;
      mostrarVisitante();
    });
}

function mostrarUsuarioLogado(nome) {
  const indicator = document.getElementById("user-indicator");
  const avatar = document.getElementById("user-avatar");
  const userName = document.getElementById("user-name");
  const userType = document.getElementById("user-type");
  const userActions = document.getElementById("user-actions");
  const linkAgendamentos = document.getElementById("link-meus-agendamentos");

  // Configurar avatar
  avatar.className = "user-avatar";
  avatar.title = "Clique para ver seus dados";

  // Configurar informações
  userName.textContent = nome;
  userType.textContent = "Usuário";
  userType.className = "user-type";

  // Configurar link de agendamentos para usuário
  linkAgendamentos.className = "";

  // Adicionar botão de sair
  userActions.innerHTML = `
                <form method="POST" action="../back-end/auth_unificado.php" style="margin: 0;" id="logout-form">
                    <input type="hidden" name="acao" value="logout">
                    <button type="button" class="btn-logout-small" onclick="showLogoutConfirm()">
                        <i class="fa-solid fa-sign-out-alt"></i> Sair
                    </button>
                </form>
            `;

  // Remover classes de visitante e empresa
  indicator.classList.remove("guest", "empresa");

  // Mostrar indicador
  indicator.classList.add("show");
}

// NOVO: Função para mostrar empresa logada
function mostrarEmpresaLogada(nome) {
  const indicator = document.getElementById("user-indicator");
  const avatar = document.getElementById("user-avatar");
  const userName = document.getElementById("user-name");
  const userType = document.getElementById("user-type");
  const userActions = document.getElementById("user-actions");
  const linkAgendamentos = document.getElementById("link-meus-agendamentos");

  // Configurar avatar amarelo para empresa
  avatar.className = "user-avatar empresa";
  avatar.title = "Empresa - Clique para ver dados";

  // Configurar informações
  userName.textContent = nome;
  userType.textContent = "Empresa";
  userType.className = "user-type empresa";

  // Configurar link de agendamentos para empresa
  linkAgendamentos.className = "empresa";

  // Adicionar botão de sair
  userActions.innerHTML = `
                <form method="POST" action="../back-end/auth_unificado.php" style="margin: 0;" id="logout-form">
                    <input type="hidden" name="acao" value="logout_empresa">
                    <button type="button" class="btn-logout-small" onclick="showLogoutConfirm()">
                        <i class="fa-solid fa-sign-out-alt"></i> Sair
                    </button>
                </form>
            `;

  // Remover classe de visitante e adicionar classe empresa
  indicator.classList.remove("guest");
  indicator.classList.add("empresa");

  // Mostrar indicador
  indicator.classList.add("show");
}

function mostrarVisitante() {
  const indicator = document.getElementById("user-indicator");
  const avatar = document.getElementById("user-avatar");
  const userName = document.getElementById("user-name");
  const userType = document.getElementById("user-type");
  const userActions = document.getElementById("user-actions");
  const linkAgendamentos = document.getElementById("link-meus-agendamentos");

  // Configurar avatar de visitante
  avatar.className = "user-avatar guest";
  avatar.title = "Clique para fazer login";

  // ATUALIZADO: Nova mensagem conforme solicitado
  userName.textContent = "Entre aqui";
  userType.textContent = "Clique para acessar";
  userType.className = "user-type guest";

  // Remover classe empresa do link de agendamentos
  linkAgendamentos.className = "";

  // Limpar ações
  userActions.innerHTML = "";

  // Adicionar classe de visitante
  indicator.classList.add("guest");

  // Mostrar indicador
  indicator.classList.add("show");
}

function verificarLoginEIrPara(event, destino) {
  event.preventDefault();

  fetch("../back-end/verificar_login_ajax.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.logado) {
        // Usuário/Empresa logado - redirecionar conforme tipo e destino
        if (destino === "agendamento") {
          if (data.tipo_conta === "empresa") {
            window.location.href = "pag_agendar_empresa.php";
          } else {
            window.location.href = "pag_agendar_logado.php";
          }
        } else {
          if (data.tipo_conta === "empresa") {
            window.location.href = "pag_dados_empresa.php";
          } else {
            window.location.href = "pag_dados_usuario.php";
          }
        }
      } else {
        // Usuário não logado - ir para login com parâmetro
        if (destino === "agendamento") {
          window.location.href =
            "pag_login_usuario.php?redirect_to=agendamento";
        } else {
          window.location.href = "pag_login_usuario.php";
        }
      }
    })
    .catch((error) => {
      console.error("Erro ao verificar login:", error);
      window.location.href = "pag_login_usuario.php";
    });
}

// ATUALIZADA: Função para acessar agendamentos (agora funciona para empresas também)
function verificarLoginEAcessar(event) {
  event.preventDefault();

  fetch("../back-end/verificar_login_ajax.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.logado) {
        if (data.tipo_conta === "empresa") {
          // Empresas vão para sua própria página de agendamentos
          window.location.href = "pag_meus_agendamentos_empresa.php";
        } else {
          // Usuários vão para página normal de agendamentos
          window.location.href = "pag_meus_agendamentos.php";
        }
      } else {
        showPopup(
          "Login Necessário",
          "Você precisa fazer login para acessar seus agendamentos.",
          "info",
          [
            {
              text: "Fazer Login",
              class: "popup-btn-primary",
              action: function () {
                closePopup();
                window.location.href =
                  "pag_login_usuario.php?login_required=true&redirect=meus_agendamentos";
              },
            },
            {
              text: "Cancelar",
              class: "popup-btn-secondary",
              action: closePopup,
            },
          ]
        );
      }
    })
    .catch((error) => {
      console.error("Erro ao verificar login:", error);
      showPopup("Erro", "Erro ao verificar login. Tente novamente.", "error");
    });
}

// Sistema de pop-up personalizado
function showPopup(title, message, type = "info", buttons = null) {
  const overlay = document.getElementById("popup-overlay");
  const titleElement = document.getElementById("popup-title");
  const messageElement = document.getElementById("popup-message");
  const iconElement = document.getElementById("popup-icon");
  const buttonsContainer = document.getElementById("popup-buttons");

  titleElement.textContent = title;
  messageElement.textContent = message;

  // Configurar ícone baseado no tipo
  let iconClass = "fa-info-circle";
  let iconColorClass = "info";

  switch (type) {
    case "error":
      iconClass = "fa-exclamation-triangle";
      iconColorClass = "error";
      break;
    case "success":
      iconClass = "fa-check-circle";
      iconColorClass = "success";
      break;
    case "warning":
      iconClass = "fa-exclamation-triangle";
      iconColorClass = "";
      break;
    default:
      iconClass = "fa-info-circle";
      iconColorClass = "info";
  }

  iconElement.innerHTML = `<i class="fa-solid ${iconClass}"></i>`;
  iconElement.className = `popup-icon ${iconColorClass}`;

  // Configurar botões
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

// Função para confirmar logout
function showLogoutConfirm() {
  showPopup("Confirmar Saída", "Tem certeza que deseja sair?", "warning", [
    {
      text: "Sim, Sair",
      class: "popup-btn-danger",
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

// Executar verificação quando a página carregar
document.addEventListener("DOMContentLoaded", function () {
  verificarStatusUsuario();
});
