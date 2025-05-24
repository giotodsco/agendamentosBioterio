<?php
// acexx/back-end/auth_unificado.php
session_start();
require_once 'functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'login') {
        // PROCESSO DE LOGIN UNIFICADO
        $login = trim($_POST['login'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $redirect_to = $_POST['redirect_to'] ?? '';

        if (empty($login) || empty($senha)) {
            header("Location: ../front-end/pag_login_usuario.php?erro_login=true");
            exit();
        }

        try {
            // Usar função unificada de validação
            $resultado = validarLogin($login, $senha);
            
            if ($resultado['sucesso']) {
                $usuario = $resultado['usuario'];
                $tipoConta = $resultado['tipo_conta'];
                
                if ($tipoConta === 'empresa') {
                    // Login de empresa bem-sucedido
                    $_SESSION['empresa_logada'] = true;
                    $_SESSION['empresa_id'] = $usuario['id'];
                    $_SESSION['empresa_nome'] = $usuario['nome'];
                    $_SESSION['empresa_email'] = $usuario['email'];
                    $_SESSION['empresa_cnpj'] = $usuario['cnpj'];
                    $_SESSION['tipo_conta'] = 'empresa';
                    
                    // Limpar sessões de usuário comum se existirem
                    unset($_SESSION['usuario_logado']);
                    unset($_SESSION['usuario_id']);
                    unset($_SESSION['usuario_nome']);
                    unset($_SESSION['usuario_email']);
                    
                    header("Location: ../front-end/pag_agendar_empresa.php");
                    exit();
                    
                } else {
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
                }
            } else {
                $redirect_param = $redirect_to ? "&redirect_to=$redirect_to" : "";
                header("Location: ../front-end/pag_login_usuario.php?erro_login=true$redirect_param");
                exit();
            }
        } catch (Exception $e) {
            error_log("Erro no login unificado: " . $e->getMessage());
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

    } elseif ($acao === 'cadastro_empresa') {
        // PROCESSO DE CADASTRO DE EMPRESA
        $nome_instituicao = trim($_POST['nome_instituicao'] ?? '');
        $cnpj = $_POST['cnpj'] ?? '';
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $confirmar_senha = $_POST['confirmar_senha'] ?? '';

        // Validações básicas
        if (empty($nome_instituicao) || empty($cnpj) || empty($email) || empty($senha) || empty($confirmar_senha)) {
            header("Location: ../front-end/pag_login_usuario.php?erro_empresa=" . urlencode("Preencha todos os campos.") . "&tab=empresa");
            exit();
        }

        if ($senha !== $confirmar_senha) {
            header("Location: ../front-end/pag_login_usuario.php?erro_empresa=" . urlencode("As senhas não coincidem.") . "&tab=empresa");
            exit();
        }

        if (strlen($senha) < 6) {
            header("Location: ../front-end/pag_login_usuario.php?erro_empresa=" . urlencode("A senha deve ter pelo menos 6 caracteres.") . "&tab=empresa");
            exit();
        }

        // Validação de email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header("Location: ../front-end/pag_login_usuario.php?erro_empresa=" . urlencode("Email inválido.") . "&tab=empresa");
            exit();
        }

        // Validação do CNPJ usando a função melhorada
        if (!validarCNPJ($cnpj)) {
            header("Location: ../front-end/pag_login_usuario.php?erro_empresa=" . urlencode("CNPJ inválido.") . "&tab=empresa");
            exit();
        }

        // Limpa o CNPJ
        $cnpj_limpo = preg_replace('/\D/', '', $cnpj);

        try {
            $conexao = conectarBanco();

            $erros = [];

            // Verifica se email já existe em empresas
            $stmt = $conexao->prepare("SELECT COUNT(*) FROM empresas WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $erros[] = "Este email já está cadastrado como empresa";
            }

            // Verifica se email já existe em usuários normais
            $stmt = $conexao->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $erros[] = "Este email já está cadastrado como pessoa física";
            }

            // Verifica se CNPJ já existe
            $stmt = $conexao->prepare("SELECT COUNT(*) FROM empresas WHERE cnpj = ?");
            $stmt->execute([$cnpj_limpo]);
            if ($stmt->fetchColumn() > 0) {
                $erros[] = "Este CNPJ já está cadastrado";
            }

            // Se há erros, redireciona com mensagem
            if (!empty($erros)) {
                $mensagem_erro = implode(". ", $erros) . ".";
                header("Location: ../front-end/pag_login_usuario.php?erro_empresa=" . urlencode($mensagem_erro) . "&tab=empresa");
                exit();
            }

            // Criptografa a senha
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            // Insere a empresa
            $stmt = $conexao->prepare("INSERT INTO empresas (nome_instituicao, cnpj, email, senha, ativo) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$nome_instituicao, $cnpj_limpo, $email, $senha_hash]);

            header("Location: ../front-end/pag_login_usuario.php?cadastro_sucesso=true&tab=empresa");
            exit();

        } catch (PDOException $e) {
            error_log("Erro no cadastro da empresa: " . $e->getMessage());
            header("Location: ../front-end/pag_login_usuario.php?erro_empresa=" . urlencode("Erro ao cadastrar. Tente novamente.") . "&tab=empresa");
            exit();
        }

    } elseif ($acao === 'logout') {
        // PROCESSO DE LOGOUT USUÁRIO
        session_destroy();
        header("Location: ../front-end/pag_login_usuario.php");
        exit();
        
    } elseif ($acao === 'logout_empresa') {
        // PROCESSO DE LOGOUT EMPRESA
        session_destroy();
        header("Location: ../front-end/pag_login_usuario.php");
        exit();
    }
} else {
    header("Location: ../front-end/pag_login_usuario.php");
    exit();
}
?>