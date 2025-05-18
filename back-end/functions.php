<?php

function conectarBanco() {
    $servidor = "localhost";
    $usuario = "root"; 
    $senha = ""; // <-- MUDE ISSO PARA A SENHA REAL DO SEU USUÁRIO ROOT DO MYSQL/MARIADB
    $banco = "bioterio_db";
    $porta = "3306"; // <-- Definido para a porta padrão do MySQL/MariaDB

    try {
        $conexao = new PDO("mysql:host=$servidor;port=$porta;dbname=$banco;charset=utf8", $usuario, $senha);
        $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conexao;
    } catch (PDOException $e) {
        die("Erro de conexão com o banco de dados: " . $e->getMessage());
    }
}
?>
?>