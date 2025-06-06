document.addEventListener("DOMContentLoaded", function () {
  // Destacar usuários com mais agendamentos
  const userCards = document.querySelectorAll(".user-card");
  userCards.forEach((card) => {
    const totalAgendamentos = parseInt(
      card.querySelector(".stat-item .number").textContent
    );
    if (totalAgendamentos >= 5) {
      card.style.border = "2px solid #28a745";
      card.style.boxShadow = "0 4px 15px rgba(40, 167, 69, 0.2)";
    }
  });

  // Auto-submit no filtro de nome após digitar
  const nomeInput = document.getElementById("nome");
  let timeout;

  nomeInput.addEventListener("input", function () {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
      if (this.value.length >= 3 || this.value.length === 0) {
        this.form.submit();
      }
    }, 500);
  });

  // Animação de entrada dos cards
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.style.opacity = "0";
        entry.target.style.transform = "translateY(20px)";
        entry.target.style.transition = "all 0.5s ease";

        setTimeout(() => {
          entry.target.style.opacity = "1";
          entry.target.style.transform = "translateY(0)";
        }, 100);

        observer.unobserve(entry.target);
      }
    });
  });

  userCards.forEach((card) => {
    observer.observe(card);
  });

  // Destacar usuários com muitos agendamentos concluídos
  userCards.forEach((card) => {
    const concluidosElement = card.querySelector(
      ".stat-item.concluidos .number"
    );
    if (concluidosElement) {
      const totalConcluidos = parseInt(concluidosElement.textContent);
      if (totalConcluidos >= 3) {
        concluidosElement.parentElement.style.background =
          "linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%)";
        concluidosElement.parentElement.style.border = "2px solid #28a745";
      }
    }
  });
});