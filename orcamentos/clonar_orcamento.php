<?php
// clonar_orcamento.php
// Duplica um orçamento existente e seus itens, definindo o status como 'Rascunho'

require '../conexao.php';

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    die("ID inválido.");
}

try {
    $pdo->beginTransaction();

    // 1. Buscar dados do orçamento original
    $stmt = $pdo->prepare("SELECT * FROM mod_orc_orcamentos WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $orcamentoOriginal = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$orcamentoOriginal) {
        throw new Exception("Orçamento original não encontrado.");
    }

    // 2. Inserir NOVO Orçamento (Cópia do Cabeçalho)
    // Define status como Rascunho, data de criação atual, validade padrão
    // Colunas ajustadas para refletir apenas o que existe na tabela
    $sqlInsert = "INSERT INTO mod_orc_orcamentos (
        cliente_id, status, data_criacao, validade_dias, 
        valor_total, observacoes, anotacoes_internas
    ) VALUES (
        :cliente_id, 'Rascunho', NOW(), :validade_dias, 
        :valor_total, :observacoes, :anotacoes_internas
    )";

    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->execute([
        ':cliente_id' => $orcamentoOriginal['cliente_id'],
        ':validade_dias' => $orcamentoOriginal['validade_dias'],
        ':valor_total' => $orcamentoOriginal['valor_total'],
        ':observacoes' => $orcamentoOriginal['observacoes'],
        ':anotacoes_internas' => $orcamentoOriginal['anotacoes_internas']
    ]);

    $novoId = $pdo->lastInsertId();

    // 3. Buscar Itens do Orçamento Original
    $stmtItens = $pdo->prepare("SELECT * FROM mod_orc_itens WHERE orcamento_id = :id");
    $stmtItens->execute([':id' => $id]);
    $itensOriginais = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

    // 4. Inserir Itens no Novo Orçamento
    // Usa produto_id e servico_id conforme schema correto
    $sqlInsertItem = "INSERT INTO mod_orc_itens (
        orcamento_id, tipo_item, produto_id, servico_id, descricao, 
        quantidade, unidade, preco_unitario, subtotal
    ) VALUES (
        :orcamento_id, :tipo_item, :produto_id, :servico_id, :descricao, 
        :quantidade, :unidade, :preco_unitario, :subtotal
    )";

    $stmtInsertItem = $pdo->prepare($sqlInsertItem);

    foreach ($itensOriginais as $item) {
        $stmtInsertItem->execute([
            ':orcamento_id' => $novoId,
            ':tipo_item' => $item['tipo_item'],
            ':produto_id' => $item['produto_id'],
            ':servico_id' => $item['servico_id'],
            ':descricao' => $item['descricao'],
            ':quantidade' => $item['quantidade'],
            ':unidade' => $item['unidade'],
            ':preco_unitario' => $item['preco_unitario'],
            ':subtotal' => $item['subtotal']
        ]);
    }

    $pdo->commit();

    // 5. Redirecionar para a Edição do Novo Orçamento
    header("Location: editar_orcamento.php?id=$novoId&msg=clonado");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Exibe erro detalhado se falhar
    die("Erro ao clonar orçamento: " . $e->getMessage());
}
?>