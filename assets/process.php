<?php
include __DIR__ . "/../db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = $_POST['nome'];
    $cpf = $_POST['cpf'];
    $secao = $_POST['secao'];
    $ramal = $_POST['ramal'];
    $autorizado = $_POST['autorizado'];
    $carro = $_POST['carro'] ?? 'nao';
    $placa = $_POST['placa'] ?? null;
    $modelo = $_POST['modelo'] ?? null;

    $sql = "INSERT INTO visitante (nome, cpf, secao, ramal, autorizado, carro_entrou, placa, modelo, data_hora)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssss", $nome, $cpf, $secao, $ramal, $autorizado, $carro, $placa, $modelo);

    if ($stmt->execute()) {
        // redireciona de volta para o formulário com mensagem
        header("Location: ../index.php?sucesso=1");
        exit;
    } else {
        echo "Erro: " . $stmt->error;
    }
}
