<?php
// =================================================================
// imprimir_orcamento.php - LAYOUT PADRONIZADO (BASEADO NO PDF)
// =================================================================

require '../conexao.php';

// --- CONFIGURAÇÕES GERAIS ---

// --- FUNÇÕES DE SUPORTE ---
function formatarMoeda($valor)
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

function formatarCpfCnpj($valor)
{
    $valor = preg_replace('/\D/', '', $valor);
    if (strlen($valor) === 11) {
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $valor);
    } elseif (strlen($valor) === 14) {
        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $valor);
    }
    return $valor;
}

function getLogoPath($logo_url, $mapos_root = '../../mapos/')
{
    if (empty($logo_url))
        return '';

    $local_path = $mapos_root . 'assets/uploads/' . basename($logo_url);
    if (file_exists($local_path))
        return $local_path;

    if (filter_var($logo_url, FILTER_VALIDATE_URL))
        return $logo_url;

    // Tentativas extras
    $paths = [
        $mapos_root . $logo_url,
        '../' . $logo_url
    ];
    foreach ($paths as $p) {
        if (file_exists($p))
            return $p;
    }
    return '';
}

// --- 1. BUSCAR DADOS (Igual gerar_pdf.php + imprimir_orcamento.php) ---
$orcamento_id = $_GET['id'] ?? null;
$rodapeTipo = $_GET['rodape_tipo'] ?? 'padrao';

if (!$orcamento_id || !is_numeric($orcamento_id))
    die("ID inválido.");

try {
    // Orçamento + Cliente
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

    // Itens
    $stmtItens = $pdo->prepare("SELECT * FROM mod_orc_itens WHERE orcamento_id = :id ORDER BY id ASC");
    $stmtItens->execute(['id' => $orcamento_id]);
    $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

    // Emitente
    $stmtConfig = $pdo->query("SELECT * FROM emitente LIMIT 1");
    $dados_emitente = $stmtConfig->fetch(PDO::FETCH_ASSOC);

    $config = [];
    if ($dados_emitente) {
        $config['nome_fantasia'] = $dados_emitente['nome'];
        $config['cnpj'] = $dados_emitente['cnpj'];
        $config['telefone_contato'] = $dados_emitente['telefone'];
        $config['email_contato'] = $dados_emitente['email'];
        $config['logo_url'] = $dados_emitente['url_logo'];
        $config['endereco_completo'] = "{$dados_emitente['rua']}, {$dados_emitente['numero']} - {$dados_emitente['bairro']} - {$dados_emitente['cidade']}/{$dados_emitente['uf']} - CEP: {$dados_emitente['cep']}";
    }

    // Separação de Itens
    $produtos = [];
    $servicos = [];
    $subtotalProdutos = 0;
    $subtotalServicos = 0;

    foreach ($itens as $item) {
        $tipo = trim($item['tipo_item']);
        $totalItem = (float) $item['subtotal'];

        if ($tipo === 'P') {
            $produtos[] = $item;
            $subtotalProdutos += $totalItem;
        } else {
            // S (Serviços) e M (Manuais/Diversos) vão para o segundo grupo
            $servicos[] = $item;
            $subtotalServicos += $totalItem;
        }
    }

    // Definir Flags de Lógica
    $isZeroMaterials = ($subtotalProdutos <= 0.001);
    $isHybridLabor = ($subtotalProdutos > 0 && $rodapeTipo === 'mao_de_obra');

} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Orçamento #<?= $orcamento['id'] ?> - <?= htmlspecialchars($orcamento['cliente_nome']) ?></title>
    <style>
        /* ESTILOS COPIADOS E ADAPTADOS DE GERAR_PDF.PHP */
        @page {
            margin: 10mm 10mm 10mm 10mm;
        }

        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 0;
        }

        /* O header na impressão de navegador não precisa de margem negativa */
        .header {
            background-color: #f8f9fa;
            padding: 20px 40px;
            border-bottom: 2px solid #0d6efd;
            margin-bottom: 20px;
        }

        .content {
            padding: 0 40px;
        }

        /* Rodapé fixo - Ajustado para visualização em tela vs impressão */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: #f8f9fa;
            padding: 10px 40px;
            font-size: 10px;
            color: #777;
            border-top: 1px solid #ddd;
        }

        /* Espaço no final do corpo para não sobrescrever o rodapé */
        body {
            padding-bottom: 50px;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            color: #0d6efd;
            text-transform: uppercase;
        }

        .subtitle {
            font-size: 14px;
            color: #555;
            margin-bottom: 5px;
        }

        .table-items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            margin-bottom: 20px;
        }

        /* Cores de Cabeçalho - Ajuste `print-color-adjust` para forçar cor na impressão */
        .table-items th {
            background-color: #0d6efd !important;
            color: #fff !important;
            padding: 8px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .table-items td {
            border-bottom: 1px solid #eee;
            padding: 8px;
            font-size: 11px;
        }

        .table-items tr:nth-child(even) {
            background-color: #f9f9f9;
            -webkit-print-color-adjust: exact;
        }

        .section-title {
            color: #0d6efd;
            text-transform: uppercase;
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }

        .total-box {
            margin-top: 20px;
            text-align: right;
        }

        .total-label {
            font-size: 14px;
            font-weight: bold;
            color: #555;
        }

        .total-value {
            font-size: 20px;
            font-weight: bold;
            color: #198754;
        }

        .badge {
            display: inline-block;
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            color: white;
            background-color: #6c757d;
            -webkit-print-color-adjust: exact;
        }

        /* Utilitários de Impressão */
        @media print {
            body {
                -webkit-print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }

            /* Evita quebras indesejadas */
            .table-items tr {
                page-break-inside: avoid;
            }

            .service-total-wrapper {
                page-break-inside: avoid;
                break-inside: avoid;
                /* Suporte moderno */
            }

            .obs-block {
                page-break-inside: avoid;
                break-inside: avoid;
                page-break-before: avoid;
                /* Tenta grudar no elemento anterior */
            }

            .total-box {
                page-break-inside: avoid;
                break-inside: avoid;
                page-break-before: avoid;
                /* Tenta grudar na tabela */
            }

            .info-box {
                page-break-inside: avoid;
                break-inside: avoid;
            }
        }
    </style>
</head>

<body>

    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%;">
                    <?php
                    $logo_path = getLogoPath($config['logo_url'] ?? '');
                    if ($logo_path):
                        ?>
                        <img src="<?= htmlspecialchars($logo_path) ?>" style="max-height: 80px; max-width: 200px;">
                    <?php else: ?>
                        <h2 style="margin:0; color:#0d6efd;"><?= htmlspecialchars($config['nome_fantasia'] ?? 'Empresa') ?>
                        </h2>
                    <?php endif; ?>

                    <div style="margin-top: 10px; font-size:12px;">
                        <strong><?= htmlspecialchars($config['nome_fantasia'] ?? '') ?></strong><br>
                        <?= !empty($config['cnpj']) ? "CNPJ: " . formatarCpfCnpj($config['cnpj']) . "<br>" : "" ?>
                        <?= htmlspecialchars($config['endereco_completo'] ?? '') ?><br>
                        <?= htmlspecialchars($config['telefone_contato'] ?? '') ?> -
                        <?= htmlspecialchars($config['email_contato'] ?? '') ?>
                        <?php if (isset($_SESSION['usuario_nome'])): ?>
                            <br><br><small>Emitido por:
                                <strong><?= htmlspecialchars($_SESSION['usuario_nome']) ?></strong></small>
                        <?php endif; ?>
                    </div>
                </td>
                <td style="width: 50%; text-align: right; vertical-align: top;">
                    <div class="title">ORÇAMENTO #<?= $orcamento['id'] ?></div>
                    <div class="subtitle">Data: <?= date('d/m/Y', strtotime($orcamento['data_criacao'])) ?></div>
                    <div class="subtitle">Validade: <?= htmlspecialchars($orcamento['validade_dias']) ?> dias</div>
                    <br>
                    Status: <span class="badge"><?= strtoupper($orcamento['status']) ?></span>
                </td>
            </tr>
        </table>
    </div>

    <div class="content">

        <!-- DADOS DO CLIENTE -->
        <div
            style="background-color: #e9ecef; padding: 15px; border-radius: 5px; margin-bottom: 20px; -webkit-print-color-adjust: exact;">
            <strong style="color: #0d6efd; text-transform: uppercase; font-size: 11px;">Dados do Cliente</strong><br>
            <div style="font-size: 14px; font-weight: bold; margin-top: 5px;">
                <?= htmlspecialchars($orcamento['cliente_nome']) ?>
            </div>
            <div style="margin-top: 2px;"><?= htmlspecialchars($orcamento['endereco']) ?>,
                <?= htmlspecialchars($orcamento['cidade']) ?> - <?= htmlspecialchars($orcamento['estado']) ?>
            </div>
            <div style="margin-top: 2px;">Tel: <?= htmlspecialchars($orcamento['telefone']) ?></div>
        </div>

        <!-- 1. PRODUTOS -->
        <?php if (!empty($produtos)): ?>
            <div class="product-block">
                <span class="section-title">1. Produtos / Materiais</span>
                <table class="table-items">
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 45%;">Descrição</th>
                            <th style="width: 10%; text-align: center;">Unid.</th>
                            <th style="width: 10%; text-align: center;">Qtd.</th>
                            <th style="width: 15%; text-align: right;">Unitário</th>
                            <th style="width: 15%; text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $count = 1;
                        foreach ($produtos as $item):
                            $unitario = ($item['quantidade'] > 0) ? $item['subtotal'] / $item['quantidade'] : $item['preco_unitario'];
                            ?>
                            <tr>
                                <td><?= $count++ ?></td>
                                <td><?= htmlspecialchars($item['descricao']) ?></td>
                                <td style="text-align: center;"><?= strtoupper($item['unidade']) ?></td>
                                <td style="text-align: center;"><?= number_format($item['quantidade'], 2, ',', '.') ?></td>
                                <td style="text-align: right;"><?= formatarMoeda($unitario) ?></td>
                                <td style="text-align: right;"><strong><?= formatarMoeda($item['subtotal']) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <?php if ($isZeroMaterials): ?>
                            <tr>
                                <td colspan="6"
                                    style="color: #d9534f; font-weight: bold; font-size: 11px; text-align: center; border: none; padding: 5px 0;">
                                    Atenção: Itens listados para fins técnicos. Aquisição por conta do cliente.
                                </td>
                            </tr>
                        <?php elseif ($isHybridLabor): ?>
                            <tr>
                                <td colspan="6"
                                    style="color: #d9534f; font-weight: bold; font-size: 11px; text-align: center; border: none; padding: 5px 0;">
                                    Estimativa técnica: A aquisição dos materiais é de responsabilidade do cliente. Confira os
                                    preços diretamente na loja.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <tr>
                            <td colspan="5" style="text-align: right; border: none; padding-top: 10px;">Total Produtos:</td>
                            <td style="text-align: right; border: none; padding-top: 10px;">
                                <strong><?= formatarMoeda($subtotalProdutos) ?></strong>
                            </td>
                        </tr>
                        <?php if (empty($servicos)): ?>
                            <tr>
                                <td colspan="4"
                                    style="text-align: right; border: none; padding-top: 20px; color: #555; font-size: 14px; font-weight: bold;">
                                    VALOR TOTAL:</td>
                                <td colspan="2"
                                    style="text-align: right; border: none; padding-top: 20px; color: #198754; font-size: 20px; font-weight: bold; white-space: nowrap;">
                                    <?= formatarMoeda($orcamento['valor_total']) ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>

        <!-- 2. SERVIÇOS E TOTAIS FINAIS -->
        <div class="service-total-wrapper">
            <?php if (!empty($servicos)): ?>
                <div class="service-section">
                    <span class="section-title">2. Serviços / Mão de Obra</span>
                    <table class="table-items">
                        <thead>
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 45%;">Descrição</th>
                                <th style="width: 10%; text-align: center;">Unid.</th>
                                <th style="width: 10%; text-align: center;">Qtd.</th>
                                <th style="width: 15%; text-align: right;">Unitário</th>
                                <th style="width: 15%; text-align: right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $count = 1;
                            foreach ($servicos as $item):
                                $unitario = ($item['quantidade'] > 0) ? $item['subtotal'] / $item['quantidade'] : $item['preco_unitario'];
                                ?>
                                <tr>
                                    <td><?= $count++ ?></td>
                                    <td><?= htmlspecialchars($item['descricao']) ?></td>
                                    <td style="text-align: center;"><?= strtoupper($item['unidade']) ?></td>
                                    <td style="text-align: center;"><?= number_format($item['quantidade'], 2, ',', '.') ?></td>
                                    <td style="text-align: right;"><?= formatarMoeda($unitario) ?></td>
                                    <td style="text-align: right;"><strong><?= formatarMoeda($item['subtotal']) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" style="text-align: right; border: none; padding-top: 10px;">Total Serviços:
                                </td>
                                <td style="text-align: right; border: none; padding-top: 10px;">
                                    <strong><?= formatarMoeda($subtotalServicos) ?></strong>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!empty($servicos)): ?>
                <div class="total-box">
                    <span class="total-label">VALOR TOTAL:</span>
                    <span class="total-value"><?= formatarMoeda($orcamento['valor_total']) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($orcamento['observacoes'])): ?>
                <div class="obs-block" style="margin-top: 20px; border-top: 1px solid #ddd; padding-top: 10px;">
                    <strong>Observações:</strong><br>
                    <?= nl2br(htmlspecialchars($orcamento['observacoes'])) ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <div class="footer">
        <?php
        if ($isZeroMaterials || $isHybridLabor) {
            echo "Este documento não possui valor fiscal. O valor total indicado é uma estimativa (Mão de Obra + Ref. Materiais). O pagamento ao profissional refere-se apenas aos serviços executados.";
        } else {
            echo "Este documento não possui valor fiscal. O valor total inclui materiais e mão de obra conforme descrito nas tabelas acima. Após o prazo de validade, os preços de materiais estão sujeitos a reajuste de mercado.";
        }
        echo "<br>Gerado pelo Sistema em " . date('d/m/Y H:i');
        ?>
    </div>

    <script>
        window.onload = function () {
            window.print();
        }
    </script>
</body>

</html>