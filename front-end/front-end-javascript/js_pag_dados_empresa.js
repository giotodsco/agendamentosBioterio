// Sistema de pop-up personalizado unificado
function showCustomConfirm(message, onConfirm, onCancel = null) {
    const overlay = document.getElementById("popup-overlay");
    const messageElement = document.getElementById("popup-message");
    const confirmBtn = document.getElementById("popup-confirm");
    const cancelBtn = document.getElementById("popup-cancel");

    // Atualizar a mensagem
    messageElement.textContent = message;

    // Mostrar o pop-up
    overlay.style.display = "flex";

    // Configurar ações dos botões
    confirmBtn.onclick = function () {
        overlay.style.display = "none";
        if (onConfirm && typeof onConfirm === 'function') {
            onConfirm();
        }
    };

    cancelBtn.onclick = function () {
        overlay.style.display = "none";
        if (onCancel && typeof onCancel === 'function') {
            onCancel();
        }
    };

    // Fechar com ESC
    function handleEscape(e) {
        if (e.key === "Escape") {
            overlay.style.display = "none";
            document.removeEventListener("keydown", handleEscape);
            if (onCancel && typeof onCancel === 'function') {
                onCancel();
            }
        }
    }

    document.addEventListener("keydown", handleEscape);

    // Fechar clicando fora do pop-up
    overlay.onclick = function (e) {
        if (e.target === overlay) {
            overlay.style.display = "none";
            if (onCancel && typeof onCancel === 'function') {
                onCancel();
            }
        }
    };
}

// Função específica para logout
function showLogoutConfirm(event) {
    event.preventDefault();
    showCustomConfirm(
        'Tem certeza que deseja sair?',
        () => {
            document.getElementById('logout-form').submit();
        }
    );
}
