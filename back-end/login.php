<?php
// acexx/back-end/login.php
session_start();
require_once 'functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ra = trim($_POST['ra'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($ra) || empty($senha)) {
        header("Location: pag_adm.php?erro_login=true");
        exit();
    }

    // Validação simplificada para demonstração
    // Admin: RA = 1, Senha = admin123
    // Operador: RA = 2, Senha = operador123
    $usuarios_funcionarios = [
        '1' => [
            'senha' => 'admin123',
            'nome' => 'Administrador do Sistema',
            'email' => 'admin@bioterio.com',
            'tipo' => 'admin'
        ],
        '2' => [
            'senha' => 'operador123',
            'nome' => 'Operador do Sistema',
            'email' => 'operador@bioterio.com',
            'tipo' => 'operador'
        ]
    ];

    if (isset($usuarios_funcionarios[$ra]) && $usuarios_funcionarios[$ra]['senha'] === $senha) {
        $usuario = $usuarios_funcionarios[$ra];
        
        // Configurar sessão
        $_SESSION['logado'] = true;
        $_SESSION['usuario_id'] = $ra; // Usando RA como ID para simplicidade
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_email'] = $usuario['email'];
        $_SESSION['tipo_usuario'] = $usuario['tipo'];
        $_SESSION['ra'] = $ra;
        
        // Redirecionar baseado no tipo de usuário
        if ($usuario['tipo'] === 'admin') {
            header("Location: pag_agendamentos_adm.php");
        } elseif ($usuario['tipo'] === 'operador') {
            header("Location: pag_agendamentos_operador.php");
        } else {
            header("Location: ../front-end/pag_inicial.html");
        }
        exit();
        
    } else {
        header("Location: pag_adm.php?erro_login=true");
        exit();
    }
} else {
    header("Location: pag_adm.php");
    exit();
}
?>