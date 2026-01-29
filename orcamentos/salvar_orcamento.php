<?php
// salvar_orcamento.php
// Responsável por receber o JSON do frontend e salvar no banco de dados

require '../conexao.php';

header('Content-Type: application/json');

try {
    // 1. Recebe e Decodifica o JSON
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);

    if (!$input) {
        throw new Exception("Nenhum dado recebido ou JSON inválido.");
    }

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

    // 2. Validação Básica
    if (empty($input['cliente_id'])) {
        throw new Exception("Erro: Cliente não selecionado.");
    }
    if (empty($input['itens']) || !is_array($input['itens']) || count($input['itens']) === 0) {
        throw new Exception("Erro: O orçamento deve ter pelo menos um item.");
    }

    // 3. Inicia Transação
    $pdo->beginTransaction();

    $orcamento_id = null;
    $is_update = !empty($input['id']);

    // Tratamento da Data de Criação (Se não enviada, usa NOW())
    $data_criacao = !empty($input['data_criacao']) ? str_replace('T', ' ', $input['data_criacao']) : date('Y-m-d H:i:s');

    if ($is_update) {
        $orcamento_id = $input['id'];
        // --- UPDATE ---
        $stmt = $pdo->prepare("UPDATE mod_orc_orcamentos SET cliente_id = :cliente_id, valor_total = :valor_total, observacoes = :observacoes, anotacoes_internas = :anotacoes_internas, validade_dias = :validade_dias, status = :status, data_criacao = :data_criacao WHERE id = :id");

        $stmt->bindValue(':id', $orcamento_id);
        $stmt->bindValue(':cliente_id', $input['cliente_id'], PDO::PARAM_INT);
        $stmt->bindValue(':data_criacao', $data_criacao); // Atualiza data
        $stmt->bindValue(':valor_total', $input['valor_total']);
        $stmt->bindValue(':observacoes', $input['observacoes'] ?? '');
        $stmt->bindValue(':anotacoes_internas', $input['anotacoes_internas'] ?? '');
        $stmt->bindValue(':validade_dias', $input['validade_dias'] ?? 10);
        $stmt->bindValue(':status', $input['status'] ?? 'Emitido');
        $stmt->execute();

        // Remove itens antigos para reinserir
        $stmtDel = $pdo->prepare("DELETE FROM mod_orc_itens WHERE orcamento_id = :id");
        $stmtDel->bindValue(':id', $orcamento_id);
        $stmtDel->execute();

    } else {
        // --- INSERT ---
        // Agora aceita data_criacao customizada também no insert
        $stmt = $pdo->prepare("INSERT INTO mod_orc_orcamentos (cliente_id, data_criacao, valor_total, observacoes, anotacoes_internas, status, validade_dias) VALUES (:cliente_id, :data_criacao, :valor_total, :observacoes, :anotacoes_internas, :status, :validade_dias)");

        $stmt->bindValue(':cliente_id', $input['cliente_id'], PDO::PARAM_INT);
        $stmt->bindValue(':data_criacao', $data_criacao);
        $stmt->bindValue(':valor_total', $input['valor_total']);
        $stmt->bindValue(':observacoes', $input['observacoes'] ?? '');
        $stmt->bindValue(':anotacoes_internas', $input['anotacoes_internas'] ?? '');
        $stmt->bindValue(':status', 'Rascunho'); // Status padrão inicial
        $stmt->bindValue(':validade_dias', $input['validade_dias'] ?? 10);
        $stmt->execute();

        $orcamento_id = $pdo->lastInsertId();
    }

    // 5. Inserir Itens (mod_orc_itens) - Comum para Insert e Update
    $stmtItem = $pdo->prepare("INSERT INTO mod_orc_itens (orcamento_id, produto_id, servico_id, tipo_item, descricao, quantidade, unidade, preco_unitario, taxa, subtotal) VALUES (:orcamento_id, :produto_id, :servico_id, :tipo_item, :descricao, :quantidade, :unidade, :preco_unitario, :taxa, :subtotal)");

    foreach ($input['itens'] as $item) {
        $tipo = $item['tipo_item']; // 'P', 'S', ou 'M'
        $produto_id = ($tipo === 'P') ? $item['id_origem'] : null;
        $servico_id = ($tipo === 'S') ? $item['id_origem'] : null;
        // Se for 'M' (Manual), ambos IDs ficam null

        $stmtItem->bindValue(':orcamento_id', $orcamento_id);
        $stmtItem->bindValue(':produto_id', $produto_id);
        $stmtItem->bindValue(':servico_id', $servico_id);
        $stmtItem->bindValue(':tipo_item', $tipo);
        $stmtItem->bindValue(':descricao', $item['descricao']);
        $stmtItem->bindValue(':quantidade', $item['quantidade']);
        $stmtItem->bindValue(':unidade', $item['unidade']);
        $stmtItem->bindValue(':preco_unitario', $item['preco']);
        $stmtItem->bindValue(':taxa', $item['taxa'] ?? 0);
        $stmtItem->bindValue(':subtotal', $item['total']);

        $stmtItem->execute();
    }

    // 6. Commit
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Orçamento salvo com sucesso!',
        'id' => $orcamento_id,
        'redirect' => "ver_detalhes.php?id=$orcamento_id"
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
