<?php
// acexx/back-end/verificar_login_ajax.php
session_start();

// Definir o cabeçalho para JSON
header('Content-Type: application/json');

// Verificar se é uma empresa logada
$empresa_logada = isset($_SESSION['empresa_logada']) && $_SESSION['empresa_logada'] === true;

// Verificar se é um usuário comum logado
$usuario_logado = isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true;

// Determinar tipo de conta e status
$logado = $empresa_logada || $usuario_logado;
$tipo_conta = '';
$nome = '';
$email = '';

if ($empresa_logada) {
    $tipo_conta = 'empresa';
    $nome = $_SESSION['empresa_nome'] ?? '';
    $email = $_SESSION['empresa_email'] ?? '';
} elseif ($usuario_logado) {
    $tipo_conta = 'usuario';
    $nome = $_SESSION['usuario_nome'] ?? '';
    $email = $_SESSION['usuario_email'] ?? '';
}

// Retornar resposta em JSON
echo json_encode([
    'logado' => $logado,
    'tipo_conta' => $tipo_conta,
    'usuario_nome' => $nome,
    'usuario_email' => $email,
    'empresa_logada' => $empresa_logada,
    'usuario_logado' => $usuario_logado
]);
?>