<?php
// buscar_item.php - Busca Unificada (Produto e Serviço)
header('Content-Type: application/json');

// Ajuste o caminho conforme necessidade.
// Como este arquivo está em /orcamentos/orcamentos/, o conexao.php está em /orcamentos/
ini_set('display_errors', 0);
require '../conexao.php';

$termo = $_GET['termo'] ?? '';
$tipo = $_GET['tipo'] ?? 'produto'; // 'produto' ou 'servico'

if (empty($termo)) {
    echo json_encode([]);
    exit;
}

try {
    $pdo->exec("SET NAMES utf8");
    $resultados = [];

    if ($tipo == 'produto') {
        $stmt = $pdo->prepare("SELECT idProdutos as id, descricao, COALESCE(unidade, 'UN') as unidade, precoVenda as preco FROM produtos WHERE (descricao LIKE :termo1 OR idProdutos LIKE :termo2) LIMIT 20");
        $stmt->bindValue(':termo1', '%' . $termo . '%');
        $stmt->bindValue(':termo2', '%' . $termo . '%');
        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($tipo == 'servico') {
        $stmt = $pdo->prepare("SELECT idServicos as id, nome as descricao, 'HR' as unidade, preco FROM servicos WHERE (nome LIKE :termo1 OR idServicos LIKE :termo2) LIMIT 20");
        $stmt->bindValue(':termo1', '%' . $termo . '%');
        $stmt->bindValue(':termo2', '%' . $termo . '%');
        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Tratamento de segurança para UTF-8 e Números
    foreach ($resultados as &$item) {
        $item['id'] = (int) $item['id'];
        $item['preco'] = (float) $item['preco'];

        // Garante UTF-8 se não estiver (Compatível com PHP 8.2+)
        if (is_string($item['descricao']) && function_exists('mb_detect_encoding') && function_exists('mb_convert_encoding')) {
            if (!mb_detect_encoding($item['descricao'], 'UTF-8', true)) {
                $item['descricao'] = mb_convert_encoding($item['descricao'], 'UTF-8', 'ISO-8859-1');
            }
        }
    }

    echo json_encode($resultados);

} catch (Exception $e) {
    // Retorna array vazio em caso de erro para não quebrar o JS
    echo json_encode([]);
}
