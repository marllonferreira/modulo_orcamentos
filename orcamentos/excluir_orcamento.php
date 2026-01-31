<?php
// excluir_orcamento.php
// Responsável por deletar o orçamento e seus itens de forma segura

require '../conexao.php';

header('Content-Type: application/json');

// 1. Verifica se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
    exit;
}

try {
    // 2. Recebe e Decodifica o JSON
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);

    if (!$input || empty($input['id'])) {
        throw new Exception("Dados inválidos ou ID do orçamento não informado.");
    }

    // 3. Validação CSRF
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($input['csrf_token']) || $input['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception("Erro de segurança: Token CSRF inválido ou expirado.");
    }

    $id = (int) $input['id'];

    // 4. Verifica se o orçamento está Aprovado (Não pode excluir)
    $stmtCheck = $pdo->prepare("SELECT status FROM mod_orc_orcamentos WHERE id = :id");
    $stmtCheck->execute([':id' => $id]);
    $orc = $stmtCheck->fetch();

    if (!$orc) {
        throw new Exception("Orçamento não encontrado.");
    }

    if (strtoupper($orc['status']) === 'APROVADO') {
        throw new Exception("Orçamentos com status 'Aprovado' não podem ser excluídos.");
    }

    // 5. Inicia Transação
    $pdo->beginTransaction();

    // Step 1: Deletar os itens associados
    $stmtItens = $pdo->prepare("DELETE FROM mod_orc_itens WHERE orcamento_id = :id");
    $stmtItens->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtItens->execute();

    // Step 2: Deletar o orçamento
    $stmtOrc = $pdo->prepare("DELETE FROM mod_orc_orcamentos WHERE id = :id");
    $stmtOrc->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtOrc->execute();

    if ($stmtOrc->rowCount() === 0) {
        throw new Exception("Orçamento não encontrado ou já excluído.");
    }

    // 5. Commit
    $pdo->commit();

    // Log Opcional (Caso o MapOS tenha função global log_info)
    if (function_exists('log_info')) {
        log_info("Excluiu o orçamento ID: $id no módulo de orçamentos.");
    }

    echo json_encode([
        'success' => true,
        'message' => "Orçamento #$id excluído com sucesso!"
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
