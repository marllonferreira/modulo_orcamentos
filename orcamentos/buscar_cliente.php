<?php
// buscar_cliente.php - Versão com endereço concatenado
header('Content-Type: application/json; charset=utf-8');

require '../conexao.php';

$termo = $_GET['termo'] ?? '';
$resultados = [];

if (strlen($termo) >= 2) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                idClientes as id, 
                nomeCliente as nome, 
                telefone, 
                CONCAT_WS(', ', 
                    NULLIF(rua, ''), 
                    NULLIF(numero, ''), 
                    NULLIF(bairro, ''), 
                    NULLIF(complemento, '')
                ) as endereco,
                cidade,
                estado
            FROM clientes 
            WHERE nomeCliente LIKE :termo
            ORDER BY nomeCliente ASC 
            LIMIT 10
        ");

        $stmt->execute([':termo' => "%{$termo}%"]);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

echo json_encode($resultados);
?>