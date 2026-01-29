<?php
// search_clientes_orcamento.php
// Endpoint JSON para buscar apenas clientes que JÁ POSSUEM ORÇAMENTOS no módulo

require_once __DIR__ . '/../conexao.php';

header('Content-Type: application/json');

$term = $_GET['term'] ?? '';

if (empty($term)) {
    echo json_encode([]);
    exit;
}

try {
    // Busca clientes que tenham pelo menos um orçamento na tabela mod_orc_orcamentos
    // DISTINCT para não repetir o mesmo cliente
    $sql = "SELECT DISTINCT c.idClientes, c.nomeCliente 
            FROM clientes c
            JOIN mod_orc_orcamentos o ON o.cliente_id = c.idClientes
            WHERE c.nomeCliente LIKE :term
            LIMIT 20";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':term' => '%' . $term . '%']);

    $results = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $results[] = [
            'id' => $row['idClientes'],
            'label' => $row['nomeCliente'],
            'value' => $row['nomeCliente']
        ];
    }

    echo json_encode($results);

} catch (Exception $e) {
    // Retorna array vazio em caso de erro para não quebrar o JS
    echo json_encode([]);
}
