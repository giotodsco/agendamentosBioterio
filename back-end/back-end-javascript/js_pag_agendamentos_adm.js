// Função para excluir agendamento individual
function excluirAgendamentoIndividual(agendamentoId, data) {
  showCustomConfirm(
    "⚠️ ATENÇÃO CRÍTICA: Deseja EXCLUIR PERMANENTEMENTE este agendamento?\n\nEsta ação é IRREVERSÍVEL e removerá completamente o agendamento do sistema!\n\nTem certeza absoluta?",
    () => {
      // Fazer requisição AJAX para excluir
      fetch("", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          "X-Requested-With": "XMLHttpRequest",
        },
        body: `agendamento_id=${agendamentoId}&acao=excluir`,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // Remover o card da interface
            const card = document.getElementById(`card-${agendamentoId}`);
            if (card) {
              card.style.transition = "all 0.5s ease";
              card.style.opacity = "0";
              card.style.transform = "translateX(-100%)";

              setTimeout(() => {
                card.remove();

                // Verificar se ainda há cards neste dia
                const grid = document.getElementById(
                  `appointments-grid-${data}`
                );
                const remainingCards =
                  grid.querySelectorAll(".appointment-card");

                if (remainingCards.length === 0) {
                  // Remover toda a seção do dia
                  const daySection = document.getElementById(
                    `day-section-${data}`
                  );
                  if (daySection) {
                    daySection.style.transition = "all 0.5s ease";
                    daySection.style.opacity = "0";
                    daySection.style.transform = "translateY(-20px)";

                    setTimeout(() => {
                      daySection.remove();

                      // Mostrar mensagem se não há mais agendamentos
                      checkIfNoAppointments();
                    }, 500);
                  }
                } else {
                  // Atualizar contador
                  const counter = document.getElementById(
                    `count-agendamentos-${data}`
                  );
                  if (counter) {
                    counter.textContent = remainingCards.length;
                  }
                }

                // Mostrar mensagem de sucesso
                showSuccessMessage(
                  "Agendamento excluído permanentemente com sucesso!"
                );
              }, 500);
            }
          } else {
            alert("Erro ao excluir agendamento. Tente novamente.");
          }
        })
        .catch((error) => {
          console.error("Erro:", error);
          alert("Erro ao excluir agendamento. Tente novamente.");
        });
    }
  );
}

// Função para excluir todos os agendamentos de um dia
function excluirTodosAgendamentosDia(data) {
  const grid = document.getElementById(`appointments-grid-${data}`);
  const cards = grid.querySelectorAll(".appointment-card");
  const totalCards = cards.length;

  if (totalCards === 0) {
    alert("Não há agendamentos para excluir neste dia.");
    return;
  }

  showCustomConfirm(
    `⚠️ ATENÇÃO MÁXIMA: Deseja EXCLUIR PERMANENTEMENTE todos os ${totalCards} agendamento(s) do dia ${formatarData(
      data
    )}?\n\nEsta ação é COMPLETAMENTE IRREVERSÍVEL e removerá:\n- Todos os agendamentos desta data\n- Todos os dados associados\n- A seção completa do dia\n\nTEM CERTEZA ABSOLUTA? NÃO HÁ COMO DESFAZER!`,
    () => {
      let excluidos = 0;
      const agendamentoIds = Array.from(cards).map((card) => {
        const id = card.id.replace("card-", "");
        return id;
      });

      // Excluir cada agendamento
      agendamentoIds.forEach((id, index) => {
        fetch("", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
            "X-Requested-With": "XMLHttpRequest",
          },
          body: `agendamento_id=${id}&acao=excluir`,
        })
          .then((response) => response.json())
          .then((responseData) => {
            excluidos++;

            // Quando todos foram excluídos
            if (excluidos === agendamentoIds.length) {
              // Remover toda a seção do dia com animação
              const daySection = document.getElementById(`day-section-${data}`);
              if (daySection) {
                daySection.style.transition = "all 0.8s ease";
                daySection.style.opacity = "0";
                daySection.style.transform = "translateY(-50px) scale(0.95)";

                setTimeout(() => {
                  daySection.remove();

                  // Verificar se ainda há dias
                  checkIfNoAppointments();

                  // Mostrar mensagem de sucesso
                  showSuccessMessage(
                    `Todos os ${totalCards} agendamentos do dia foram excluídos permanentemente!`
                  );
                }, 800);
              }
            }
          })
          .catch((error) => {
            console.error("Erro ao excluir agendamento:", error);
          });
      });
    }
  );
}

// Função para verificar se não há mais agendamentos
function checkIfNoAppointments() {
  const container = document.querySelector(".appointments-container");
  const daySections = container.querySelectorAll(".day-section");

  if (daySections.length === 0) {
    container.innerHTML = `
                    <div class="no-appointments">
                        <i class="fa-solid fa-calendar-times"></i><br>
                        <strong>Nenhum agendamento encontrado</strong><br>
                        <small>Todos os agendamentos foram removidos ou não há agendamentos no período selecionado</small>
                    </div>
                `;
  }
}

// Função para mostrar mensagem de sucesso
function showSuccessMessage(message) {
  // Remover mensagens anteriores
  const existingAlerts = document.querySelectorAll(".alert-success");
  existingAlerts.forEach((alert) => alert.remove());

  // Criar nova mensagem
  const alertDiv = document.createElement("div");
  alertDiv.className = "alert alert-success";
  alertDiv.innerHTML = `
                <i class="fa-solid fa-check-circle"></i>
                ${message}
            `;

  // Inserir no início do conteúdo
  const content = document.querySelector(".content");
  const pageTitle = content.querySelector(".page-title");
  content.insertBefore(alertDiv, pageTitle.nextSibling);

  // Remover após 5 segundos
  setTimeout(() => {
    alertDiv.style.opacity = "0";
    setTimeout(() => alertDiv.remove(), 500);
  }, 5000);
}

// Função auxiliar para formatar data
function formatarData(data) {
  const [ano, mes, dia] = data.split("-");
  return `${dia}/${mes}/${ano}`;
}

// Sistema de pop-up personalizado
function showCustomConfirm(message, onConfirm) {
  const overlay = document.getElementById("popup-overlay");
  const messageElement = document.getElementById("popup-message");
  const confirmBtn = document.getElementById("popup-confirm");
  const cancelBtn = document.getElementById("popup-cancel");

  messageElement.textContent = message;
  overlay.style.display = "flex";

  // Remover listeners anteriores
  confirmBtn.onclick = null;
  cancelBtn.onclick = null;

  // Adicionar novos listeners
  confirmBtn.onclick = function () {
    overlay.style.display = "none";
    onConfirm();
  };

  cancelBtn.onclick = function () {
    overlay.style.display = "none";
  };

  // Fechar com ESC
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      overlay.style.display = "none";
    }
  });
}

// Destacar agendamentos pendentes ao carregar a página
document.addEventListener("DOMContentLoaded", function () {
  // Scroll suave para o primeiro agendamento pendente
  const pendenteCard = document.querySelector(".pendente-card");
  if (pendenteCard) {
    setTimeout(() => {
      pendenteCard.scrollIntoView({
        behavior: "smooth",
        block: "center",
      });
    }, 1000);
  }

  // Animar cards pendentes
  const pendenteCards = document.querySelectorAll(".pendente-card");
  pendenteCards.forEach((card) => {
    card.style.position = "relative";
    card.style.overflow = "hidden";

    // Adicionar efeito de brilho
    const shine = document.createElement("div");
    shine.style.position = "absolute";
    shine.style.top = "0";
    shine.style.left = "-100%";
    shine.style.width = "100%";
    shine.style.height = "100%";
    shine.style.background =
      "linear-gradient(90deg, transparent, rgba(255, 193, 7, 0.3), transparent)";
    shine.style.animation = "shine 3s infinite";
    shine.style.pointerEvents = "none";
    card.appendChild(shine);
  });

  // Adicionar animação de brilho
  const style = document.createElement("style");
  style.textContent = `
                @keyframes shine {
                    0% { left: -100%; }
                    50% { left: 100%; }
                    100% { left: 100%; }
                }
            `;
  document.head.appendChild(style);
});
