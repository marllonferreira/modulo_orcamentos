<?php
// salvar_orcamento_ia.php - Módulo isolado para salvar edições feitas via IA
require '../conexao.php';
header('Content-Type: application/json');

try {
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);

    if (!$input)
        throw new Exception("Nenhum dado recebido.");

    // ==========================================
    // 🛡️ VALIDAÇÃO CSRF (Segurança)
    // ==========================================
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($input['csrf_token']) || $input['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception("Erro de segurança (CSRF): Token inválido ou expirado. Recarregue a página.");
    }
    // ==========================================
    if (empty($input['id']))
        throw new Exception("ID do orçamento não informado.");

    $pdo->beginTransaction();

    // 1. Atualizar Cabeçalho do Orçamento
    $data_criacao = !empty($input['data_criacao']) ? str_replace('T', ' ', $input['data_criacao']) : date('Y-m-d H:i:s');

    $stmt = $pdo->prepare("UPDATE mod_orc_orcamentos SET 
        valor_total = :valor_total, 
        observacoes = :observacoes, 
        anotacoes_internas = :anotacoes_internas, 
        validade_dias = :validade_dias, 
        status = :status, 
        data_criacao = :data_criacao 
        WHERE id = :id");

    $stmt->execute([
        ':id' => $input['id'],
        ':valor_total' => $input['valor_total'],
        ':observacoes' => $input['observacoes'] ?? '',
        ':anotacoes_internas' => $input['anotacoes_internas'] ?? '',
        ':validade_dias' => $input['validade_dias'] ?? 10,
        ':status' => $input['status'],
        ':data_criacao' => $data_criacao
    ]);

    // 2. Recriar Itens
    // Remove antigos
    $pdo->prepare("DELETE FROM mod_orc_itens WHERE orcamento_id = :id")->execute([':id' => $input['id']]);

    // Insere novos
    $stmtItem = $pdo->prepare("INSERT INTO mod_orc_itens (orcamento_id, produto_id, servico_id, tipo_item, descricao, quantidade, unidade, preco_unitario, taxa, subtotal) VALUES (:orcamento_id, :produto_id, :servico_id, :tipo_item, :descricao, :quantidade, :unidade, :preco_unitario, :taxa, :subtotal)");

    foreach ($input['itens'] as $item) {
        $tipo = $item['tipo_item'];
        $produto_id = ($tipo === 'P' && !empty($item['id_origem'])) ? $item['id_origem'] : null;
        $servico_id = ($tipo === 'S' && !empty($item['id_origem'])) ? $item['id_origem'] : null;

        $stmtItem->execute([
            ':orcamento_id' => $input['id'],
            ':produto_id' => $produto_id,
            ':servico_id' => $servico_id,
            ':tipo_item' => $tipo,
            ':descricao' => $item['descricao'],
            ':quantidade' => $item['quantidade'],
            ':unidade' => $item['unidade'],
            ':preco_unitario' => $item['preco'],
            ':taxa' => $item['taxa'], // Usa a taxa vinda do frontend
            ':subtotal' => $item['total']
        ]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Orçamento atualizado pela IA!']);

} catch (Exception $e) {
    if ($pdo->inTransaction())
        $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar: ' . $e->getMessage()]);
}
?>