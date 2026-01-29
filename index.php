<?php
// index.php - Painel de Controle Principal (Dashboard)
require 'conexao.php';

// Define a página atual para o breadcrumb do header.php
$pagina_atual = 'Dashboard';

// Include correto para a raiz (pasta 'tema' está no mesmo nível)
include 'tema/header.php';

// --- 1. FUNÇÕES DE SUPORTE (KPIs) ---

function contarTabela(PDO $pdo, string $tabela): int
{
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM {$tabela}");
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function contarOrcamentosPorStatus(PDO $pdo): array
{
    // Status enum da tabela mod_orc_orcamentos
    $statuses = ['Rascunho', 'Em Revisão', 'Emitido', 'Aguardando Aprovação', 'Aprovado', 'Rejeitado', 'Cancelado'];
    $counts = [];

    foreach ($statuses as $status) {
        try {
            $sql = "SELECT COUNT(*) FROM mod_orc_orcamentos WHERE status = :status";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['status' => $status]);
            $counts[$status] = $stmt->fetchColumn();
        } catch (PDOException $e) {
            $counts[$status] = 0;
        }
    }
    return $counts;
}

function listarOrcamentosAtivos(PDO $pdo): array
{
    // Exclui Cancelados e Aprovados (histórico) para focar no que precisa de ação
    $ativo_statuses = ['Rascunho', 'Em Revisão', 'Emitido', 'Aguardando Aprovação'];
    $status_list = "'" . implode("','", $ativo_statuses) . "'";

    try {
        $sql = "SELECT 
                    o.id, 
                    o.status, 
                    DATE_FORMAT(o.data_criacao, '%d/%m/%Y') AS data_formatada,
                    o.valor_total,
                    c.nomeCliente AS nome_cliente 
                FROM mod_orc_orcamentos o
                JOIN clientes c ON o.cliente_id = c.idClientes
                WHERE 
                    o.status IN ({$status_list})
                    AND o.data_criacao >= DATE_SUB(NOW(), INTERVAL 2 MONTH)
                ORDER BY o.data_criacao DESC
                LIMIT 10";

        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        return [];
    }
}

function contarOrcamentosUltimosMeses(PDO $pdo): array
{
    try {
        $sql = "SELECT 
                    DATE_FORMAT(data_criacao, '%Y-%m') as mes_ordem,
                    DATE_FORMAT(data_criacao, '%m/%Y') as mes_exibicao, 
                    COUNT(*) as total 
                FROM mod_orc_orcamentos 
                WHERE data_criacao >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY mes_ordem, mes_exibicao
                ORDER BY mes_ordem ASC";

        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }

}

function listarOrcamentosVencidos(PDO $pdo): array
{
    // Status que consideramos "em aberto" e passíveis de vencimento
    $status_abertos = ['Rascunho', 'Em Revisão', 'Emitido', 'Aguardando Aprovação', 'Negociação', 'Aberto'];
    $status_list = "'" . implode("','", $status_abertos) . "'";

    try {
        // Busca orçamentos onde a data atual é maior que data_criacao + validade_dias
        $sql = "SELECT 
                    o.id, 
                    o.status, 
                    DATE_FORMAT(o.data_criacao, '%d/%m/%Y') AS data_criacao_fmt,
                    o.validade_dias,
                    DATEDIFF(NOW(), o.data_criacao) as dias_decorridos,
                    o.valor_total,
                    c.nomeCliente AS nome_cliente 
                FROM mod_orc_orcamentos o
                JOIN clientes c ON o.cliente_id = c.idClientes
                WHERE 
                    o.status IN ({$status_list})
                    AND DATEDIFF(NOW(), o.data_criacao) > o.validade_dias
                ORDER BY o.data_criacao ASC
                LIMIT 5"; // Limita para não poluir o topo

        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}


// --- 2. COLETA DE DADOS ---
$total_produtos = contarTabela($pdo, 'produtos');
$total_servicos = contarTabela($pdo, 'servicos');
$total_clientes = contarTabela($pdo, 'clientes');
$total_orcamentos = contarTabela($pdo, 'mod_orc_orcamentos');

$contagem_status = contarOrcamentosPorStatus($pdo);
$orcamentos_ativos = listarOrcamentosAtivos($pdo);
$orcamentos_vencidos = listarOrcamentosVencidos($pdo);
$dados_evolucao = contarOrcamentosUltimosMeses($pdo);

// Mapeamento de cores para badges e gráficos
$status_colors = [
    'Rascunho' => '#ffc107',              // Amarelo (Warning)
    'Em Revisão' => '#333333',            // Preto/Cinza Escuro (Inverse)
    'Emitido' => '#0d6efd',               // Azul (Primary)
    'Aguardando Aprovação' => '#0dcaf0',  // Azul Claro (Info)
    'Aprovado' => '#198754',              // Verde (Success)
    'Rejeitado' => '#dc3545',             // Vermelho (Important)
    'Cancelado' => '#dc3545'              // Vermelho (Important)
];

// Classes Bootstrap correspondentes (para badges)
$status_badges = [
    'Rascunho' => 'warning',
    'Em Revisão' => 'inverse',
    'Emitido' => 'primary',
    'Aguardando Aprovação' => 'info',
    'Aprovado' => 'success',
    'Rejeitado' => 'important',
    'Cancelado' => 'important'
];
?>

<!-- ESTILOS ESPECÍFICOS DO DASHBOARD -->
<style>
    .card-modern {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        border: none;
        margin-bottom: 20px;
        transition: transform 0.2s;
    }

    .card-modern:hover {
        transform: translateY(-5px);
    }

    .stat-box {
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: #fff;
    }

    .stat-content h3 {
        margin: 0;
        font-size: 28px;
        font-weight: 700;
        color: #333;
    }

    .stat-content p {
        margin: 0;
        font-size: 14px;
        color: #777;
        text-transform: uppercase;
        font-weight: 600;
    }

    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
        padding: 15px;
    }

    /* NOVO DESIGN "MAPOS DASHBOARD" PARA BOTÕES DE AÇÃO */
    .card-action {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 15px;
        /* Padding lateral reduzido para caber texto no PC */
        border-radius: 20px;
        color: #333 !important;
        /* Texto escuro conforme imagem */
        font-weight: 600;
        text-decoration: none !important;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.08);
        border: none !important;
        height: 90px;
        box-sizing: border-box;
        margin-bottom: 10px;
        /* Restaurando espaçamento removido */
    }

    .card-action:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);
        filter: brightness(1.05);
    }

    .card-action-title {
        font-size: 0.9rem;
        /* "Uma besteirinha" menor */
        margin: 0;
        flex: 1;
        line-height: 1.1;
    }

    .card-action-icon {
        background: rgba(255, 255, 255, 0.4);
        width: 36px;
        /* Reduzido de 42px para dar folga */
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-left: 8px;
        flex-shrink: 0;
    }

    .card-action-icon i {
        font-size: 18px;
        /* Ícone interno ajustado */
        color: #000;
    }

    /* Cores das Categorias do MapOS */
    .btn-mapos-blue {
        background: linear-gradient(90deg, #9fcbff 0%, #7a98ff 100%);
    }

    /* Clientes */
    .btn-mapos-orange {
        background: linear-gradient(90deg, #ffc78d 0%, #ff945e 100%);
    }

    /* Produtos */
    .btn-mapos-pink {
        /* background: linear-gradient(90deg, #ff9ea2 0%, #f53f7b 100%); COR ANTERIOR VERMELHO/ROSA */
        background: linear-gradient(90deg, #66a3ff 0%, #0d6efd 100%);
        /* NOVO AZUL MAPOS */
    }

    /* Ordens */
    .btn-mapos-cyan {
        background: linear-gradient(90deg, #76f2e7 0%, #3cd3c1 100%);
    }

    /* Serviços */
    .btn-mapos-green {
        background: linear-gradient(90deg, #6ef7bc 0%, #27ce8a 100%);
    }

    /* Vendas */
    .btn-mapos-yellow {
        background: linear-gradient(90deg, #ffe162 0%, #ffbc00 100%);
    }

    /* Lançamentos */

    /* FIX: Alinhamento Vertical Perfeito em Títulos */
    .widget-title {
        display: flex;
        align-items: center;
        height: auto !important;
        padding: 10px;
    }

    .widget-title .icon {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 10px 0 0 !important;
        float: none !important;
        border: none !important;
    }

    .widget-title h5 {
        margin: 0 !important;
        padding: 0 !important;
        float: none !important;
        line-height: normal !important;
    }

    /* --- RESPONSIVIDADE MOBILE --- */
    @media (max-width: 767px) {

        /* Corrige margens do grid fluido em mobile */
        [class*="span"] {
            margin-left: 0 !important;
            margin-bottom: 20px !important;
            width: 100% !important;
            display: block !important;
        }

        .row-fluid {
            width: 100% !important;
        }

        /* Ajuste dos cards de estatísticas */
        .stat-box {
            flex-direction: row;
            /* Mantém lado a lado se couber, ou ajusta */
            justify-content: space-between;
            padding: 15px;
        }

        /* Ajuste do tamanho da fonte em mobile */
        .stat-content h3 {
            font-size: 24px;
        }

        .stat-content p {
            font-size: 12px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            font-size: 20px;
        }

        /* Ajuste dos botões de ação */
        .card-action-title {
            font-size: 0.95rem;
        }

        .card-action-icon {
            width: 38px;
            height: 38px;
            margin-left: 8px;
        }

        .card-action-icon i {
            font-size: 18px;
        }

        .card-action {
            padding: 12px 15px;
            height: 75px;
            margin-bottom: 07px !important;
            /* Forçando espaçamento reduzido no mobile */
        }
    }
</style>





<?php
// Definição de cores do Toast baseada no tema atual
$temaAtual = $configuration['app_theme'] ?? 'white';
$isDark = in_array($temaAtual, ['puredark', 'darkviolet', 'darkorange']);

// Configuração Base (Fundo e Texto)
$toastBg = $isDark ? '#2E363F' : '#ffffff';
$toastColor = $isDark ? '#ffffff' : '#333333';
$toastShadow = $isDark ? '0 4px 15px rgba(0,0,0,0.5)' : '0 4px 15px rgba(0,0,0,0.1)';

// Configuração de Acento (Cor da Borda e Ícone)
switch ($temaAtual) {
    case 'darkviolet':
        $accentColor = '#9370DB'; // Roxo/Violeta
        break;
    case 'whitegreen':
        $accentColor = '#28b779'; // Verde
        break;
    case 'darkorange':
    case 'puredark':
        $accentColor = '#fc9d0f'; // Laranja
        break;
    case 'whiteblack':
        $accentColor = '#2E363F'; // Cinza Escuro
        break;
    default:
        $accentColor = '#27a9e3'; // Azul padrão Mapos (white)
        break;
}
?>

<!-- NOTIFICAÇÃO FLUTUANTE (TOAST) -->
<?php if (!empty($orcamentos_vencidos)): ?>
    <div id="toastVencidos" style="
        position: fixed; 
        top: 60px; 
        right: 20px; 
        width: 280px; 
        background-color: <?= $toastBg ?>; 
        color: <?= $toastColor ?>;
        border-right: 5px solid <?= $accentColor ?>; 
        border-left: none;
        box-shadow: <?= $toastShadow ?>; 
        padding: 12px; 
        z-index: 10000; 
        border-radius: 6px; 
        display: none; 
        font-family: 'Open Sans', sans-serif;">

        <div style="display: flex; justify-content: space-between; align-items: start;">
            <div style="flex: 1;">
                <h5
                    style="margin: 0 0 4px 0; color: <?= $toastColor ?>; font-weight: 600; font-size: 13px; text-transform: uppercase; display: flex; align-items: center;">
                    <i class="icon-warning-sign" style="color: <?= $accentColor ?>; margin-right: 6px;"></i> Orçamentos
                    Vencidos
                </h5>
                <p style="margin: 0; font-size: 12px; opacity: 0.9; line-height: 1.3;">
                    Existem <b><?= count($orcamentos_vencidos) ?> orçamentos</b> expirados.
                </p>
            </div>
            <button type="button" onclick="$('#toastVencidos').fadeOut();"
                style="border: none; background: none; cursor: pointer; opacity: 0.6; color: inherit; font-size: 16px; line-height: 1; margin-left:10px;">&times;</button>
        </div>

        <div style="margin-top: 8px; text-align: right;">
            <a href="#modalVencidos" role="button" data-toggle="modal" onclick="$('#toastVencidos').hide();"
                class="btn btn-mini"
                style="font-size: 11px; background: <?= $accentColor ?>; color: #fff; border: none;">Ver lista</a>
        </div>
    </div>


    <script>
        $(document).ready(function () {
            // Exibe o toast com efeito
            setTimeout(function () {
                $('#toastVencidos').fadeIn(500);
            }, 1000);

            // Oculta automaticamente após 7 segundos (solicitado pelo usuário)
            setTimeout(function () {
                $('#toastVencidos').fadeOut(1000);
            }, 8000); // 1s delay + 7s display = 8000ms total
        });
    </script>

    <!-- MODAL DE DETALHES -->
    <?php if ($isDark): ?>
        <style>
            /* Forçando override total do hover no tema escuro */
            #modalVencidos .table tbody tr:hover td,
            #modalVencidos .table tbody tr:hover th,
            #modalVencidos .table-striped tbody tr:nth-child(odd):hover td,
            #modalVencidos .table-striped tbody tr:nth-child(even):hover td,
            /* Correção para o Cabeçalho (thead) */
            #modalVencidos .table thead tr:hover th,
            #modalVencidos .table thead th:hover {
                background-color: #444 !important;
                color: #fff !important;
            }

            /* Garantindo que links dentro da tabela também fiquem visíveis */
            #modalVencidos .table tbody tr:hover td a {
                color: #fff !important;
            }
        </style>
    <?php endif; ?>

    <div id="modalVencidos" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="modalVencidosLabel"
        aria-hidden="true" style="border: none; background-color: <?= $toastBg ?>; color: <?= $toastColor ?>;">

        <div class="modal-header"
            style="background-color: <?= $isDark ? 'rgba(0,0,0,0.2)' : '#f5f5f5' ?>; border-bottom: 1px solid <?= $isDark ? '#444' : '#eee' ?>; color: <?= $toastColor ?>;">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true"
                style="color: <?= $toastColor ?>; opacity: 0.6;">×</button>
            <h3 id="modalVencidosLabel" style="color: <?= $toastColor ?>;">Orçamentos Vencidos</h3>
        </div>

        <div class="modal-body" style="padding: 0; background-color: <?= $toastBg ?>;">
            <table class="table table-hover" style="margin: 0; border: none; color: <?= $toastColor ?>;">
                <thead>
                    <tr>
                        <th style="border-bottom: 1px solid <?= $isDark ? '#444' : '#ddd' ?>; color: <?= $toastColor ?>;">
                            #ID</th>
                        <th style="border-bottom: 1px solid <?= $isDark ? '#444' : '#ddd' ?>; color: <?= $toastColor ?>;">
                            Cliente</th>
                        <th style="border-bottom: 1px solid <?= $isDark ? '#444' : '#ddd' ?>; color: <?= $toastColor ?>;">
                            Vencimento</th>
                        <th style="border-bottom: 1px solid <?= $isDark ? '#444' : '#ddd' ?>; color: <?= $toastColor ?>;">
                            Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orcamentos_vencidos as $vencido): ?>
                        <tr>
                            <td style="text-align: center; border-top: 1px solid <?= $isDark ? '#444' : '#ddd' ?>;">
                                <b>#<?= $vencido['id'] ?></b>
                            </td>
                            <td style="border-top: 1px solid <?= $isDark ? '#444' : '#ddd' ?>;">
                                <?= htmlspecialchars($vencido['nome_cliente']) ?>
                            </td>
                            <td style="border-top: 1px solid <?= $isDark ? '#444' : '#ddd' ?>;">
                                <span class="label label-important">Há
                                    <?= $vencido['dias_decorridos'] - $vencido['validade_dias'] ?> dias</span>
                            </td>
                            <td style="text-align: center; border-top: 1px solid <?= $isDark ? '#444' : '#ddd' ?>;">
                                <a href="orcamentos/ver_detalhes.php?id=<?= $vencido['id'] ?>"
                                    class="btn btn-mini btn-inverse">Abrir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="modal-footer"
            style="background-color: <?= $isDark ? 'rgba(0,0,0,0.2)' : '#f5f5f5' ?>; border-top: 1px solid <?= $isDark ? '#444' : '#eee' ?>;">
            <button class="btn" data-dismiss="modal" aria-hidden="true">Fechar</button>
            <a href="orcamentos/listar_orcamentos.php" class="btn btn-primary"
                style="background-image: none; background-color: <?= $accentColor ?>; border-color: <?= $accentColor ?>;">Ir
                para Lista Completa</a>
        </div>
    </div>
<?php endif; ?>


<!-- CARDS DE TOTAIS PADRONIZADOS -->
<div class="row-fluid" style="margin-top: 20px;">
    <!-- Orçamentos -->
    <div class="span3">
        <a href="orcamentos/listar_orcamentos.php" class="card-action btn-mapos-pink" style="height: 90px;">
            <div class="card-action-title" style="font-size: 1.1rem;">
                <span style="font-size: 1.8rem; font-weight: 800; display: block; line-height: 1;">
                    <?php echo $total_orcamentos; ?>
                </span>
                Orçamentos
            </div>
            <div class="card-action-icon">
                <i class='bx bx-file'></i>
            </div>
        </a>
    </div>

    <!-- Produtos -->
    <div class="span3">
        <a href="<?php echo MAPOS_URL; ?>index.php/produtos" target="_blank" class="card-action btn-mapos-orange"
            style="height: 90px;">
            <div class="card-action-title" style="font-size: 1.1rem;">
                <span style="font-size: 1.8rem; font-weight: 800; display: block; line-height: 1;">
                    <?php echo $total_produtos; ?>
                </span>
                Produtos
            </div>
            <div class="card-action-icon">
                <i class='bx bx-barcode'></i>
            </div>
        </a>
    </div>

    <!-- Serviços -->
    <div class="span3">
        <a href="<?php echo MAPOS_URL; ?>index.php/servicos" target="_blank" class="card-action btn-mapos-cyan"
            style="height: 90px;">
            <div class="card-action-title" style="font-size: 1.1rem;">
                <span style="font-size: 1.8rem; font-weight: 800; display: block; line-height: 1;">
                    <?php echo $total_servicos; ?>
                </span>
                Serviços
            </div>
            <div class="card-action-icon">
                <i class='bx bx-wrench'></i>
            </div>
        </a>
    </div>

    <!-- Clientes -->
    <div class="span3">
        <a href="<?php echo MAPOS_URL; ?>index.php/clientes" target="_blank" class="card-action btn-mapos-blue"
            style="height: 90px;">
            <div class="card-action-title" style="font-size: 1.1rem;">
                <span style="font-size: 1.8rem; font-weight: 800; display: block; line-height: 1;">
                    <?php echo $total_clientes; ?>
                </span>
                Clientes
            </div>
            <div class="card-action-icon">
                <i class='bx bx-user'></i>
            </div>
        </a>
    </div>
</div>

<!-- ESTILOS ESPECÍFICOS PARA CORRIGIR TABELAS EM TEMAS ESCUROS -->
<?php if ($isDark): ?>
    <style>
        /* Força a tabela de orçamentos recentes a ter fundo branco e texto escuro,
                                                                                       independente do tema global ser escuro. O usuário quer "continuar branco" mas legível. */

        /* Widget Content (Fundo Branco) */
        .widget-box.card-modern .widget-content {
            background-color: #ffffff !important;
            color: #333333 !important;
        }

        /* Títulos da Widget - SEM FUNDO (Efeito clean solicitado) */
        .widget-box.card-modern .widget-title {
            background-color: #ffffff !important;
            border-bottom: 1px solid #eeeeee !important;
            /* Borda suave apenas para separar */
            color: #000000 !important;
        }

        /* FORÇAR PRETO NOS ELEMENTOS DE TEXTO */
        .widget-box.card-modern .widget-title h5,
        .widget-box.card-modern .widget-title span,
        .widget-box.card-modern .widget-title i {
            color: #000000 !important;
            text-shadow: none !important;
            float: none !important;
        }

        /* Ícone específico precisa de opacidade total */
        .widget-box.card-modern .widget-title .icon i {
            opacity: 1 !important;
        }

        /* Tabela Específica */
        .widget-box.card-modern table.table {
            background-color: #ffffff !important;
            color: #333333 !important;
        }

        /* Cabeçalho da Tabela */
        .widget-box.card-modern table.table th {
            background-color: #ffffff !important;
            color: #333333 !important;
            border-bottom: 1px solid #dddddd !important;
        }

        /* Células da Tabela */
        .widget-box.card-modern table.table td {
            background-color: #ffffff !important;
            color: #333333 !important;
            border-top: 1px solid #eeeeee !important;
        }

        /* Links dentro da Tabela (Botões) */
        .widget-box.card-modern table.table a.btn {
            color: #ffffff !important;
            /* Texto do botão deve ser branco */
        }

        /* Hover da Tabela - EVITAR PRETO INVISÍVEL */
        /* Substitui o hover do tema dark por um cinza claro padrão */
        .table-striped tbody>tr:nth-child(odd)>td,
        .table-striped tbody>tr:nth-child(odd)>th {
            background-color: #f9f9f9 !important;
        }

        /* TESTE AGRESSIVO DE HOVER - PARA DEPURAÇÃO */
        .widget-box.card-modern .table-hover tbody tr:hover td,
        .widget-box.card-modern .table-hover tbody tr:hover th {
            background-color: #eeeeeeff !important;
            /* VERMELHO VIVO */
            color: #060404ff !important;
            /* VERDE LIMAO */
        }

        .widget-box.card-modern .table-hover tbody tr:hover a,
        .widget-box.card-modern .table-hover tbody tr:hover i,
        .widget-box.card-modern .table-hover tbody tr:hover span {
            color: #eeeeeeff !important;
        }
    </style>
<?php endif; ?>


<!-- GRÁFICOS -->
<div class="row-fluid">
    <!-- Evolução Mensal (Barra) -->
    <div class="span8">
        <div class="widget-box card-modern" style="background: #fff; padding: 15px;">
            <div class="widget-title" style="background: none; border-bottom: 1px solid #eee;">
                <span class="icon"><i class="icon-signal"></i></span>
                <h5>Evolução de Orçamentos (Últimos 6 Meses)</h5>
            </div>
            <div class="widget-content">
                <div class="chart-container">
                    <canvas id="evolucaoChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Status (Pizza) -->
    <div class="span4">
        <div class="widget-box card-modern" style="background: #fff; padding: 15px;">
            <div class="widget-title" style="background: none; border-bottom: 1px solid #eee;">
                <span class="icon"><i class="icon-adjust"></i></span>
                <h5>Distribuição por Status</h5>
            </div>
            <div class="widget-content">
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TABELA RECENTES E AÇÕES -->
<div class="row-fluid">
    <!-- Tabela -->
    <div class="span8">
        <div class="widget-box card-modern" style="padding: 0; overflow: hidden;">
            <div class="widget-title" style="background: #f9f9f9; border-bottom: 1px solid #eee;">
                <span class="icon"><i class="icon-tasks"></i></span>
                <h5>Orçamentos em Aberto (Últimos 2 Meses)</h5>
            </div>
            <div class="widget-content nopadding" style="overflow-x: auto;">
                <div class="alert alert-info"
                    style="margin: 0; padding: 8px 15px; font-size: 12px; border-radius: 0; white-space: normal;">
                    <i class="icon-info-sign"></i> <b>Nota:</b> A lista abaixo exibe apenas orçamentos criados nos
                    últimos 60 dias. Para ver o histórico completo, use o menu "Listar Todos".
                </div>
                <table class="table table-striped table-bordered table-hover" style="border: none;">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Cliente</th>
                            <th>Data</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orcamentos_ativos)): ?>
                            <tr>
                                <td colspan="6" class="text-center" style="padding: 20px;">Nenhum orçamento pendente
                                    encontrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orcamentos_ativos as $orc): ?>
                                <tr>
                                    <td style="text-align: center;"><?php echo $orc['id']; ?></td>
                                    <td><?php echo htmlspecialchars($orc['nome_cliente']); ?></td>
                                    <td style="text-align: center;"><?php echo $orc['data_formatada']; ?></td>
                                    <td style="text-align: right;">R$
                                        <?php echo number_format($orc['valor_total'], 2, ',', '.'); ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="label label-<?php echo $status_badges[$orc['status']] ?? 'inverse'; ?>">
                                            <?php echo $orc['status']; ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <a href="orcamentos/ver_detalhes.php?id=<?php echo $orc['id']; ?>"
                                            class="btn btn-mini btn-info">
                                            <i class="icon-eye-open"></i> Ver
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Ações Rápidas -->
    <div class="span4">
        <div class="widget-box card-modern" style="background: #fff; padding: 15px;">
            <div class="widget-title" style="background: none; border-bottom: 1px solid #eee; margin-bottom: 15px;">
                <span class="icon"><i class="icon-bolt"></i></span>
                <h5>Ações Rápidas</h5>
            </div>
            <div class="widget-content">
                <div class="row-fluid">
                    <div class="span12">
                        <a href="orcamentos/novo_orcamento.php" class="card-action btn-mapos-pink">
                            <div class="card-action-title">Novo Orçamento</div>
                            <div class="card-action-icon">
                                <i class='bx bx-plus'></i>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="row-fluid">
                    <div class="span12">
                        <a href="orcamentos/listar_orcamentos.php" class="card-action btn-mapos-green">
                            <div class="card-action-title">Listar Todos</div>
                            <div class="card-action-icon">
                                <i class='bx bx-list-ul'></i>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="row-fluid">
                    <div class="span12">
                        <a href="<?php echo MAPOS_URL; ?>index.php/clientes" target="_blank"
                            class="card-action btn-mapos-blue">
                            <div class="card-action-title">Clientes</div>
                            <div class="card-action-icon">
                                <i class='bx bx-user'></i>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'tema/footer.php'; ?>

<!-- Chart.js via CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Dados PHP -> JS
    const dadosStatus = <?php echo json_encode($contagem_status); ?>;
    const dadosEvolucao = <?php echo json_encode($dados_evolucao); ?>;
    const coreStatus = <?php echo json_encode($status_colors); ?>;

    // --- GRÁFICO DE STATUS (PIZZA) ---
    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    const labelsStatus = Object.keys(dadosStatus);
    const dataStatus = Object.values(dadosStatus);
    const bgStatus = labelsStatus.map(k => coreStatus[k] || '#ccc');

    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: labelsStatus,
            datasets: [{
                data: dataStatus,
                backgroundColor: bgStatus,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 10 } }
            }
        }
    });

    // --- GRÁFICO DE EVOLUÇÃO (BARRAS) ---
    const ctxEvolucao = document.getElementById('evolucaoChart').getContext('2d');
    const labelsMeses = dadosEvolucao.map(d => d.mes_exibicao);
    const dataMeses = dadosEvolucao.map(d => d.total);

    new Chart(ctxEvolucao, {
        type: 'bar',
        data: {
            labels: labelsMeses,
            datasets: [{
                label: 'Orçamentos',
                data: dataMeses,
                backgroundColor: '#27a9e3',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // Importante para caber no container
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } },
                x: { grid: { display: false } }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
</script>