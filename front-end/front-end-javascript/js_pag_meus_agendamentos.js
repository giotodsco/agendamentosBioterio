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

// Função para confirmar cancelamento de agendamento
function confirmarCancelamento(agendamentoId, tipoAcao = 'cancelar') {
    let mensagem = '';
    let formId = '';

    if (tipoAcao === 'cancelar_pendente') {
        mensagem = 'Tem certeza que deseja cancelar esta solicitação? A solicitação será removida da análise e você receberá um email de confirmação.';
        formId = `cancel-pending-form-${agendamentoId}`;
    } else if (tipoAcao === 'cancelar') {
        mensagem = 'Tem certeza que deseja cancelar este agendamento confirmado? Você receberá um email de confirmação do cancelamento.';
        formId = `cancel-form-${agendamentoId}`;
    } else if (tipoAcao === 'excluir') {
        mensagem = 'Tem certeza que deseja excluir permanentemente este agendamento? Esta ação não pode ser desfeita!';
        formId = `delete-form-${agendamentoId}`;
    }

    showCustomConfirm(
        mensagem,
        () => {
            document.getElementById(formId).submit();
        }
    );
}

// Adicionar animações suaves aos cards
document.addEventListener('DOMContentLoaded', function () {
    const cards = document.querySelectorAll('.appointment-card');
    
    // Animação de entrada
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.3s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });

    // Adicionar efeito hover melhorado - MAIS SUTIL
    cards.forEach(card => {
        card.addEventListener('mouseenter', function () {
            this.style.transform = 'translateY(-3px) scale(1.01)'; // Reduzido de scale(1.02)
            this.style.boxShadow = '0 6px 20px rgba(64, 122, 53, 0.12)'; // Reduzido
        });

        card.addEventListener('mouseleave', function () {
            this.style.transform = 'translateY(0) scale(1)';
            this.style.boxShadow = '0 3px 12px rgba(64, 122, 53, 0.1)'; // Reduzido
        });
    });

    // Animar estatísticas
    const statNumbers = document.querySelectorAll('.stat-number');
    statNumbers.forEach(stat => {
        const finalValue = parseInt(stat.textContent);
        let currentValue = 0;
        const increment = Math.ceil(finalValue / 20);
        
        const timer = setInterval(() => {
            currentValue += increment;
            if (currentValue >= finalValue) {
                currentValue = finalValue;
                clearInterval(timer);
            }
            stat.textContent = currentValue;
        }, 50);
    });

    // Adicionar indicador visual para status pendente
    const pendingCards = document.querySelectorAll('.appointment-card.pending-card');
    pendingCards.forEach(card => {
        // Adicionar pulso sutil
        setInterval(() => {
            card.style.borderLeftColor = card.style.borderLeftColor === 'rgb(255, 152, 0)' ? '#ffc107' : '#ff9800';
        }, 2000);
    });
});

// Função para feedback visual após ações
function mostrarFeedback(tipo, mensagem) {
    const feedbackDiv = document.createElement('div');
    feedbackDiv.className = `alert alert-${tipo}`;
    feedbackDiv.style.position = 'fixed';
    feedbackDiv.style.top = '20px';
    feedbackDiv.style.right = '20px';
    feedbackDiv.style.zIndex = '9999';
    feedbackDiv.style.maxWidth = '400px';
    feedbackDiv.style.opacity = '0';
    feedbackDiv.style.transform = 'translateX(100%)';
    feedbackDiv.style.transition = 'all 0.3s ease';
    
    feedbackDiv.innerHTML = `
        <i class="fa-solid fa-${tipo === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
        ${mensagem}
    `;
    
    document.body.appendChild(feedbackDiv);
    
    // Animar entrada
    setTimeout(() => {
        feedbackDiv.style.opacity = '1';
        feedbackDiv.style.transform = 'translateX(0)';
    }, 100);
    
    // Remover após 4 segundos
    setTimeout(() => {
        feedbackDiv.style.opacity = '0';
        feedbackDiv.style.transform = 'translateX(100%)';
        
        setTimeout(() => {
            document.body.removeChild(feedbackDiv);
        }, 300);
    }, 4000);
}

// Função para atualizar contador de agendamentos em tempo real
function atualizarContadores() {
    const cards = document.querySelectorAll('.appointment-card');
    const stats = {
        total: cards.length,
        pendentes: 0,
        confirmados: 0,
        concluidos: 0,
        negados: 0,
        totalPessoas: 0
    };
    
    cards.forEach(card => {
        const pessoasElement = card.querySelector('.pessoas-info');
        if (pessoasElement) {
            const pessoasText = pessoasElement.textContent.trim();
            const pessoasMatch = pessoasText.match(/(\d+)/);
            if (pessoasMatch) {
                stats.totalPessoas += parseInt(pessoasMatch[1]);
            }
        }
        
        if (card.classList.contains('pending-card')) {
            stats.pendentes++;
        } else if (card.classList.contains('concluido-card')) {
            stats.concluidos++;
        } else if (card.classList.contains('negado-card')) {
            stats.negados++;
        } else if (card.querySelector('.status-confirmado')) {
            stats.confirmados++;
        }
    });
    
    // Atualizar os números nas estatísticas
    const statElements = document.querySelectorAll('.stat-number');
    if (statElements.length >= 6) {
        statElements[0].textContent = stats.total;
        statElements[1].textContent = stats.pendentes;
        statElements[2].textContent = stats.confirmados;
        statElements[3].textContent = stats.concluidos;
        statElements[4].textContent = stats.negados;
        statElements[5].textContent = stats.totalPessoas;
    }
}

// Função para destacar agendamentos próximos
function destacarAgendamentosProximos() {
    const hoje = new Date();
    const amanha = new Date(hoje);
    amanha.setDate(hoje.getDate() + 1);
    
    const cards = document.querySelectorAll('.appointment-card');
    
    cards.forEach(card => {
        const dataElement = card.querySelector('.appointment-date');
        if (dataElement) {
            const dataTexto = dataElement.textContent.trim();
            const dataMatch = dataTexto.match(/(\d{2})\/(\d{2})\/(\d{4})/);
            
            if (dataMatch) {
                const dataAgendamento = new Date(dataMatch[3], dataMatch[2] - 1, dataMatch[1]);
                
                // Se é amanhã e está confirmado
                if (dataAgendamento.toDateString() === amanha.toDateString() && 
                    card.querySelector('.status-confirmado')) {
                    
                    // Adicionar destaque especial
                    card.style.border = '2px solid #ff9800';
                    card.style.boxShadow = '0 8px 25px rgba(255, 152, 0, 0.3)';
                    
                    // Adicionar badge "AMANHÃ"
                    const badge = document.createElement('div');
                    badge.style.cssText = `
                        position: absolute;
                        top: 10px;
                        right: 10px;
                        background: linear-gradient(135deg, #ff9800, #f57c00);
                        color: white;
                        padding: 4px 8px;
                        border-radius: 12px;
                        font-size: 10px;
                        font-weight: bold;
                        z-index: 10;
                    `;
                    badge.textContent = 'AMANHÃ';
                    card.style.position = 'relative';
                    card.appendChild(badge);
                }
            }
        }
    });
}

// Executar quando a página carregar
document.addEventListener('DOMContentLoaded', function () {
    destacarAgendamentosProximos();
    atualizarContadores();
});