<?php
// listar_orcamentos.php - MATRIX ADMIN LAYOUT

// ✅ CAMINHO CORRIGIDO: Busca 'conexao.php' um nível acima
require '../conexao.php';

// --- FUNÇÕES DE SUPORTE ---

function getStatusBadge($status)
{
    $statusUpper = function_exists('mb_strtoupper') ? mb_strtoupper($status, 'UTF-8') : strtoupper($status);

    switch ($statusUpper) {
        case 'APROVADO':
            return '<span class="label label-success">Aprovado</span>';

        case 'EMITIDO':
            return '<span class="label label-primary">Emitido</span>'; // Azul Escuro

        case 'AGUARDANDO APROVAÇÃO':
        case 'AGUARDANDO APROVACAO':
            return '<span class="label label-info">Aguardando Aprovação</span>'; // Azul Claro

        case 'RASCUNHO':
            return '<span class="label label-warning">Rascunho</span>';

        case 'EM REVISÃO':
        case 'EM REVISAO':
            return '<span class="label label-inverse">Em Revisão</span>'; // Escuro/Cinza

        case 'REJEITADO':
            return '<span class="label label-important">Rejeitado</span>';

        case 'CANCELADO':
            return '<span class="label label-important">Cancelado</span>';

        default:
            return '<span class="label">' . htmlspecialchars($status) . '</span>';
    }
}

function formatarMoeda($valor)
{
    $valor = (float) $valor;
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

// --- FILTROS E ORDENAÇÃO ---
$search = filter_input(INPUT_GET, 'q', FILTER_DEFAULT);
$dataInicial = filter_input(INPUT_GET, 'data_inicial', FILTER_DEFAULT);
$dataFinal = filter_input(INPUT_GET, 'data_final', FILTER_DEFAULT);
$statusFiltro = filter_input(INPUT_GET, 'status_filtro', FILTER_DEFAULT);

$sortCols = ['id', 'cliente_nome', 'data_criacao', 'valor_total', 'status'];
$sort = filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_SPECIAL_CHARS);
$dir = filter_input(INPUT_GET, 'dir', FILTER_SANITIZE_SPECIAL_CHARS);

// Defaults
if (!in_array($sort, $sortCols))
    $sort = 'id';
if (strtoupper((string) $dir) !== 'ASC')
    $dir = 'DESC';

function getOrcamentos(PDO $pdo, $search, $sort, $dir, $dataInicial, $dataFinal, $statusFiltro)
{
    $sql = "
        SELECT 
            o.id, 
            c.nomeCliente AS cliente_nome, 
            o.data_criacao, 
            o.valor_total, 
            o.status
        FROM 
            mod_orc_orcamentos o
        JOIN 
            clientes c ON o.cliente_id = c.idClientes
        WHERE 1=1
    ";

    $params = [];

    // Busca Textual (ID ou Nome)
    if (!empty($search)) {
        if (is_numeric($search)) {
            $sql .= " AND o.id = :search_id ";
            $params[':search_id'] = $search;
        } else {
            $sql .= " AND c.nomeCliente LIKE :search_name ";
            $params[':search_name'] = "%$search%";
        }
    }

    // Filtro de Data
    if (!empty($dataInicial)) {
        $sql .= " AND o.data_criacao >= :data_inicial ";
        $params[':data_inicial'] = $dataInicial;
    }
    if (!empty($dataFinal)) {
        $sql .= " AND o.data_criacao <= :data_final ";
        // Ajuste para pegar até o final do dia se for datetime, mas aqui parece ser DATE ou o usuário manda YYYY-MM-DD
        $params[':data_final'] = $dataFinal;
    }

    // Filtro de Status
    if (!empty($statusFiltro)) {
        $sql .= " AND o.status = :status_filtro ";
        $params[':status_filtro'] = $statusFiltro;
    }

    // Ordenação
    $sql .= " ORDER BY $sort $dir ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$orcamentos = getOrcamentos($pdo, $search, $sort, $dir, $dataInicial, $dataFinal, $statusFiltro);

// Helper para gerar links de ordenação mantendo todos os filtros
function getSortLink($col, $label, $currentSort, $currentDir, $search, $dataInicial, $dataFinal, $statusFiltro)
{
    $newDir = ($currentSort === $col && $currentDir === 'ASC') ? 'DESC' : 'ASC';
    $arrow = '';
    if ($currentSort === $col) {
        $arrow = ($currentDir === 'ASC') ? ' <i class="icon-arrow-up"></i>' : ' <i class="icon-arrow-down"></i>';
    }

    $query = http_build_query([
        'q' => $search,
        'data_inicial' => $dataInicial,
        'data_final' => $dataFinal,
        'status_filtro' => $statusFiltro,
        'sort' => $col,
        'dir' => $newDir
    ]);

    return "<a href='?$query' style='color: #5a5c69; text-decoration: none; font-weight: 700;'>$label $arrow</a>";
}

// Define a página atual para o breadcrumb do header.php
$pagina_atual = 'Orçamentos';

// INCLUINDO O TOPO (HEADER) COM O CAMINHO CORRIGIDO
include '../tema/header.php';
?>

<style>
    /* ========================================= */
    /* 🎨 CORREÇÃO DE VISIBILIDADE EM TEMAS ESCUROS */
    <?php
    if ($isDark):
        ?>
        /* Força a tabela e widget a manterem fundo claro e texto escuro para legibilidade */
        .widget-box.card-like {
            background-color: #ffffff !important;
            color: #333333 !important;
        }

        .widget-box.card-like .widget-content {
            background-color: #ffffff !important;
            color: #333333 !important;
        }

        /* Tabela */
        .table.table-striped,
        .table.table-bordered {
            background-color: #ffffff !important;
            color: #333333 !important;
        }

        .table th {
            background-color: #f8f9fc !important;
            color: #5a5c69 !important;
            border-bottom: 2px solid #e3e6f0 !important;
        }

        /* Previne que o header fique preto no hover (comum em Datatables/Matrix Admin) */
        .table thead th:hover {
            background-color: #f8f9fc !important;
            color: #5a5c69 !important;
        }

        .table td {
            background-color: #ffffff !important;
            color: #333333 !important;
            border-top: 1px solid #eeeeee !important;
        }

        /* Hover - Evita texto branco em fundo branco ou hover preto */
        .table-striped tbody>tr:nth-child(odd)>td,
        .table-striped tbody>tr:nth-child(odd)>th {
            background-color: #f9f9f9 !important;
        }

        .table-hover tbody tr:hover>td,
        .table-hover tbody tr:hover>th {
            background-color: #eeeeeeff !important;
            color: #060404ff !important;
        }

        /* Garante que links e ícones dentro do hover fiquem ocultos/cinzas conforme manual */
        .table-hover tbody tr:hover a,
        .table-hover tbody tr:hover i,
        .table-hover tbody tr:hover span {
            color: #eeeeeeff !important;
        }

        /* Paginação e outros textos */
        .dataTables_info,
        .dataTables_paginate {
            color: #333333 !important;
        }

    <?php endif; ?>
    /* ========================================= */

    #content {
        background: none !important;
        /* Fundo removido para manter o padrão do tema (escuro) no topo */
        padding-bottom: 0 !important;
        /* Zerado */
        /* Ajuste de altura mínima para não empurrar rodapé desnecessariamente, mas manter preenchido */
        min-height: auto !important;
        padding-top: 0 !important;
    }

    /* Container Card */
    .widget-box.card-like {
        background: #fff !important;
        border: 1px solid #e3e6f0 !important;
        border-radius: 8px !important;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
        margin-top: 20px !important;
        margin-bottom: 5px !important;
        /* Mínimo para não colar no footer */
        overflow: hidden;
        /* Evita que quinas da tabela vazem */
    }

    /* Scroll Horizontal Apenas na Tabela se necessário */
    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    /* Header Azul - Layout Flexível para Mobile/Desktop */
    .widget-title.card-header-blue {
        background-color: #0d6efd !important;
        border-bottom: 1px solid #0d6efd !important;
        border-top-left-radius: 8px !important;
        border-top-right-radius: 8px !important;
        padding: 15px 20px !important;
        min-height: 40px !important;
        /* Altura mínima garantida */
        height: auto !important;
        color: #fff !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        flex-wrap: wrap;
        /* Permite quebra em telas pequenas */
        gap: 15px;
    }

    .widget-title.card-header-blue .caption {
        display: flex;
        align-items: center;
    }

    .widget-title.card-header-blue h5 {
        color: #fff !important;
        margin: 0;
        font-size: 1.1rem !important;
        font-weight: 500 !important;
    }

    /* Lado Direito: Ações (Busca + Botão) */
    .widget-title.card-header-blue .actions {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* Container de Busca no Header (Sem deformação) */
    .header-search-form {
        margin: 0 !important;
        display: flex;
        align-items: center;
    }

    .header-search-input {
        border-radius: 20px 0 0 20px !important;
        border: 1px solid rgba(255, 255, 255, 0.4) !important;
        background-color: rgba(255, 255, 255, 0.15) !important;
        color: #fff !important;
        height: 34px !important;
        /* Altura fixa */
        padding: 4px 15px !important;
        width: 200px !important;
        box-shadow: none !important;
        transition: all 0.2s;
        margin-bottom: 0 !important;
    }

    .header-search-input:focus {
        background-color: rgba(255, 255, 255, 0.25) !important;
        border-color: rgba(255, 255, 255, 0.8) !important;
        outline: none;
    }

    .header-search-input::placeholder {
        color: rgba(255, 255, 255, 0.7) !important;
    }

    .header-search-btn {
        border-radius: 0 20px 20px 0 !important;
        border: 1px solid rgba(255, 255, 255, 0.4) !important;
        border-left: none !important;
        height: 35px !important;
        /* Ajustado para 35px para igualar ao input */
        /* Mesma altura do input */
        padding: 0 15px !important;
        background-color: rgba(255, 255, 255, 0.25) !important;
        color: #fff !important;
        line-height: 35px !important;
        /* Ajustado para 35px */
        /* Centraliza ícone */
        margin-bottom: 0 !important;
    }

    .header-search-btn:hover {
        background-color: rgba(255, 255, 255, 0.4) !important;
    }


    /* Botão Novo Outline White */
    .btn-outline-white-rounded {
        border: 1px solid #fff !important;
        color: #fff !important;
        border-radius: 50px !important;
        padding: 5px 15px;
        background: transparent;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        height: 34px !important;
        /* Alinha com a busca */
        box-sizing: border-box;
    }

    .btn-outline-white-rounded:hover {
        background: #fff !important;
        color: #0d6efd !important;
    }

    /* Table Styles */
    .table thead th {
        background-color: #f8f9fc !important;
        color: #5a5c69 !important;
        border-bottom: 2px solid #e3e6f0 !important;
        padding: 12px !important;
        font-size: 0.85rem;
    }

    .table td {
        vertical-align: middle !important;
        padding: 12px !important;
        color: #5a5c69;
    }

    /* Badges Pill */
    .label {
        border-radius: 50px !important;
        padding: 4px 10px !important;
    }

    /* Actions */
    /* Actions Buttons - Modern & Colorful */
    .btn-action-view,
    .btn-action-edit {
        border-radius: 4px;
        padding: 5px 10px;
        color: #fff !important;
        border: none;
        transition: all 0.2s;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    /* Ver Detalhes - Teal / Info Moderno */
    .btn-action-view {
        background-color: #17a2b8;
    }

    .btn-action-view:hover {
        background-color: #138496;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    /* Editar - Vibrant Blue */
    .btn-action-edit {
        background-color: #0d6efd;
    }

    .btn-action-edit:hover {
        background-color: #0b5ed7;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    /* Icons inside buttons */
    .btn-action-view i,
    .btn-action-edit i {
        color: #fff !important;
        margin: 0;
    }

    /* --- MOBILE RESPONSIVE --- */
    @media (max-width: 767px) {

        /* Header vira coluna */
        .widget-title.card-header-blue {
            flex-direction: column;
            align-items: flex-start !important;
            padding: 15px !important;
        }

        /* Ações (Busca + Botão) ocupam largura total e empilham */
        .widget-title.card-header-blue .actions {
            width: 100%;
            flex-direction: column;
            gap: 10px;
            margin-top: 10px;
        }

        /* Formulário de Busca 100% */
        .header-search-form {
            width: 100%;
            display: flex;
        }

        /* Input de Busca Flex */
        .header-search-input {
            width: 100% !important;
            flex: 1;
            /* Ocupa espaço restante */
        }

        /* Botão Novo 100% */
        .btn-outline-white-rounded {
            width: 100%;
            justify-content: center;
        }

        /* Ajuste de Tabela */
        .table-responsive {
            border: 1px solid #e3e6f0;
            border-radius: 4px;
        }

        .table.data-table {
            min-width: 1000px !important;
            /* Aumentado para garantir espaço para todas as colunas */
        }

        .table th {
            white-space: nowrap;
            /* Evita quebra de titulos */
        }
    }
</style>

<div class="row-fluid" style="margin-bottom: 0;">
    <div class="span12">
        <div class="widget-box card-like">

            <!-- HEADER COM BUSCA INTEGRADA -->
            <div class="widget-title card-header-blue">
                <div class="caption">
                    <span class="icon" style="margin-right:10px;"><i class="icon-th icon-white"></i></span>
                    <h5>Listagem de Orçamentos</h5>
                </div>

                <div class="actions">
                    <!-- BUSCA (Dentro do Header) -->
                    <form method="GET" action="" class="header-search-form" autocomplete="off">
                        <!-- Mantém ordenação se existir -->
                        <?php if ($sort != 'id'): ?><input type="hidden" name="sort"
                                value="<?= $sort ?>"><?php endif; ?>
                        <?php if ($dir != 'DESC'): ?><input type="hidden" name="dir" value="<?= $dir ?>"><?php endif; ?>

                        <input type="text" name="q" class="header-search-input" placeholder="Buscar ID ou Cliente..."
                            value="<?= htmlspecialchars((string) $search) ?>">
                        <button type="submit" class="btn header-search-btn"><i class="icon-search"></i></button>
                    </form>

                    <!-- BOTÃO NOVO -->
                    <a href="novo_orcamento.php" class="btn-outline-white-rounded">
                        <i class="icon-plus"></i> Novo orçamento
                    </a>
                </div>
            </div>

            <!-- TABELA -->
            <div class="widget-content nopadding table-responsive">
                <table class="table table-bordered table-striped data-table table-hover"
                    style="margin-bottom: 0 !important; border-bottom: none !important;">
                    <thead>
                        <tr>
                            <th style="width: 60px;">
                                <?= getSortLink('id', '#ID', $sort, $dir, $search, $dataInicial, $dataFinal, $statusFiltro) ?>
                            </th>
                            <th>
                                <?= getSortLink('cliente_nome', 'Cliente', $sort, $dir, $search, $dataInicial, $dataFinal, $statusFiltro) ?>
                            </th>
                            <th style="width: 110px;">
                                <?= getSortLink('data_criacao', 'Data', $sort, $dir, $search, $dataInicial, $dataFinal, $statusFiltro) ?>
                            </th>
                            <th style="width: 130px;">
                                <?= getSortLink('valor_total', 'Total', $sort, $dir, $search, $dataInicial, $dataFinal, $statusFiltro) ?>
                            </th>
                            <th style="width: 120px;">
                                <?= getSortLink('status', 'Status', $sort, $dir, $search, $dataInicial, $dataFinal, $statusFiltro) ?>
                            </th>
                            <th style="width: 100px;">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($orcamentos) > 0): ?>
                            <?php foreach ($orcamentos as $orcamento): ?>
                                <tr>
                                    <td style="text-align:center; font-weight:bold;">
                                        <?php echo htmlspecialchars($orcamento['id']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($orcamento['cliente_nome']); ?></td>
                                    <td style="text-align:center">
                                        <?php echo (new DateTime($orcamento['data_criacao']))->format('d/m/Y'); ?>
                                    </td>
                                    <td style="text-align:right; font-weight:bold; color:#198754;">
                                        <?php echo formatarMoeda($orcamento['valor_total']); ?>
                                    </td>
                                    <td style="text-align:center">
                                        <?php echo getStatusBadge($orcamento['status']); ?>
                                    </td>
                                    <td style="text-align:center">
                                        <a href="ver_detalhes.php?id=<?php echo $orcamento['id']; ?>"
                                            class="btn btn-action-view tip-top" title="Ver Detalhes">
                                            <i class="icon-eye-open icon-white"></i>
                                        </a>
                                        <a href="editar_orcamento.php?id=<?php echo $orcamento['id']; ?>"
                                            class="btn btn-action-edit tip-top" title="Editar Orçamento">
                                            <i class="icon-pencil icon-white"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center; padding: 30px; color: #858796;">
                                    <i class="icon-inbox" style="font-size: 2em; display:block; margin-bottom:10px;"></i>
                                    Nenhum orçamento encontrado.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div> <!-- /.table-responsive -->
        </div>
    </div>
</div>

<?php include '../tema/footer.php'; ?>