function exportarPDF() {
  const filtros = new URLSearchParams(window.location.search);
  filtros.append("export", "pdf");
  window.open("exportar_relatorio.php?" + filtros.toString(), "_blank");
}

function exportarExcel() {
  const filtros = new URLSearchParams(window.location.search);
  filtros.append("export", "excel");
  window.open("exportar_relatorio.php?" + filtros.toString(), "_blank");
}

function exportarDataEspecifica(tipo) {
  const dataEspecifica = document.getElementById("data_especifica").value;
  if (!dataEspecifica) {
    alert("Por favor, selecione uma data primeiro.");
    return;
  }

  const params = new URLSearchParams();
  params.append("export", tipo);
  params.append("data_especifica", dataEspecifica);

  window.open("exportar_relatorio.php?" + params.toString(), "_blank");
}

function exportarDataEspecificaDireta(data, tipo) {
  const params = new URLSearchParams();
  params.append("export", tipo);
  params.append("data_especifica", data);

  window.open("exportar_relatorio.php?" + params.toString(), "_blank");
}

function filtrarSemConcluidos() {
  const url = new URL(window.location);
  url.searchParams.delete("status");
  window.location.href = url.toString();
}

function showCustomConfirm(message, onConfirm) {
  const overlay = document.getElementById("popup-overlay");
  const messageElement = document.getElementById("popup-message");
  const confirmBtn = document.getElementById("popup-confirm");
  const cancelBtn = document.getElementById("popup-cancel");

  messageElement.textContent = message;
  overlay.style.display = "flex";

  confirmBtn.onclick = null;
  cancelBtn.onclick = null;

  confirmBtn.onclick = function () {
    overlay.style.display = "none";
    onConfirm();
  };

  cancelBtn.onclick = function () {
    overlay.style.display = "none";
  };

  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      overlay.style.display = "none";
    }
  });
}

document.addEventListener("DOMContentLoaded", function () {
  const todaySection = document.querySelector(".today-highlight");
  if (todaySection) {
    setTimeout(() => {
      todaySection.scrollIntoView({
        behavior: "smooth",
        block: "start",
      });
    }, 500);
  }

  const dataInicio = document.getElementById("data_inicio");
  const dataFim = document.getElementById("data_fim");

  const urlParams = new URLSearchParams(window.location.search);
  if (!urlParams.has("data_inicio") && !urlParams.has("data_fim")) {
    const hoje = new Date();
    const semanaPassada = new Date(hoje.getTime() - 7 * 24 * 60 * 60 * 1000);
    const proximoMes = new Date(hoje.getTime() + 30 * 24 * 60 * 60 * 1000);

    dataInicio.value = semanaPassada.toISOString().split("T")[0];
    dataFim.value = proximoMes.toISOString().split("T")[0];
  }

  const statusSelect = document.getElementById("status");
  if (statusSelect.value) {
    statusSelect.style.borderColor = "#28a745";
    statusSelect.style.boxShadow = "0 0 0 3px rgba(40, 167, 69, 0.1)";
  }
});