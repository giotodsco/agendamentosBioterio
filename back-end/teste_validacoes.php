<?php
// Arquivo para testar as validações de CPF e CNPJ

// Inclui as funções corrigidas
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

// Função para detectar tipo de login
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

// TESTES DE VALIDAÇÃO
echo "<h1>Teste de Validações de CPF e CNPJ</h1>";

// Teste de CPFs válidos
$cpfsValidos = [
    '11144477735',
    '111.444.777-35',
    '12345678909'
];

echo "<h2>Teste de CPFs Válidos:</h2>";
foreach ($cpfsValidos as $cpf) {
    $resultado = validarCPF($cpf) ? "VÁLIDO" : "INVÁLIDO";
    echo "CPF: $cpf - Resultado: <strong>$resultado</strong><br>";
}

// Teste de CPFs inválidos
$cpfsInvalidos = [
    '11111111111',
    '12345678901',
    '000.000.000-00',
    '123.456.789-10'
];

echo "<h2>Teste de CPFs Inválidos:</h2>";
foreach ($cpfsInvalidos as $cpf) {
    $resultado = validarCPF($cpf) ? "VÁLIDO" : "INVÁLIDO";
    echo "CPF: $cpf - Resultado: <strong>$resultado</strong><br>";
}

// Teste de CNPJs válidos (incluindo o exemplo que você mencionou)
$cnpjsValidos = [
    '47.960.950/0001-21',
    '47960950000121',  // Sem formatação
    '11.222.333/0001-81'
];

echo "<h2>Teste de CNPJs Válidos:</h2>";
foreach ($cnpjsValidos as $cnpj) {
    $resultado = validarCNPJ($cnpj) ? "VÁLIDO" : "INVÁLIDO";
    echo "CNPJ: $cnpj - Resultado: <strong>$resultado</strong><br>";
}

// Teste de CNPJs inválidos
$cnpjsInvalidos = [
    '11111111111111',
    '47.960.950/0001-22',  // Dígito verificador errado
    '12.345.678/0001-99'
];

echo "<h2>Teste de CNPJs Inválidos:</h2>";
foreach ($cnpjsInvalidos as $cnpj) {
    $resultado = validarCNPJ($cnpj) ? "VÁLIDO" : "INVÁLIDO";
    echo "CNPJ: $cnpj - Resultado: <strong>$resultado</strong><br>";
}

// Teste de detecção de tipo de login
$valoresLogin = [
    'usuario@email.com',
    '47.960.950/0001-21',
    '47960950000121',
    '12345678909',
    '111.444.777-35'
];

echo "<h2>Teste de Detecção de Tipo de Login:</h2>";
foreach ($valoresLogin as $valor) {
    $tipo = detectarTipoLogin($valor);
    echo "Valor: $valor - Tipo detectado: <strong>$tipo</strong><br>";
}

echo "<h2>Instruções para implementação:</h2>";
echo "<ol>";
echo "<li>Substitua o conteúdo do arquivo <strong>functions.php</strong> pelo código do artifact 'functions_melhorado'</li>";
echo "<li>Substitua o conteúdo do arquivo <strong>pag_login_usuario.php</strong> pelo código do artifact 'pag_login_melhorado'</li>";
echo "<li>Agora o sistema permite:</li>";
echo "<ul>";
echo "<li>Login com CNPJ sem formatação (ex: 47960950000121)</li>";
echo "<li>Login com CNPJ formatado (ex: 47.960.950/0001-21)</li>";
echo "<li>Login com email para ambos tipos de usuário</li>";
echo "<li>Validação corrigida de CPF e CNPJ usando algoritmo oficial da Receita Federal</li>";
echo "<li>Feedback visual em tempo real nas validações</li>";
echo "</ul>";
echo "</ol>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 50px auto;
    padding: 20px;
    line-height: 1.6;
}

h1 {
    color: #2c5530;
    border-bottom: 3px solid #407a35;
    padding-bottom: 10px;
}

h2 {
    color: #407a35;
    margin-top: 30px;
}

strong {
    color: #2c5530;
}

ol, ul {
    margin-left: 20px;
}

li {
    margin-bottom: 5px;
}
</style>