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

// Destacar agendamentos pendentes
document.addEventListener("DOMContentLoaded", function () {
  // Scroll suave para o primeiro agendamento pendente
  const pendenteCard = document.querySelector(".pending-card");
  if (pendenteCard) {
    setTimeout(() => {
      pendenteCard.scrollIntoView({
        behavior: "smooth",
        block: "center",
      });
    }, 1000);
  }
});
