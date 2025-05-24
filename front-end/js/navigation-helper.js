/**
 * Sistema de Navegação Inteligente - Biotério FSA
 * Este arquivo contém funções para navegação baseada no status de login do usuário
 */

/**
 * Verifica se o usuário está logado e redireciona adequadamente
 * @param {Event} event - Evento do clique
 * @param {string} destino - 'agendamento' ou 'login' ou 'dados'
 */
function verificarLoginENavegar(event, destino) {
    event.preventDefault();
    
    fetch('../back-end/verificar_login_ajax.php')
        .then(response => response.json())
        .then(data => {
            if (data.logado) {
                // Usuário logado - redirecionar conforme destino
                switch(destino) {
                    case 'agendamento':
                        window.location.href = 'pag_agendar_logado.php';
                        break;
                    case 'dados':
                        window.location.href = 'pag_dados_usuario.php';
                        break;
                    case 'meus-agendamentos':
                        window.location.href = 'pag_meus_agendamentos.php';
                        break;
                    default:
                        window.location.href = 'pag_dados_usuario.php';
                }
            } else {
                // Usuário não logado - ir para login com parâmetro adequado
                if (destino === 'agendamento') {
                    window.location.href = 'pag_login_usuario.php?redirect_to=agendamento';
                } else {
                    window.location.href = 'pag_login_usuario.php';
                }
            }
        })
        .catch(error => {
            console.error('Erro ao verificar login:', error);
            // Em caso de erro, sempre levar para login
            const redirectParam = destino === 'agendamento' ? '?redirect_to=agendamento' : '';
            window.location.href = `pag_login_usuario.php${redirectParam}`;
        });
}

/**
 * Atualiza links de agendamento na página atual para usar navegação inteligente
 */
function ativarNavegacaoInteligente() {
    // Encontrar todos os links que vão para agendamento
    const linksAgendamento = document.querySelectorAll('a[href*="pag_agendar"], a[href*="agendamento"]');
    
    linksAgendamento.forEach(link => {
        // Verificar se não é um link para página logada (evitar duplicação)
        if (!link.href.includes('pag_agendar_logado.php')) {
            link.addEventListener('click', function(event) {
                verificarLoginENavegar(event, 'agendamento');
            });
        }
    });
    
    // Encontrar botões específicos de "Fazer Agendamento"
    const botoesAgendamento = document.querySelectorAll('button[data-action="agendamento"], .btn-agendamento');
    
    botoesAgendamento.forEach(botao => {
        botao.addEventListener('click', function(event) {
            verificarLoginENavegar(event, 'agendamento');
        });
    });
}

/**
 * Verifica status do usuário e atualiza interface se necessário
 */
function verificarStatusUsuario() {
    fetch('../back-end/verificar_login_ajax.php')
        .then(response => response.json())
        .then(data => {
            if (data.logado) {
                // Usuário está logado - atualizar interface se necessário
                atualizarInterfaceUsuarioLogado(data.usuario_nome);
            } else {
                // Usuário não está logado
                atualizarInterfaceUsuarioDeslogado();
            }
        })
        .catch(error => {
            console.log('Erro ao verificar status do usuário:', error);
        });
}

/**
 * Atualiza interface para usuário logado
 * @param {string} nomeUsuario - Nome do usuário logado
 */
function atualizarInterfaceUsuarioLogado(nomeUsuario) {
    // Encontrar elementos que devem ser atualizados para usuários logados
    const elementosLogin = document.querySelectorAll('.login-required-text');
    elementosLogin.forEach(elemento => {
        elemento.textContent = `Olá, ${nomeUsuario}!`;
    });
    
    // Atualizar textos de botões se necessário
    const botoesLogin = document.querySelectorAll('.btn-login-text');
    botoesLogin.forEach(botao => {
        botao.textContent = 'Meus Dados';
    });
}

/**
 * Atualiza interface para usuário não logado
 */
function atualizarInterfaceUsuarioDeslogado() {
    // Manter interface padrão para usuários não logados
    console.log('Usuário não está logado - interface padrão mantida');
}

/**
 * Função de popup personalizado (pode ser usada em qualquer página)
 */
function showPopup(title, message, type = 'info', buttons = null) {
    // Verificar se já existe um popup na página
    let overlay = document.getElementById('popup-overlay');
    
    if (!overlay) {
        // Criar popup se não existir
        overlay = createPopupOverlay();
        document.body.appendChild(overlay);
    }
    
    const titleElement = document.getElementById('popup-title');
    const messageElement = document.getElementById('popup-message');
    const iconElement = document.getElementById('popup-icon');
    const buttonsContainer = document.getElementById('popup-buttons');
    
    titleElement.textContent = title;
    messageElement.textContent = message;
    
    // Configurar ícone baseado no tipo
    let iconClass = 'fa-info-circle';
    let iconColorClass = '';
    
    switch(type) {
        case 'error':
            iconClass = 'fa-exclamation-triangle';
            iconColorClass = 'error';
            break;
        case 'success':
            iconClass = 'fa-check-circle';
            iconColorClass = 'success';
            break;
        case 'warning':
            iconClass = 'fa-exclamation-triangle';
            iconColorClass = 'warning';
            break;
        default:
            iconClass = 'fa-info-circle';
            iconColorClass = '';
    }
    
    iconElement.innerHTML = `<i class="fa-solid ${iconClass}"></i>`;
    iconElement.className = `popup-icon ${iconColorClass}`;
    
    // Configurar botões
    if (buttons) {
        buttonsContainer.innerHTML = '';
        buttons.forEach(button => {
            const btn = document.createElement('button');
            btn.className = `popup-btn ${button.class || 'popup-btn-primary'}`;
            btn.textContent = button.text;
            btn.onclick = button.action;
            buttonsContainer.appendChild(btn);
        });
    } else {
        buttonsContainer.innerHTML = '<button class="popup-btn popup-btn-primary" onclick="closePopup()">OK</button>';
    }
    
    overlay.style.display = 'flex';
}

/**
 * Fecha o popup
 */
function closePopup() {
    const overlay = document.getElementById('popup-overlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

/**
 * Cria a estrutura HTML do popup se não existir
 */
function createPopupOverlay() {
    const overlay = document.createElement('div');
    overlay.id = 'popup-overlay';
    overlay.className = 'custom-popup-overlay';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 10000;
    `;
    
    overlay.innerHTML = `
        <div class="custom-popup" style="
            background-color: rgb(225, 225, 228);
            border-radius: 15px;
            padding: 30px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: popupSlideIn 0.3s ease-out;
        ">
            <div class="popup-icon" id="popup-icon" style="font-size: 50px; margin-bottom: 20px; color: #ffc107;">
                <i class="fa-solid fa-info-circle"></i>
            </div>
            <div class="popup-title" id="popup-title" style="font-size: 20px; font-weight: bold; color: rgb(55, 75, 51); margin-bottom: 15px;">Informação</div>
            <div class="popup-message" id="popup-message" style="font-size: 16px; color: rgb(60, 59, 59); margin-bottom: 25px; line-height: 1.4;">Mensagem do sistema</div>
            <div class="popup-buttons" id="popup-buttons" style="display: flex; gap: 15px; justify-content: center;">
                <button class="popup-btn popup-btn-primary" onclick="closePopup()" style="
                    padding: 12px 25px;
                    border: none;
                    border-radius: 8px;
                    font-size: 16px;
                    font-weight: bold;
                    cursor: pointer;
                    transition: all 0.3s;
                    font-family: Georgia, 'Times New Roman', Times, serif;
                    background-color: rgba(64, 122, 53, 0.819);
                    color: white;
                ">OK</button>
            </div>
        </div>
    `;
    
    return overlay;
}

// Ativar navegação inteligente quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    ativarNavegacaoInteligente();
    verificarStatusUsuario();
});

// Exportar funções para uso global
window.verificarLoginENavegar = verificarLoginENavegar;
window.ativarNavegacaoInteligente = ativarNavegacaoInteligente;
window.verificarStatusUsuario = verificarStatusUsuario;
window.showPopup = showPopup;
window.closePopup = closePopup;