// Função para mostrar seções
function showSection(sectionId) {
  // Esconder todas as seções
  const sections = document.querySelectorAll(".help-section");
  sections.forEach((section) => {
    section.classList.remove("active");
  });

  // Mostrar seção selecionada
  document.getElementById(sectionId).classList.add("active");

  // Atualizar menu ativo
  const menuItems = document.querySelectorAll(".sidebar-menu a");
  menuItems.forEach((item) => {
    item.classList.remove("active");
  });

  // Adicionar classe ativa ao item clicado
  event.target.classList.add("active");

  // Scroll para o topo
  document.querySelector(".help-sections").scrollTop = 0;
}

// Função para alternar FAQ
function toggleFaq(element) {
  const faqItem = element.parentElement;
  const isActive = faqItem.classList.contains("active");

  // Fechar todos os FAQs
  document.querySelectorAll(".faq-item").forEach((item) => {
    item.classList.remove("active");
  });

  // Abrir o clicado se não estava ativo
  if (!isActive) {
    faqItem.classList.add("active");
  }
}

// Função de busca
function searchHelp() {
  const searchTerm = document.getElementById("searchInput").value.toLowerCase();

  if (!searchTerm) {
    alert("Digite algo para pesquisar!");
    return;
  }

  // Lista de termos e suas seções correspondentes
  const searchMapping = {
    "pessoa-fisica": [
      "pessoa",
      "física",
      "individual",
      "cpf",
      "usuario",
      "cadastro individual",
    ],
    empresa: [
      "empresa",
      "cnpj",
      "instituição",
      "corporativo",
      "grupo",
      "empresarial",
    ],
    agendamento: [
      "agendar",
      "agendamento",
      "visita",
      "horário",
      "data",
      "cancelar",
      "calendário",
    ],
    diferenças: ["diferença", "comparar", "versus", "vs", "tipos"],
    sistema: ["funciona", "como", "sistema", "login", "unificado"],
    faq: ["dúvida", "pergunta", "problema", "ajuda", "frequente"],
    contato: ["telefone", "email", "suporte", "localização", "ajuda"],
  };

  // Procurar seção correspondente
  let foundSection = "inicio";
  for (const [section, keywords] of Object.entries(searchMapping)) {
    if (
      keywords.some(
        (keyword) =>
          searchTerm.includes(keyword) || keyword.includes(searchTerm)
      )
    ) {
      foundSection = section;
      break;
    }
  }

  // Mostrar seção encontrada
  showSection(foundSection);

  // Destacar termo na seção (opcional)
  if (foundSection !== "inicio") {
    const section = document.getElementById(foundSection);
    section.scrollIntoView({ behavior: "smooth" });
  }
}

// Permitir busca com Enter
document
  .getElementById("searchInput")
  .addEventListener("keypress", function (e) {
    if (e.key === "Enter") {
      searchHelp();
    }
  });

// Verificar status do usuário e ajustar interface
function verificarStatusUsuario() {
  fetch("../back-end/verificar_login_ajax.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.logado) {
        // Usuário está logado
        ajustarInterfaceLogado(data.tipo_conta, data.usuario_nome);
      } else {
        // Usuário não está logado - manter interface padrão
        ajustarInterfaceDeslogado();
      }
    })
    .catch((error) => {
      console.log("Erro ao verificar login, mantendo interface padrão");
      ajustarInterfaceDeslogado();
    });
}

function ajustarInterfaceLogado(tipoConta, nomeUsuario) {
  // Ajustar botão do header
  const authBtn = document.getElementById("auth-btn");
  if (tipoConta === "empresa") {
    authBtn.href = "pag_meus_agendamentos_empresa.php";
    authBtn.innerHTML =
      '<i class="fa-solid fa-calendar-check"></i> Meus Agendamentos';
  } else {
    authBtn.href = "pag_meus_agendamentos.php";
    authBtn.innerHTML =
      '<i class="fa-solid fa-calendar-check"></i> Meus Agendamentos';
  }

  // Ajustar botões da seção início
  const loginBtnInicio = document.getElementById("login-btn-inicio");
  const agendamentosBtnInicio = document.getElementById(
    "agendamentos-btn-inicio"
  );
  const novoAgendamentoBtnInicio = document.getElementById(
    "novo-agendamento-btn-inicio"
  );

  if (loginBtnInicio) loginBtnInicio.style.display = "none";

  if (agendamentosBtnInicio && novoAgendamentoBtnInicio) {
    if (tipoConta === "empresa") {
      agendamentosBtnInicio.href = "pag_meus_agendamentos_empresa.php";
      novoAgendamentoBtnInicio.href = "pag_agendar_empresa.php";
    } else {
      agendamentosBtnInicio.href = "pag_meus_agendamentos.php";
      novoAgendamentoBtnInicio.href = "pag_agendar_logado.php";
      novoAgendamentoBtnInicio.classList.remove("empresa");
    }
    agendamentosBtnInicio.style.display = "inline-flex";
    novoAgendamentoBtnInicio.style.display = "inline-flex";
  }

  // Ajustar botão da seção agendamento
  const agendarBtnSection = document.getElementById("agendar-btn-section");
  const agendamentosBtnSection = document.getElementById(
    "agendamentos-btn-section"
  );

  if (agendarBtnSection) agendarBtnSection.style.display = "none";

  if (agendamentosBtnSection) {
    if (tipoConta === "empresa") {
      agendamentosBtnSection.href = "pag_meus_agendamentos_empresa.php";
    } else {
      agendamentosBtnSection.href = "pag_meus_agendamentos.php";
    }
    agendamentosBtnSection.style.display = "inline-flex";
  }
}

function ajustarInterfaceDeslogado() {
  // Manter interface padrão - não precisa fazer nada
  // Os botões já estão configurados para usuários não logados
}

// Inicialização
document.addEventListener("DOMContentLoaded", function () {
  // Verificar status do usuário
  verificarStatusUsuario();

  // Verificar se há hash na URL
  const hash = window.location.hash.substr(1);
  if (hash && document.getElementById(hash)) {
    showSection(hash);
  }

  // Adicionar smooth scroll
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute("href"));
      if (target) {
        target.scrollIntoView({ behavior: "smooth" });
      }
    });
  });
});
