function showPopup() {
  document.getElementById("popup-overlay").style.display = "flex";
}

function closePopup() {
  document.getElementById("popup-overlay").style.display = "none";
  // Limpar URL para remover parâmetro de erro
  if (window.history.replaceState) {
    window.history.replaceState({}, document.title, window.location.pathname);
  }
}

// Fechar popup com ESC
document.addEventListener("keydown", function (e) {
  if (e.key === "Escape") {
    closePopup();
  }
});