<?php

session_start();
require_once 'functions.php';

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: ../front-end/pag_adm.php");
    exit();
}

try {
    $conexao = conectarBanco();
    $stmt = $conexao->query("SELECT * FROM agendamentos ORDER BY data_agendamento DESC, hora_agendamento ASC");
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h2>Agendamentos Cadastrados:</h2>";
    if (count($agendamentos) > 0) {
        echo "<table>";
        echo "<thead><tr><th>ID</th><th>Nome</th><th>Email</th><th>É Aluno?</th><th>Data</th><th>Hora</th><th>Registro</th></tr></thead>";
        echo "<tbody>";
        foreach ($agendamentos as $agendamento) {
            $e_aluno_texto = $agendamento['e_aluno'] ? 'Sim' : 'Não';
            echo "<tr>";
            echo "<td>" . htmlspecialchars($agendamento['id']) . "</td>";
            echo "<td>" . htmlspecialchars($agendamento['nome']) . "</td>";
            echo "<td>" . htmlspecialchars($agendamento['email']) . "</td>";
            echo "<td>" . htmlspecialchars($e_aluno_texto) . "</td>";
            echo "<td>" . htmlspecialchars(date('d/m/Y', strtotime($agendamento['data_agendamento']))) . "</td>";
            echo "<td>" . htmlspecialchars(date('H:i', strtotime($agendamento['hora_agendamento']))) . "</td>";
            echo "<td>" . htmlspecialchars(date('d/m/Y H:i', strtotime($agendamento['data_criacao']))) . "</td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
    } else {
        echo "<p>Nenhum agendamento encontrado.</p>";
    }
} catch (PDOException $e) {
    echo "<p>Erro ao carregar agendamentos: " . $e->getMessage() . "</p>";
}
?>