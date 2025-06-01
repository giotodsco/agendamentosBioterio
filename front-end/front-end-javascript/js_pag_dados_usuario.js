// Sistema de pop-up personalizado
function showLogoutConfirm(event) {
  event.preventDefault();

  const overlay = document.getElementById("popup-overlay");
  const confirmBtn = document.getElementById("popup-confirm");
  const cancelBtn = document.getElementById("popup-cancel");

  overlay.style.display = "flex";

  confirmBtn.onclick = function () {
    overlay.style.display = "none";
    document.getElementById("logout-form").submit();
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
