<?php
// gerar_pdf.php - GERAÇÃO DE PDF PROFISSIONAL COM DOMPDF
// buffer inicia aqui para evitar erro de 'headers already sent' ou lixo no PDF
ob_start();

if (!isset($_SESSION)) {
    session_start();
}


require '../vendor/autoload.php';
require '../conexao.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Define timezone para evitar warnings
$isDark = ($configuration['app_theme'] == 'puredark' || $configuration['app_theme'] == 'darkviolet');

$orcamento_id = $_GET['id'] ?? null;
$rodapeTipo = $_GET['rodape_tipo'] ?? 'padrao';

if (!$orcamento_id || !is_numeric($orcamento_id)) {
    die("ID inválido.");
}

try {
    // 1. BUSCAR DADOS
    // Orçamento + Cliente (Colunas ajustadas para MapOS)
    $stmt = $pdo->prepare("
        SELECT o.*, 
               c.nomeCliente as cliente_nome, 
               CONCAT(c.rua, ', ', c.numero, ', ', c.bairro) AS endereco,
               c.cidade, c.estado, c.telefone
        FROM mod_orc_orcamentos o
        JOIN clientes c ON o.cliente_id = c.idClientes
        WHERE o.id = :id
    ");
    $stmt->execute(['id' => $orcamento_id]);
    $orcamento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$orcamento)
        die("Orçamento não encontrado.");

    // Itens (Tabela mod_orc_itens)
    $stmtItens = $pdo->prepare("SELECT * FROM mod_orc_itens WHERE orcamento_id = :id ORDER BY id ASC");
    $stmtItens->execute(['id' => $orcamento_id]);
    $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

    // Configurações da Empresa (Tabela emitente)
    $stmtConfig = $pdo->query("SELECT * FROM emitente LIMIT 1");
    $dados_emitente = $stmtConfig->fetch(PDO::FETCH_ASSOC);

    // Mapeia para o formato esperado ($config)
    $config = [];
    if ($dados_emitente) {
        $config['nome_fantasia'] = $dados_emitente['nome'];
        $config['cnpj'] = $dados_emitente['cnpj'];
        $config['telefone_contato'] = $dados_emitente['telefone'];
        $config['email_contato'] = $dados_emitente['email'];
        $config['logo_url'] = $dados_emitente['url_logo'];
        $config['endereco_completo'] = "{$dados_emitente['rua']}, {$dados_emitente['numero']} - {$dados_emitente['bairro']} - {$dados_emitente['cidade']}/{$dados_emitente['uf']} - CEP: {$dados_emitente['cep']}";
    }

    // 2. PREPARAR DADOS PARA O VIEW
    function fmtMoeda($val)
    {
        return 'R$ ' . number_format((float) $val, 2, ',', '.');
    }

    // Logo (Lógica robusta igual imprimir_orcamento.php)
    $logoHtml = '';
    if (!empty($config['logo_url'])) {
        $logo_url_db = $config['logo_url'];
        $logo_path = '';

        $nome_arquivo = basename($logo_url_db);
        // Usa constante raiz definida no config_geral.php para ser imune a renomeação da pasta do sistema
        $caminho_padrao_mapos = MAPOS_ROOT_PATH . 'assets/uploads/' . $nome_arquivo;

        if (file_exists($caminho_padrao_mapos)) {
            $logo_path = $caminho_padrao_mapos;
        } elseif (filter_var($logo_url_db, FILTER_VALIDATE_URL)) {
            $logo_path = $logo_url_db;
        } else {
            $possible_paths = [
                MAPOS_ROOT_PATH . $logo_url_db,
                MAPOS_ROOT_PATH . 'assets/uploads/' . $logo_url_db,
                __DIR__ . '/../' . $logo_url_db
            ];
            foreach ($possible_paths as $path) {
                if (file_exists($path)) {
                    $logo_path = $path;
                    break;
                }
            }
        }

        if (!empty($logo_path)) {
            // Para DOMPDF, é melhor usar caminhos absolutos ou base64.
            // Se for URL remota e allow_url_fopen estiver on, file_get_contents funciona.
            // Se for local, file_get_contents funciona.
            // Base64 é o método mais seguro para evitar problemas de caminho no DOMPDF.
            $type = pathinfo($logo_path, PATHINFO_EXTENSION);
            if (empty($type))
                $type = 'png'; // fallback

            // Suprime avisos caso o acesso falhe
            $data = @file_get_contents($logo_path);

            if ($data) {
                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                $logoHtml = "<img src='{$base64}' style='max-height: 80px; max-width: 200px;'>";
            }
        }
    }

    // SEPARAÇÃO DOS ITENS E CÁLCULO DE SUBTOTAL
    $produtos = [];
    $servicos = [];
    $subtotalProdutos = 0;
    $subtotalServicos = 0;

    foreach ($itens as $item) {
        $tipo = trim($item['tipo_item']);
        // Ajuste: coluna 'subtotal'
        $totalItem = (float) $item['subtotal'];

        if ($tipo === 'P') {
            $produtos[] = $item;
            $subtotalProdutos += $totalItem;
        } else {
            $servicos[] = $item;
            $subtotalServicos += $totalItem;
        }
    }

    // 3. MONTAR O HTML DO PDF
    // Regras de Paginação REATIVADAS:
    // - .product-block: Quebra livre, header repete.
    // - .service-total-wrapper: Bloco indivisível (Serviços + Totais).
    // - Rodapé: VOLTA A SER FIXO (bottom: -60px) com margem de segurança de 80px na página.

    $html = "
    <html>
    <head>
        <style>
            /* MARGEM SEGURA DE 80px PARA NÃO ATROPELAR O RODAPÉ FIXO */
            @page { margin: 50px 0px 80px 0px; }
            body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #333; margin: 0px; }
            
            /* CABEÇALHO COMPACTO E TELEFONE UNIFICADO */
            .header { background-color: #f8f9fa; padding: 40px 40px 10px 40px; border-bottom: 2px solid #0d6efd; margin-top: -50px; margin-bottom: 5px; }
            .content { padding: 10px 40px; }
            
            /* RODAPÉ FIXO EM TODAS AS PÁGINAS (Comportamento Original) */
            .footer { position: fixed; bottom: -60px; left: 0; right: 0; background-color: #f8f9fa; height: 30px; padding: 10px 40px; font-size: 10px; color: #777; border-top: 1px solid #ddd; }
            
            .title { font-size: 24px; font-weight: bold; color: #0d6efd; text-transform: uppercase; }
            .subtitle { font-size: 14px; color: #555; margin-bottom: 5px; }
            
            .table-items { width: 100%; border-collapse: collapse; margin-top: 5px; }
            .table-items th { background-color: #0d6efd; color: #fff; padding: 8px; text-align: left; font-size: 11px; font-weight: bold; text-transform: uppercase; }
            
            /* GATILHO PARA REPETIR CABEÇALHO DA TABELA NA PRÓXIMA PÁGINA */
            .table-items thead { display: table-header-group; }
            
            /* Rodapé da tabela (para subtotais): Garante que não se separe da tabela */
            .table-items tfoot { display: table-row-group; page-break-inside: avoid; }
            .table-items tfoot td { background-color: #fff; font-weight: bold; border-top: 2px solid #ddd; }

            /* Permite quebra de linhas, exceto se for pequeno demais (opcional) */
            .table-items tr { page-break-inside: auto; } 
            .table-items td { border-bottom: 1px solid #eee; padding: 8px; font-size: 11px; }
            .table-items tr:nth-child(even) { background-color: #f9f9f9; }
            
            .section-title { 
                color: #0d6efd; 
                text-transform: uppercase; 
                font-size: 11px; 
                font-weight: bold; 
                margin-bottom: 5px; 
                display: block; 
                /* Tenta manter o título colado na tabela */
                page-break-after: avoid; 
            }

            .total-box { margin-top: 20px; text-align: right; }
            .total-label { font-size: 14px; font-weight: bold; color: #555; }
            .total-value { font-size: 20px; font-weight: bold; color: #198754; }
            
            .badge { display: inline-block; padding: 3px 6px; border-radius: 3px; font-size: 10px; font-weight: bold; color: white; background-color: #6c757d; }
            
            /* -- REGRAS DE PAGINAÇÃO VÁLIDAS -- */

            /* Produtos: Permite fluir livremente entre páginas. MARGEM REDUZIDA PARA 10px */
            .product-block {
                display: block;
                /* page-break-inside: auto; (Padrão) */
                margin-bottom: 10px;
            }

            /* Serviços + Totais: Tenta manter junto. Se não couber, joga tudo pro começo da prox pag */
            .service-total-wrapper {
                page-break-inside: avoid; 
                display: block;
            }

            /* Observações: */
            .obs-block {
                margin-top: 20px; 
                border-top: 1px solid #ddd; 
                padding-top: 10px;
                page-break-inside: avoid;
            }
            
            /* Total Geral: Tenta não separar do conteúdo anterior */
            .total-box { 
                margin-top: 20px; 
                text-align: right; 
                page-break-before: avoid; /* Tenta ficar na mesma página do bloco anterior */
                page-break-inside: avoid;
            }

        </style>
    </head>
    <body>

        <div class='header'>
            <table style='width: 100%;'>
                <tr>
                    <td style='width: 50%;'>
                        {$logoHtml}
                        <div style='margin-top: 10px;'>
                            <strong>" . htmlspecialchars($config['nome_fantasia'] ?? 'Minha Empresa') . "</strong><br>
                            " . (!empty($config['cnpj']) ? "CNPJ: " . htmlspecialchars($config['cnpj']) . "<br>" : "") . "
                            " . htmlspecialchars($config['endereco_completo'] ?? '') . "<br>
                            " . htmlspecialchars($config['telefone_contato'] ?? '') . " - " . htmlspecialchars($config['email_contato'] ?? '') . "
                            " . (isset($_SESSION['usuario_nome']) ? "<br><br><small>Emitido por: <strong>" . htmlspecialchars($_SESSION['usuario_nome']) . "</strong></small>" : "") . "
                        </div>
                    </td>
                    <td style='width: 50%; text-align: right;'>
                        <div class='title'>ORÇAMENTO #{$orcamento['id']}</div>
                        <div class='subtitle'>Data: " . date('d/m/Y', strtotime($orcamento['data_criacao'])) . "</div>
                        <div class='subtitle'>Validade: {$orcamento['validade_dias']} dias</div>
                        <br>
                        Status: <span class='badge'>" . strtoupper($orcamento['status']) . "</span>
                    </td>
                </tr>
            </table>
        </div>

        <div class='content'>
            
            <!-- DADOS DO CLIENTE -->
            <div style='background-color: #e9ecef; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>
                <strong style='color: #0d6efd; text-transform: uppercase; font-size: 11px;'>Dados do Cliente</strong><br>
                <div style='font-size: 14px; font-weight: bold; margin-top: 5px;'>" . htmlspecialchars($orcamento['cliente_nome']) . "</div>
                <div style='margin-top: 2px;'>" . htmlspecialchars($orcamento['endereco']) . ", " . htmlspecialchars($orcamento['cidade']) . " - " . htmlspecialchars($orcamento['estado']) . "</div>
                <div style='margin-top: 2px;'>Tel: " . htmlspecialchars($orcamento['telefone']) . "</div>
            </div>";

    // --- 1. TABELA DE PRODUTOS ---
    if (!empty($produtos)) {
        $html .= "
            <div class='product-block'>
                <span class='section-title'>1. Produtos / Materiais</span>
                <table class='table-items'>
                    <thead>
                        <tr>
                            <th style='width: 5%;'>#</th>
                            <th style='width: 45%;'>Descrição</th>
                            <th style='width: 10%; text-align: center;'>Unid.</th>
                            <th style='width: 10%; text-align: center;'>Qtd.</th>
                            <th style='width: 15%; text-align: right;'>Unitário</th>
                            <th style='width: 15%; text-align: right;'>Total</th>
                        </tr>
                    </thead>
                    <tbody>";

        $count = 1;
        foreach ($produtos as $item) {
            $html .= "
            <tr>
                <td>{$count}</td>
                <td>" . htmlspecialchars($item['descricao']) . "</td>
                <td style='text-align: center;'>" . strtoupper($item['unidade']) . "</td>
                <td style='text-align: center;'>" . number_format($item['quantidade'], 2, ',', '.') . "</td>
                <td style='text-align: right;'>" . fmtMoeda(($item['quantidade'] > 0) ? $item['subtotal'] / $item['quantidade'] : $item['preco_unitario']) . "</td>
                <td style='text-align: right;'><strong>" . fmtMoeda($item['subtotal']) . "</strong></td>
            </tr>";
            $count++;
        }

        // USO CORRETO DO TFOOT: Garante que o total nunca se desprenda da tabela
        $html .= "
                    </tbody>
                    <tfoot>
                        " . (($subtotalProdutos <= 0.001) ?
            "<tr>
                                <td colspan='6' style='color: #d9534f; font-weight: bold; font-size: 11px; text-align: center; border: none; padding: 5px 0;'>
                                    Atenção: Itens listados para fins técnicos. Aquisição por conta do cliente.
                                </td>
                            </tr>"
            : (($subtotalProdutos > 0 && $rodapeTipo === 'mao_de_obra') ?
                "<tr>
                                <td colspan='6' style='color: #d9534f; font-weight: bold; font-size: 11px; text-align: center; border: none; padding: 5px 0;'>
                                    Estimativa técnica: A aquisição dos materiais é de responsabilidade do cliente. Confira os preços diretamente na loja.
                                </td>
                            </tr>"
                : "")) . "
                        
                        <tr>
                            <td colspan='5' style='text-align: right; border: none; padding-top: 10px;'>Total Produtos:</td>
                            <td style='text-align: right; border: none; padding-top: 10px;'><strong>" . fmtMoeda($subtotalProdutos) . "</strong></td>
                        </tr>
                        " . (empty($servicos) ? "
                        <tr>
                            <td colspan='4' style='text-align: right; border: none; padding-top: 20px; color: #555; font-size: 14px; font-weight: bold;'>VALOR TOTAL:</td>
                            <td colspan='2' style='text-align: right; border: none; padding-top: 20px; color: #198754; font-size: 20px; font-weight: bold; white-space: nowrap;'>" . fmtMoeda($orcamento['valor_total']) . "</td>
                        </tr>" : "") . "
                    </tfoot>
                </table>
            </div>";
    }

    // --- INÍCIO DO BLOCO INDIVISÍVEL (SERVIÇOS + TOTAL + OBS) ---
    // A ideia é: Se este bloco não couber na página, ele vai inteiro pra próxima.

    $html .= "<div class='service-total-wrapper'>";

    // --- 2. TABELA DE SERVIÇOS ---
    if (!empty($servicos)) {
        $html .= "
            <div class='service-section'>
                <span class='section-title'>2. Serviços / Mão de Obra</span>
                <table class='table-items'>
                    <thead>
                        <tr>
                            <th style='width: 5%;'>#</th>
                            <th style='width: 45%;'>Descrição</th>
                            <th style='width: 10%; text-align: center;'>Unid.</th>
                            <th style='width: 10%; text-align: center;'>Qtd.</th>
                            <th style='width: 15%; text-align: right;'>Unitário</th>
                            <th style='width: 15%; text-align: right;'>Total</th>
                        </tr>
                    </thead>
                    <tbody>";

        $count = 1;
        foreach ($servicos as $item) {
            $html .= "
            <tr>
                <td>{$count}</td>
                <td>" . htmlspecialchars($item['descricao']) . "</td>
                <td style='text-align: center;'>" . strtoupper($item['unidade']) . "</td>
                <td style='text-align: center;'>" . number_format($item['quantidade'], 2, ',', '.') . "</td>
                <td style='text-align: right;'>" . fmtMoeda(($item['quantidade'] > 0) ? $item['subtotal'] / $item['quantidade'] : $item['preco_unitario']) . "</td>
                <td style='text-align: right;'><strong>" . fmtMoeda($item['subtotal']) . "</strong></td>
            </tr>";
            $count++;
        }

        $html .= "
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan='5' style='text-align: right; border: none; padding-top: 10px;'>Total Serviços:</td>
                            <td style='text-align: right; border: none; padding-top: 10px;'><strong>" . fmtMoeda($subtotalServicos) . "</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>";
    }

    // FINAL (TOTAL E RODAPÉ) - DENTRO DO WRAPPER
    $html .= "
            <!-- TOTAL GERAL (Apenas se houver serviços, senão já foi exibido na tabela de produtos) -->
            " . (!empty($servicos) ? "
            <div class='total-box'>
                <span class='total-label'>VALOR TOTAL:</span>
                <span class='total-value'>" . fmtMoeda($orcamento['valor_total']) . "</span>
            </div>" : "") . "

            <!-- OBSERVAÇÕES -->
            " . (!empty($orcamento['observacoes']) ? "
            <div class='obs-block'>
                <strong>Observações:</strong><br>
                " . nl2br(htmlspecialchars($orcamento['observacoes'])) . "
            </div>" : "") . "
            
    </div> <!-- Fim de service-total-wrapper -->";

    // Fechamento .content
    $html .= "</div>

        <!-- RODAPÉ FIXO EM TODAS AS PÁGINAS -->
        <div class='footer'>
            " .
        (($subtotalProdutos <= 0.001 || ($subtotalProdutos > 0 && $rodapeTipo === 'mao_de_obra')) ?
            "Este documento não possui valor fiscal. O valor total indicado é uma estimativa (Mão de Obra + Ref. Materiais). O pagamento ao profissional refere-se apenas aos serviços executados." :
            "Este documento não possui valor fiscal. O valor total inclui materiais e mão de obra conforme descrito nas tabelas acima. Após o prazo de validade, os preços de materiais estão sujeitos a reajuste de mercado.")
        . "
            <br>Gerado pelo Sistema em " . date('d/m/Y H:i') . "
        </div>

    </body>
    </html>";

    // 4. GERAÇÃO DO PDF
    // Limpa qualquer output buffer anterior (espaços em branco, avisos do PHP, etc) para não corromper o PDF
    ob_clean();

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    // Configura diretorio temporario para evitar erro de permissão
    $tmpDir = sys_get_temp_dir();
    $options->set('tempDir', $tmpDir);
    $options->set('fontDir', $tmpDir);
    $options->set('fontCache', $tmpDir);

    $dompdf = new Dompdf($options);
    $dompdf->setPaper('A4', 'portrait');

    $dompdf->loadHtml($html);
    $dompdf->render();

    // Sanitiza nome do cliente para uso em arquivo (apenas letras, numeros e traços)
    $nomeClienteSanitizado = preg_replace('/[^a-zA-Z0-9_-]/', '_', $orcamento['cliente_nome']);
    // Remove underscores repetidos
    $nomeClienteSanitizado = preg_replace('/_+/', '_', $nomeClienteSanitizado);

    // Header para garantir download e tipo correto
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="Orcamento_' . $nomeClienteSanitizado . '_ID_0' . $orcamento_id . '.pdf"');

    // Output final
    echo $dompdf->output();
    exit;

} catch (\Throwable $e) {
    // Caso de erro crítico, limpa o buffer e mostra o erro
    ob_end_clean();
    die("<h1>Erro ao Gerar PDF</h1><p>Ocorreu um problema técnico: " . $e->getMessage() . "</p><pre>" . $e->getTraceAsString() . "</pre>");
}
