<?php
// ver_detalhes.php - MATRIX ADMIN LAYOUT - VISUAL "CARD" MODERNO
require '../conexao.php';
// Carregar Configurações e Arrays Auxiliares
require_once '../config_geral.php';
require_once 'config_ia.php';

// --- FUNÇÕES DE SUPORTE ---
function formatarMoeda($valor)
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

function getStatusBadge($status)
{
    $status = mb_strtoupper($status, 'UTF-8');
    switch ($status) {
        case 'APROVADO':
        case 'VENDIDO':
            return '<span class="label label-success label-pill">Aprovado</span>';
        case 'EMITIDO':
            return '<span class="label label-primary label-pill">Emitido</span>';
        case 'AGUARDANDO APROVAÇÃO':
        case 'AGUARDANDO APROVACAO':
            return '<span class="label label-info label-pill">Aguardando Aprovação</span>';
        case 'RASCUNHO':
            return '<span class="label label-warning label-pill">Rascunho</span>';
        case 'EM REVISÃO':
        case 'EM REVISAO':
            return '<span class="label label-inverse label-pill">Em Revisão</span>';
        case 'REJEITADO':
            return '<span class="label label-important label-pill">Rejeitado</span>';
        case 'CANCELADO':
            return '<span class="label label-important label-pill">Cancelado</span>';
        default:
            return '<span class="label label-pill">' . htmlspecialchars($status) . '</span>';
    }
}

function formatarTelefone($numero)
{
    $numero = preg_replace('/[^0-9]/', '', $numero);
    $len = strlen($numero);
    if ($len == 11)
        return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $numero);
    if ($len == 10)
        return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $numero);
    return $numero;
}

// --- LÓGICA DE DADOS ---
$orcamento_id = $_GET['id'] ?? null;
if (!$orcamento_id || !is_numeric($orcamento_id))
    die("ID Inválido");

$produtos = [];
$servicos = [];
$manuais = [];
$subtotal_produtos = 0;
$subtotal_servicos = 0;
$subtotal_manuais = 0;

// Variáveis Taxa (Por Grupo)
$total_sem_taxa_produtos = 0;
$has_tax_produtos = false;
$total_sem_taxa_servicos = 0;
$has_tax_servicos = false;
$total_sem_taxa_manuais = 0;
$has_tax_manuais = false;

// Variáveis Globais
$total_sem_taxa_acumulado = 0;
$possui_taxa = false;

try {
    // 1. Cabeçalho
    $stmt = $pdo->prepare("
        SELECT o.*, c.nomeCliente AS cliente_nome, c.telefone, c.rua, c.numero, c.bairro, c.cidade, c.estado 
        FROM mod_orc_orcamentos o
        JOIN clientes c ON o.cliente_id = c.idClientes
        WHERE o.id = :id
    ");
    $stmt->bindValue(':id', $orcamento_id);
    $stmt->execute();
    $orcamento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$orcamento)
        die("Orçamento não encontrado.");

    // 2. Itens
    $stmt = $pdo->prepare("SELECT * FROM mod_orc_itens WHERE orcamento_id = :id ORDER BY id ASC");
    $stmt->bindValue(':id', $orcamento_id);
    $stmt->execute();
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Separação
    foreach ($itens as $item) {
        $tipo = trim($item['tipo_item']);
        $total = (float) $item['subtotal'];

        // Cálculo Base do Item
        $val_base = (float) $item['preco_unitario'] * (float) $item['quantidade'];
        $tem_taxa_item = ((float) ($item['taxa'] ?? 0) > 0);

        // Acumula Global
        $total_sem_taxa_acumulado += $val_base;
        if ($tem_taxa_item)
            $possui_taxa = true;

        switch ($tipo) {
            case 'P':
                $produtos[] = $item;
                $subtotal_produtos += $total;
                $total_sem_taxa_produtos += $val_base;
                if ($tem_taxa_item)
                    $has_tax_produtos = true;
                break;
            case 'S':
                $servicos[] = $item;
                $subtotal_servicos += $total;
                $total_sem_taxa_servicos += $val_base;
                if ($tem_taxa_item)
                    $has_tax_servicos = true;
                break;
            case 'M':
                $manuais[] = $item;
                $subtotal_manuais += $total;
                $total_sem_taxa_manuais += $val_base;
                if ($tem_taxa_item)
                    $has_tax_manuais = true;
                break;
        }
    }

} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}

// Define a página atual para o breadcrumb do header.php
$pagina_atual = 'Detalhes do Orçamento';

// INCLUINDO O TOPO (HEADER) COM O CAMINHO CORRIGIDO
include '../tema/header.php';
?>

<!-- ESTILOS CUSTOMIZADOS PARA VISUAL "BOOTSTRAP 5 CARD" -->
<style>
    /* Container estilo "Card" */
    .widget-box.card-like {
        background: #fff !important;
        border: 1px solid #e3e6f0 !important;
        border-radius: 8px !important;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
        margin-top: 10px;
    }

    /* Cabeçalho do Card (Azul) */
    .widget-title.card-header-blue {
        background-color: #0d6efd !important;
        border-bottom: 1px solid #0d6efd !important;
        border-top-left-radius: 8px !important;
        border-top-right-radius: 8px !important;
        height: auto !important;
        padding: 15px 20px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        color: #fff !important;
    }

    .widget-title.card-header-blue h5 {
        font-size: 1.25rem !important;
        font-weight: 600 !important;
        margin: 0 !important;
        color: #fff !important;
        text-align: left !important;
        float: none !important;
        text-shadow: none !important;
    }

    .widget-content.card-body-like {
        padding: 24px !important;
        border: none !important;
        border-bottom-left-radius: 8px !important;
        border-bottom-right-radius: 8px !important;
    }

    /* Tipografia e Labels */
    h4 {
        font-size: 16px !important;
        font-weight: 700 !important;
        color: #333 !important;
        margin-top: 0;
        margin-bottom: 10px;
    }

    .detail-label {
        font-weight: 600 !important;
        color: #5a5c69 !important;
        font-size: 13px !important;
        margin-bottom: 2px;
    }

    .detail-value {
        font-size: 14px !important;
        color: #2c2e3e !important;
        line-height: 1.5;
    }

    /* Badges Arredondados (Pills) */
    .label-pill {
        border-radius: 50rem !important;
        padding: 4px 12px !important;
        font-size: 0.85rem !important;
        font-weight: 600 !important;
        display: inline-block;
        line-height: 14px;
    }

    .label-success {
        background-color: #1cc88a !important;
    }

    .label-info {
        background-color: #36b9cc !important;
    }

    .label-warning {
        background-color: #f6c23e !important;
        color: #fff !important;
    }

    /* Botões do Modelo */
    .btn-outline-custom {
        background-color: #fff !important;
        border: 1px solid #d1d3e2 !important;
        color: #0d6efd !important;
        font-weight: 500 !important;
        box-shadow: none !important;
        background-image: none !important;
    }

    .btn-outline-custom:hover {
        background-color: #f8f9fa !important;
        border-color: #b1b3cd !important;
        color: #0a58ca !important;
    }

    .btn-outline-cyan {
        background-color: #fff !important;
        border: 1px solid #17a2b8 !important;
        color: #17a2b8 !important;
        background-image: none !important;
    }

    .btn-outline-cyan:hover {
        background-color: #17a2b8 !important;
        color: #fff !important;
    }

    .btn-danger-custom {
        background-color: #dc3545 !important;
        border-color: #dc3545 !important;
        color: #fff !important;
        background-image: none !important;
    }

    .btn-success-custom {
        background-color: #198754 !important;
        border-color: #198754 !important;
        color: #fff !important;
        background-image: none !important;
    }

    /* Back Button (Top Right) */
    .btn-back-custom {
        background-color: #6c757d !important;
        border-color: #6c757d !important;
        color: #fff !important;
        font-size: 12px !important;
        padding: 5px 10px !important;
        background-image: none !important;
    }

    /* Tabela Limpa */
    .table-clean {
        width: 100%;
        border-collapse: collapse;
    }

    .table-clean th {
        background-color: #f8f9fc !important;
        border-bottom: 2px solid #e3e6f0 !important;
        color: #5a5c69 !important;
        font-weight: 700 !important;
        font-size: 12px !important;
        text-transform: uppercase;
        padding: 12px !important;
        text-align: left;
    }

    .table-clean td {
        vertical-align: middle !important;
        padding: 12px !important;
        color: #5a5c69 !important;
        border-bottom: 1px solid #e3e6f0;
        font-size: 13px !important;
    }

    .table-clean tr:last-child td {
        border-bottom: none;
    }

    .table-clean tr.bg-light-blue td {
        background-color: #e3f2fd !important;
        color: #0d6efd !important;
        font-weight: bold;
        border-bottom: 1px solid #bbdefb !important;
    }

    /* Botões Coloridos Vibrantes */
    .btn-purple {
        background-color: #6f42c1 !important;
        border-color: #6f42c1 !important;
        color: #fff !important;
        background-image: none !important;
    }

    .btn-purple:hover {
        background-color: #59359a !important;
    }

    .btn-blue {
        background-color: #0d6efd !important;
        border-color: #0d6efd !important;
        color: #fff !important;
        background-image: none !important;
    }

    .btn-blue:hover {
        background-color: #0b5ed7 !important;
    }

    .btn-orange {
        background-color: #fd7e14 !important;
        border-color: #fd7e14 !important;
        color: #fff !important;
        background-image: none !important;
    }

    .btn-orange:hover {
        background-color: #e96b02 !important;
    }

    hr {
        border-top: 1px solid #e3e6f0 !important;
        border-bottom: none;
        margin: 20px 0;
    }

    /* ESTILOS VISUAIS DOS BOTÕES DE AÇÃO (GLOBAL) */

    /* Botão Voltar (Transparente com borda branca) */
    .btn-back-custom {
        border: 2px solid #fff !important;
        color: #fff !important;
        border-radius: 50px !important;
        padding: 8px 25px !important;
        font-weight: bold !important;
        background-color: transparent !important;
        transition: all 0.3s ease !important;
        text-decoration: none !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
    }

    .btn-back-custom:hover {
        background-color: #fff !important;
        color: #0d6efd !important;
    }

    /* Botão IA (Roxo Sólido) */
    .btn-ia-custom {
        border: 2px solid #fff !important;
        color: #fff !important;
        border-radius: 50px !important;
        padding: 8px 25px !important;
        font-weight: bold !important;
        background-color: #6f42c1 !important;
        /* ROXO */
        transition: all 0.3s ease !important;
        text-decoration: none !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
        margin-left: 10px !important;
    }

    .btn-ia-custom:hover {
        background-color: #fff !important;
        color: #6f42c1 !important;
    }

    /* --- RESPONSIVIDADE MOBILE --- */
    @media (max-width: 767px) {

        /* Tabelas com scroll */
        .table-responsive-custom {
            display: block;
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border: 1px solid #ddd;
            /* Borda visual para indicar limite */
        }

        /* Força largura mínima para evitar quebra de valores */
        .table-clean {
            min-width: 800px !important;
        }

        /* Detalhes empilhados */
        .detail-row .span6,
        .detail-row .span3 {
            display: block;
            width: 100% !important;
            margin-left: 0 !important;
            margin-bottom: 20px;
        }

        /* Alinhamento de texto em mobile */
        .detail-label,
        .detail-value {
            text-align: left !important;
        }

        /* Botões empilhados */
        .form-actions {
            text-align: center !important;
        }

        .form-actions a,
        .form-actions button {
            display: block;
            width: 100%;
            margin: 0 0 10px 0 !important;
            box-sizing: border-box;
        }

        /* NOVO LAYOUT DO HEADER (CÓPIA FIEL DE EDITAR_ORCAMENTO) */
        .widget-title.card-header-blue {
            background-color: #0d6efd !important;
            border-bottom: 1px solid #0d6efd !important;
            border-top-left-radius: 8px !important;
            border-top-right-radius: 8px !important;
            padding: 10px 20px !important;
            /* Padding reduzido */
            min-height: 40px !important;
            height: auto !important;
            color: #fff !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
        }

        /* REGRAS REMOVIDAS DAQUI PORQUE ESTAVAM DENTRO DO MOBILE */
        /* Serão recolocadas no lugar certo */

        /* MEDIA QUERY MOBILE (Apenas Layout) */
        @media (max-width: 767px) {
            .widget-title.card-header-blue {
                flex-direction: column !important;
                align-items: flex-start !important;
                height: auto !important;
            }

            .widget-title.card-header-blue>div {
                width: 100%;
                margin-bottom: 10px;
            }

            .buttons {
                width: 100%;
            }

            /* Força layout mobile sem alterar cores */
            .btn-action-mobile-uniform {
                width: 100% !important;
                display: flex !important;
                justify-content: center !important;
                box-sizing: border-box !important;
                margin-left: 0 !important;
                margin-bottom: 10px !important;
                height: auto !important;
            }
        }

        /* ========================================= */
        /* 🎨 CORREÇÃO DE VISIBILIDADE EM TEMAS ESCUROS */
        <?php
        if ($isDark):
            ?>
            .widget-box.card-like {
                background-color: #ffffff !important;
                color: #333333 !important;
            }

            .widget-box.card-like .widget-content {
                background-color: #ffffff !important;
                color: #333333 !important;
            }

            .table-clean {
                background-color: #ffffff !important;
                color: #333333 !important;
            }

            .table th {
                background-color: #f8f9fc !important;
                color: #5a5c69 !important;
                border-bottom: 2px solid #e3e6f0 !important;
            }

            /* Previne que o header fique preto no hover */
            .table thead th:hover {
                background-color: #f8f9fc !important;
                color: #5a5c69 !important;
            }

            .table-clean td {
                background-color: #ffffff !important;
                color: #333333 !important;
            }

            /* Sincronização de Hover (Ajuste Manual do Usuário) */
            .table-hover tbody tr:hover td,
            .table-hover tbody tr:hover th {
                background-color: #eeeeeeff !important;
                color: #060404ff !important;
            }

            .table-hover tbody tr:hover a,
            .table-hover tbody tr:hover i,
            .table-hover tbody tr:hover span {
                color: #eeeeeeff !important;
            }

        <?php endif; ?>
        /* ========================================= */
</style>

<div class="row-fluid" style="margin-top:0">
    <div class="span12">
        <div class="widget-box card-like">

            <!-- CABEÇALHO AZUL (MODELO COPIADO DE EDITAR) -->
            <div class="widget-title card-header-blue">
                <div style="display: flex; align-items: center;">
                    <h5>Detalhes do Orçamento #<?= $orcamento['id'] ?></h5>
                </div>
                <div class="buttons" style="margin:0;">
                    <!-- Botão Voltar -->
                    <a href="listar_orcamentos.php" id="btn-voltar-detalhes"
                        class="btn-back-custom btn-action-mobile-uniform"
                        onmouseover="this.querySelector('i').className='icon-arrow-left';"
                        onmouseout="this.querySelector('i').className='icon-arrow-left icon-white';">
                        <i class="icon-arrow-left icon-white"></i> Voltar
                    </a>

                    <!-- Botão IA -->
                    <?php if (defined('IA_ENABLED') && IA_ENABLED): ?>
                        <a href="editar_orcamento_ia.php?id=<?= $orcamento['id'] ?>"
                            class="btn-ia-custom btn-action-mobile-uniform">
                            ✨ Editar com IA
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="widget-content card-body-like">

                <!-- DADOS DO CLIENTE E ORÇAMENTO -->
                <div class="row-fluid detail-row">
                    <div class="span6">
                        <h4><i class="icon-user"></i> Cliente</h4>
                        <div class="detail-label">Nome:</div>
                        <div class="detail-value"><?= htmlspecialchars($orcamento['cliente_nome']) ?></div>

                        <div class="detail-label" style="margin-top:5px;">Telefone:</div>
                        <div class="detail-value"><?= formatarTelefone(htmlspecialchars($orcamento['telefone'])) ?>
                        </div>
                    </div>

                    <div class="span6">
                        <h4><i class="icon-map-marker"></i> Endereço</h4>
                        <div class="detail-value">
                            <?= htmlspecialchars($orcamento['rua']) ?>,
                            <?= htmlspecialchars($orcamento['numero']) ?><br>
                            <?= htmlspecialchars($orcamento['bairro']) ?><br>
                            <?= htmlspecialchars($orcamento['cidade']) ?> -
                            <?= htmlspecialchars($orcamento['estado']) ?>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- INFO STATUS -->
                <div class="row-fluid detail-row">
                    <div class="span2">
                        <div class="detail-label">Status</div>
                        <div class="detail-value"><?= getStatusBadge($orcamento['status']) ?></div>
                    </div>
                    <div class="span2">
                        <div class="detail-label">Data Criação</div>
                        <div class="detail-value"><?= date('d/m/Y', strtotime($orcamento['data_criacao'])) ?></div>
                    </div>
                    <div class="span2">
                        <div class="detail-label">Validade</div>
                        <div class="detail-value"><?= htmlspecialchars($orcamento['validade_dias']) ?> dias</div>
                    </div>
                    <div class="span6">
                        <div class="detail-label" style="text-align:right; font-size: 20px !important;">Valor Total
                        </div>
                        <div class="detail-value"
                            style="color:#198754 !important; font-weight:bold; font-size:20px !important; text-align:right;">
                            <?= formatarMoeda($orcamento['valor_total']) ?>
                        </div>
                    </div>
                </div>

                <!-- TABELA DE ITENS -->
                <h4 style="margin-top:30px; margin-bottom:15px;"><i class="icon-list"></i> Itens do Orçamento</h4>

                <?php $count = 1; ?>

                <div class="table-responsive-custom">
                    <table class="table-clean table-hover">
                        <thead>
                            <!-- Título da Primeira Seção (Produtos) no Topo do Cabeçalho -->
                            <?php if (!empty($produtos)): ?>
                                <tr class="bg-light-blue" style="border-bottom: 2px solid #ddd;">
                                    <td colspan="7"
                                        style="text-align: left; padding: 10px; font-size: 1.1em; font-weight: bold;">
                                        <i class="icon-th-large"></i> 1. PRODUTOS (MATERIAL)
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <tr>
                                <th style="width: 25px; text-align: center;">#</th>
                                <th>Descrição</th>
                                <th style="width:100px; text-align:center;">Unid.</th>
                                <th style="width:100px; text-align:center;">Qtd.</th>
                                <th style="width:120px; text-align:right;">Preço Unit.</th>
                                <th style="width:90px; text-align:center;">Taxa (%)</th>
                                <th style="width:120px; text-align:right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($produtos) && empty($servicos) && empty($manuais)): ?>
                                <tr>
                                    <td colspan="7" style="text-align:center;">Nenhum item encontrado.</td>
                                </tr>
                            <?php endif; ?>

                            <!-- PRODUTOS -->
                            <?php if (!empty($produtos)): ?>
                                <?php foreach ($produtos as $item): ?>
                                    <tr style="font-size: 1.1em;">
                                        <td style="text-align: center;"><?= $count++ ?></td>
                                        <td><?= htmlspecialchars($item['descricao']) ?></td>
                                        <td style="text-align:center;"><?= strtoupper($item['unidade']) ?></td>
                                        <td style="text-align:center;"><?= number_format($item['quantidade'], 2, ',', '.') ?>
                                        </td>
                                        <td style="text-align:right;"><?= formatarMoeda($item['preco_unitario']) ?></td>
                                        <td style="text-align:center;"><?= number_format($item['taxa'] ?? 0, 2, ',', '.') ?>%
                                        </td>
                                        <td style="text-align:right;"><strong><?= formatarMoeda($item['subtotal']) ?></strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr style="background-color: #f8f9fc;">
                                    <?php if ($has_tax_produtos): ?>
                                        <td colspan="4" style="text-align:right; border-bottom:none;"><strong
                                                style="position: relative; left: 80px;">SUBTOTAL PRODUTOS:</strong></td>
                                        <td colspan="3" style="text-align:right; border-bottom:none;">
                                            <span style="margin-right: 25px; white-space: nowrap; color:#858799;"><small>(Sem
                                                    Taxa)</small>
                                                <strong><?= formatarMoeda($total_sem_taxa_produtos) ?></strong></span>
                                            <span style="white-space: nowrap;"><small>(c/ Taxa)</small>
                                                <strong><?= formatarMoeda($subtotal_produtos) ?></strong></span>
                                        </td>
                                    <?php else: ?>
                                        <td colspan="6" style="text-align:right; border-bottom:none;"><strong>SUBTOTAL
                                                PRODUTOS:</strong></td>
                                        <td style="text-align:right; border-bottom:none;">
                                            <strong><?= formatarMoeda($subtotal_produtos) ?></strong>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endif; ?>

                            <!-- SERVIÇOS -->
                            <?php if (!empty($servicos)): ?>
                                <tr class="bg-light-blue" style="border-top: 15px solid #fff;"> <!-- Espaçamento visual -->
                                    <td colspan="7"
                                        style="text-align: left; padding: 10px; font-size: 1.1em; font-weight: bold; border-bottom: 2px solid #ddd;">
                                        <i class="icon-wrench"></i> 2. SERVIÇOS (MÃO DE OBRA)
                                    </td>
                                </tr>
                                <!-- Repetir cabeçalho para Serviços? Opcional. Vamos deixar sem para manter fluidez no mobile, ou adicionar se o usuário pedir. -->
                                <?php $count = 1; ?>
                                <?php foreach ($servicos as $item): ?>
                                    <tr style="font-size: 1.1em;">
                                        <td style="text-align: center;"><?= $count++ ?></td>
                                        <td><?= htmlspecialchars($item['descricao']) ?></td>
                                        <td style="text-align:center;"><?= strtoupper($item['unidade']) ?></td>
                                        <td style="text-align:center;"><?= number_format($item['quantidade'], 2, ',', '.') ?>
                                        </td>
                                        <td style="text-align:right;"><?= formatarMoeda($item['preco_unitario']) ?></td>
                                        <td style="text-align:center;"><?= number_format($item['taxa'] ?? 0, 2, ',', '.') ?>%
                                        </td>
                                        <td style="text-align:right;"><strong><?= formatarMoeda($item['subtotal']) ?></strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr style="background-color: #f8f9fc;">
                                    <?php if ($has_tax_servicos): ?>
                                        <td colspan="4" style="text-align:right; border-bottom:none;"><strong
                                                style="position: relative; left: 80px;">SUBTOTAL SERVIÇOS:</strong></td>
                                        <td colspan="3" style="text-align:right; border-bottom:none;">
                                            <span style="margin-right: 25px; white-space: nowrap; color:#858799;"><small>(Sem
                                                    Taxa)</small>
                                                <strong><?= formatarMoeda($total_sem_taxa_servicos) ?></strong></span>
                                            <span style="white-space: nowrap;"><small>(c/ Taxa)</small>
                                                <strong><?= formatarMoeda($subtotal_servicos) ?></strong></span>
                                        </td>
                                    <?php else: ?>
                                        <td colspan="6" style="text-align:right; border-bottom:none;"><strong>SUBTOTAL
                                                SERVIÇOS:</strong></td>
                                        <td style="text-align:right; border-bottom:none;">
                                            <strong><?= formatarMoeda($subtotal_servicos) ?></strong>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endif; ?>

                            <!-- MANUAIS -->
                            <?php if (!empty($manuais)): ?>
                                <tr class="bg-light-blue" style="border-top: 15px solid #fff;">
                                    <td colspan="7"
                                        style="text-align: left; padding: 10px; font-size: 1.1em; font-weight: bold; border-bottom: 2px solid #ddd;">
                                        <i class="icon-edit"></i> 3. ITENS AVULSOS / MANUAIS
                                    </td>
                                </tr>
                                <?php $count = 1; ?>
                                <?php foreach ($manuais as $item): ?>
                                    <tr style="font-size: 1.1em;">
                                        <td style="text-align: center;"><?= $count++ ?></td>
                                        <td><?= htmlspecialchars($item['descricao']) ?></td>
                                        <td style="text-align:center;"><?= strtoupper($item['unidade']) ?></td>
                                        <td style="text-align:center;"><?= number_format($item['quantidade'], 2, ',', '.') ?>
                                        </td>
                                        <td style="text-align:right;"><?= formatarMoeda($item['preco_unitario']) ?></td>
                                        <td style="text-align:center;"><?= number_format($item['taxa'] ?? 0, 2, ',', '.') ?>%
                                        </td>
                                        <td style="text-align:right;"><strong><?= formatarMoeda($item['subtotal']) ?></strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr style="background-color: #f8f9fc;">
                                    <?php if ($has_tax_manuais): ?>
                                        <td colspan="4" style="text-align:right; border-bottom:none;"><strong
                                                style="position: relative; left: 80px;">SUBTOTAL AVULSOS:</strong></td>
                                        <td colspan="3" style="text-align:right; border-bottom:none;">
                                            <span style="margin-right: 25px; white-space: nowrap; color:#858796;"><small>(Sem
                                                    Taxa)</small>
                                                <strong><?= formatarMoeda($total_sem_taxa_manuais) ?></strong></span>
                                            <span style="white-space: nowrap;"><small>(c/ Taxa)</small>
                                                <strong><?= formatarMoeda($subtotal_manuais) ?></strong></span>
                                        </td>
                                    <?php else: ?>
                                        <td colspan="6" style="text-align:right; border-bottom:none;"><strong>SUBTOTAL
                                                AVULSOS:</strong></td>
                                        <td style="text-align:right; border-bottom:none;">
                                            <strong><?= formatarMoeda($subtotal_manuais) ?></strong>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endif; ?>

                            <!-- TOTAL GERAL (Mantido dentro da tabela para layout unificado) -->
                            <tr style="background-color:#f8f9fc; border-top: 2px solid #e3e6f0;">
                                <?php if ($possui_taxa): ?>
                                    <td colspan="4"
                                        style="text-align:right; padding: 20px 10px; font-size: 1.1em; border-bottom:none;">
                                        <strong style="position: relative; left: 80px;">VALOR TOTAL DO ORÇAMENTO:</strong>
                                    </td>
                                    <td colspan="3" style="text-align:right; padding: 20px 10px; border-bottom:none;">
                                        <span
                                            style="margin-right: 15px; white-space: nowrap; color:#858796; font-size: 1.1em;"><small>(Sem
                                                Taxa)</small>
                                            <strong><?= formatarMoeda($total_sem_taxa_acumulado) ?></strong></span>
                                        <span style="white-space: nowrap; font-size: 1.3em;"><small
                                                style="font-size:0.6em;">(c/ Taxa)</small>
                                            <strong><?= formatarMoeda($orcamento['valor_total']) ?></strong></span>
                                    </td>
                                <?php else: ?>
                                    <td colspan="6"
                                        style="text-align:right; padding: 20px 10px; font-size: 1.1em; border-bottom:none;">
                                        <strong>VALOR TOTAL DO ORÇAMENTO:</strong>
                                    </td>
                                    <td
                                        style="text-align:right; padding: 20px 10px; font-size: 1.4em; color: #198754; border-bottom:none;">
                                        <strong><?= formatarMoeda($orcamento['valor_total']) ?></strong>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- OBSERVAÇÕES -->
                <?php if (!empty($orcamento['observacoes'])): ?>
                    <div class="alert alert-info" style="margin-top:20px; border-radius: 8px;">
                        <strong>Observações:</strong><br>
                        <?= nl2br(htmlspecialchars($orcamento['observacoes'])) ?>
                    </div>
                <?php endif; ?>

                <!-- ANOTAÇÕES INTERNAS -->
                <?php if (!empty($orcamento['anotacoes_internas'])): ?>
                    <div class="alert"
                        style="margin-top:10px; border-radius: 8px; background-color: #fcf8e3; border: 1px solid #faebcc; color: #8a6d3b;">
                        <strong><i class="icon-comment"></i> Anotações Internas:</strong><br>
                        <?= nl2br(htmlspecialchars($orcamento['anotacoes_internas'])) ?>
                    </div>
                <?php endif; ?>

                <!-- BOTÕES DE AÇÃO (Estilo Modelo) -->
                <div class="form-actions"
                    style="background:none; border:none; padding: 0; margin-top:30px; text-align: right;">

                    <!-- Clonar -->
                    <a href="clonar_orcamento.php?id=<?= $orcamento['id'] ?>" id="btn-clonar" class="btn btn-purple"
                        style="margin-right: 5px;">
                        <i class="icon-file icon-white"></i> Clonar Orçamento
                    </a>

                    <!-- Editar -->
                    <a href="editar_orcamento.php?id=<?= $orcamento['id'] ?>" class="btn btn-blue"
                        style="margin-right: 5px;">
                        <i class="icon-pencil icon-white"></i> Editar Orçamento
                    </a>

                    <!-- Imprimir -->
                    <a href="imprimir_orcamento.php?id=<?= $orcamento['id'] ?>" target="_blank" class="btn btn-orange"
                        id="btn-imprimir" style="margin-right: 5px;">
                        <i class="icon-print icon-white"></i> Imprimir
                    </a>

                    <!-- PDF -->
                    <a href="gerar_pdf.php?id=<?= $orcamento['id'] ?>" class="btn btn-danger-custom" id="btn-pdf"
                        style="margin-right: 5px;">
                        <i class="icon-file icon-white"></i> Baixar PDF
                    </a>

                    <?php if (strtoupper($orcamento['status']) !== 'APROVADO'): ?>
                        <!-- APROVAR -->
                        <!-- APROVAR -->
                        <a href="aprovar_orcamento.php?id=<?= $orcamento['id'] ?>" id="btn-aprovar"
                            class="btn btn-success-custom">
                            <i class="icon-ok icon-white"></i> Aprovar
                        </a>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../tema/footer.php'; ?>

<!-- SWEETALERT2 PARA CONFIRMAÇÃO MODERNA -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
// DETECÇÃO DE TEMA "CAMALEÃO"
// Tenta usar configuração global, fallback para 'white'
$appTheme = $configuration['app_theme'] ?? 'white';
$isDarkTheme = in_array($appTheme, ['puredark', 'darkviolet', 'darkorange', 'black']);

if ($isDarkTheme):
    ?>
    <style>
        /* SweetAlert2 Dark/Chameleon Overrides */
        div:where(.swal2-container) div:where(.swal2-popup) {
            background-color: #2E343B !important;
            /* Fundo compatível com PureDark */
            color: #f0f0f0 !important;
            border: 1px solid #444 !important;
        }

        div:where(.swal2-container) .swal2-title {
            color: #ffffff !important;
        }

        div:where(.swal2-container) .swal2-html-container {
            color: #e0e0e0 !important;
        }

        div:where(.swal2-container) .swal2-close {
            color: #fff !important;
        }

        div:where(.swal2-container) .swal2-timer-progress-bar {
            background: rgba(255, 255, 255, 0.3) !important;
        }

        /* Estilização interna do HTML injetado no modal */
        .swal2-html-container small {
            color: #aaa !important;
            /* Texto de ajuda mais claro */
        }

        .swal2-html-container strong {
            color: #fff !important;
        }
    </style>
<?php endif; ?>

<style>
    /* Ajustes de Tamanho (Compacto) - APENAS para Modal de Orçamento */
    div:where(.swal2-container) .compact-swal-popup {
        width: 25em !important;
        padding: 1.5em !important;
    }

    div:where(.swal2-container) .compact-swal-popup .swal2-title {
        font-size: 1.3em !important;
        padding-top: 0.5em !important;
    }

    div:where(.swal2-container) .compact-swal-popup .swal2-icon {
        transform: scale(0.7) !important;
        margin-top: 0.5em !important;
        margin-bottom: 0.5em !important;
    }

    /* Removemos override de fonte interna para preservar animações complexas (ex: Success) */
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        // --- LÓGICA DO SELETOR DE RODAPÉ (IMPRESSÃO E PDF) ---
        // Valor total de produtos vindo do PHP
        const subtotalProdutos = <?= number_format($subtotal_produtos, 2, '.', '') ?>;

        function handleFooterSelection(e, btn) {
            e.preventDefault();
            const urlBase = btn.getAttribute('href');

            // Definição do pré-selecionado: Se produtos == 0, sugere "mao_de_obra"
            const preSelectMaoDeObra = (parseFloat(subtotalProdutos) <= 0.001);

            Swal.fire({
                title: 'Selecione o Tipo de Orçamento',
                customClass: {
                    popup: 'compact-swal-popup'
                },
                html: `
                    <div style="text-align: left; font-size: 14px; line-height: 1.6;">
                        <p style="margin-bottom: 15px; font-weight: 500;">Como deseja exibir as notas legais no documento?</p>
                        
                        <label style="display: flex; align-items: start; gap: 10px; cursor: pointer; margin-bottom: 12px; padding: 10px; border: 1px solid #ddd; border-radius: 8px; transition: background 0.2s;">
                            <input type="radio" name="rodape_tipo" value="padrao" 
                                ${!preSelectMaoDeObra ? 'checked' : ''} 
                                style="margin-top: 5px;">
                            <div>
                                <strong style="font-size: 15px;">📦 Padrão (Material Incluso)</strong><br>
                                <small style="color: #666;">Para orçamentos com venda de materiais e serviços.</small>
                            </div>
                        </label>

                        <label style="display: flex; align-items: start; gap: 10px; cursor: pointer; padding: 10px; border: 1px solid #ddd; border-radius: 8px; transition: background 0.2s;">
                            <input type="radio" name="rodape_tipo" value="mao_de_obra" 
                                ${preSelectMaoDeObra ? 'checked' : ''} 
                                style="margin-top: 5px;">
                            <div>
                                <strong style="font-size: 15px;">🛠️ Apenas Mão de Obra</strong><br>
                                <small style="color: #666;">Para serviços ou quando o cliente compra o material.</small>
                            </div>
                        </label>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '🖨️ Gerar Documento',
                cancelButtonText: 'Cancelar',
                focusConfirm: false,
                preConfirm: () => {
                    return document.querySelector('input[name="rodape_tipo"]:checked').value;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const tipoEscolhido = result.value;
                    // Redireciona com o parâmetro
                    const separador = urlBase.includes('?') ? '&' : '?';
                    window.open(urlBase + separador + 'rodape_tipo=' + tipoEscolhido, btn.target || '_self');
                }
            });
        }

        const btnImprimir = document.getElementById('btn-imprimir');
        if (btnImprimir) {
            btnImprimir.addEventListener('click', function (e) {
                handleFooterSelection(e, this);
            });
        }

        const btnPdf = document.getElementById('btn-pdf');
        if (btnPdf) {
            btnPdf.addEventListener('click', function (e) {
                handleFooterSelection(e, this);
            });
        }

        // --- LÓGICA DO BOTÃO CLONAR ---
        const btnClonar = document.getElementById('btn-clonar');
        if (btnClonar) {
            btnClonar.addEventListener('click', function (e) {
                e.preventDefault();
                const urlDestino = this.getAttribute('href');

                Swal.fire({
                    title: 'Clonar Orçamento?',
                    customClass: {
                        popup: 'compact-swal-popup'
                    },
                    text: "Você criará uma cópia exata deste orçamento como Rascunho.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#6f42c1',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sim, clonar!',
                    cancelButtonText: 'Cancelar',
                    background: '#fff',
                    borderRadius: '10px'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = urlDestino;
                    }
                });
            });
        }

        // --- LÓGICA DO BOTÃO APROVAR ---
        const btnAprovar = document.getElementById('btn-aprovar');
        if (btnAprovar) {
            btnAprovar.addEventListener('click', function (e) {
                e.preventDefault();
                const urlDestino = this.getAttribute('href');

                Swal.fire({
                    title: 'Aprovar Orçamento?',
                    customClass: {
                        popup: 'compact-swal-popup'
                    },
                    text: "O status será alterado para Aprovado.",
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sim, aprovar!',
                    cancelButtonText: 'Cancelar',
                    background: '#fff',
                    borderRadius: '10px'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = urlDestino;
                    }
                });
            });
        }
    });
</script>