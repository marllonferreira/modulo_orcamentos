<?php
// aprovar_orcamento.php
// Altera o status do orçamento para "Aprovado"

require '../conexao.php';

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    die("ID inválido.");
}

try {
    $stmt = $pdo->prepare("UPDATE mod_orc_orcamentos SET status = 'Aprovado' WHERE id = :id");
    $stmt->execute([':id' => $id]);

    // Redireciona de volta para os detalhes com mensagem de sucesso
    header("Location: ver_detalhes.php?id=$id&msg=aprovado");
    exit;

} catch (PDOException $e) {
    die("Erro ao aprovar orçamento: " . $e->getMessage());
}
?>