<?php
// acexx/back-end/auth_usuario.php
session_start();
require_once 'functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'login') {
        // PROCESSO DE LOGIN USUÁRIO COMUM
        $email = trim($_POST['login'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $redirect_to = $_POST['redirect_to'] ?? '';

        if (empty($email) || empty($senha)) {
            header("Location: ../front-end/pag_login_usuario.php?erro_login=true");
            exit();
        }

        try {
            $conexao = conectarBanco();
            
            // Login de usuário comum (sempre com email)
            $stmt = $conexao->prepare("
                SELECT id, nome, email, senha
                FROM usuarios 
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usuario && password_verify($senha, $usuario['senha'])) {
                // Login de usuário comum bem-sucedido
                $_SESSION['usuario_logado'] = true;
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_email'] = $usuario['email'];
                $_SESSION['tipo_conta'] = 'usuario';
                
                // Limpar sessões de empresa se existirem
                unset($_SESSION['empresa_logada']);
                unset($_SESSION['empresa_id']);
                unset($_SESSION['empresa_nome']);
                unset($_SESSION['empresa_email']);
                unset($_SESSION['empresa_cnpj']);
                
                // Redirecionar baseado no parâmetro redirect_to
                if ($redirect_to === 'agendamento') {
                    header("Location: ../front-end/pag_agendar_logado.php");
                } else {
                    // Redireciona para onde o usuário estava tentando ir
                    $redirect = $_SESSION['redirect_after_login'] ?? '../front-end/pag_agendar_logado.php';
                    unset($_SESSION['redirect_after_login']);
                    header("Location: $redirect");
                }
                exit();
            } else {
                $redirect_param = $redirect_to ? "&redirect_to=$redirect_to" : "";
                header("Location: ../front-end/pag_login_usuario.php?erro_login=true$redirect_param");
                exit();
            }
        } catch (Exception $e) {
            error_log("Erro no login de usuário: " . $e->getMessage());
            $redirect_param = $redirect_to ? "&redirect_to=$redirect_to" : "";
            header("Location: ../front-end/pag_login_usuario.php?erro_login=true$redirect_param");
            exit();
        }

    } elseif ($acao === 'cadastro') {
        // PROCESSO DE CADASTRO PESSOA FÍSICA
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $cpf = $_POST['cpf'] ?? '';
        $senha = $_POST['senha'] ?? '';
        $confirmar_senha = $_POST['confirmar_senha'] ?? '';

        // Validações básicas
        if (empty($nome) || empty($email) || empty($cpf) || empty($senha) || empty($confirmar_senha)) {
            header("Location: ../front-end/pag_login_usuario.php?erro_cadastro=" . urlencode("Preencha todos os campos.") . "&tab=cadastro");
            exit();
        }

        if ($senha !== $confirmar_senha) {
            header("Location: ../front-end/pag_login_usuario.php?erro_cadastro=" . urlencode("As senhas não coincidem.") . "&tab=cadastro");
            exit();
        }

        if (strlen($senha) < 6) {
            header("Location: ../front-end/pag_login_usuario.php?erro_cadastro=" . urlencode("A senha deve ter pelo menos 6 caracteres.") . "&tab=cadastro");
            exit();
        }

        // Validação de email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header("Location: ../front-end/pag_login_usuario.php?erro_cadastro=" . urlencode("Email inválido.") . "&tab=cadastro");
            exit();
        }

        // Validação do CPF usando a função melhorada
        if (!validarCPF($cpf)) {
            header("Location: ../front-end/pag_login_usuario.php?erro_cadastro=" . urlencode("CPF inválido.") . "&tab=cadastro");
            exit();
        }

        // Limpa o CPF
        $cpf_limpo = preg_replace('/\D/', '', $cpf);

        try {
            $conexao = conectarBanco();

            $erros = [];

            // Verifica se email já existe em usuários
            $stmt = $conexao->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $erros[] = "Este email já está cadastrado como pessoa física";
            }

            // Verifica se email já existe em empresas
            $stmt = $conexao->prepare("SELECT COUNT(*) FROM empresas WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $erros[] = "Este email já está cadastrado como empresa";
            }

            // Verifica se CPF já existe
            $stmt = $conexao->prepare("SELECT COUNT(*) FROM usuarios WHERE cpf = ?");
            $stmt->execute([$cpf_limpo]);
            if ($stmt->fetchColumn() > 0) {
                $erros[] = "Este CPF já está cadastrado";
            }

            // Se há erros, redireciona com mensagem
            if (!empty($erros)) {
                $mensagem_erro = implode(". ", $erros) . ".";
                header("Location: ../front-end/pag_login_usuario.php?erro_cadastro=" . urlencode($mensagem_erro) . "&tab=cadastro");
                exit();
            }

            // Criptografa a senha
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            // Insere o usuário
            $stmt = $conexao->prepare("INSERT INTO usuarios (nome, email, cpf, senha) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nome, $email, $cpf_limpo, $senha_hash]);

            header("Location: ../front-end/pag_login_usuario.php?cadastro_sucesso=true");
            exit();

        } catch (PDOException $e) {
            error_log("Erro no cadastro: " . $e->getMessage());
            header("Location: ../front-end/pag_login_usuario.php?erro_cadastro=" . urlencode("Erro ao cadastrar. Tente novamente.") . "&tab=cadastro");
            exit();
        }

    } elseif ($acao === 'logout') {
        // PROCESSO DE LOGOUT USUÁRIO
        session_destroy();
        header("Location: ../front-end/pag_login_usuario.php");
        exit();
    }
} else {
    header("Location: ../front-end/pag_login_usuario.php");
    exit();
}
?>