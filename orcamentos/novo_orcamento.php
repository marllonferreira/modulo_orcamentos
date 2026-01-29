<?php
// novo_orcamento.php - VERSÃO FINAL OTIMIZADA PARA MOBILE (MATRIX ADMIN LAYOUT)

// ✅ CAMINHO CORRIGIDO: Busca 'conexao.php' um nível acima
require '../conexao.php';

// Função para converter recursivamente para UTF-8 (Robustez para o JSON)
// Função para converter recursivamente para UTF-8 (Robustez para o JSON)
// Função para converter recursivamente para UTF-8 (Robustez para o JSON)
// Função para converter recursivamente para UTF-8 (Robustez para o JSON)
function utf8_converter($array)
{
    if (!is_array($array))
        return [];
    array_walk_recursive($array, function (&$item, $key) {
        if (function_exists('mb_detect_encoding')) {
            if (!mb_detect_encoding($item, 'UTF-8', true))
                $item = utf8_encode($item);
        } else {
            $item = utf8_encode($item);
        }
    });
    return $array;
}

// Função para buscar APENAS PRODUTOS (Simplificada e Robusta)
function getProdutos(PDO $pdo)
{
    try {
        $pdo->exec("SET NAMES utf8");
        $stmt = $pdo->query('SELECT idProdutos as id, descricao, COALESCE(unidade, "UN") as unidade, precoVenda as preco_referencia FROM produtos ORDER BY descricao');
        if (!$stmt)
            return [];
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}
$produtos_dados = getProdutos($pdo);
// Encode seguro, ignora erro UTF8 se houver
$produtos_json = json_encode($produtos_dados, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
if (!$produtos_json || $produtos_json === 'null')
    $produtos_json = '[]';

// Função para buscar APENAS SERVIÇOS (Simplificada)
function getServicos(PDO $pdo)
{
    try {
        $pdo->exec("SET NAMES utf8");
        $stmt = $pdo->query('SELECT idServicos as id, nome as descricao, preco as preco_referencia FROM servicos ORDER BY nome');
        if (!$stmt)
            return [];
        $servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($servicos as &$servico) {
            $servico['unidade'] = 'HR';
        }
        return $servicos;
    } catch (Exception $e) {
        return [];
    }
}
$servicos_dados = getServicos($pdo);
$servicos_json = json_encode($servicos_dados, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
if (!$servicos_json || $servicos_json === 'null')
    $servicos_json = '[]';

// --- LISTA DE VARIÁVEIS DE SUPORTE (Unidades de Medida e Estados) ---
$estados_brasil = [
    'AC' => 'Acre',
    'AL' => 'Alagoas',
    'AP' => 'Amapá',
    'AM' => 'Amazonas',
    'BA' => 'Bahia',
    'CE' => 'Ceará',
    'DF' => 'Distrito Federal',
    'ES' => 'Espírito Santo',
    'GO' => 'Goiás',
    'MA' => 'Maranhão',
    'MT' => 'Mato Grosso',
    'MS' => 'Mato Grosso do Sul',
    'MG' => 'Minas Gerais',
    'PA' => 'Pará',
    'PB' => 'Paraíba',
    'PR' => 'Paraná',
    'PE' => 'Pernambuco',
    'PI' => 'Piauí',
    'RJ' => 'Rio de Janeiro',
    'RN' => 'Rio Grande do Norte',
    'RS' => 'Rio Grande do Sul',
    'RO' => 'Rondônia',
    'RR' => 'Roraima',
    'SC' => 'Santa Catarina',
    'SP' => 'São Paulo',
    'SE' => 'Sergipe',
    'TO' => 'Tocantins'
];

// ✅ CARREGA CONFIGURAÇÕES GERAIS
require_once '../config_geral.php';

// ✅ CARREGA UNIDADES DE MEDIDA DO ARQUIVO JSON DO MAPOS (SINCRONIZADO)
$tabela_medidas_path = MAPOS_ROOT_PATH . 'assets/json/tabela_medidas.json';
if (file_exists($tabela_medidas_path)) {
    $tabela_medidas_json = file_get_contents($tabela_medidas_path);
    $tabela_medidas = json_decode($tabela_medidas_json, true);

    // Cria mapa de unidades (sigla => descrição) para o JavaScript
    $unidades_medida = [];
    foreach ($tabela_medidas['medidas'] as $medida) {
        $unidades_medida[$medida['sigla']] = $medida['descricao'];
    }
} else {
    // Fallback caso o arquivo não exista
    $unidades_medida = [
        'UN' => 'UNIDADE',
        'CX' => 'CAIXA',
        'PC' => 'PEÇA',
        'KG' => 'QUILOGRAMA',
        'M' => 'METRO',
        'M2' => 'METRO QUADRADO'
    ];
}
// Lista Específica para Serviços
$unidades_servico = [
    'HR' => 'HORA',
    'DIA' => 'DIA',
    'MES' => 'MÊS',
    'SV' => 'SERVIÇO',
    'UN' => 'UNIDADE',
    'KM' => 'QUILÔMETRO',
    'M2' => 'METRO QUADRADO',
    'VERBA' => 'VERBA'
];

// Converte array de unidades com segurança
$unidades_medida = utf8_converter($unidades_medida);
$unidades_servico = utf8_converter($unidades_servico);

$unidades_medida_json = json_encode($unidades_medida, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
$unidades_servico_json = json_encode($unidades_servico, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);

if ($unidades_medida_json === false)
    $unidades_medida_json = '{}';
if ($unidades_servico_json === false)
    $unidades_servico_json = '{}';

// Define a página atual para o breadcrumb do header.php
$pagina_atual = 'Novo Orçamento';

// INCLUINDO O TOPO (HEADER) COM O CAMINHO CORRIGIDO
// Isso garante que $configuration seja carregado do banco de dados
include '../tema/header.php';

// ---------------------------------------------------------------------------------------------------
// 🎨 PREPARAÇÃO DO TEMA CAMALEÃO (COPIADO DO INDEX.PHP)
// ---------------------------------------------------------------------------------------------------
// Agora $configuration['app_theme'] estará disponível vindo do header.php
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

<style>
    /* ========================================= */
    /* 🎨 CORREÇÃO DE VISIBILIDADE DE INPUTS EM TEMAS ESCUROS */
    <?php if ($isDark): ?>
        /* Inputs */
        input[type="text"],
        input[type="number"],
        input[type="date"],
        input[type="datetime-local"],
        input[type="password"],
        input[type="email"],
        select,
        textarea,
        .uneditable-input {
            background-color: #ffffff !important;
            color: #333333 !important;
            border: 1px solid #cccccc !important;
        }

        input::placeholder,
        textarea::placeholder {
            color: #999999 !important;
        }

        /* Tabelas e Conteúdo */
        .widget-box.card-like {
            background-color: #ffffff !important;
            color: #333333 !important;
        }

        .table#tabela-itens {
            background-color: #ffffff !important;
            color: #333333 !important;
        }

        .table th {
            background-color: #f8f9fc !important;
            color: #5a5c69 !important;
            border-bottom: 2px solid #e3e6f0 !important;
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

        #tabela-itens tbody tr:hover>td,
        #tabela-itens tbody tr:hover>th {
            background-color: #f5f5f5ff !important;
            color: #ffffff !important;
        }

        #tabela-itens tbody tr:hover input,
        #tabela-itens tbody tr:hover select {
            background-color: #ffffffff !important;
            color: #161616ff !important;
        }

        /* Labels e Textos auxiliares */
        .widget-box.card-like label,
        .widget-box.card-like .control-label,
        .widget-box.card-like .help-block {
            color: #333333 !important;
        }

    <?php endif; ?>
    /* ========================================= */

    /* Configuração Global do Fundo */
    #content {
        background: none !important;
        /* Visual corrigido para aceitar o fundo do tema */
        padding-bottom: 0 !important;
    }

    /* Container estilo "Card" */
    .widget-box.card-like {
        background: #fff !important;
        border: 1px solid #e3e6f0 !important;
        border-radius: 8px !important;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
        margin-top: 20px !important;
        max-width: 1200px;
        /* Limita largura máxima da box */
        margin-left: auto;
        margin-right: auto;
        overflow: hidden;
        /* Evita que tabelas ou conteúdos vazem (estratégia listar_orcamentos.php) */
    }

    /* Remove bordas duplicadas se houver */
    .widget-box .widget-box {
        border: none !important;
        box-shadow: none !important;
        margin: 0 !important;
    }

    /* Scroll Horizontal Global para Tabelas (Inspirado em listar_orcamentos.php) */
    .table-responsive-custom {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        display: block;
    }

    /* CABEÇALHO PRINCIPAL COMPACTO */
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

    .widget-title.card-header-blue h5 {
        color: #fff !important;
        font-size: 1.1rem !important;
        margin: 0 !important;
        font-weight: 500 !important;
        line-height: 1.2 !important;
    }

    /* Botão Voltar */
    .btn-outline-white-rounded {
        border: 1px solid #fff !important;
        color: #fff !important;
        border-radius: 50px !important;
        padding: 5px 15px;
        background: transparent;
        font-weight: 500;
        text-shadow: none !important;
        text-decoration: none !important;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 0.9rem;
    }

    .btn-outline-white-rounded:hover {
        background: #fff !important;
        color: #0d6efd !important;
    }


    /* SEÇÕES INTERNAS */
    .widget-title.card-header-section {
        height: auto !important;
        padding: 10px 20px !important;
        display: flex !important;
        align-items: center !important;
        border-bottom: 1px solid #e3e6f0 !important;
        background-color: #f8f9fc !important;
        /* Cinza bem claro */
        color: #5a5c69 !important;
    }

    .widget-title.card-header-section h5 {
        font-size: 0.95rem !important;
        font-weight: 700 !important;
        color: #5a5c69 !important;
        text-transform: uppercase;
        margin: 0;
        text-shadow: none !important;
    }

    .widget-title.card-header-section .icon {
        margin-right: 10px;
        opacity: 0.7;
        color: #5a5c69 !important;
    }


    /* FORMULÁRIO E INPUTS */
    .widget-content {
        padding: 25px !important;
        border: none !important;
        border-bottom-left-radius: 8px !important;
        border-bottom-right-radius: 8px !important;
    }

    /* Restrição de Largura de Inputs */
    .control-group {
        border: none !important;
        margin-bottom: 15px !important;
        padding: 0 !important;
    }

    input[type="text"],
    input[type="number"],
    textarea,
    select {
        border-radius: 4px !important;
        border: 1px solid #d1d3e2 !important;
        padding: 4px 10px !important;
        /* Mais compacto */
        height: 30px !important;
        /* Altura fixa controlada */
        line-height: normal !important;
        box-shadow: none !important;
        transition: border-color 0.2s;
        margin-bottom: 0 !important;
        color: #495057 !important;
        width: 100%;
        /* Default width */
        max-width: 100%;
        box-sizing: border-box;
        font-size: 13px !important;
        /* Fonte proporcional */
        /* Garante que padding não estoure width */
    }

    textarea {
        height: auto !important;
        padding: 8px !important;
    }

    /* Max-Width para Inputs Específicos */
    #cliente_nome,
    #endereco {
        max-width: 500px;
        /* Reduzido um pouco mais */
    }

    #telefone,
    #estado,
    #cidade {
        max-width: 100%;
    }

    /* Focus State */
    input:focus,
    textarea:focus,
    select:focus {
        border-color: #bac8f3 !important;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25) !important;
    }

    /* Labels e Grupos */
    /* Transformando Form Horizontal em Vertical para aproximar Labels */
    .form-horizontal .control-label,
    .form-vertical .control-label {
        float: none !important;
        width: auto !important;
        text-align: left !important;
        padding-top: 0 !important;
        margin-bottom: 5px !important;
        display: block !important;
        font-weight: 600 !important;
        color: #5a5c69 !important;
    }

    .form-horizontal .controls,
    .form-vertical .controls {
        margin-left: 0 !important;
        padding-left: 0 !important;
    }


    /* TABELA DE ITENS */
    #tabela-itens {
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid #e3e6f0;
        border-radius: 6px;
        /* overflow: hidden; REMOVIDO PARA NÃO CORTAR AUTOCOMPLETE */
        width: 100%;
    }

    #tabela-itens thead th {
        background-color: #f8f9fc !important;
        color: #5a5c69 !important;
        text-transform: uppercase;
        font-weight: 700;
        border-bottom: 1px solid #e3e6f0;
        padding: 10px;
        font-size: 0.85rem;
    }

    #tabela-itens td {
        border-top: 1px solid #e3e6f0;
        padding: 10px;
        vertical-align: middle;
    }

    /* Classes Específicas Restauradas */
    .widget-title.card-header-blue-section {
        background-color: #27a9e3 !important;
        /* Azul "Legacy" mais claro/vibrante */
        color: #fff !important;
        border-bottom: 1px solid #27a9e3 !important;
    }

    .widget-title.card-header-dark {
        background-color: #2E363F !important;
        /* Escuro do Matrix Admin */
        color: #fff !important;
        border-bottom: 1px solid #2E363F !important;
    }

    /* Ajuste para ícones dentro dos headers coloridos */
    .widget-title.card-header-blue-section .icon,
    .widget-title.card-header-dark .icon {
        color: #fff !important;
        opacity: 0.9;
    }

    .widget-title.card-header-blue-section h5,
    .widget-title.card-header-dark h5 {
        color: #fff !important;
        text-shadow: none !important;
    }

    /* BOTÕES - Green Fix */
    .btn-success-vibrant {
        background-color: #198754 !important;
        background-image: none !important;
        /* Remove gradiente do bootstrap 2 */
        border-color: #198754 !important;
        border-radius: 6px !important;
        font-weight: 600 !important;
        padding: 10px 20px;
        text-shadow: none !important;
        color: #fff !important;
        font-size: 1.1em;
    }

    .btn-success-vibrant:hover {
        background-color: #146c43 !important;
    }

    /* AUTOCOMPLETE */
    /* container relativo pro autocomplete colar no input */
    .autocomplete-container {
        position: relative !important;
        display: block;
        max-width: 600px;
    }

    .autocomplete-items {
        position: absolute;
        border: 1px solid #d1d3e2;
        border-top: none;
        z-index: 99;
        /* Position the autocomplete items to be the same width as the container */
        top: 100%;
        left: 0;
        right: 0;
        background-color: #fff;
        border-radius: 0 0 6px 6px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        max-height: 200px;
        overflow-y: auto;
    }

    .autocomplete-items div {
        padding: 10px;
        cursor: pointer;
        font-size: 14px;
        /* Aumentado */
        background-color: #fff;
        border-bottom: 1px solid #e3e6f0;
    }

    /* Dropdown do Menu Adicionar Item */
    .dropdown-menu li a {
        font-size: 14px !important;
        padding: 10px 15px !important;
    }

    /* TOAST CAMALEÃO DINÂMICO */
    .toast-camaleao {
        position: fixed;
        top: 60px;
        /* Logo abaixo do header */
        right: 20px;
        width: 300px;
        background-color:
            <?= $toastBg ?>
        ;
        color:
            <?= $toastColor ?>
        ;
        border-left: none;
        border-right: 5px solid
            <?= $accentColor ?>
        ;
        box-shadow:
            <?= $toastShadow ?>
        ;
        padding: 15px;
        z-index: 10000;
        border-radius: 6px;
        font-family: 'Open Sans', sans-serif;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-20px);
        transition: all 0.4s cubic-bezier(0.68, -0.55, 0.27, 1.55);
    }

    .toast-camaleao.show {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .toast-icon {
        font-size: 20px;
        color:
            <?= $accentColor ?>
        ;
    }

    .toast-content {
        flex: 1;
    }

    .toast-title {
        margin: 0 0 5px 0;
        font-weight: 700;
        font-size: 14px;
        color:
            <?= $toastColor ?>
        ;
    }

    .toast-message {
        font-size: 13px;
        opacity: 0.9;
        line-height: 1.4;
        margin: 0;
    }

    .toast-close {
        background: none;
        border: none;
        font-size: 18px;
        color:
            <?= $toastColor ?>
        ;
        opacity: 0.5;
        cursor: pointer;
        line-height: 1;
        padding: 0;
        margin-top: -2px;
    }

    .toast-close:hover {
        opacity: 1;
    }

    /* --- RESPONSIVIDADE MOBILE E TABLET (ATÉ 1024px) --- */

    /* --- RESPONSIVIDADE MOBILE E TABLET (ATÉ 1024px) --- */
    /* Ajustado para cobrir modo paisagem que estava quebrando entre 767px e 1000px */
    @media (max-width: 1024px) {

        /* Ajuste de inputs e spans */
        .row-fluid .span3,
        .row-fluid .span1,
        .row-fluid .span5,
        .row-fluid .span6,
        .row-fluid .span2 {
            width: 100% !important;
            margin-left: 0 !important;
            display: block !important;
            margin-bottom: 15px;
        }

        /* Tabela rolável (Tratado pelo Global agora) */

        /* Botões Full Width */
        .btn-large {
            width: 100% !important;
        }

        .btn-group {
            display: block;
            width: 100%;
        }

        .btn-group .btn {
            display: block;
            width: 100%;
            margin-bottom: 10px;
            border-radius: 4px !important;
        }

        /* Total Geral empilhado */
        .row-fluid .span6[style*="text-align: right"] {
            text-align: left !important;
            margin-top: 20px;
        }

        /* Inputs específicos */
        #cliente_nome,
        #endereco {
            max-width: 100% !important;
        }
    }
</style>

<div class="row-fluid" style="margin-top:0">
    <div class="span12">
        <div class="widget-box card-like">

            <!-- HEADER PRINCIPAL -->
            <div class="widget-title card-header-blue">
                <div style="display: flex; align-items: center;">
                    <span class="icon" style="margin-right:10px;"><i class="icon-plus icon-white"></i></span>
                    <h5>Novo Orçamento</h5>
                </div>
                <div class="buttons" style="margin:0;">
                    <a href="listar_orcamentos.php" class="btn-outline-white-rounded">
                        <i class="icon-arrow-left"></i> Voltar
                    </a>
                </div>
            </div>

            <div class="widget-content">
                <div id="alert-container"></div>

                <form class="form-horizontal" id="form-orcamento" autocomplete="off">
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <!-- DADOS DO CLIENTE -->
                    <div class="widget-title card-header-section card-header-dark"
                        style="margin-bottom: 20px; border-radius: 4px;">
                        <span class="icon"><i class="icon-user icon-white"></i></span>
                        <h5>Dados do Cliente</h5>
                    </div>

                    <div class="control-group">
                        <label for="cliente_nome" class="control-label">Cliente <span class="required">*</span></label>
                        <div class="controls">
                            <input type="hidden" id="cliente_id" name="cliente_id" value="">
                            <!-- Container relativo para o autocomplete ancorar corretamente -->
                            <div class="autocomplete-container">
                                <input id="cliente_nome" type="text" name="cliente_nome"
                                    placeholder="Digite o nome do cliente..." required autocomplete="off" />
                            </div>
                        </div>
                    </div>

                    <div class="row-fluid">
                        <div class="span3">
                            <div class="control-group">
                                <label for="telefone" class="control-label">Telefone</label>
                                <div class="controls">
                                    <input id="telefone" type="text" name="telefone" placeholder="(00) 0000-0000"
                                        readonly />
                                </div>
                            </div>
                        </div>
                        <div class="span1">
                            <div class="control-group">
                                <label for="estado" class="control-label">UF</label>
                                <div class="controls">
                                    <input id="estado" type="text" name="estado" placeholder="UF" readonly />
                                </div>
                            </div>
                        </div>
                        <div class="span3">
                            <div class="control-group">
                                <label for="cidade" class="control-label">Cidade</label>
                                <div class="controls">
                                    <input id="cidade" type="text" name="cidade" placeholder="Cidade" readonly />
                                </div>
                            </div>
                        </div>
                        <div class="span5">
                            <div class="control-group">
                                <label for="endereco" class="control-label">Endereço</label>
                                <div class="controls">
                                    <input id="endereco" type="text" name="endereco" placeholder="Endereço Completo"
                                        readonly />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ITENS -->
                    <div class="widget-title card-header-section card-header-blue-section"
                        style="margin-top: 30px; margin-bottom: 20px; border-radius: 4px;">
                        <span class="icon"><i class="icon-list icon-white"></i></span>
                        <h5>Itens do Orçamento</h5>
                    </div>

                    <div class="table-responsive-custom" style="padding: 0;">
                        <table class="table" id="tabela-itens">
                            <thead>
                                <tr>
                                    <th style="min-width: 200px;">Descrição</th>
                                    <th style="min-width: 80px;">Unidade</th>
                                    <th style="min-width: 80px;">Qtd.</th>
                                    <th style="min-width: 100px;">Preço Unit.</th>
                                    <th style="min-width: 80px;">Taxa (%)</th>
                                    <th style="min-width: 100px;">Total</th>
                                    <th style="min-width: 50px;">Ação</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <div class="row-fluid" style="margin-top:100px; margin-bottom: 80px;">
                        <div class="span6">
                            <div class="btn-group">
                                <button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown"
                                    style="border-radius: 4px;">
                                    <i class="icon-plus icon-white"></i> Adicionar Item <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a href="#" id="btn-adicionar-produto">Produto</a></li>
                                    <li><a href="#" id="btn-adicionar-servico">Serviço</a></li>
                                    <li><a href="#" id="btn-adicionar-manual">Item Manual</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="span6" style="text-align: right; padding-right: 15px;">
                            <h3
                                style="margin:0; font-size: 1.8em !important; color: #333; font-weight: bold !important;">
                                Total Geral: <span id="total-geral-display" style="color: #28a745 !important;">R$
                                    0,00</span>
                            </h3>
                            <input type="hidden" id="total_geral_hidden" name="total_geral_hidden" value="0.00">
                        </div>
                    </div>
            </div>

            <!-- DETALHES FINAIS -->
            <div class="widget-title card-header-section card-header-dark"
                style="margin-top: 30px; margin-bottom: 20px; border-radius: 4px;">
                <span class="icon"><i class="icon-pencil icon-white"></i></span>
                <h5>Detalhes Finais</h5>
            </div>

            <div style="padding: 20px;">
                <div class="row-fluid">
                    <div class="span3">
                        <div class="control-group">
                            <label class="control-label">Validade (Dias)</label>
                            <div class="controls">
                                <input type="number" name="validade_dias" value="7" class="span12" min="1" required />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row-fluid">
                    <div class="span6">
                        <div class="control-group">
                            <label class="control-label">Observações</label>
                            <div class="controls">
                                <textarea name="observacoes" class="span12" rows="5"
                                    placeholder="Observações para o cliente..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="span6">
                        <div class="control-group">
                            <label class="control-label" style="color:#d9534f;">Anotações Internas</label>
                            <div class="controls">
                                <textarea name="anotacoes_internas" class="span12" rows="5"
                                    placeholder="Não visível no PDF..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions" style="background: transparent; border: none; padding: 20px; text-align: center;">
                <button type="submit" class="btn btn-success-vibrant btn-large" style="width: 50%;">
                    <i class="icon-save"></i> Salvar Orçamento
                </button>
            </div>

            </form>
        </div>
    </div>
</div>
</div>

<?php include '../tema/footer.php'; ?>

<!-- Cleave.js (Para máscaras monetárias se necessário, mas Matrix usa maskMoney geralmente. Mantendo Cleave por enquanto) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/cleave.js/1.6.0/cleave.min.js"></script>

<script>
    // =================================================================
    // JAVASCRIPT REUTILIZADO E ADAPTADO PARA O NOVO LAYOUT
    // =================================================================

    // =================================================================
    // JAVASCRIPT REUTILIZADO E ADAPTADO PARA O NOVO LAYOUT
    // =================================================================

    // HELPER FUNCTIONS (Importante para novo_orcamento também)
    function escapeHtml(text) {
        if (!text) return text;
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function formatarMoeda(valor) {
        if (!valor) return 'R$ 0,00';
        return 'R$ ' + parseFloat(valor).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function parseBRLToFloat(valorStr) {
        if (!valorStr) return 0;
        if (typeof valorStr === 'number') return valorStr;
        return parseFloat(valorStr.replace('R$', '').replace(/\./g, '').replace(',', '.').trim()) || 0;
    }

    const UNIDADES_MEDIDA_MAP = <?php echo $unidades_medida_json; ?>;
    // Lista fixa para Serviços garantindo integridade
    const UNIDADES_SERVICO_MAP = {
        'HR': 'HORA',
        'DIA': 'DIA',
        'MES': 'MÊS',
        'SV': 'SERVIÇO',
        // 'UN': 'UNIDADE', // Removido para usar do JSON global
        'KM': 'QUILÔMETRO',
        'M2': 'METRO QUADRADO',
        'VERBA': 'VERBA'
    };
    const UNIDADES_MEDIDA_KEYS = Object.keys(UNIDADES_MEDIDA_MAP);
    const UNIDADES_SERVICO_KEYS = Object.keys(UNIDADES_SERVICO_MAP);
    const CLIENTE_FIELDS = ['telefone', 'estado', 'cidade', 'endereco'];

    function updateUnitDisplay(selectElement, isFocus) {
        Array.from(selectElement.options).forEach(option => {
            const key = option.value;
            // Busca description em ambos os mapas
            const fullName = UNIDADES_MEDIDA_MAP[key] || UNIDADES_SERVICO_MAP[key] || key;
            option.textContent = isFocus ? fullName : key;
        });
    }

    function formatarMoeda(value) {
        return 'R$ ' + parseFloat(value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function parseBRLToFloat(value) {
        if (!value) return 0.00;
        let cleaned = value.toString().replace(/[R$\.]/g, '').replace(',', '.').trim();
        return parseFloat(cleaned) || 0.00;
    }

    // --- CLIENT ---
    function resetClienteFields(clienteId = null) {
        document.getElementById('cliente_id').value = clienteId || '';
        CLIENTE_FIELDS.forEach(field => {
            const input = document.getElementById(field);
            if (input) {
                if (!clienteId) {
                    // Reset fields
                    if (field === 'estado') input.value = ''; // Should be empty until focused/filled
                    input.value = '';
                    input.readOnly = false;
                } else {
                    // Lock fields
                    input.readOnly = true;
                }
            }
        });
        document.getElementById('cliente_nome').readOnly = false;
    }

    function preencherCliente(cliente) {
        document.getElementById('cliente_id').value = cliente.id;
        document.getElementById('cliente_nome').value = cliente.nome;
        document.getElementById('telefone').value = cliente.telefone || '';
        document.getElementById('endereco').value = cliente.endereco || '';
        document.getElementById('cidade').value = cliente.cidade || '';
        document.getElementById('estado').value = cliente.estado || '';
        resetClienteFields(cliente.id);
    }

    function autocompleteCliente(input) {
        let currentFocus;
        const inputId = input.id;
        input.addEventListener("input", function (e) {
            const val = this.value;
            closeAllLists();
            resetClienteFields(null);
            if (val.length < 3) { return false; }

            const a = document.createElement("div");
            a.setAttribute("id", inputId + "autocomplete-list");
            a.setAttribute("class", "autocomplete-items");

            // Garantir que a largura bate com o input pai
            a.style.width = this.offsetWidth + 'px';
            a.style.left = '0';

            // Append to BODY to avoid overflow clipping
            document.body.appendChild(a);

            // Calculate Position
            const rect = this.getBoundingClientRect();
            a.style.position = 'absolute';
            a.style.left = (rect.left + window.scrollX) + 'px';
            a.style.top = (rect.bottom + window.scrollY) + 'px';
            a.style.width = rect.width + 'px';
            a.style.zIndex = '10001'; // Ensure it's on top of everything

            fetch(`buscar_cliente.php?termo=${encodeURIComponent(val)}`)
                .then(r => r.json())
                .then(clientes => {
                    clientes.forEach(cliente => {
                        const b = document.createElement("div");
                        b.innerHTML = `<strong>${cliente.nome.substr(0, val.length)}</strong>${cliente.nome.substr(val.length)} (${cliente.cidade} - ${cliente.estado})`;
                        b.dataset.cliente = JSON.stringify(cliente);
                        b.addEventListener("click", function (e) {
                            preencherCliente(JSON.parse(this.dataset.cliente));
                            closeAllLists();
                        });
                        a.appendChild(b);
                    });
                    if (clientes.length === 0) {
                        const c = document.createElement("div");
                        c.innerHTML = `<em>Nenhum cliente encontrado.</em>`;
                        a.appendChild(c);
                    }
                })
                .catch(e => console.error(e));
        });

        input.addEventListener("keydown", function (e) {
            let x = document.getElementById(this.id + "autocomplete-list");
            if (x) x = x.getElementsByTagName("div");
            if (e.keyCode === 40) { currentFocus++; addActive(x); }
            else if (e.keyCode === 38) { currentFocus--; addActive(x); }
            else if (e.keyCode === 13) {
                e.preventDefault();
                if (currentFocus > -1 && x && x[currentFocus]) x[currentFocus].click();
            }
        });
        function addActive(x) {
            if (!x) return false;
            removeActive(x);
            if (currentFocus >= x.length) currentFocus = 0;
            if (currentFocus < 0) currentFocus = (x.length - 1);
            if (x[currentFocus]) x[currentFocus].classList.add("autocomplete-active");
        }
        function removeActive(x) { for (let i = 0; i < x.length; i++) x[i].classList.remove("autocomplete-active"); }
        function closeAllLists(elmnt) {
            const x = document.getElementsByClassName("autocomplete-items");
            for (let i = 0; i < x.length; i++) {
                if (elmnt !== x[i] && elmnt !== input) x[i].parentNode.removeChild(x[i]);
            }
        }
        document.addEventListener("click", function (e) { closeAllLists(e.target); });
    }

    // --- ITENS ---
    // --- ITENS ---
    function createItemRow(itemData = null, itemType = 'P', autoFocus = true) {
        const isService = itemType === 'S';
        const isManual = itemType === 'M';

        // Determina qual lista de unidades usar
        let unidadesKeys;
        if (isManual) {
            // Se for Manual, funde as duas listas removendo duplicatas e ordena
            unidadesKeys = [...new Set([...UNIDADES_MEDIDA_KEYS, ...UNIDADES_SERVICO_KEYS])].sort();
        } else {
            unidadesKeys = isService ? UNIDADES_SERVICO_KEYS : UNIDADES_MEDIDA_KEYS;
        }
        const defaultUnidade = isService ? 'HR' : 'UNID';

        const item = itemData || { id: '', descricao: '', unidade: defaultUnidade, preco_referencia: 0 };

        const row = document.createElement('tr');
        row.classList.add('item-row');

        let precoFmt = '0,00';
        if (item.preco_referencia) {
            precoFmt = formatarMoeda(item.preco_referencia).replace('R$ ', '');
        }

        const ph = itemType === 'P' ? 'Buscar Produto...' : (itemType === 'S' ? 'Buscar Serviço...' : 'Descrição Item');

        row.innerHTML = `
            <td>
                <input type="hidden" name="item_origem_id[]" class="item-origem-id" value="${item.id}">
                <input type="hidden" name="tipo_item[]" class="item-tipo-item" value="${itemType}">
                <input type="hidden" name="descricao[]" class="item-descricao-hidden" value="${item.descricao}">
                <div style="position: relative; display: inline-block; width: 100%;">
                    <input type="text" class="span12 item-produto" value="${escapeHtml(item.descricao)}" placeholder="${ph}" required autocomplete="off">
                </div>
            </td>
            <td>
                <select name="unidade[]" class="span12 item-unidade" required>
                    ${unidadesKeys.map(u => `<option value="${u}" ${u === item.unidade ? 'selected' : ''}>${u}</option>`).join('')}
                </select>
            </td>
            <td><input type="number" name="qtd[]" class="span12 item-qtd" value="1" min="1" step="1" required></td>
            <td><input type="text" name="preco_unitario[]" class="span12 item-preco-unitario" value="${precoFmt}" required></td>
            <td><input type="number" name="taxa[]" class="span12 item-taxa" value="0" min="0" max="100" step="0.01"></td>
            <td>
                <input type="text" class="span12 item-total-linha" value="${item.preco_referencia ? formatarMoeda(item.preco_referencia) : 'R$ 0,00'}" readonly style="background-color: #eee;">
                <input type="hidden" name="total_linha_hidden[]" class="item-total-linha-hidden" value="${item.preco_referencia || 0}">
            </td>
            <td style="text-align:center;">
                <button type="button" class="btn btn-mini btn-danger btn-remover-item"><i class="icon-trash"></i></button>
            </td>
        `;

        document.querySelector('#tabela-itens tbody').appendChild(row);
        applyMaskAndEvents(row, itemType);

        if (autoFocus) {
            row.querySelector('.item-produto').focus();
        }

        updateTotalGeral();
    }

    // LISTA DE ITENS - NOVA IMPLEMENTAÇÃO SIMPLIFICADA E ROBUSTA
    function autocompleteCatalogo(input, row) {
        let currentFocus;
        const itemType = row.querySelector('.item-tipo-item').value;
        // Se for Manual, não tem autocomplete
        if (itemType === 'M') return;

        // Define tipo para o backend
        const tipoBusca = itemType === 'P' ? 'produto' : 'servico';

        input.addEventListener("input", function (e) {
            const val = this.value;
            closeAllLists();
            if (!val) return false;

            const a = document.createElement("div");
            a.setAttribute("id", this.id + "autocomplete-list");
            a.setAttribute("class", "autocomplete-items");
            // Append to BODY to avoid overflow clipping
            document.body.appendChild(a);

            // Calculate Position
            const rect = this.getBoundingClientRect();
            a.style.position = 'absolute';
            a.style.left = (rect.left + window.scrollX) + 'px';
            a.style.top = (rect.bottom + window.scrollY) + 'px';
            a.style.width = rect.width + 'px';
            a.style.zIndex = '10001';

            // AJAX FETCH
            fetch(`buscar_item.php?tipo=${tipoBusca}&termo=${encodeURIComponent(val)}`)
                .then(r => {
                    if (!r.ok) throw new Error("Erro na resposta do servidor");
                    return r.json();
                })
                .then(data => {
                    if (!Array.isArray(data)) {
                        console.error("Dados inválidos recebidos:", data);
                        return;
                    }

                    data.forEach(item => {
                        const b = document.createElement("div");
                        // Formatação visual do item na lista
                        b.innerHTML = `<strong>${escapeHtml(item.descricao.substr(0, val.length))}</strong>${escapeHtml(item.descricao.substr(val.length))}`;
                        b.innerHTML += `<br><small style='color:#666'>R$ ${parseFloat(item.preco).toFixed(2).replace('.', ',')} (${escapeHtml(item.unidade)})</small>`;

                        // Dados para preenchimento
                        b.dataset.id = item.id;
                        b.dataset.descricao = item.descricao;
                        b.dataset.unidade = item.unidade;
                        b.dataset.preco = item.preco;

                        b.addEventListener("click", function () {
                            row.querySelector('.item-origem-id').value = this.dataset.id;
                            row.querySelector('.item-descricao-hidden').value = this.dataset.descricao;
                            row.querySelector('.item-produto').value = this.dataset.descricao;

                            // Atualiza unidade visualmente
                            const unidadeSelect = row.querySelector('.item-unidade');
                            unidadeSelect.value = this.dataset.unidade;

                            // Preço com formatação correta
                            row.querySelector('.item-preco-unitario').value = formatarMoeda(this.dataset.preco).replace('R$ ', '');

                            updateRowTotal(row);
                            closeAllLists();
                        });
                        a.appendChild(b);
                    });

                    if (data.length === 0) {
                        const c = document.createElement("div");
                        c.innerHTML = `<em style="color:red">Nenhum item encontrado.</em>`;
                        a.appendChild(c);
                    }
                })
                .catch(err => {
                    console.error("Erro busca:", err);
                    const c = document.createElement("div");
                    c.innerHTML = `<em style="color:red">Erro na busca.</em>`;
                    a.appendChild(c);
                });
        });

        // Navegação via Teclado
        input.addEventListener("keydown", function (e) {
            let x = document.getElementById(this.id + "autocomplete-list");
            if (x) x = x.getElementsByTagName("div");
            if (e.keyCode === 40) { // Seta Baixo
                currentFocus++;
                addActive(x);
            } else if (e.keyCode === 38) { // Seta Cima
                currentFocus--;
                addActive(x);
            } else if (e.keyCode === 13) { // Enter
                e.preventDefault();
                if (currentFocus > -1 && x && x[currentFocus]) x[currentFocus].click();
            }
        });

        function addActive(x) {
            if (!x) return false;
            removeActive(x);
            if (currentFocus >= x.length) currentFocus = 0;
            if (currentFocus < 0) currentFocus = (x.length - 1);
            x[currentFocus].classList.add("autocomplete-active");
        }

        function removeActive(x) {
            for (let i = 0; i < x.length; i++) x[i].classList.remove("autocomplete-active");
        }

        function closeAllLists(elmnt) {
            const x = document.getElementsByClassName("autocomplete-items");
            for (let i = 0; i < x.length; i++) {
                if (elmnt !== x[i] && elmnt !== input) x[i].parentNode.removeChild(x[i]);
            }
        }

        document.addEventListener("click", function (e) {
            closeAllLists(e.target);
        });
    }



    function applyMaskAndEvents(row, itemType) {
        const precoInput = row.querySelector('.item-preco-unitario');
        const produtoDisplay = row.querySelector('.item-produto');
        const taxaInput = row.querySelector('.item-taxa');

        new Cleave(precoInput, { numeral: true, numeralThousandsGroupStyle: 'thousand', delimiter: '.', numeralDecimalMark: ',', numeralDecimalScale: 2 });

        precoInput.addEventListener('blur', function () {
            if (this.value) {
                let floatValue = parseBRLToFloat(this.value);
                this.value = formatarMoeda(floatValue).replace('R$ ', '');
            }
            updateRowTotal(row);
        });

        [precoInput, row.querySelector('.item-qtd'), taxaInput, row.querySelector('.item-unidade')].forEach(inp => {
            inp.addEventListener('change', () => updateRowTotal(row));
            inp.addEventListener('keyup', () => updateRowTotal(row));
        });

        produtoDisplay.addEventListener('input', function () {
            row.querySelector('.item-descricao-hidden').value = this.value;
        });

        if (itemType === 'P' || itemType === 'S') {
            autocompleteCatalogo(produtoDisplay, row);
        }

        row.querySelector('.btn-remover-item').addEventListener('click', function () {
            row.remove();
            updateTotalGeral();
        });

        // Unit toggle
        const unidadeSelect = row.querySelector('.item-unidade');
        updateUnitDisplay(unidadeSelect, false);
        unidadeSelect.addEventListener('focus', function () { updateUnitDisplay(this, true); });
        unidadeSelect.addEventListener('blur', function () { setTimeout(() => updateUnitDisplay(this, false), 150); });
    }

    function updateRowTotal(row) {
        const precoUnitario = parseBRLToFloat(row.querySelector('.item-preco-unitario').value);
        const qtd = parseFloat(row.querySelector('.item-qtd').value) || 0;
        const taxa = parseFloat(row.querySelector('.item-taxa').value) || 0;
        const total = (precoUnitario * qtd) * (1 + (taxa / 100)); // Taxa adds

        row.querySelector('.item-total-linha').value = formatarMoeda(total);
        row.querySelector('.item-total-linha-hidden').value = total.toFixed(2);
        updateTotalGeral();
    }

    function updateTotalGeral() {
        let total = 0;
        document.querySelectorAll('.item-total-linha-hidden').forEach(inp => total += parseFloat(inp.value) || 0);
        document.getElementById('total-geral-display').innerText = formatarMoeda(total);
        document.getElementById('total_geral_hidden').value = total.toFixed(2);
    }

    function showNotification(isSuccess, message, id = null) {
        // Remove toast anterior se existir
        const existingToast = document.querySelector('.toast-camaleao');
        if (existingToast) existingToast.remove();

        const toast = document.createElement('div');
        toast.className = 'toast-camaleao';

        // Ícone dinâmico base
        const iconClass = isSuccess ? 'icon-ok-sign' : 'icon-remove-sign';

        // Estrutura HTML do Toast
        toast.innerHTML = `
            <div class="toast-icon"><i class="${iconClass}"></i></div>
            <div class="toast-content">
                <h5 class="toast-title">${isSuccess ? 'Sucesso' : 'Atenção'}</h5>
                <p class="toast-message">${message}</p>
            </div>
            <button class="toast-close" onclick="this.parentElement.classList.remove('show'); setTimeout(() => this.parentElement.remove(), 400);">&times;</button>
        `;

        document.body.appendChild(toast);

        // Force reflow
        void toast.offsetWidth;
        toast.classList.add('show');

        // Auto remove
        setTimeout(() => {
            if (toast.parentElement) {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 400);
            }
        }, 5000); // 5 segundos

        if (isSuccess && !message.includes('Selecione')) {
            setTimeout(() => window.location.href = 'listar_orcamentos.php', 1500);
        }
    }

    // INIT
    document.addEventListener('DOMContentLoaded', function () {
        autocompleteCliente(document.getElementById('cliente_nome'));
        // Cria primeira linha sem foco (autoFocus=false)
        createItemRow(null, 'P', false);
        // Foca explicitamente no cliente
        document.getElementById('cliente_nome').focus();

        document.getElementById('btn-adicionar-produto').addEventListener('click', (e) => { e.preventDefault(); createItemRow(null, 'P'); });
        document.getElementById('btn-adicionar-servico').addEventListener('click', (e) => { e.preventDefault(); createItemRow(null, 'S'); });
        document.getElementById('btn-adicionar-manual').addEventListener('click', (e) => { e.preventDefault(); createItemRow({ descricao: '', unidade: 'UNID', preco_referencia: 1.00 }, 'M'); });

        document.getElementById('form-orcamento').addEventListener('submit', function (event) {
            event.preventDefault();

            // Coleta manual para montar objeto JSON estruturado
            const csrfToken = document.querySelector('input[name="csrf_token"]').value;
            const clienteId = document.getElementById('cliente_id').value;
            const validade = document.getElementsByName('validade_dias')[0].value;
            const observacoes = document.getElementsByName('observacoes')[0].value;
            const anotacoesInternas = document.getElementsByName('anotacoes_internas')[0].value;
            // Corrigido para pegar valor de Total hidden que já está limpo
            const totalGeral = document.getElementById('total_geral_hidden').value;

            // Coleta itens - Lógica robusta percorrendo as linhas
            const itens = [];
            document.querySelectorAll('.item-row').forEach(row => {
                const idOrigem = row.querySelector('.item-origem-id').value;
                const tipoItem = row.querySelector('.item-tipo-item').value;
                const descricao = row.querySelector('.item-produto').value || row.querySelector('.item-descricao-hidden').value;
                const unidade = row.querySelector('.item-unidade').value;

                // Conversão segura de valores monetários e quantidade
                const qtd = parseFloat(row.querySelector('.item-qtd').value) || 0;

                // Preço e total vêm do input visível que pode ter formatação brasileira.
                // Mas, existe um hidden field para o total da linha? Sim, .item-total-linha-hidden
                // E para o preço unitário? Não, ele é um input text com máscara.
                // Vamos usar o input text e converter.
                const precoFmt = row.querySelector('.item-preco-unitario').value;
                const preco = parseFloat(precoFmt.replace('R$ ', '').replaceAll('.', '').replace(',', '.')) || 0;
                const taxa = parseFloat(row.querySelector('.item-taxa').value) || 0;

                const totalLinhaVal = parseFloat(row.querySelector('.item-total-linha-hidden').value) || (qtd * preco);

                itens.push({
                    id_origem: idOrigem,
                    tipo_item: tipoItem,
                    descricao: descricao,
                    unidade: unidade,
                    quantidade: qtd,
                    preco: preco,
                    taxa: taxa,
                    total: totalLinhaVal
                });
            });

            if (!clienteId) {
                showNotification(false, 'Selecione um cliente!');
                return;
            }
            if (itens.length === 0) {
                showNotification(false, 'Adicione items ao orçamento!');
                return;
            }

            const payload = {
                csrf_token: csrfToken,
                cliente_id: clienteId,
                validade_dias: validade,
                observacoes: observacoes,
                anotacoes_internas: anotacoesInternas,
                valor_total: totalGeral,
                itens: itens
            };

            fetch('salvar_orcamento.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showNotification(true, `Orçamento #${data.id} criado!`);
                        setTimeout(() => window.location.href = data.redirect, 1500);
                    } else {
                        showNotification(false, data.message || 'Erro ao salvar.');
                    }
                })
                .catch(e => showNotification(false, 'Erro de conexão: ' + e.message));
        });
    });

</script>