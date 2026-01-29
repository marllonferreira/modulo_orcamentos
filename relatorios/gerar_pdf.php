<?php
// gerar_relatorio.php

require_once __DIR__ . '/../config_geral.php';
require_once __DIR__ . '/../conexao.php';

// Verifica se existe o autoload do Mapos (Composer)
$autoloadPath = MAPOS_ROOT_PATH . 'application/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die('Erro: Autoload do Mapos não encontrado em ' . $autoloadPath);
}
require_once $autoloadPath;

// Recebe Filtros
$tipo = $_GET['tipo'] ?? 'geral';
$dataInicial = $_GET['dataInicial'] ?? null;
$dataFinal = $_GET['dataFinal'] ?? null;
// Nota: O filtro vem como 'cliente_id' do form (hidden input)
$clienteId = $_GET['cliente_id'] ?? null;
$status = $_GET['status'] ?? null;
$analise = $_GET['analise'] ?? 'aprovados';

// Inicializa Variáveis
$tituloRelatorio = 'Relatório de Orçamentos';
$filtrosDescricao = [];
$orcamentos = [];
$estatisticas = [];

// ==========================================================
// 1. CONSTRUÇÃO DA QUERY SQL
// ==========================================================

if ($tipo == 'estatistica') {
    // ------------------------------------------------------
    // RELATÓRIO ESTATÍSTICO (GROUP BY)
    // ------------------------------------------------------
    $tituloRelatorio = 'Estatísticas de Clientes';

    // Status alvo baseados na análise (Aprovados, Cancelados ou Rejeitados)
    $statusMapping = [
        'aprovados' => 'Aprovado',
        'cancelados' => 'Cancelado',
        'rejeitados' => 'Rejeitado'
    ];
    $statusAlvo = $statusMapping[$analise] ?? 'Aprovado';
    $tituloRelatorio .= ' (' . ($analise == 'rejeitados' ? 'Rejeitados' : ucfirst($analise)) . ')';

    // Ajuste de nomes de coluna: id, cliente_id, valor_total
    $sql = "SELECT c.nomeCliente, COUNT(o.id) as total, SUM(o.valor_total) as valor_total
            FROM mod_orc_orcamentos o
            JOIN clientes c ON c.idClientes = o.cliente_id
            WHERE o.status = :status";

    $params = [':status' => $statusAlvo];

    if ($dataInicial) {
        $sql .= " AND o.data_criacao >= :dataInicial";
        $params[':dataInicial'] = date('Y-m-d 00:00:00', strtotime($dataInicial));
        $filtrosDescricao[] = "De: " . date('d/m/Y', strtotime($dataInicial));
    }
    if ($dataFinal) {
        $sql .= " AND o.data_criacao <= :dataFinal";
        $params[':dataFinal'] = date('Y-m-d 23:59:59', strtotime($dataFinal));
        $filtrosDescricao[] = "Até: " . date('d/m/Y', strtotime($dataFinal));
    }

    $sql .= " GROUP BY o.cliente_id ORDER BY total DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $estatisticas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} else {
    // ------------------------------------------------------
    // RELATÓRIO DE LISTAGEM (GERAL / CUSTOM)
    // ------------------------------------------------------

    // Removido JOIN com usuarios pois a tabela mod_orc_orcamentos não tem usuario_id
    $sql = "SELECT o.*, c.nomeCliente 
            FROM mod_orc_orcamentos o
            LEFT JOIN clientes c ON c.idClientes = o.cliente_id
            WHERE 1=1";

    $params = [];

    if ($tipo == 'custom') {
        if ($dataInicial) {
            $sql .= " AND o.data_criacao >= :dataInicial";
            $params[':dataInicial'] = date('Y-m-d 00:00:00', strtotime($dataInicial));
            $filtrosDescricao[] = "De: " . date('d/m/Y', strtotime($dataInicial));
        }
        if ($dataFinal) {
            $sql .= " AND o.data_criacao <= :dataFinal";
            $params[':dataFinal'] = date('Y-m-d 23:59:59', strtotime($dataFinal));
            $filtrosDescricao[] = "Até: " . date('d/m/Y', strtotime($dataFinal));
        }
        if ($clienteId) {
            $sql .= " AND o.cliente_id = :clienteId";
            $params[':clienteId'] = $clienteId;
            // Busca nome do cliente para exibir no filtro
            $stmtC = $pdo->prepare("SELECT nomeCliente FROM clientes WHERE idClientes = ?");
            $stmtC->execute([$clienteId]);
            $nomeCliente = $stmtC->fetchColumn();
            $filtrosDescricao[] = "Cliente: " . $nomeCliente;
        }
        if ($status) {
            $sql .= " AND o.status = :status";
            $params[':status'] = $status;
            $filtrosDescricao[] = "Status: " . $status;
        }
    }

    $sql .= " ORDER BY o.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orcamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ==========================================================
// 2. CONFIGURAÇÃO DO EMITENTE E LOGO (Base64)
// ==========================================================
$stmtEmitente = $pdo->query("SELECT * FROM emitente LIMIT 1");
$emitente = $stmtEmitente->fetch(PDO::FETCH_ASSOC);

$logoHtml = ''; // Variável que será usada no template

if ($emitente && !empty($emitente['url_logo'])) {
    $logo_url_db = $emitente['url_logo'];
    $logo_path = '';
    $nome_arquivo = basename($logo_url_db);

    // Caminhos possíveis para encontrar a logo localmente
    $caminho_padrao_mapos = MAPOS_ROOT_PATH . 'assets/uploads/' . $nome_arquivo;

    if (file_exists($caminho_padrao_mapos)) {
        $logo_path = $caminho_padrao_mapos;
    } elseif (filter_var($logo_url_db, FILTER_VALIDATE_URL)) {
        $logo_path = $logo_url_db;
    } else {
        $possible_paths = [
            MAPOS_ROOT_PATH . $logo_url_db,
            MAPOS_ROOT_PATH . 'assets/uploads/' . $logo_url_db,
            __DIR__ . '/../../' . $logo_url_db // Tenta subir níveis relativo ao módulo
        ];
        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                $logo_path = $path;
                break;
            }
        }
    }

    if (!empty($logo_path)) {
        $type = pathinfo($logo_path, PATHINFO_EXTENSION);
        if (empty($type))
            $type = 'png';

        $data = @file_get_contents($logo_path);
        if ($data) {
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
            // Salva apenas a string base64 ou tag img completa? 
            // O template_relatorio.php espera a URL ou tag? Vamos adaptar o template para receber $logoHtml
            // Mas para manter compatibilidade com o template atual que checa url_logo, vamos injetar o Base64 em 'url_logo' 
            // OU melhor: enviar uma variável $logoBase64 e ajustar o template.
            $logoBase64 = $base64;
        }
    }
}


// ==========================================================
// 2. GERAÇÃO DO HTML (BUFFER)
// ==========================================================

ob_start();
include __DIR__ . '/../views/relatorios/template_relatorio.php';
$html = ob_get_clean();

// ==========================================================
// 3. GERAÇÃO DO PDF (MPDF)
// ==========================================================
try {
    $tempDir = MAPOS_ROOT_PATH . 'assets/uploads/temp/';

    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0777, true);
    }

    $mpdf = new \Mpdf\Mpdf(['c', 'A4', 'tempDir' => $tempDir]);

    $mpdf->allow_charset_conversion = true;
    $mpdf->charset_in = 'UTF-8';
    $mpdf->WriteHTML($html);

    // Saída forcando Download para não sair da página
    $mpdf->Output('Relatorio_Orcamentos_' . date('d_m_Y') . '.pdf', 'D');

} catch (\Mpdf\MpdfException $e) {
    echo "Erro ao gerar PDF: " . $e->getMessage();
}
