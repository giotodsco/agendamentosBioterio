<?php
// acexx/back-end/functions.php
// Incluir as funções existentes + novas funções de email
require_once 'email_config.php';

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

// Função CORRIGIDA para validar CPF - algoritmo oficial da Receita Federal
function validarCPF($cpf) {
    // Remove todos os caracteres não numéricos
    $cpf = preg_replace('/\D/', '', $cpf);
    
    // Verifica se tem 11 dígitos
    if (strlen($cpf) != 11) {
        return false;
    }
    
    // Verifica CPFs conhecidos como inválidos
    $cpfsInvalidos = [
        '00000000000', '11111111111', '22222222222', '33333333333',
        '44444444444', '55555555555', '66666666666', '77777777777',
        '88888888888', '99999999999'
    ];
    
    if (in_array($cpf, $cpfsInvalidos)) {
        return false;
    }
    
    // Calcula o primeiro dígito verificador
    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += intval($cpf[$i]) * (10 - $i);
    }
    $resto = $soma % 11;
    $digitoVerificador1 = ($resto < 2) ? 0 : (11 - $resto);
    
    // Verifica o primeiro dígito verificador
    if (intval($cpf[9]) != $digitoVerificador1) {
        return false;
    }
    
    // Calcula o segundo dígito verificador
    $soma = 0;
    for ($i = 0; $i < 10; $i++) {
        $soma += intval($cpf[$i]) * (11 - $i);
    }
    $resto = $soma % 11;
    $digitoVerificador2 = ($resto < 2) ? 0 : (11 - $resto);
    
    // Verifica o segundo dígito verificador
    return intval($cpf[10]) == $digitoVerificador2;
}

// Função CORRIGIDA para validar CNPJ - algoritmo oficial da Receita Federal
function validarCNPJ($cnpj) {
    // Remove todos os caracteres não numéricos
    $cnpj = preg_replace('/\D/', '', $cnpj);
    
    // Verifica se tem 14 dígitos
    if (strlen($cnpj) != 14) {
        return false;
    }
    
    // Verifica CNPJs conhecidos como inválidos
    $cnpjsInvalidos = [
        '00000000000000', '11111111111111', '22222222222222', '33333333333333',
        '44444444444444', '55555555555555', '66666666666666', '77777777777777',
        '88888888888888', '99999999999999'
    ];
    
    if (in_array($cnpj, $cnpjsInvalidos)) {
        return false;
    }
    
    // Calcula o primeiro dígito verificador
    $soma = 0;
    $multiplicadores1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    
    for ($i = 0; $i < 12; $i++) {
        $soma += intval($cnpj[$i]) * $multiplicadores1[$i];
    }
    
    $resto = $soma % 11;
    $digitoVerificador1 = ($resto < 2) ? 0 : (11 - $resto);
    
    // Verifica o primeiro dígito verificador
    if (intval($cnpj[12]) != $digitoVerificador1) {
        return false;
    }
    
    // Calcula o segundo dígito verificador
    $soma = 0;
    $multiplicadores2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    
    for ($i = 0; $i < 13; $i++) {
        $soma += intval($cnpj[$i]) * $multiplicadores2[$i];
    }
    
    $resto = $soma % 11;
    $digitoVerificador2 = ($resto < 2) ? 0 : (11 - $resto);
    
    // Verifica o segundo dígito verificador
    return intval($cnpj[13]) == $digitoVerificador2;
}

// FUNÇÃO MELHORADA: Detectar automaticamente se o login é de empresa ou usuário
function detectarTipoLogin($valor) {
    // Remove caracteres especiais para análise
    $valorLimpo = preg_replace('/\D/', '', $valor);
    
    // Se contém @ é email
    if (strpos($valor, '@') !== false) {
        return 'email';
    }
    
    // Se tem 14 dígitos numéricos (com ou sem formatação), é CNPJ
    if (strlen($valorLimpo) == 14) {
        return 'cnpj';
    }
    
    // Se contém / e tem pelo menos 11 dígitos, provavelmente é CNPJ formatado
    if (strpos($valor, '/') !== false && strlen($valorLimpo) >= 11) {
        return 'cnpj';
    }
    
    // Se tem 11 dígitos, pode ser CPF, mas pessoa física usa email
    if (strlen($valorLimpo) == 11) {
        return 'possivel_cpf';
    }
    
    // Default: email
    return 'email';
}

// FUNÇÃO ATUALIZADA: Validação unificada de login (permite CNPJ sem formatação)
function validarLogin($login, $senha) {
    $tipoDetectado = detectarTipoLogin($login);
    
    try {
        $conexao = conectarBanco();
        
        // Se for detectado como CNPJ (formatado ou não) ou possível CPF
        if ($tipoDetectado === 'cnpj' || $tipoDetectado === 'possivel_cpf') {
            // Limpar valor para busca por CNPJ
            $cnpjLimpo = preg_replace('/\D/', '', $login);
            
            // Tentar login como empresa (busca por email OU CNPJ limpo)
            $stmt = $conexao->prepare("
                SELECT id, nome_instituicao as nome, email, senha, cnpj, 'empresa' as tipo
                FROM empresas 
                WHERE (email = ? OR cnpj = ?) AND ativo = 1
            ");
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
        
        // Se for email ou se a tentativa como empresa falhou, tenta como usuário comum
        if ($tipoDetectado === 'email' || $tipoDetectado === 'possivel_cpf') {
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
        }
        
        return ['sucesso' => false, 'mensagem' => 'Email/CNPJ ou senha incorretos.'];
        
    } catch (PDOException $e) {
        return ['sucesso' => false, 'mensagem' => 'Erro na autenticação: ' . $e->getMessage()];
    }
}

// NOVA FUNÇÃO: Validar data mínima (2 dias de antecedência)
function validarDataMinima($data) {
    $dataAtual = new DateTime();
    $dataAgendamento = new DateTime($data);
    $dataMinima = clone $dataAtual;
    $dataMinima->add(new DateInterval('P2D')); // Adiciona 2 dias
    
    return $dataAgendamento >= $dataMinima;
}

// ATUALIZADO: Função para gerar horários disponíveis com opção para empresas
function gerarHorariosDisponiveis($isEmpresa = false) {
    $horarios = [];
    
    if ($isEmpresa) {
        // Horários para empresas: 8:00 às 16:00
        $inicio = new DateTime('08:00');
        $fim = new DateTime('16:00');
    } else {
        // Horários para usuários individuais: 10:00 às 18:00
        $inicio = new DateTime('10:00');
        $fim = new DateTime('18:00');
    }
    
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

// FUNÇÃO ATUALIZADA: Verificar se uma data está disponível para agendamento
function dataDisponivel($data) {
    try {
        $conexao = conectarBanco();
        
        // Verifica se é dia útil
        if (!isDiaUtil($data)) {
            return false;
        }
        
        // NOVA: Verifica se atende a antecedência mínima de 2 dias
        if (!validarDataMinima($data)) {
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

// FUNÇÃO ATUALIZADA: Gerar datas disponíveis (próximos 30 dias úteis) - agora considera 2 dias de antecedência
function gerarDatasDisponiveis($limite = 30) {
    $datas = [];
    $dataAtual = new DateTime();
    // ALTERADO: Começar a partir do 2º dia
    $dataAtual->add(new DateInterval('P2D'));
    $contador = 0;
    
    while ($contador < $limite) {
        // Verifica se é dia útil (segunda a sexta)
        if ($dataAtual->format('N') >= 1 && $dataAtual->format('N') <= 5) {
            $dataSql = $dataAtual->format('Y-m-d');
            
            // Verifica se a data não está bloqueada (não precisa verificar dataMinima novamente, já está sendo considerada)
            if (isDiaUtil($dataSql) && contarAgendamentosData($dataSql) < 10) {
                $datas[] = [
                    'data' => $dataSql,
                    'data_formatada' => $dataAtual->format('d/m/Y'),
                    'dia_semana' => $dataAtual->format('l'),
                    'agendamentos_restantes' => 10 - contarAgendamentosData($dataSql)
                ];
                $contador++;
            }
        }
        
        $dataAtual->add(new DateInterval('P1D'));
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

// Função para buscar agendamentos completos com filtros
function buscarAgendamentosCompletos($filtros = []) {
    try {
        $conexao = conectarBanco();
        
        $sql = "SELECT 
                    a.*,
                    u.nome as usuario_nome,
                    e.nome_instituicao as empresa_nome,
                    CASE 
                        WHEN a.empresa_id IS NOT NULL THEN 'empresa'
                        WHEN a.usuario_id IS NOT NULL THEN 'usuario_logado'
                        ELSE 'anonimo'
                    END as tipo_usuario_real
                FROM agendamentos a 
                LEFT JOIN usuarios u ON a.usuario_id = u.id 
                LEFT JOIN empresas e ON a.empresa_id = e.id";
        
        $where = [];
        $params = [];

        if (!empty($filtros['data_inicio'])) {
            $where[] = "a.data_agendamento >= ?";
            $params[] = $filtros['data_inicio'];
        }
        
        if (!empty($filtros['data_fim'])) {
            $where[] = "a.data_agendamento <= ?";
            $params[] = $filtros['data_fim'];
        }
        
        // CORRIGIDO: Tratamento especial para filtro de concluídos
        if (!empty($filtros['status_especial']) && $filtros['status_especial'] === 'concluido') {
            $hoje = date('Y-m-d');
            $where[] = "(a.status = 'concluido' OR (a.status = 'confirmado' AND a.data_agendamento < ?))";
            $params[] = $hoje;
        } elseif (!empty($filtros['status'])) {
            $where[] = "a.status = ?";
            $params[] = $filtros['status'];
        }
        
        // Filtro para excluir status específico
        if (!empty($filtros['status_excluir'])) {
            if (is_array($filtros['status_excluir'])) {
                $placeholders = implode(',', array_fill(0, count($filtros['status_excluir']), '?'));
                $where[] = "a.status NOT IN ($placeholders)";
                $params = array_merge($params, $filtros['status_excluir']);
            } else {
                $where[] = "a.status != ?";
                $params[] = $filtros['status_excluir'];
            }
        }
        
        if (!empty($filtros['tipo_agendamento'])) {
            $where[] = "a.tipo_agendamento = ?";
            $params[] = $filtros['tipo_agendamento'];
        }
        
        if (!empty($filtros['empresa_id'])) {
            $where[] = "a.empresa_id = ?";
            $params[] = $filtros['empresa_id'];
        }
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $sql .= " ORDER BY a.data_agendamento DESC, a.hora_agendamento ASC";
        
        $stmt = $conexao->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        return [];
    }
}

// Função para buscar agendamentos de um usuário específico
function buscarAgendamentosUsuario($usuarioId, $filtros = []) {
    try {
        $conexao = conectarBanco();
        
        $sql = "SELECT * FROM agendamentos WHERE usuario_id = ?";
        $params = [$usuarioId];
        
        if (!empty($filtros['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filtros['status'];
        }
        
        if (!empty($filtros['data_inicio'])) {
            $sql .= " AND data_agendamento >= ?";
            $params[] = $filtros['data_inicio'];
        }
        
        if (!empty($filtros['data_fim'])) {
            $sql .= " AND data_agendamento <= ?";
            $params[] = $filtros['data_fim'];
        }
        
        $sql .= " ORDER BY data_agendamento DESC, hora_agendamento ASC";
        
        $stmt = $conexao->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        return [];
    }
}

// Função para buscar agendamentos de uma empresa específica
function buscarAgendamentosEmpresa($empresaId, $filtros = []) {
    try {
        $conexao = conectarBanco();
        
        $sql = "SELECT * FROM agendamentos WHERE empresa_id = ?";
        $params = [$empresaId];
        
        if (!empty($filtros['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filtros['status'];
        }
        
        if (!empty($filtros['data_inicio'])) {
            $sql .= " AND data_agendamento >= ?";
            $params[] = $filtros['data_inicio'];
        }
        
        if (!empty($filtros['data_fim'])) {
            $sql .= " AND data_agendamento <= ?";
            $params[] = $filtros['data_fim'];
        }
        
        $sql .= " ORDER BY data_agendamento DESC, hora_agendamento ASC";
        
        $stmt = $conexao->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        return [];
    }
}

// Função para verificar conflitos de horário
function verificarConflitoHorario($data, $hora, $agendamentoId = null) {
    try {
        $conexao = conectarBanco();
        
        $sql = "SELECT COUNT(*) as total FROM agendamentos 
                WHERE data_agendamento = ? AND hora_agendamento = ? 
                AND status IN ('confirmado', 'pendente')";
        $params = [$data, $hora];
        
        if ($agendamentoId) {
            $sql .= " AND id != ?";
            $params[] = $agendamentoId;
        }
        
        $stmt = $conexao->prepare($sql);
        $stmt->execute($params);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $resultado['total'] > 0;
        
    } catch (PDOException $e) {
        return true; // Em caso de erro, considera que há conflito por segurança
    }
}

// Função para obter estatísticas dos agendamentos
function obterEstatisticasAgendamentos($periodo = 30) {
    try {
        $conexao = conectarBanco();
        $dataInicio = date('Y-m-d', strtotime("-$periodo days"));
        
        // Total de agendamentos por status
        $stmt = $conexao->prepare("
            SELECT status, COUNT(*) as total 
            FROM agendamentos 
            WHERE data_agendamento >= ? 
            GROUP BY status
        ");
        $stmt->execute([$dataInicio]);
        $statusStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Agendamentos por tipo de usuário
        $stmt = $conexao->prepare("
            SELECT 
                CASE 
                    WHEN empresa_id IS NOT NULL THEN 'empresa'
                    WHEN usuario_id IS NOT NULL THEN 'usuario_logado'
                    ELSE 'anonimo'
                END as tipo_usuario,
                COUNT(*) as total
            FROM agendamentos 
            WHERE data_agendamento >= ? 
            GROUP BY tipo_usuario
        ");
        $stmt->execute([$dataInicio]);
        $tipoStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Agendamentos por dia da semana
        $stmt = $conexao->prepare("
            SELECT DAYNAME(data_agendamento) as dia_semana, COUNT(*) as total 
            FROM agendamentos 
            WHERE data_agendamento >= ? 
            GROUP BY DAYOFWEEK(data_agendamento)
            ORDER BY DAYOFWEEK(data_agendamento)
        ");
        $stmt->execute([$dataInicio]);
        $diaSemanaStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'status' => $statusStats,
            'tipo_usuario' => $tipoStats,
            'dia_semana' => $diaSemanaStats
        ];
        
    } catch (PDOException $e) {
        return [
            'status' => [],
            'tipo_usuario' => [],
            'dia_semana' => []
        ];
    }
}

// Função para limpar agendamentos antigos
function limparAgendamentosAntigos($diasParaMantar = 90) {
    try {
        $conexao = conectarBanco();
        $dataLimite = date('Y-m-d', strtotime("-$diasParaMantar days"));
        
        $stmt = $conexao->prepare("
            DELETE FROM agendamentos 
            WHERE data_agendamento < ? AND status IN ('cancelado', 'concluido')
        ");
        $stmt->execute([$dataLimite]);
        
        return ['sucesso' => true, 'removidos' => $stmt->rowCount()];
        
    } catch (PDOException $e) {
        return ['sucesso' => false, 'mensagem' => 'Erro ao limpar agendamentos: ' . $e->getMessage()];
    }
}

// Função para verificar se usuário está logado
function verificarUsuarioLogado() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true;
}

// Função para verificar se empresa está logada
function verificarEmpresaLogada() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['empresa_logada']) && $_SESSION['empresa_logada'] === true;
}

// Função para obter dados do usuário logado
function obterUsuarioLogado() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (verificarUsuarioLogado()) {
        return [
            'id' => $_SESSION['usuario_id'],
            'nome' => $_SESSION['usuario_nome'],
            'email' => $_SESSION['usuario_email'],
            'tipo' => 'usuario'
        ];
    }
    
    if (verificarEmpresaLogada()) {
        return [
            'id' => $_SESSION['empresa_id'],
            'nome' => $_SESSION['empresa_nome'],
            'email' => $_SESSION['empresa_email'],
            'cnpj' => $_SESSION['empresa_cnpj'] ?? '',
            'tipo' => 'empresa'
        ];
    }
    
    return null;
}

// Função para formatar data para exibição
function formatarData($data, $formato = 'd/m/Y') {
    try {
        $dateObj = new DateTime($data);
        return $dateObj->format($formato);
    } catch (Exception $e) {
        return $data;
    }
}

// Função para formatar horário para exibição
function formatarHorario($horario) {
    try {
        $timeObj = new DateTime($horario);
        return $timeObj->format('H:i');
    } catch (Exception $e) {
        return $horario;
    }
}

// Função para calcular idade baseada na data de nascimento
function calcularIdade($dataNascimento) {
    try {
        $nascimento = new DateTime($dataNascimento);
        $hoje = new DateTime();
        $idade = $hoje->diff($nascimento);
        return $idade->y;
    } catch (Exception $e) {
        return 0;
    }
}

// =============================================
// FUNÇÕES PARA INTEGRAÇÃO DE EMAIL
// =============================================

/**
 * Enviar email de cadastro concluído
 */
function enviarEmailCadastro($nome, $email, $tipoUsuario = 'usuario') {
    try {
        $emailService = new EmailService();
        $resultado = $emailService->emailCadastroSucesso($nome, $email, $tipoUsuario);
        
        if ($resultado['sucesso']) {
            error_log("Email de cadastro enviado para: $email");
        } else {
            error_log("Falha ao enviar email de cadastro para: $email - " . $resultado['mensagem']);
        }
        
        return $resultado;
    } catch (Exception $e) {
        error_log("Erro ao enviar email de cadastro: " . $e->getMessage());
        return ['sucesso' => false, 'mensagem' => 'Erro ao enviar email'];
    }
}

/**
 * Enviar email de agendamento confirmado (usuários)
 */
function enviarEmailAgendamentoConfirmado($agendamentoId) {
    try {
        $conexao = conectarBanco();
        
        // Buscar dados do agendamento
        $stmt = $conexao->prepare("
            SELECT a.*, u.nome as usuario_nome, u.email as usuario_email
            FROM agendamentos a
            LEFT JOIN usuarios u ON a.usuario_id = u.id
            WHERE a.id = ?
        ");
        $stmt->execute([$agendamentoId]);
        $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$agendamento) {
            return ['sucesso' => false, 'mensagem' => 'Agendamento não encontrado'];
        }
        
        $emailService = new EmailService();
        $resultado = $emailService->emailAgendamentoConfirmado(
            $agendamento['nome'],
            $agendamento['email'],
            $agendamento['data_agendamento'],
            $agendamento['hora_agendamento'],
            $agendamento['id']
        );
        
        if ($resultado['sucesso']) {
            error_log("Email de confirmação enviado para agendamento ID: $agendamentoId");
        }
        
        return $resultado;
    } catch (Exception $e) {
        error_log("Erro ao enviar email de confirmação: " . $e->getMessage());
        return ['sucesso' => false, 'mensagem' => 'Erro ao enviar email'];
    }
}

/**
 * Enviar email de agendamento pendente (empresas)
 */
function enviarEmailAgendamentoPendente($agendamentoId) {
    try {
        $conexao = conectarBanco();
        
        // Buscar dados do agendamento
        $stmt = $conexao->prepare("
            SELECT a.*, e.nome_instituicao as empresa_nome
            FROM agendamentos a
            LEFT JOIN empresas e ON a.empresa_id = e.id
            WHERE a.id = ?
        ");
        $stmt->execute([$agendamentoId]);
        $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$agendamento) {
            return ['sucesso' => false, 'mensagem' => 'Agendamento não encontrado'];
        }
        
        $emailService = new EmailService();
        $resultado = $emailService->emailAgendamentoPendente(
            $agendamento['nome'],
            $agendamento['email'],
            $agendamento['data_agendamento'],
            $agendamento['hora_agendamento'],
            $agendamento['id'],
            $agendamento['quantidade_pessoas'] ?? 1
        );
        
        if ($resultado['sucesso']) {
            error_log("Email de pendência enviado para agendamento ID: $agendamentoId");
        }
        
        return $resultado;
    } catch (Exception $e) {
        error_log("Erro ao enviar email de pendência: " . $e->getMessage());
        return ['sucesso' => false, 'mensagem' => 'Erro ao enviar email'];
    }
}

/**
 * Enviar email de agendamento aprovado (empresas)
 */
function enviarEmailAgendamentoAprovado($agendamentoId) {
    try {
        $conexao = conectarBanco();
        
        // Buscar dados do agendamento
        $stmt = $conexao->prepare("
            SELECT a.*, e.nome_instituicao as empresa_nome
            FROM agendamentos a
            LEFT JOIN empresas e ON a.empresa_id = e.id
            WHERE a.id = ?
        ");
        $stmt->execute([$agendamentoId]);
        $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$agendamento) {
            return ['sucesso' => false, 'mensagem' => 'Agendamento não encontrado'];
        }
        
        $emailService = new EmailService();
        $resultado = $emailService->emailAgendamentoAprovado(
            $agendamento['nome'],
            $agendamento['email'],
            $agendamento['data_agendamento'],
            $agendamento['hora_agendamento'],
            $agendamento['id'],
            $agendamento['quantidade_pessoas'] ?? 1
        );
        
        if ($resultado['sucesso']) {
            error_log("Email de aprovação enviado para agendamento ID: $agendamentoId");
        }
        
        return $resultado;
    } catch (Exception $e) {
        error_log("Erro ao enviar email de aprovação: " . $e->getMessage());
        return ['sucesso' => false, 'mensagem' => 'Erro ao enviar email'];
    }
}

/**
 * Enviar email de agendamento negado (empresas)
 */
function enviarEmailAgendamentoNegado($agendamentoId, $motivo = '') {
    try {
        $conexao = conectarBanco();
        
        // Buscar dados do agendamento
        $stmt = $conexao->prepare("
            SELECT a.*, e.nome_instituicao as empresa_nome
            FROM agendamentos a
            LEFT JOIN empresas e ON a.empresa_id = e.id
            WHERE a.id = ?
        ");
        $stmt->execute([$agendamentoId]);
        $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$agendamento) {
            return ['sucesso' => false, 'mensagem' => 'Agendamento não encontrado'];
        }
        
        $emailService = new EmailService();
        $resultado = $emailService->emailAgendamentoNegado(
            $agendamento['nome'],
            $agendamento['email'],
            $agendamento['data_agendamento'],
            $agendamento['hora_agendamento'],
            $agendamento['id'],
            $motivo
        );
        
        if ($resultado['sucesso']) {
            error_log("Email de negação enviado para agendamento ID: $agendamentoId");
        }
        
        return $resultado;
    } catch (Exception $e) {
        error_log("Erro ao enviar email de negação: " . $e->getMessage());
        return ['sucesso' => false, 'mensagem' => 'Erro ao enviar email'];
    }
}

/**
 * Enviar email de agendamento cancelado
 */
function enviarEmailAgendamentoCancelado($agendamentoId) {
    try {
        $conexao = conectarBanco();
        
        // Buscar dados do agendamento
        $stmt = $conexao->prepare("
            SELECT a.*, 
                   u.nome as usuario_nome, u.email as usuario_email,
                   e.nome_instituicao as empresa_nome, e.email as empresa_email
            FROM agendamentos a
            LEFT JOIN usuarios u ON a.usuario_id = u.id
            LEFT JOIN empresas e ON a.empresa_id = e.id
            WHERE a.id = ?
        ");
        $stmt->execute([$agendamentoId]);
        $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$agendamento) {
            return ['sucesso' => false, 'mensagem' => 'Agendamento não encontrado'];
        }
        
        $tipoUsuario = $agendamento['empresa_id'] ? 'empresa' : 'usuario';
        
        $emailService = new EmailService();
        $resultado = $emailService->emailAgendamentoCancelado(
            $agendamento['nome'],
            $agendamento['email'],
            $agendamento['data_agendamento'],
            $agendamento['hora_agendamento'],
            $agendamento['id'],
            $tipoUsuario
        );
        
        if ($resultado['sucesso']) {
            error_log("Email de cancelamento enviado para agendamento ID: $agendamentoId");
        }
        
        return $resultado;
    } catch (Exception $e) {
        error_log("Erro ao enviar email de cancelamento: " . $e->getMessage());
        return ['sucesso' => false, 'mensagem' => 'Erro ao enviar email'];
    }
}

/**
 * Enviar email de agendamento concluído
 */
function enviarEmailAgendamentoConcluido($agendamentoId) {
    try {
        $conexao = conectarBanco();
        
        // Buscar dados do agendamento
        $stmt = $conexao->prepare("
            SELECT a.*, 
                   u.nome as usuario_nome, u.email as usuario_email,
                   e.nome_instituicao as empresa_nome, e.email as empresa_email
            FROM agendamentos a
            LEFT JOIN usuarios u ON a.usuario_id = u.id
            LEFT JOIN empresas e ON a.empresa_id = e.id
            WHERE a.id = ?
        ");
        $stmt->execute([$agendamentoId]);
        $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$agendamento) {
            return ['sucesso' => false, 'mensagem' => 'Agendamento não encontrado'];
        }
        
        $tipoUsuario = $agendamento['empresa_id'] ? 'empresa' : 'usuario';
        
        $emailService = new EmailService();
        $resultado = $emailService->emailAgendamentoConcluido(
            $agendamento['nome'],
            $agendamento['email'],
            $agendamento['data_agendamento'],
            $agendamento['hora_agendamento'],
            $agendamento['id'],
            $tipoUsuario
        );
        
        if ($resultado['sucesso']) {
            error_log("Email de conclusão enviado para agendamento ID: $agendamentoId");
        }
        
        return $resultado;
    } catch (Exception $e) {
        error_log("Erro ao enviar email de conclusão: " . $e->getMessage());
        return ['sucesso' => false, 'mensagem' => 'Erro ao enviar email'];
    }
}

/**
 * Enviar email de lembrete (1 dia antes do agendamento)
 */
function enviarEmailLembrete($agendamentoId) {
    try {
        $conexao = conectarBanco();
        
        // Buscar dados do agendamento
        $stmt = $conexao->prepare("
            SELECT a.*, 
                   u.nome as usuario_nome, u.email as usuario_email,
                   e.nome_instituicao as empresa_nome, e.email as empresa_email
            FROM agendamentos a
            LEFT JOIN usuarios u ON a.usuario_id = u.id
            LEFT JOIN empresas e ON a.empresa_id = e.id
            WHERE a.id = ? AND a.status = 'confirmado'
        ");
        $stmt->execute([$agendamentoId]);
        $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$agendamento) {
            return ['sucesso' => false, 'mensagem' => 'Agendamento não encontrado ou não confirmado'];
        }
        
        $tipoUsuario = $agendamento['empresa_id'] ? 'empresa' : 'usuario';
        
        $emailService = new EmailService();
        $resultado = $emailService->emailLembreteAgendamento(
            $agendamento['nome'],
            $agendamento['email'],
            $agendamento['data_agendamento'],
            $agendamento['hora_agendamento'],
            $agendamento['id'],
            $tipoUsuario
        );
        
        if ($resultado['sucesso']) {
            error_log("Email de lembrete enviado para agendamento ID: $agendamentoId");
        }
        
        return $resultado;
    } catch (Exception $e) {
        error_log("Erro ao enviar email de lembrete: " . $e->getMessage());
        return ['sucesso' => false, 'mensagem' => 'Erro ao enviar email'];
    }
}

/**
 * Função para processar lembretes automáticos
 * Deve ser executada diariamente via cron job
 */
function processarLembretesAutomaticos() {
    try {
        $conexao = conectarBanco();
        
        // Buscar agendamentos para amanhã que ainda não receberam lembrete
        $amanha = date('Y-m-d', strtotime('+1 day'));
        
        $stmt = $conexao->prepare("
            SELECT id FROM agendamentos 
            WHERE data_agendamento = ? 
            AND status = 'confirmado' 
            AND lembrete_enviado = 0
        ");
        $stmt->execute([$amanha]);
        $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $enviados = 0;
        $erros = 0;
        
        foreach ($agendamentos as $agendamento) {
            $resultado = enviarEmailLembrete($agendamento['id']);
            
            if ($resultado['sucesso']) {
                // Marcar lembrete como enviado
                $updateStmt = $conexao->prepare("
                    UPDATE agendamentos 
                    SET lembrete_enviado = 1, data_lembrete = NOW() 
                    WHERE id = ?
                ");
                $updateStmt->execute([$agendamento['id']]);
                $enviados++;
            } else {
                $erros++;
            }
        }
        
        return [
            'sucesso' => true,
            'enviados' => $enviados,
            'erros' => $erros,
            'total' => count($agendamentos)
        ];
        
    } catch (Exception $e) {
        error_log("Erro ao processar lembretes automáticos: " . $e->getMessage());
        return ['sucesso' => false, 'mensagem' => 'Erro ao processar lembretes'];
    }
}

/**
 * Função para marcar agendamentos como concluídos automaticamente
 * Deve ser executada diariamente via cron job
 */
function marcarAgendamentosConcluidos() {
    try {
        $conexao = conectarBanco();
        
        // Marcar como concluídos os agendamentos de ontem que estavam confirmados
        $ontem = date('Y-m-d', strtotime('-1 day'));
        
        $stmt = $conexao->prepare("
            UPDATE agendamentos 
            SET status = 'concluido', data_conclusao = NOW() 
            WHERE data_agendamento = ? 
            AND status = 'confirmado'
        ");
        $stmt->execute([$ontem]);
        
        $marcados = $stmt->rowCount();
        
        // Buscar os agendamentos que foram marcados como concluídos para enviar emails
        $stmt = $conexao->prepare("
            SELECT id FROM agendamentos 
            WHERE data_agendamento = ? 
            AND status = 'concluido' 
            AND data_conclusao >= CURDATE()
        ");
        $stmt->execute([$ontem]);
        $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Enviar emails de conclusão
        foreach ($agendamentos as $agendamento) {
            enviarEmailAgendamentoConcluido($agendamento['id']);
        }
        
        return [
            'sucesso' => true,
            'marcados' => $marcados,
            'emails_enviados' => count($agendamentos)
        ];
        
    } catch (Exception $e) {
        error_log("Erro ao marcar agendamentos como concluídos: " . $e->getMessage());
        return ['sucesso' => false, 'mensagem' => 'Erro ao marcar como concluídos'];
    }
}

/**
 * Função para gerar relatório de uso do sistema
 */
function gerarRelatorioUso($dataInicio, $dataFim) {
    try {
        $conexao = conectarBanco();
        
        $relatorio = [
            'periodo' => [
                'inicio' => $dataInicio,
                'fim' => $dataFim
            ],
            'totais' => [],
            'por_status' => [],
            'por_tipo_usuario' => [],
            'por_mes' => [],
            'por_dia_semana' => []
        ];
        
        // Totais gerais
        $stmt = $conexao->prepare("
            SELECT 
                COUNT(*) as total_agendamentos,
                COUNT(DISTINCT CASE WHEN usuario_id IS NOT NULL THEN usuario_id END) as usuarios_unicos,
                COUNT(DISTINCT CASE WHEN empresa_id IS NOT NULL THEN empresa_id END) as empresas_unicas
            FROM agendamentos 
            WHERE data_agendamento BETWEEN ? AND ?
        ");
        $stmt->execute([$dataInicio, $dataFim]);
        $relatorio['totais'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Por status
        $stmt = $conexao->prepare("
            SELECT status, COUNT(*) as total 
            FROM agendamentos 
            WHERE data_agendamento BETWEEN ? AND ?
            GROUP BY status
        ");
        $stmt->execute([$dataInicio, $dataFim]);
        $relatorio['por_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Por tipo de usuário
        $stmt = $conexao->prepare("
            SELECT 
                CASE 
                    WHEN empresa_id IS NOT NULL THEN 'empresa'
                    WHEN usuario_id IS NOT NULL THEN 'usuario'
                    ELSE 'anonimo'
                END as tipo,
                COUNT(*) as total
            FROM agendamentos 
            WHERE data_agendamento BETWEEN ? AND ?
            GROUP BY tipo
        ");
        $stmt->execute([$dataInicio, $dataFim]);
        $relatorio['por_tipo_usuario'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Por mês
        $stmt = $conexao->prepare("
            SELECT 
                DATE_FORMAT(data_agendamento, '%Y-%m') as mes,
                COUNT(*) as total
            FROM agendamentos 
            WHERE data_agendamento BETWEEN ? AND ?
            GROUP BY mes
            ORDER BY mes
        ");
        $stmt->execute([$dataInicio, $dataFim]);
        $relatorio['por_mes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Por dia da semana
        $stmt = $conexao->prepare("
            SELECT 
                DAYNAME(data_agendamento) as dia_semana,
                WEEKDAY(data_agendamento) as ordem,
                COUNT(*) as total
            FROM agendamentos 
            WHERE data_agendamento BETWEEN ? AND ?
            GROUP BY dia_semana, ordem
            ORDER BY ordem
        ");
        $stmt->execute([$dataInicio, $dataFim]);
        $relatorio['por_dia_semana'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $relatorio;
        
    } catch (Exception $e) {
        error_log("Erro ao gerar relatório: " . $e->getMessage());
        return null;
    }
}

// =============================================
// FUNÇÕES DE SEGURANÇA E VALIDAÇÃO
// =============================================

/**
 * Sanitizar entrada de dados
 */
function sanitizarEntrada($dados) {
    if (is_array($dados)) {
        return array_map('sanitizarEntrada', $dados);
    }
    
    return htmlspecialchars(strip_tags(trim($dados)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validar formato de email
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validar força da senha
 */
function validarForcaSenha($senha) {
    $criterios = [
        'minimo_caracteres' => strlen($senha) >= 6,
        'tem_letra' => preg_match('/[a-zA-Z]/', $senha),
        'tem_numero' => preg_match('/[0-9]/', $senha)
    ];
    
    $forca = array_sum($criterios);
    
    return [
        'valida' => $criterios['minimo_caracteres'],
        'forca' => $forca,
        'criterios' => $criterios
    ];
}

/**
 * Gerar token seguro
 */
function gerarTokenSeguro($tamanho = 32) {
    return bin2hex(random_bytes($tamanho));
}

/**
 * Log de atividades do sistema
 */
function logarAtividade($acao, $descricao, $usuarioId = null, $dados = []) {
    try {
        $conexao = conectarBanco();
        
        $stmt = $conexao->prepare("
            INSERT INTO log_atividades (acao, descricao, usuario_id, dados, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $dadosJson = !empty($dados) ? json_encode($dados) : null;
        
        $stmt->execute([$acao, $descricao, $usuarioId, $dadosJson, $ip, $userAgent]);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Erro ao registrar log: " . $e->getMessage());
        return false;
    }
}

// =============================================
// FUNÇÕES DE BACKUP E MANUTENÇÃO
// =============================================

/**
 * Criar backup dos dados
 */
function criarBackupDados($incluirLogs = false) {
    try {
        $conexao = conectarBanco();
        $backupData = [];
        
        // Tabelas principais
        $tabelas = ['usuarios', 'empresas', 'agendamentos'];
        
        if ($incluirLogs) {
            $tabelas[] = 'log_atividades';
        }
        
        foreach ($tabelas as $tabela) {
            $stmt = $conexao->query("SELECT * FROM $tabela");
            $backupData[$tabela] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        $nomeArquivo = 'backup_bioterio_' . date('Y-m-d_H-i-s') . '.json';
        $caminhoArquivo = 'backups/' . $nomeArquivo;
        
        // Criar diretório se não existir
        if (!is_dir('backups')) {
            mkdir('backups', 0755, true);
        }
        
        file_put_contents($caminhoArquivo, json_encode($backupData, JSON_PRETTY_PRINT));
        
        return [
            'sucesso' => true,
            'arquivo' => $nomeArquivo,
            'caminho' => $caminhoArquivo,
            'tamanho' => filesize($caminhoArquivo)
        ];
        
    } catch (Exception $e) {
        error_log("Erro ao criar backup: " . $e->getMessage());
        return ['sucesso' => false, 'mensagem' => 'Erro ao criar backup'];
    }
}

/**
 * Verificar integridade do sistema
 */
function verificarIntegridadeSistema() {
    $verificacoes = [];
    
    try {
        $conexao = conectarBanco();
        
        // Verificar tabelas essenciais
        $tabelasEssenciais = ['usuarios', 'empresas', 'agendamentos'];
        foreach ($tabelasEssenciais as $tabela) {
            try {
                $stmt = $conexao->query("SELECT COUNT(*) FROM $tabela");
                $verificacoes["tabela_$tabela"] = ['status' => 'ok', 'registros' => $stmt->fetchColumn()];
            } catch (Exception $e) {
                $verificacoes["tabela_$tabela"] = ['status' => 'erro', 'mensagem' => $e->getMessage()];
            }
        }
        
        // Verificar agendamentos órfãos
        $stmt = $conexao->query("
            SELECT COUNT(*) FROM agendamentos a
            LEFT JOIN usuarios u ON a.usuario_id = u.id
            LEFT JOIN empresas e ON a.empresa_id = e.id
            WHERE a.usuario_id IS NOT NULL AND u.id IS NULL
            OR a.empresa_id IS NOT NULL AND e.id IS NULL
        ");
        $orfaos = $stmt->fetchColumn();
        $verificacoes['agendamentos_orfaos'] = [
            'status' => $orfaos > 0 ? 'atencao' : 'ok',
            'quantidade' => $orfaos
        ];
        
        // Verificar conflitos de horário
        $stmt = $conexao->query("
            SELECT COUNT(*) FROM agendamentos a1
            JOIN agendamentos a2 ON a1.data_agendamento = a2.data_agendamento 
            AND a1.hora_agendamento = a2.hora_agendamento
            AND a1.id != a2.id
            AND a1.status IN ('confirmado', 'pendente')
            AND a2.status IN ('confirmado', 'pendente')
        ");
        $conflitos = $stmt->fetchColumn();
        $verificacoes['conflitos_horario'] = [
            'status' => $conflitos > 0 ? 'erro' : 'ok',
            'quantidade' => $conflitos
        ];
        
        return $verificacoes;
        
    } catch (Exception $e) {
        return ['erro_geral' => ['status' => 'erro', 'mensagem' => $e->getMessage()]];
    }
}
?>