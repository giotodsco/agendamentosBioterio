<?php
// acexx/back-end/check_login.php
session_start();

function verificarLoginUsuario($redirect_to_login = true) {
    if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
        if ($redirect_to_login) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header("Location: ../front-end/pag_login_usuario.php?login_required=true");
            exit();
        }
        return false;
    }
    return true;
}

function getUsuarioLogado() {
    if (verificarLoginUsuario(false)) {
        return [
            'id' => $_SESSION['usuario_id'],
            'nome' => $_SESSION['usuario_nome'],
            'email' => $_SESSION['usuario_email']
        ];
    }
    return null;
}
?>