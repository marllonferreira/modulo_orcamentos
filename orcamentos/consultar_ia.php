<?php
require_once 'config_ia.php';

// Evita que avisos do PHP corrompam o JSON no PHP 8.x
ini_set('display_errors', 0);
header('Content-Type: application/json');

// Lista de modelos para tentar (ordem de preferência: mais rápido/barato -> mais robusto)
// IMPORTANTE: A versão atual estável e funcional é a GEMINI 2.5 FLASH. Não alterar para versões anteriores (1.5/2.0) sem testar.
$modelos = [
    'gemini-2.5-flash', // Mais inteligente, prioridade para resolver interpretação de contexto
    'gemini-2.5-flash-lite', // Backup mais rápido
];

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

// Recebe os dados
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (!isset($data['itens']) || !is_array($data['itens'])) {
    echo json_encode(['success' => false, 'message' => 'Nenhum item enviado para consulta.']);
    exit;
}

$itens = $data['itens'];
$descricoes = array_map(function ($item, $k) {
    $u = strtoupper($item['unidade'] ?? '');

    // Prefixo Forçado para quebrar o padrão visual do início da string para a IA
    $prefixo = "";
    $sufixo = "";

    if ($u === 'M' || $u === 'MT' || $u === 'METRO') {
        $prefixo = "[APENAS 1 METRO] ";
        $sufixo = " (Preço unitário por METRO linear)";
    } elseif ($u === 'CX' || $u === 'CAIXA') {
        $prefixo = "[CAIXA FECHADA] ";
        $sufixo = " (Preço da CAIXA COMPLETA)";
    } else {
        $prefixo = "[$u] ";
        $sufixo = "";
    }

    // Adiciona ID único visível para a IA não agrupar
    return "- Item #" . ($k + 1) . ": " . $prefixo . $item['descricao'] . $sufixo;
}, $itens, array_keys($itens));

$prompt = "ATUAR COMO: Especialista Senior em Precificação de Materiais e Serviços no Brasil.

TAREFA: Analisar a lista de itens e atribuir um 'Preço Unitário de Venda' (R$) coerente com a 'Unidade' especificada.

REGRA DE OURO (CRÍTICA):
A UNIDADE define o preço. Você DEVE diferenciar drasticamente o preço baseando-se na unidade.
- Se a unidade for 'M' (Metro): Sugira o preço de APENAS 1 metro. (Ex: R$ 2,00)
- Se a unidade for 'CX' (Caixa): Sugira o preço da CAIXA COMPLETA (geralmente contém 305m ou 100m). (Ex: R$ 400,00)
- Se a unidade for 'UN' (Unidade): Preço unitário padrão.
- Se a unidade for 'HR' (Hora): Preço de mão de obra por hora.

Se houver itens iguais com unidades diferentes (ex: Cabo em M e Cabo em CX), o preço da CX deve ser MUITAS VEZES maior
que o do M.

FORMATO DE RESPOSTA (JSON Puro, sem markdown):
[{\"item\": \"nome do item\", \"preco_sugerido\": 0.00}]

ITENS PARA PRECIFICAÇÃO:
" . implode("\n", $descricoes);

$payload = [
    "contents" => [
        [
            "parts" => [
                ["text" => $prompt]
            ]
        ]
    ]
];

$last_error = '';
$sucesso = false;
$response = '';

// Obtém chaves disponíveis
// Se for definido como string antiga (retrocompatibilidade), converte para array
$api_keys = defined('GEMINI_API_KEYS') ? GEMINI_API_KEYS : (defined('GEMINI_API_KEY') ? [GEMINI_API_KEY] : []);

if (empty($api_keys)) {
    echo json_encode(['success' => false, 'message' => 'Nenhuma Chave de API configurada em config_ia.php.']);
    exit;
}

// Loop de Tentativa (Fallback de Modelos e Rotação de Chaves)
foreach ($modelos as $modelo) {

    // Para cada modelo, tenta todas as chaves disponíveis
    foreach ($api_keys as $index => $apiKey) {

        // Monta a URL dinâmica baseada no modelo atual e chave atual
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelo}:generateContent?key=" . trim($apiKey);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        // Timeout curto (15s)
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        // Desativa verificação SSL por compatibilidade com ambientes locais (Laragon/Windows)
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err = curl_error($ch);
        curl_close($ch);

        if ($http_code === 200) {
            $sucesso = true;
            break 2; // Funcionou! Sai dos dois loops (chaves e modelos).
        } elseif ($http_code === 429) {
            // COTA EXCEDIDA (Rate Limit)
            $last_error = "COTA_EXCEDIDA (Chave " . ($index + 1) . ")";
            // Continua para a próxima chave (o loop 'foreach $api_keys' segue naturalmente)
        } else {
            // Erro diferente (ex: 400 Bad Request, 500 Server Error)
            $last_error = "Modelo $modelo / Chave " . ($index + 1) . " falhou ($http_code). " . ($curl_err ?: "Resposta: " .
                (string) $response);
            // Se for erro de modelo inválido, talvez a chave funcione com outro modelo.
// Mas se for erro 403 (Permissão), a chave é ruim.
// Por segurança, continuamos tentando.
        }
    }
}

if (!$sucesso) {
    if ($last_error === "COTA_EXCEDIDA") {
        echo json_encode([
            'success' => false,
            'error_code' => 'QUOTA_EXCEEDED',
            'message' => 'Limite de consultas da IA
atingido. Tente novamente mais tarde.'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Todos os modelos de IA falharam. Último erro: ' . $last_error]);
    }
    exit;
}

// Processamento da resposta (Igual ao anterior)
$res_data = json_decode((string) $response, true);
$raw_text = $res_data['candidates'][0]['content']['parts'][0]['text'] ?? '';

if (empty($raw_text)) {
    echo json_encode(['success' => false, 'message' => 'IA retornou uma resposta vazia.', 'raw_data' => $res_data]);
    exit;
}

$clean_json = preg_replace('/^```json\s*|```\s*$/', '', trim((string) $raw_text));
$ia_sugestoes = json_decode((string) $clean_json, true);

if (!$ia_sugestoes) {
    echo json_encode(['success' => false, 'message' => 'Falha ao processar resposta da IA.', 'raw' => $raw_text]);
    exit;
}

echo json_encode(['success' => true, 'sugestoes' => $ia_sugestoes]);
?>