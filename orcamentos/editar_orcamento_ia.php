<?php
// editar_orcamento_ia.php - VERSÃO COM INTEGRAÇÃO DE IA + BUSCA DE PRODUTOS
require '../conexao.php';

// Função auxiliar UTF-8 (Compatível com PHP 8.2+)
function utf8_converter($array)
{
    if (!is_array($array))
        return [];
    array_walk_recursive($array, function (&$item, $key) {
        if (is_string($item)) {
            if (function_exists('mb_detect_encoding') && function_exists('mb_convert_encoding')) {
                if (!mb_detect_encoding($item, 'UTF-8', true)) {
                    $item = mb_convert_encoding($item, 'UTF-8', 'ISO-8859-1');
                }
            }
        }
    });
    return $array;
}

// Carregar Configurações e Arrays Auxiliares
require_once '../config_geral.php';
require_once 'config_ia.php'; // Garante que a constante esteja disponível

// Verificação de Segurança (Feature Flag)
if (!defined('IA_ENABLED') || !IA_ENABLED) {
    echo "<div style='font-family:sans-serif; text-align:center; padding:50px; color:#555;'>
            <h1>Funcionalidade Desativada</h1>
            <p>A edição com Inteligência Artificial não está ativa no momento.</p>
            <a href='listar_orcamentos.php' style='color:#007bff; text-decoration:none;'>&larr; Voltar</a>
          </div>";
    exit;
}

$tabela_medidas_path = MAPOS_ROOT_PATH . 'assets/json/tabela_medidas.json';
$unidades_medida = ['UN' => 'UNIDADE', 'CX' => 'CAIXA', 'PC' => 'PEÇA', 'KG' => 'QUILOGRAMA', 'M' => 'METRO'];
if (file_exists($tabela_medidas_path)) {
    $tm = json_decode(file_get_contents($tabela_medidas_path), true);
    $unidades_medida = [];
    foreach ($tm['medidas'] as $m)
        $unidades_medida[$m['sigla']] = $m['descricao'];
}
$unidades_servico = ['HR' => 'HORA', 'DIA' => 'DIA', 'MES' => 'MÊS', 'SV' => 'SERVIÇO', 'UN' => 'UNIDADE', 'KM' => 'QUILÔMETRO', 'VERBA' => 'VERBA'];

// Busca Dados do Orçamento
$orcamento_id = $_GET['id'] ?? null;
if (!$orcamento_id)
    die("ID inválido.");
$stmt = $pdo->prepare("SELECT o.*, c.nomeCliente as cliente_nome, c.telefone FROM mod_orc_orcamentos o LEFT JOIN clientes c ON o.cliente_id = c.idClientes WHERE o.id = :id");
$stmt->execute([':id' => $orcamento_id]);
$orcamento_dados = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$orcamento_dados)
    die("Orçamento não encontrado.");

$stmtItens = $pdo->prepare("SELECT * FROM mod_orc_itens WHERE orcamento_id = :id ORDER BY id ASC");
$stmtItens->execute([':id' => $orcamento_id]);
$itens_dados = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

// JSONs para o Frontend
$orcamento_edit_json = json_encode($orcamento_dados) ?: '{}';
$itens_edit_json = json_encode($itens_dados) ?: '[]';
$unidades_medida_json = json_encode(utf8_converter($unidades_medida), JSON_UNESCAPED_UNICODE) ?: '{}';
$unidades_servico_json = json_encode(utf8_converter($unidades_servico), JSON_UNESCAPED_UNICODE) ?: '{}';

// Define a página atual para o breadcrumb do header.php
$pagina_atual = 'Análise IA';

// INCLUINDO O TOPO (HEADER) COM O CAMINHO CORRIGIDO
include '../tema/header.php';

// ---------------------------------------------------------------------------------------------------
// 🎨 PREPARAÇÃO DO TEMA CAMALEÃO (COPIADO DO NOVO_ORCAMENTO.PHP)
// ---------------------------------------------------------------------------------------------------
$temaAtual = $configuration['app_theme'] ?? 'white';
$isDark = in_array($temaAtual, ['puredark', 'darkviolet', 'darkorange']);

// Configuração Base (Fundo e Texto)
$toastBg = $isDark ? '#2E363F' : '#ffffff';
$toastColor = $isDark ? '#ffffff' : '#333333';
$toastShadow = $isDark ? '0 4px 15px rgba(0,0,0,0.5)' : '0 4px 15px rgba(0,0,0,0.1)';

// Configuração de Acento (Cor da Borda e Ícone)
switch ($temaAtual) {
    case 'darkviolet':
        $accentColor = '#9370DB';
        break;
    case 'whitegreen':
        $accentColor = '#28b779';
        break;
    case 'darkorange':
    case 'puredark':
        $accentColor = '#fc9d0f';
        break;
    case 'whiteblack':
        $accentColor = '#2E363F';
        break;
    default:
        $accentColor = '#27a9e3';
        break;
}
?>

<style>
    /* Estilos IA & Layout */
    #content {
        background: none !important;
        padding-bottom: 0 !important;
    }

    /* 🎨 CORREÇÃO DE VISIBILIDADE DE INPUTS EM TEMAS ESCUROS */
    <?php if (isset($isDark) && $isDark): ?>
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

        /* Hover das linhas da tabela para evitar fundo preto em temas dark */
        #tabela-itens tbody tr:hover>td,
        #tabela-itens tbody tr:hover>th {
            background-color: #f5f5f5 !important;
            color: #333333 !important;
        }

        #tabela-itens tbody tr:hover input,
        #tabela-itens tbody tr:hover select {
            background-color: #ffffff !important;
            color: #333333 !important;
        }

    <?php endif; ?>

    .widget-box.card-like {
        background: #fff !important;
        border-radius: 8px !important;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
        margin-top: 20px !important;
        max-width: 1300px;
        margin-left: auto;
        margin-right: auto;
    }

    .widget-title.card-header-ia {
        background-color: #6f42c1 !important;
        color: #fff !important;
        padding: 12px 20px !important;
        display: flex !important;
        align-items: center;
        justify-content: space-between;
        border-radius: 8px 8px 0 0;
    }

    .btn-ia {
        background-color: #fff !important;
        color: #6f42c1 !important;
        border: 2px solid #fff !important;
        border-radius: 50px !important;
        padding: 8px 25px !important;
        font-weight: bold !important;
        transition: all 0.3s;
    }

    .btn-ia:hover {
        background-color: #6f42c1 !important;
        color: #fff !important;
        border-color: #fff !important;
    }

    .btn-ia:disabled {
        background-color: #ccc !important;
        color: #666 !important;
        border-color: #ccc !important;
        cursor: not-allowed;
    }

    .col-ia {
        background-color: #f0ebff !important;
        /* Slightly more purple tint */
        font-weight: bold !important;
        color: #4b2c82 !important;
        /* Much darker purple for better readability */
        text-align: center !important;
        font-size: 1.1em !important;
    }

    .ia-loading {
        animation: pulse 1.5s infinite;
        color: #4b2c82 !important;
        font-weight: bold !important;
        font-style: italic;
        font-size: 0.9em;
    }

    @keyframes pulse {
        0% {
            opacity: 0.5;
        }

        50% {
            opacity: 1;
        }

        100% {
            opacity: 0.5;
        }
    }

    /* Sub-headers das seções */
    .card-header-section {
        background-color: #f8f9fc !important;
        border-bottom: 1px solid #e3e6f0 !important;
        padding: 5px 15px !important;
        display: flex !important;
        align-items: center !important;
        border-radius: 4px;
        height: 35px !important;
    }

    .card-header-blue-section {
        background-color: #007bff !important;
        color: #fff !important;
        border: none !important;
    }

    .card-header-blue-section h5,
    .card-header-blue-section i {
        color: #fff !important;
    }

    .card-header-section h5 {
        margin: 0 !important;
        font-size: 14px !important;
        font-weight: bold !important;
        line-height: 35px !important;
    }

    .card-header-section .icon {
        padding: 0 10px 0 0 !important;
        display: flex !important;
        align-items: center !important;
        height: 35px !important;
        border: NONE !important;
    }

    .card-header-section .icon i {
        margin-top: 0 !important;
    }

    /* Inputs */
    input[type="text"],
    input[type="number"],
    select,
    textarea {
        border-radius: 4px !important;
        border: 1px solid #d1d3e2 !important;
        padding: 4px 10px !important;
        height: 30px !important;
        width: 100%;
        box-sizing: border-box;
    }

    .table th,
    .table td {
        vertical-align: middle !important;
    }

    /* Toast Notification */
    #toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
    }

    .toast {
        min-width: 250px;
        background: #fff;
        padding: 15px 20px;
        border-radius: 4px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        opacity: 0;
        transition: opacity 0.3s, transform 0.3s;
        transform: translateX(100%);
        border-left: 5px solid #ccc;
    }

    .toast.show {
        opacity: 1;
        transform: translateX(0);
    }

    .toast.success {
        border-left-color: #2ecc71;
    }

    .toast.error {
        border-left-color: #e74c3c;
    }

    .toast-icon {
        margin-right: 10px;
        font-size: 1.2em;
    }

    /* TOAST CAMALEÃO DINÂMICO */
    .toast-camaleao {
        position: fixed;
        top: 60px;
        right: 20px;
        width: 300px;
        background-color:
            <?= $toastBg ?>
        ;
        color:
            <?= $toastColor ?>
        ;
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

    .toast-icon-cam {
        font-size: 20px;
        color:
            <?= $accentColor ?>
        ;
    }

    .toast-content-cam {
        flex: 1;
    }

    .toast-title-cam {
        margin: 0 0 5px 0;
        font-weight: 700;
        font-size: 14px;
        color:
            <?= $toastColor ?>
        ;
    }

    .toast-message-cam {
        font-size: 13px;
        opacity: 0.9;
        line-height: 1.4;
        margin: 0;
    }

    .toast-close-cam {
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

    /* Autocomplete */
    .autocomplete-items {
        position: absolute;
        border: 1px solid #d1d3e2;
        z-index: 1050;
        top: 100%;
        left: 0;
        right: 0;
        background-color: #fff;
        max-height: 200px;
        overflow-y: auto;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        border-radius: 0 0 4px 4px;
    }

    .autocomplete-items div {
        padding: 10px;
        cursor: pointer;
        border-bottom: 1px solid #eee;
    }

    .autocomplete-items div:hover {
        background-color: #e9e9e9;
    }

    /* Botão Voltar Padronizado */
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
        justify-content: center !important;
        gap: 8px !important;
    }

    .btn-back-custom:hover {
        background-color: #fff !important;
        color: #0d6efd !important;
    }

    /* Botão IA Padronizado */
    .btn-ia-custom {
        border: 2px solid #fff !important;
        color: #fff !important;
        border-radius: 50px !important;
        padding: 8px 25px !important;
        font-weight: bold !important;
        background-color: #563d7c !important;
        /* Roxo mais escuro para contraste */
        /* background-color: #6f42c1 !important; Original */
        /* ROXO */
        transition: all 0.3s ease !important;
        text-decoration: none !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 8px !important;
        margin-left: 10px !important;
        /* Margem esquerda padrão desktop */
    }

    .btn-ia-custom:hover {
        background-color: #fff !important;
        color: #6f42c1 !important;
    }

    /* Dropdown do Menu Adicionar Item - Fonte Maior (Igual editar_orcamento.php) */
    .dropdown-menu li a {
        font-size: 14px !important;
        padding: 10px 15px !important;
    }

    /* --- RESPONSIVIDADE MOBILE COM SCROLL HORIZONTAL --- */
    @media (max-width: 768px) {
        #content {
            padding: 10px !important;
            padding-bottom: 60px !important;
        }

        .widget-box.card-like {
            margin-top: 10px !important;
        }

        /* Cabeçalho do Card */
        .widget-title.card-header-ia {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }

        .widget-title.card-header-ia .buttons {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn-ia,
        .widget-title.card-header-ia .btn,
        .widget-title.card-header-ia .btn-inverse {
            width: 100% !important;
            margin: 0 !important;
            margin-bottom: 10px !important;
            /* Espaçamento entre botões */
            display: block;
            box-sizing: border-box;
            text-align: center;
            white-space: normal;
            /* Permite quebra de linha se texto for muito longo */
            height: auto !important;
            /* Permite crescer se quebrar linha */
            padding: 10px !important;
        }

        /* Formulário Cabeçalho (Empilhado) */
        .row-fluid .span6,
        .row-fluid .span3,
        .row-fluid .span12 {
            width: 100% !important;
            margin-left: 0 !important;
            display: block;
            margin-bottom: 10px;
        }

        /* CORREÇÃO DOS LABELS DE OBSERVAÇÕES */
        /* Força exibição dos labels dentro dos spans */
        .row-fluid .span6 label,
        .row-fluid .span3 label {
            display: block !important;
            color: #333;
            margin-bottom: 5px;
            visibility: visible !important;
        }

        /* Wrapper para Scroll Horizontal */
        .table-responsive-wrapper {
            display: block;
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border: 1px solid #ddd;
        }

        /* Força a tabela a manter sua largura mínima para ativar scroll */
        #tabela-itens {
            min-width: 800px;
            /* Garante que a tabela não encolha demais */
        }

        /* Botões de footer */
        .form-actions {
            padding: 10px;
        }

        .form-actions button {
            width: 100% !important;
        }

        /* Totais e Botões de Ação Inferiores */
        /* Seleciona a div flex que contem os botões (esquerda) e o total (direita) */
        .action-buttons-container {
            flex-direction: column !important;
            gap: 15px;
        }

        /* Container dos botões 'Adicionar' e 'Aplicar Tudo' */
        .btn-group-wrapper {
            width: 100%;
            flex-direction: column;
        }

        .widget-content .btn-group,
        .widget-content .btn-group button {
            width: 100% !important;
        }

        #btn-aplicar-tudo {
            width: 100% !important;
            margin-top: 10px !important;
            /* Espaço extra */
            position: static !important;
        }

        /* Container do Total */
        .total-container {
            text-align: left !important;
            width: 100%;
            margin-top: 15px !important;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }

        .total-container h3 {
            font-size: 1.5em !important;
            margin: 0;
            text-align: left;
        }

        /* Ajuste da Célula IA na Tabela para evitar sobreposição */
        td.col-ia {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            min-height: 40px;
            padding: 8px !important;
        }

        .ia-val {
            white-space: nowrap !important;
        }

        /* REGRAS MOBILE PARA BOTÕES PADRONIZADOS */
        .btn-ia-custom,
        .btn-back-custom {
            width: 100% !important;
            margin: 0 !important;
            margin-bottom: 10px !important;
            display: flex !important;
            justify-content: center !important;
            box-sizing: border-box;
        }

        /* Remove posicionamento absoluto no mobile para fluir layout */
        /* E REMOVE !IMPORTANT NO DISPLAY PARA O JS CONTROLAR */
        td.col-ia .btn-apply-ia {
            position: static !important;
            margin-left: 10px;
        }
    }
</style>

<div class="row-fluid" style="margin-top:0">
    <div class="span12">
        <div class="widget-box card-like">
            <div class="widget-title card-header-ia">
                <div style="display: flex; align-items: center;">
                    <span class="icon" style="margin-right:10px;"><i class="icon-magic icon-white"></i></span>
                    <h5 style="margin:0; color:white;">Orçamento #<?= $orcamento_id ?> - Edição com IA <i
                            class="icon-star icon-white"></i></h5>
                </div>
                <!-- BOTÕES DO HEADER (Sem aplicar tudo) -->
                <div class="buttons">
                    <button type="button" id="btn-consultar-ia" class="btn-ia-custom" style="margin-left:0 !important;">
                        ✨ ANALISAR PREÇOS COM IA
                    </button>
                    <!-- Botão Ver lista Padronizado -->
                    <!-- Botão Ver lista Padronizado -->
                    <a href="listar_orcamentos.php" class="btn-back-custom" style="margin-left: 10px;">
                        <i class="icon-list"></i> Ver lista
                    </a>
                    <!-- Botão Ver detalhes Padronizado -->
                    <a href="ver_detalhes.php?id=<?= $orcamento_id ?>" class="btn-back-custom"
                        style="margin-left: 10px;">
                        <i class="icon-eye-open"></i> Ver detalhes
                    </a>
                </div>
            </div>

            <div class="widget-content">
                <form class="form-horizontal" id="form-orcamento">
                    <input type="hidden" name="id" value="<?= $orcamento_id ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <!-- Cabeçalho Simples -->
                    <div class="row-fluid">
                        <div class="span6">
                            <label><strong>Cliente:</strong></label>
                            <input type="text" value="<?= htmlspecialchars($orcamento_dados['cliente_nome']) ?>"
                                readonly style="background:#f5f5f5;">
                        </div>
                        <div class="span3">
                            <label><strong>Status:</strong></label>
                            <select name="status">
                                <?php
                                $stats = ["Rascunho", "Emitido", "Aguardando Aprovação", "Em Revisão", "Aprovado", "Rejeitado", "Cancelado"];
                                foreach ($stats as $s)
                                    echo "<option value='$s' " . ($orcamento_dados['status'] == $s ? 'selected' : '') . ">$s</option>";
                                ?>
                            </select>
                        </div>
                        <div class="span3">
                            <label><strong>Validade (Dias):</strong></label>
                            <input type="number" name="validade_dias" value="<?= $orcamento_dados['validade_dias'] ?>">
                        </div>
                    </div>

                    <!-- Tabela de Itens -->
                    <div class="widget-title card-header-section card-header-blue-section"
                        style="margin-top:20px; margin-bottom: 0;">
                        <span class="icon"><i class="icon-list icon-white"></i></span>
                        <h5>Itens do Orçamento</h5>
                    </div>
                    <table class="table table-bordered" id="tabela-itens">
                        <thead>
                            <tr>
                                <th style="width: 25%;">Descrição</th>
                                <th style="width: 10%; text-align:center;">Unid.</th>
                                <th style="width: 10%; text-align:center;">Qtd.</th>
                                <th style="width: 12%; text-align:right;">Preço (R$)</th>
                                <th style="width: 10%; text-align:center;">Taxa (%)</th>
                                <th style="width: 12%; text-align:right;">Total (R$)</th>
                                <th style="width: 8%; text-align:center;">Ações</th>
                                <th
                                    style="width: 13%; text-align:center; background-color:#d0d7ff; border-left:2px solid #6f42c1; color: #333 !important; font-weight: bold !important;">
                                    Sugestão IA</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>

                    <!-- Botões de Ação e Totais (Refatorado para melhor controle Mobile) -->
                    <div class="action-buttons-container"
                        style="display: flex; justify-content: space-between; margin-top: 100px; margin-bottom: 80px; align-items:flex-start;">

                        <!-- Wrapper dos Botões da Esquerda -->
                        <div class="btn-group-wrapper" style="display:flex; flex-direction:column; gap:10px;">
                            <div class="btn-group">
                                <button class="btn btn-success dropdown-toggle" data-toggle="dropdown"><i
                                        class="icon-plus"></i> Adicionar Item <span class="caret"></span></button>
                                <ul class="dropdown-menu">
                                    <li><a href="#" id="btn-add-produto">Adicionar Produto</a></li>
                                    <li><a href="#" id="btn-add-servico">Adicionar Serviço</a></li>
                                    <li><a href="#" id="btn-add-manual">Item Manual / Avulso</a></li>
                                </ul>
                            </div>

                            <!-- Botão Aplicar Tudo (Agora em bloco separado abaixo do dropdown) -->
                            <button type="button" id="btn-aplicar-tudo" class="btn btn-info" style="display:none;">
                                <i class="icon-ok-sign"></i> Aplicar Todas Sugestões
                            </button>
                        </div>

                        <!-- Total (Direita) -->
                        <div class="total-container" style="text-align: right; padding-right: 15px;">
                            <h3
                                style="margin:0; font-size: 1.8em !important; color: #333; font-weight: bold !important;">
                                Total Geral: <span id="total-geral-txt" style="color: #28a745 !important;">R$
                                    0,00</span>
                            </h3>
                            <input type="hidden" id="total_geral_hidden" value="0.00">
                        </div>
                    </div>

                    <!-- Rodapé com Detalhes -->
                    <div class="row-fluid" style="margin-top: 20px;">
                        <div class="span6">
                            <label>Observações</label>
                            <textarea name="observacoes"
                                rows="3"><?= htmlspecialchars($orcamento_dados['observacoes']) ?></textarea>
                        </div>
                        <div class="span6">
                            <label>Anotações Internas</label>
                            <textarea name="anotacoes_internas"
                                rows="3"><?= htmlspecialchars($orcamento_dados['anotacoes_internas']) ?></textarea>
                        </div>
                    </div>

                    <div class="form-actions" style="margin-top:30px; text-align:center; background:transparent;">
                        <!-- BOTÃO SALVAR REESTILIZADO -->
                        <button type="submit" class="btn btn-large"
                            style="width: 300px; padding:15px; font-size:16px; background-color:#6f42c1; color:white; border:none; border-radius:4px;">
                            ✨ SALVAR COM IA
                        </button>
                        <input type="hidden" name="data_criacao"
                            value="<?= str_replace(' ', 'T', substr($orcamento_dados['data_criacao'], 0, 16)) ?>">
                    </div>
                </form>
            </div>
            <div id="toast-container"></div>
        </div>
    </div>
</div>

<?php include '../tema/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cleave.js/1.6.0/cleave.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- SWEETALERT2 -->

<script>
    const ITENS_DATA = <?= $itens_edit_json ?>;
    const ORC_DATA = <?= $orcamento_edit_json ?>;
    const UNID_MEDIDA = <?= $unidades_medida_json ?>;
    const UNID_SERVICO = <?= $unidades_servico_json ?>;

    function showNotification(isSuccess, message, reload = false) {
        // Remove toast anterior se existir
        const existingToast = document.querySelector('.toast-camaleao');
        if (existingToast) existingToast.remove();

        const toast = document.createElement('div');
        toast.className = 'toast-camaleao';

        const iconClass = isSuccess ? 'icon-ok-sign' : 'icon-remove-sign';

        toast.innerHTML = `
            <div class="toast-icon-cam"><i class="${iconClass}"></i></div>
            <div class="toast-content-cam">
                <h5 class="toast-title-cam">${isSuccess ? 'Sucesso' : 'Atenção'}</h5>
                <p class="toast-message-cam">${message}</p>
            </div>
            <button class="toast-close-cam" onclick="this.parentElement.classList.remove('show'); setTimeout(() => this.parentElement.remove(), 400);">&times;</button>
        `;

        document.body.appendChild(toast);
        void toast.offsetWidth;
        toast.classList.add('show');

        // Auto remove
        setTimeout(() => {
            if (toast.parentElement) {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 400);
            }
        }, 5000);

        if (reload) {
            setTimeout(() => window.location.href = `ver_detalhes.php?id=${ORC_DATA.id}`, 1500);
        }
    }

    function escapeHtml(text) {
        if (!text) return text;
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function fmtMoney(v) { return 'R$ ' + parseFloat(v).toLocaleString('pt-BR', { minimumFractionDigits: 2 }); }
    function parseMoney(v) { return parseFloat(v.toString().replace(/[R$\s\.]/g, '').replace(',', '.')) || 0; }

    // Cria Linha na Tabela
    function createRow(item = null, type = 'P') {
        const row = document.createElement('tr');
        row.className = 'item-row';
        row.dataset.type = type; // P=Produto, S=Servico, M=Manual
        // Adiciona um index temporário para controle da IA se não existir
        if (item && item.id) row.dataset.index = item.id;        else row.dataset.index = Date.now() + Math.random().toString(36).substr(2, 5);

        const desc = item ? item.descricao : '';
        const val = item ? item.preco_unitario : 0;
        const qtd = item ? item.quantidade : 1;
        const taxa = item ? (item.taxa || 0) : 0; // Taxa padrão 0
        const unid = item ? item.unidade : 'UN';
        const idOrigem = item ? (item.produto_id || item.servico_id || '') : '';
        if (item && item.tipo_item) row.dataset.type = item.tipo_item;

        // Monta Select de Unidades Baseada no Tipo
        let options = '';
        const sourceUnits = (row.dataset.type === 'S') ? UNID_SERVICO : UNID_MEDIDA;
        // Para manual, junta tudo
        const finalUnits = (row.dataset.type === 'M') ? { ...UNID_MEDIDA, ...UNID_SERVICO } : sourceUnits;

        for (let k in finalUnits) options += `<option value="${k}" ${k == unid ? 'selected' : ''}>${k}</option>`;

        row.innerHTML = `
            <td>
                <div style="position:relative;">
                    <input type="text" class="item-desc" value="${escapeHtml(desc)}" placeholder="${row.dataset.type === 'P' ? 'Buscar Produto...' : (row.dataset.type === 'S' ? 'Buscar Serviço...' : 'Descrição...')}" autocomplete="off">
                    <input type="hidden" name="id_origem[]" value="${idOrigem}">
                    <input type="hidden" name="tipo_item[]" value="${row.dataset.type}">
                </div>
            </td>
            <td><select name="unidade[]" class="span12">${options}</select></td>
            <td><input type="number" name="qtd[]" value="${qtd}" class="item-qtd span12" min="1" autocomplete="off"></td>
            <td><input type="text" name="preco[]" value="${parseFloat(val).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}" class="item-preco span12" autocomplete="off"></td>
            <td><input type="number" name="taxa[]" value="${parseFloat(taxa).toFixed(2)}" class="item-taxa span12" step="0.01" autocomplete="off"></td>
            <td><input type="text" class="item-total span12" readonly style="background:#eee;"></td>
            <td style="text-align:center;"><button type="button" class="btn btn-danger btn-mini btn-remove"><i class="icon-trash"></i></button></td>
            <td class="col-ia" style="position:relative; vertical-align:middle;">
                <span class="ia-val">---</span> <button type="button" class="btn btn-mini btn-info btn-apply-ia" style="display:none; position:absolute; right:5px; top:12px;" title="Aplicar"><i class="icon-ok"></i></button>
            </td>
        `;

        document.querySelector('#tabela-itens tbody').appendChild(row);

        // Lógicas
        const descInput = row.querySelector('.item-desc');
        if (row.dataset.type !== 'M') setupAutocomplete(descInput, row);

        new Cleave(row.querySelector('.item-preco'), { numeral: true, numeralDecimalMark: ',', delimiter: '.', numeralDecimalScale: 2 });

        row.querySelectorAll('input, select').forEach(el => el.addEventListener('change', updateTotals));
        row.querySelectorAll('input').forEach(el => el.addEventListener('keyup', updateTotals)); // Keyup para calculo real-time

        // Validação de campo vazio no blur (replica comportamento do editar_orcamento.php)
        const taxaInput = row.querySelector('.item-taxa');
        taxaInput.addEventListener('blur', function() {
            if (this.value === '' || isNaN(parseFloat(this.value))) {
                this.value = '0.00';
            } else {
                this.value = parseFloat(this.value).toFixed(2);
            }
            updateTotals();
        });

        const qtdInput = row.querySelector('.item-qtd');
        qtdInput.addEventListener('blur', function() {
            if (this.value === '' || parseFloat(this.value) < 1) {
                this.value = '1';
            }
            updateTotals();
        });

        row.querySelector('.btn-remove').addEventListener('click', () => { row.remove(); updateTotals(); });

        const btnApply = row.querySelector('.btn-apply-ia');
        if (btnApply) {
            btnApply.addEventListener('click', function () {
                const sugg = row.querySelector('.ia-val').textContent;
                row.querySelector('.item-preco').value = sugg.replace('R$ ', '');

                // Dispara o evento 'change' ou 'keyup' manualmente para atualizar os totais
                const event = new Event('change');
                row.querySelector('.item-preco').dispatchEvent(event);

                this.style.display = 'none';
            });
        }

        updateTotals();
    }

    // Autocomplete (Simplificado)
    function setupAutocomplete(input, row) {
        input.addEventListener('input', function () {
            const val = this.value;
            closeLists();
            if (!val) return;

            const tipo = row.dataset.type === 'P' ? 'produto' : 'servico';
            const list = document.createElement('div');
            list.className = 'autocomplete-items';
            this.parentNode.appendChild(list);

            fetch(`buscar_item.php?tipo=${tipo}&termo=${encodeURIComponent(val)}`)
                .then(r => r.json())
                .then(data => {
                    data.forEach(item => {
                        const div = document.createElement('div');
                        div.innerHTML = `<strong>${escapeHtml(item.descricao)}</strong> - R$ ${item.preco}`;
                        div.addEventListener('click', () => {
                            input.value = item.descricao;
                            row.querySelector('input[name="id_origem[]"]').value = item.id;
                            row.querySelector('.item-preco').value = parseFloat(item.preco).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                            row.querySelector('select[name="unidade[]"]').value = item.unidade || (tipo === 'P' ? 'UN' : 'HR');
                            closeLists();
                            updateTotals();
                        });
                        list.appendChild(div);
                    });
                });
        });
    }

    function closeLists() { document.querySelectorAll('.autocomplete-items').forEach(x => x.remove()); }
    document.addEventListener('click', closeLists);

    function updateTotals() {
        let geral = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            const preco = parseMoney(row.querySelector('.item-preco').value);
            const qtd = parseFloat(row.querySelector('.item-qtd').value) || 0;
            const taxa = parseFloat(row.querySelector('.item-taxa').value) || 0; // Captura a taxa

            // Cálculo com Taxa: (Preco * Qtd) * (1 + Taxa/100)
            const subtotal = (preco * qtd) * (1 + (taxa / 100));

            row.querySelector('.item-total').value = fmtMoney(subtotal);
            geral += subtotal;
        });
        document.getElementById('total-geral-txt').textContent = fmtMoney(geral);
        document.getElementById('total_geral_hidden').value = geral.toFixed(2);
    }

    // Ação Botões Iniciais
    document.getElementById('btn-add-produto').addEventListener('click', (e) => { e.preventDefault(); createRow(null, 'P'); });
    document.getElementById('btn-add-servico').addEventListener('click', (e) => { e.preventDefault(); createRow(null, 'S'); });
    document.getElementById('btn-add-manual').addEventListener('click', (e) => { e.preventDefault(); createRow(null, 'M'); });

    // Carga Inicial
    document.addEventListener('DOMContentLoaded', () => {
        // Wrap da tabela para responsividade (Scroll Horizontal)
        const table = document.getElementById('tabela-itens');
        if (table && !table.parentElement.classList.contains('table-responsive-wrapper')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive-wrapper';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }

        if (ITENS_DATA.length > 0) ITENS_DATA.forEach(i => createRow(i, i.tipo_item || 'P'));
        else createRow(null, 'P');
    });

    // IA CONSULTA
    document.getElementById('btn-consultar-ia').addEventListener('click', async function () {
        const btn = this;
        const itensParaIa = [];
        const rows = document.querySelectorAll('.item-row');

        // Limpa estados
        rows.forEach(r => {
            if (r.querySelector('.ia-val')) {
                r.querySelector('.ia-val').innerHTML = '';
                r.querySelector('.btn-apply-ia').style.display = 'none';
            }
        });

        // Coleta TODOS os itens 
        rows.forEach((r, idx) => {
            const desc = r.querySelector('.item-desc').value;
            const unid = r.querySelector('select[name="unidade[]"]').value; // Captura Unidade atual
            const idxRow = r.dataset.index; // Usa o index real da row
            if (desc.length > 2) {
                itensParaIa.push({ index: idxRow, descricao: desc, unidade: unid });
                r.querySelector('.ia-val').innerHTML = '<span class="ia-loading">Consultando...</span>';
            }
        });

        if (itensParaIa.length === 0) {
            showNotification(false, 'Adicione itens para consultar.');
            return;
        }

        btn.disabled = true;
        try {
            const resp = await fetch('consultar_ia.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ itens: itensParaIa })
            });
            const data = await resp.json();

            if (data.success) {
                let sugeriu = false;
                data.sugestoes.forEach(sug => {
                    // Match DIRETO pelo ID DE REFERÊNCIA (Evita confusão por nome similar)
                    const targetRow = document.querySelector(`.item-row[data-index="${sug.id_ref}"]`);
                    if (targetRow) {
                        const elVal = targetRow.querySelector('.ia-val');
                        const elBtn = targetRow.querySelector('.btn-apply-ia');
                        if (elVal) {
                            elVal.textContent = fmtMoney(sug.preco_sugerido);
                            if (elBtn) elBtn.style.display = 'inline-block';
                            sugeriu = true;
                        }
                    }
                });

                if (sugeriu) {
                    showNotification(true, 'Análise concluída com sucesso!');
                    document.getElementById('btn-aplicar-tudo').style.display = 'inline-block';
                } else {
                    showNotification(false, 'IA não retornou sugestões claras.');
                }

            } else if (data.error_code === 'QUOTA_EXCEEDED') {
                showNotification(false, 'Limite de uso da IA atingido. Tente novamente em breve.');
            } else {
                showNotification(false, data.message || 'Erro inesperado na IA.');
            }
        } catch (e) {
            console.error(e);
            showNotification(false, 'Falha na conexão com a IA.');
        }
        btn.disabled = false;
    });

    // APLICAR TUDO COM SWEETALERT
    document.getElementById('btn-aplicar-tudo').addEventListener('click', function () {
        Swal.fire({
            title: 'Aplicar todas as sugestões?',
            text: "Isso substituirá os preços atuais pelos sugeridos pela IA.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#6f42c1',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sim, aplicar!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                document.querySelectorAll('.item-row').forEach(r => {
                    const suggEl = r.querySelector('.ia-val');
                    if (suggEl && suggEl.textContent.includes('R$')) {
                        const val = suggEl.textContent.replace('R$ ', '');
                        r.querySelector('.item-preco').value = val;
                        const btnInd = r.querySelector('.btn-apply-ia');
                        if (btnInd) btnInd.style.display = 'none';
                    }
                });
                updateTotals();
                showNotification(true, 'Preços atualizados!');
                this.style.display = 'none';
            }
        })
    });

    // Salvar
    document.getElementById('form-orcamento').addEventListener('submit', async function (e) {
        e.preventDefault();

        // Coleta CSRF Token
        const csrfToken = document.querySelector('input[name="csrf_token"]').value;

        const payload = {
            id: this.id.value,
            csrf_token: csrfToken, // <--- ADICIONADO
            cliente_id: ORC_DATA.cliente_id,
            status: this.status.value,
            validade_dias: this.validade_dias.value,
            data_criacao: this.data_criacao.value,
            observacoes: this.observacoes.value, // Adicionado campo
            anotacoes_internas: this.anotacoes_internas.value, // Adicionado campo 
            valor_total: document.getElementById('total_geral_hidden').value,
            itens: []
        };

        document.querySelectorAll('.item-row').forEach(r => {
            payload.itens.push({
                tipo_item: r.dataset.type,
                id_origem: r.querySelector('input[name="id_origem[]"]').value,
                descricao: r.querySelector('.item-desc').value,
                unidade: r.querySelector('select[name="unidade[]"]').value,
                quantidade: r.querySelector('.item-qtd').value,
                taxa: r.querySelector('.item-taxa').value, // Envia Taxa capturada
                preco: parseMoney(r.querySelector('.item-preco').value),
                total: parseMoney(r.querySelector('.item-total').value)          });
        });

        fetch('salvar_orcamento_ia.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showNotification(true, 'Orçamento salvo com sucesso!', true);
                } else {
                    showNotification(false, data.message || 'Erro ao salvar orçamento.');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showNotification(false, 'Erro ao processar a requisição.');
            });
    });


</script>
```