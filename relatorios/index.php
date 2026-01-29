<?php
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../config_geral.php';

// Definir nome da página para o breadcrumb
$pagina_atual = 'Relatórios';

require_once __DIR__ . '/../tema/header.php';
?>

<style>
    /* Melhorias de Responsividade para dispositivos móveis */
    @media (max-width: 767px) {
        .widget-content label {
            display: block !important;
            visibility: visible !important;
            margin-bottom: 8px !important;
            font-weight: 600 !important;
            text-align: left !important;
            float: none !important;
            width: 100% !important;
        }

        /* Ajuste para grids que não quebram corretamente em todas as versões do Matrix */
        .well [class*="span"] {
            width: 100% !important;
            margin-left: 0 !important;
            margin-bottom: 15px !important;
            float: none !important;
            display: block !important;
        }

        /* Botões em tela cheia no mobile para facilitar o toque */
        .well button,
        .well .btn {
            width: 100% !important;
            margin-right: 0 !important;
            margin-bottom: 10px !important;
            display: flex !important;
            justify-content: center;
            align-items: center;
        }

        /* Remove flexbox centralizador em mobile para evitar bugs de alinhamento */
        div[style*="display:flex;justify-content: center"] {
            display: block !important;
        }

        /* Seção de estatísticas - força labels a aparecerem se estiverem em form-horizontal */
        .form-horizontal .control-label {
            width: 100% !important;
            padding-top: 0 !important;
        }
    }
</style>

<div class="row-fluid" style="margin-top: 0">
    <div class="span4">
        <div class="widget-box">
            <div class="widget-title">
                <span class="icon">
                    <i class="fas fa-print"></i>
                </span>
                <h5>Relatórios Rápidos</h5>
            </div>
            <div class="widget-content">
                <ul style="list-style: none; margin: 0;">
                    <li style="margin-bottom: 10px;">
                        <a href="gerar_pdf.php?tipo=geral" class="btn btn-inverse btn-large" style="width: 90%;">
                            <i class="fas fa-print"></i> Todos os Orçamentos (PDF)
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="span8">
        <div class="widget-box">
            <div class="widget-title">
                <span class="icon">
                    <i class="fas fa-filter"></i>
                </span>
                <h5>Relatórios Customizáveis</h5>
            </div>
            <div class="widget-content">
                <div class="span12 well">
                    <form action="gerar_pdf.php" method="get">
                        <input type="hidden" name="tipo" value="custom">

                        <div class="span12 well">
                            <div class="span6">
                                <label for="">Data de:</label>
                                <input type="date" name="dataInicial" class="span12" />
                            </div>
                            <div class="span6">
                                <label for="">até:</label>
                                <input type="date" name="dataFinal" class="span12" />
                            </div>
                        </div>

                        <div class="span12 well" style="margin-left: 0">
                            <div class="span6">
                                <label for="">Cliente:</label>
                                <input type="text" id="cliente" class="span12" placeholder="Digite o nome do cliente" />
                                <input type="hidden" name="cliente_id" id="clienteHide" />
                            </div>
                            <div class="span6">
                                <label for="">Status:</label>
                                <select name="status" id="" class="span12">
                                    <option value="">Todos</option>
                                    <option value="Rascunho">Rascunho</option>
                                    <option value="Aguardando Aprovação">Aguardando Aprovação</option>
                                    <option value="Emitido">Emitido</option>
                                    <option value="Aprovado">Aprovado</option>
                                    <option value="Em Revisão">Em Revisão</option>
                                    <option value="Rejeitado">Rejeitado</option>
                                    <option value="Cancelado">Cancelado</option>
                                </select>
                            </div>
                        </div>

                        <div class="span12" style="display:flex;justify-content: center">
                            <button type="reset" class="button btn btn-warning" style="margin-right: 10px;">
                                <span class="button__icon"><i class="bx bx-brush-alt"></i></span>
                                <span class="button__text">Limpar</span>
                            </button>
                            <button class="button btn btn-inverse">
                                <span class="button__icon"><i class="bx bx-printer"></i></span>
                                <span class="button__text">Gerar PDF</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row-fluid">
    <div class="span12">
        <div class="widget-box">
            <div class="widget-title">
                <span class="icon">
                    <i class="fas fa-chart-bar"></i>
                </span>
                <h5>Estatísticas de Clientes</h5>
            </div>
            <div class="widget-content">
                <form action="gerar_pdf.php" method="get" class="form-horizontal">
                    <input type="hidden" name="tipo" value="estatistica">

                    <div class="span12 well" style="margin-left: 0">
                        <div class="row-fluid">
                            <div class="span3">
                                <label for="">Data de:</label>
                                <input type="date" name="dataInicial" class="span12" />
                            </div>
                            <div class="span3">
                                <label for="">até:</label>
                                <input type="date" name="dataFinal" class="span12" />
                            </div>
                            <div class="span6">
                                <label for="">Tipo de Análise:</label>
                                <select name="analise" class="span12">
                                    <option value="aprovados">Clientes que MAIS Aprovam</option>
                                    <option value="cancelados">Clientes que MAIS Cancelam</option>
                                    <option value="rejeitados">Clientes que MAIS Rejeitam</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="span12" style="display:flex;justify-content: center">
                        <button class="button btn btn-inverse">
                            <span class="button__icon"><i class="bx bx-printer"></i></span>
                            <span class="button__text">Gerar PDF</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="<?= MAPOS_URL ?>assets/js/jquery-ui/css/smoothness/jquery-ui-1.9.2.custom.css" />
<script type="text/javascript" src="<?= MAPOS_URL ?>assets/js/jquery-ui/js/jquery-ui-1.9.2.custom.js"></script>

<script type="text/javascript">
    $(document).ready(function () {
        // Autocomplete Cliente
        $("#cliente").autocomplete({
            source: "search_clientes.php",
            minLength: 1,
            response: function (event, ui) {
                if (ui.content.length === 0) {
                    var noResult = { value: "", label: "Nenhum cliente com orçamento encontrado." };
                    ui.content.push(noResult);
                }
            },
            select: function (event, ui) {
                if (ui.item.value === "") {
                    return false; // Não seleciona o item de aviso
                }
                $("#clienteHide").val(ui.item.id);
            }
        });
    });
</script>

<?php require_once __DIR__ . '/../tema/footer.php'; ?>