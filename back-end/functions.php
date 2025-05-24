<?php

function conectarBanco() {
    $servidor = "localhost";
    $usuario = "root"; 
    $senha = ""; // Altere para sua senha do MySQL
    $banco = "bioterio_db";
    $porta = "3306";

    try {
        $conexao = new PDO("mysql:host=$servidor;port=$porta;dbname=$banco;charset=utf8", $usuario, $senha);
        $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conexao;
    } catch (PDOException $e) {
        die("Erro de conexão com o banco de dados: " . $e->getMessage());
    }
}

// Função MELHORADA para validar CPF
function validarCPF($cpf) {
    // Remove todos os caracteres não numéricos
    $cpf = preg_replace('/\D/', '', $cpf);
    
    // Verifica se tem 11 dígitos
    if (strlen($cpf) != 11) {
        return false;
    }
    
    // Verifica se não é uma sequência de números iguais
    if (preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }
    
    // Calcula o primeiro dígito verificador
    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += intval($cpf[$i]) * (10 - $i);
    }
    $resto = $soma % 11;
    $dv1 = ($resto < 2) ? 0 : (11 - $resto);
    
    // Verifica o primeiro dígito verificador
    if (intval($cpf[9]) !== $dv1) {
        return false;
    }
    
    // Calcula o segundo dígito verificador
    $soma = 0;
    for ($i = 0; $i < 10; $i++) {
        $soma += intval($cpf[$i]) * (11 - $i);
    }
    $resto = $soma % 11;
    $dv2 = ($resto < 2) ? 0 : (11 - $resto);
    
    // Verifica o segundo dígito verificador
    return intval($cpf[10]) === $dv2;
}

// Função MELHORADA para validar CNPJ
function validarCNPJ($cnpj) {
    // Remove todos os caracteres não numéricos
    $cnpj = preg_replace('/\D/', '', $cnpj);
    
    // Verifica se tem 14 dígitos
    if (strlen($cnpj) != 14) {
        return false;
    }
    
    // Verifica se não é uma sequência de números iguais
    if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
        return false;
    }
    
    // Calcula o primeiro dígito verificador
    $soma = 0;
    $multiplicadores1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    
    for ($i = 0; $i < 12; $i++) {
        $soma += intval($cnpj[$i]) * $multiplicadores1[$i];
    }
    
    $resto = $soma % 11;
    $dv1 = ($resto < 2) ? 0 : (11 - $resto);
    
    // Verifica o primeiro dígito verificador
    if (intval($cnpj[12]) !== $dv1) {
        return false;
    }
    
    // Calcula o segundo dígito verificador
    $soma = 0;
    $multiplicadores2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    
    for ($i = 0; $i < 13; $i++) {
        $soma += intval($cnpj[$i]) * $multiplicadores2[$i];
    }
    
    $resto = $soma % 11;
    $dv2 = ($resto < 2) ? 0 : (11 - $resto);
    
    // Verifica o segundo dígito verificador
    return intval($cnpj[13]) === $dv2;
}

// Função para gerar horários disponíveis (10:00 às 18:00, de 30 em 30 min)
function gerarHorariosDisponiveis() {
    $horarios = [];
    $inicio = new DateTime('10:00');
    $fim = new DateTime('18:00');
    
    while ($inicio <= $fim) {
        $horarios[] = $inicio->format('H:i');
        $inicio->add(new DateInterval('PT30M'));
    }
    
    return $horarios;
}

// Função para verificar se uma data é dia útil (segunda a sexta)
function isDiaUtil($data) {
    $dayOfWeek = date('N', strtotime($data));
    return $dayOfWeek >= 1 && $dayOfWeek <= 5;
}

// Função para verificar se uma data está disponível para agendamento
function dataDisponivel($data) {
    try {
        $conexao = conectarBanco();
        
        // Verifica se é dia útil
        if (!isDiaUtil($data)) {
            return false;
        }
        
        // Conta agendamentos confirmados na data
        $stmt = $conexao->prepare("
            SELECT COUNT(*) as total 
            FROM agendamentos 
            WHERE data_agendamento = ? AND status = 'confirmado'
        ");
        $stmt->execute([$data]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $resultado['total'] < 10;
    } catch (PDOException $e) {
        return false;
    }
}

// Função para contar agendamentos em uma data específica
function contarAgendamentosData($data) {
    try {
        $conexao = conectarBanco();
        $stmt = $conexao->prepare("
            SELECT COUNT(*) as total 
            FROM agendamentos 
            WHERE data_agendamento = ? AND status = 'confirmado'
        ");
        $stmt->execute([$data]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total'];
    } catch (PDOException $e) {
        return 0;
    }
}

// Função para verificar permissões do usuário
function verificarPermissao($tipoNecessario) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
        return false;
    }
    
    $tipoUsuario = $_SESSION['tipo_usuario'] ?? 'normal';
    
    switch ($tipoNecessario) {
        case 'admin':
            return $tipoUsuario === 'admin';
        case 'operador':
            return in_array($tipoUsuario, ['admin', 'operador']);
        case 'normal':
            return in_array($tipoUsuario, ['admin', 'operador', 'normal']);
        default:
            return false;
    }
}

// Função para gerar datas disponíveis (próximos 30 dias úteis)
function gerarDatasDisponiveis($limite = 30) {
    $datas = [];
    $dataAtual = new DateTime();
    $contador = 0;
    
    while ($contador < $limite) {
        $dataAtual->add(new DateInterval('P1D'));
        
        // Verifica se é dia útil (segunda a sexta)
        if ($dataAtual->format('N') >= 1 && $dataAtual->format('N') <= 5) {
            $dataSql = $dataAtual->format('Y-m-d');
            
            // Verifica se a data não está bloqueada
            if (dataDisponivel($dataSql)) {
                $datas[] = [
                    'data' => $dataSql,
                    'data_formatada' => $dataAtual->format('d/m/Y'),
                    'dia_semana' => $dataAtual->format('l'),
                    'agendamentos_restantes' => 10 - contarAgendamentosData($dataSql)
                ];
                $contador++;
            }
        }
    }
    
    return $datas;
}

// Função para cancelar agendamento
function cancelarAgendamento($agendamentoId, $usuarioId, $motivo = '') {
    try {
        $conexao = conectarBanco();
        
        // Verifica se o agendamento existe e pertence ao usuário ou se é admin/operador
        $stmt = $conexao->prepare("
            SELECT a.*, u.tipo_usuario 
            FROM agendamentos a
            LEFT JOIN usuarios u ON u.id = ?
            WHERE a.id = ? AND (a.usuario_id = ? OR u.tipo_usuario IN ('admin', 'operador'))
        ");
        $stmt->execute([$usuarioId, $agendamentoId, $usuarioId]);
        $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$agendamento) {
            return ['sucesso' => false, 'mensagem' => 'Agendamento não encontrado ou sem permissão.'];
        }
        
        if ($agendamento['status'] === 'cancelado') {
            return ['sucesso' => false, 'mensagem' => 'Agendamento já foi cancelado.'];
        }
        
        // Cancela o agendamento
        $stmt = $conexao->prepare("
            UPDATE agendamentos 
            SET status = 'cancelado', cancelado_por = ?, data_cancelamento = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$usuarioId, $agendamentoId]);
        
        return ['sucesso' => true, 'mensagem' => 'Agendamento cancelado com sucesso.'];
        
    } catch (PDOException $e) {
        return ['sucesso' => false, 'mensagem' => 'Erro ao cancelar agendamento: ' . $e->getMessage()];
    }
}

// Função para remover agendamento (apenas admin)
function removerAgendamento($agendamentoId, $usuarioId) {
    try {
        $conexao = conectarBanco();
        
        // Verifica se o usuário é admin
        $stmt = $conexao->prepare("SELECT tipo_usuario FROM usuarios WHERE id = ?");
        $stmt->execute([$usuarioId]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario || $usuario['tipo_usuario'] !== 'admin') {
            return ['sucesso' => false, 'mensagem' => 'Apenas administradores podem remover agendamentos.'];
        }
        
        // Remove o agendamento
        $stmt = $conexao->prepare("DELETE FROM agendamentos WHERE id = ?");
        $stmt->execute([$agendamentoId]);
        
        if ($stmt->rowCount() > 0) {
            return ['sucesso' => true, 'mensagem' => 'Agendamento removido com sucesso.'];
        } else {
            return ['sucesso' => false, 'mensagem' => 'Agendamento não encontrado.'];
        }
        
    } catch (PDOException $e) {
        return ['sucesso' => false, 'mensagem' => 'Erro ao remover agendamento: ' . $e->getMessage()];
    }
}

// Função para verificar login de funcionário (admin/operador)
function verificarLoginFuncionario($ra, $senha) {
    try {
        $conexao = conectarBanco();
        $stmt = $conexao->prepare("
            SELECT id, nome, email, tipo_usuario, senha 
            FROM usuarios 
            WHERE ra = ? AND tipo_usuario IN ('admin', 'operador') AND ativo = 1
        ");
        $stmt->execute([$ra]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            return [
                'sucesso' => true,
                'usuario' => $usuario
            ];
        }
        
        return ['sucesso' => false, 'mensagem' => 'RA ou senha incorretos.'];
        
    } catch (PDOException $e) {
        return ['sucesso' => false, 'mensagem' => 'Erro na autenticação: ' . $e->getMessage()];
    }
}

// NOVA FUNÇÃO: Detectar automaticamente se o login é de empresa ou usuário
function detectarTipoLogin($valor) {
    // Remove caracteres especiais para análise
    $valorLimpo = preg_replace('/\D/', '', $valor);
    
    // Se contém @ é email
    if (strpos($valor, '@') !== false) {
        return 'email';
    }
    
    // Se tem 14 dígitos numéricos ou contém /, provavelmente é CNPJ
    if (strlen($valorLimpo) == 14 || strpos($valor, '/') !== false) {
        return 'cnpj';
    }
    
    // Se tem 11 dígitos, pode ser CPF, mas pessoa física usa email
    if (strlen($valorLimpo) == 11) {
        return 'possivel_cpf';
    }
    
    // Default: email
    return 'email';
}

// NOVA FUNÇÃO: Validação unificada de login
function validarLogin($login, $senha) {
    $tipoDetectado = detectarTipoLogin($login);
    
    try {
        $conexao = conectarBanco();
        
        if ($tipoDetectado === 'cnpj' || $tipoDetectado === 'possivel_cpf') {
            // Tentar login como empresa
            $stmt = $conexao->prepare("
                SELECT id, nome_instituicao as nome, email, senha, cnpj, 'empresa' as tipo
                FROM empresas 
                WHERE (email = ? OR cnpj = ?) AND ativo = 1
            ");
            $cnpjLimpo = preg_replace('/\D/', '', $login);
            $stmt->execute([$login, $cnpjLimpo]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usuario && password_verify($senha, $usuario['senha'])) {
                return [
                    'sucesso' => true,
                    'usuario' => $usuario,
                    'tipo_conta' => 'empresa'
                ];
            }
        }
        
        // Tentar login como usuário comum (sempre com email)
        $stmt = $conexao->prepare("
            SELECT id, nome, email, senha, 'usuario' as tipo
            FROM usuarios 
            WHERE email = ?
        ");
        $stmt->execute([$login]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            return [
                'sucesso' => true,
                'usuario' => $usuario,
                'tipo_conta' => 'usuario'
            ];
        }
        
        return ['sucesso' => false, 'mensagem' => 'Email/CNPJ ou senha incorretos.'];
        
    } catch (PDOException $e) {
        return ['sucesso' => false, 'mensagem' => 'Erro na autenticação: ' . $e->getMessage()];
    }
}
?>