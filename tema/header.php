<?php
// header.php
// Clone do Layout do Mapos (Matrix Admin)

// Verifica sessão
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Configuração de Caminhos
// O MAPOS_URL Já é definido em conexão -> config_geral.php
if (!defined('MAPOS_URL')) {
    define('MAPOS_URL', BASE_URL . '../../'); // Tenta subir dois níveis se não definido
}

// Carregar configurações do Banco de Dados do Mapos
try {
    if (!isset($pdo)) {
        require_once __DIR__ . '/../conexao.php';
    }

    // Busca configs
    $stmt_config = $pdo->query("SELECT config, valor FROM configuracoes");
    $configuration = [];
    while ($row = $stmt_config->fetch(PDO::FETCH_ASSOC)) {
        $configuration[$row['config']] = $row['valor'];
    }
    // Defaults se falhar
    if (empty($configuration['app_name']))
        $configuration['app_name'] = 'Map-OS';
    if (empty($configuration['app_theme']))
        $configuration['app_theme'] = 'white';

} catch (Exception $e) {
    $configuration = [
        'app_name' => 'Map-OS (Erro DB)',
        'app_theme' => 'white'
    ];
}

// CARREGAR TEMA DO MÓDULO (SOBRESCREVE O DO MAPOS)
$themeConfigFile = __DIR__ . '/config_theme.json';
if (file_exists($themeConfigFile)) {
    $themeConfig = json_decode(file_get_contents($themeConfigFile), true);
    if (isset($themeConfig['app_theme'])) {
        $configuration['app_theme'] = $themeConfig['app_theme'];
    }
}

// Detecta se o tema atual é dark (para overrides de visibilidade)
$isDark = in_array($configuration['app_theme'], ['puredark', 'darkviolet', 'darkorange']);

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title><?= $configuration['app_name'] ?> - Módulo Orçamentos</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" type="image/png" href="<?= MAPOS_URL ?>assets/img/favicon.png" />

    <!-- MAPOS ASSETS (Matrix Admin / Bootstrap 2.3.2-ish) -->
    <link rel="stylesheet" href="<?= MAPOS_URL ?>assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="<?= MAPOS_URL ?>assets/css/bootstrap-responsive.min.css" />
    <link rel="stylesheet" href="<?= MAPOS_URL ?>assets/css/matrix-style.css" />
    <link rel="stylesheet" href="<?= MAPOS_URL ?>assets/css/matrix-media.css" />
    <link href="<?= MAPOS_URL ?>assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link rel="stylesheet" href="<?= MAPOS_URL ?>assets/css/fullcalendar.css" />

    <!-- Temas -->
    <?php if ($configuration['app_theme'] == 'white') { ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>tema/css/tema-white.css" />
    <?php } ?>
    <?php if ($configuration['app_theme'] == 'puredark') { ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>tema/css/tema-pure-dark.css" />
    <?php } ?>
    <?php if ($configuration['app_theme'] == 'darkviolet') { ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>tema/css/tema-dark-violet.css" />
    <?php } ?>
    <?php if ($configuration['app_theme'] == 'darkorange') { ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>tema/css/tema-dark-orange.css" />
    <?php } ?>
    <?php if ($configuration['app_theme'] == 'whitegreen') { ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>tema/css/tema-white-green.css" />
    <?php } ?>
    <?php if ($configuration['app_theme'] == 'whiteblack') { ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>tema/css/tema-white-black.css" />
    <?php } ?>

    <!-- Fonts -->
    <link href='https://fonts.googleapis.com/css?family=Open+Sans:400,700,800' rel='stylesheet' type='text/css'>
    <link href='https://fonts.googleapis.com/css2?family=Roboto+Condensed:wght@300;400;500;700&display=swap'
        rel='stylesheet' type='text/css'>
    <link href='https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css' rel='stylesheet'>

    <!-- SCRIPTS BÁSICOS -->
    <script type="text/javascript" src="<?= MAPOS_URL ?>assets/js/jquery-1.12.4.min.js"></script>


    <!-- Custom Scripts do Módulo -->
    <style>
        /* Ajustes específicos para o Módulo rodar isolado e Melhorar Visibilidade */
        #content {
            margin-bottom: 50px;
        }

        .module-header {
            margin-top: 10px;
        }

        /* Ajuste do Breadcrumb (Botão Início) */
        #breadcrumb {
            margin-top: 10px !important;
            /* Desce o botão início */
            position: relative !important;
            border-radius: 4px;
            /* Opcional: estética */
        }

        /* === UI VISIBILITY ENHANCEMENTS === */
        /* Aumenta a fonte geral */
        body,
        table,
        input,
        select,
        textarea {
            font-size: 14px !important;
        }

        /* Sidebar - Aumenta tamanho e legibilidade */
        #sidebar>ul>li>a {
            padding: 12px 15px !important;
            font-size: 14px !important;
        }

        #sidebar>ul>li>a>i {
            font-size: 18px !important;
            margin-right: 10px;
        }

        /* Botões - Maiores e com mais area de clique */
        .btn {
            padding: 8px 12px !important;
            font-size: 14px !important;
            line-height: 1.4 !important;
        }

        .btn-mini {
            padding: 4px 8px !important;
            font-size: 12px !important;
        }

        .btn-large {
            padding: 12px 20px !important;
            /* Salvar Button */
            font-size: 16px !important;
        }

        /* Tabela - Mais espaçamento */
        .table td,
        .table th {
            padding: 10px !important;
        }

        /* Inputs - Altura maior */
        input[type="text"],
        input[type="number"],
        input[type="password"],
        select {
            height: 35px !important;
            line-height: 35px !important;
            box-sizing: border-box !important;
        }

        /* Autocomplete Dropdown - Garantir visibilidade e sobreposição */
        .autocomplete-items {
            position: absolute;
            border: 1px solid #d4d4d4;
            border-bottom: none;
            border-top: none;
            z-index: 9999 !important;
            /* Garante que fique acima de tudo */
            top: 100%;
            left: 0;
            right: 0;
            background-color: #ffffff;
            max-height: 250px;
            overflow-y: auto;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Scrollbar Personalizado */
        ::-webkit-scrollbar {
            width: 10px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>

<body>

    <!--top-Header-menu-->
    <div class="navebarn">
        <div id="user-nav" class="navbar navbar-inverse">
            <ul class="nav">
                <li class="dropdown">
                    <a href="#" class="tip-right dropdown-toggle" data-toggle="dropdown" title="Perfis"><i
                            class='bx bx-user-circle iconN'></i><span class="text"></span></a>
                    <ul class="dropdown-menu">
                        <li class=""><a title="Meu Perfil" href="<?= MAPOS_URL ?>index.php/mapos/minhaConta"><span
                                    class="text">Meu Perfil</span></a></li>
                        <li class="divider"></li>
                        <li class=""><a title="Sair do Sistema" href="logout.php"><i class='bx bx-log-out-circle'></i>
                                <span class="text">Sair do Sistema</span></a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" class="tip-right dropdown-toggle" data-toggle="dropdown" title="Relatórios"><i
                            class='bx bx-pie-chart-alt-2 iconN'></i><span class="text"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="<?= BASE_URL ?>relatorios/index.php">Relatórios</a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" class="tip-right dropdown-toggle" data-toggle="dropdown" title="Configurações"><i
                            class='bx bx-cog iconN'></i><span class="text"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="<?= BASE_URL ?>sistema.php">Sistema</a></li>
                        <li><a href="<?= BASE_URL ?>backup_modulo.php"><i class='bx bx-cloud-download'></i> Backup
                                Módulo</a></li>
                    </ul>
                </li>
            </ul>
        </div>

        <!-- User Info -->
        <div id="userr"
            style="padding-right:50px;display:flex;flex-direction:column;align-items:flex-end;justify-content:center;position:relative;">
            <div class="user-names userT0" style="font-size: 12px;">
                <?= date('H') < 12 ? 'Bom dia' : (date('H') < 18 ? 'Boa tarde' : 'Boa noite') ?>,
            </div>
            <div class="userT" style="font-size: 12px; font-weight: bold;"><?= $_SESSION['user_name'] ?? 'Usuário' ?>
            </div>

            <?php
            $imgUser = $_SESSION['url_image_user'] ?? '';
            // Tenta validar o caminho do arquivo
            $pathImg = defined('MAPOS_ROOT_PATH') ? MAPOS_ROOT_PATH . 'assets/userImage/' . $imgUser : '';
            if (!empty($imgUser) && file_exists($pathImg)) {
                $srcImg = MAPOS_URL . 'assets/userImage/' . $imgUser;
            } else {
                $srcImg = MAPOS_URL . 'assets/img/User.png';
            }
            ?>
            <section class="sec_profile"
                style="position: absolute; right: 0; top: 0; bottom:0; display:flex; align-items:center;">
                <div class="profile" style="padding-right: 5px;">
                    <div class="profile-img"
                        style="width: 25px !important; height: 25px !important; background: #FFF !important; padding: 1px !important; border: 1px solid #FC9D0F !important; border-radius: 50% !important;">
                        <a href="<?= MAPOS_URL ?>index.php/mapos/minhaConta">
                            <img src="<?= $srcImg ?>" alt="User"
                                style="width:100% !important; height:100% !important; border-radius:50% !important; object-fit: cover !important;">
                        </a>
                    </div>
                </div>
            </section>
        </div>
    </div>
    <!--close-top-Header-menu-->

    <!--sidebar-menu-->
    <nav id="sidebar">
        <div id="newlog">
            <div class="icon2">
                <img src="<?= MAPOS_URL ?>assets/img/logo-two.png">
            </div>
            <div class="title1">
                <?= $configuration['app_theme'] == 'white' || $configuration['app_theme'] == 'whitegreen' ? '<img src="' . MAPOS_URL . 'assets/img/logo-mapos.png">' : '<img src="' . MAPOS_URL . 'assets/img/logo-mapos-branco.png">'; ?>
            </div>
        </div>
        <a href="#" class="visible-phone">
            <div class="mode">
                <div class="moon-menu">
                    <i class='bx bx-chevron-right iconX open-2'></i>
                    <i class='bx bx-chevron-left iconX close-2'></i>
                </div>
            </div>
        </a>

        <div class="menu-bar">
            <div class="menu">
                <ul class="menu-links" style="position: relative;">
                    <!-- Link de Voltar para o Mapos -->
                    <li>
                        <a class="tip-bottom" title="" href="<?= MAPOS_URL ?>">
                            <i class='bx bx-arrow-back iconX'></i>
                            <span class="title">Voltar ao Mapos</span>
                            <span class="title-tooltip">Voltar</span>
                        </a>
                    </li>

                    <!-- Links do Módulo -->
                    <li
                        class="<?= basename($_SERVER['PHP_SELF']) == 'listar_orcamentos.php' || basename($_SERVER['PHP_SELF']) == 'novo_orcamento.php' ? 'active' : '' ?>">
                        <a class="tip-bottom" title="" href="<?= BASE_URL ?>orcamentos/listar_orcamentos.php">
                            <i class='bx bx-file-blank iconX'></i>
                            <span class="title">Orçamentos</span>
                            <span class="title-tooltip">Orçamentos</span>
                        </a>
                    </li>


                    <!-- Links Mapos (Apenas visualização simples para consistência) -->
                    <li>
                        <a class="tip-bottom" title="" target="_blank" href="<?= MAPOS_URL ?>index.php/clientes">
                            <i class='bx bx-user iconX'></i>
                            <span class="title">Clientes</span>
                            <span class="title-tooltip">Clientes</span>
                        </a>
                    </li>
                    <li>
                        <a class="tip-bottom" title="" target="_blank" href="<?= MAPOS_URL ?>index.php/produtos">
                            <i class='bx bx-basket iconX'></i>
                            <span class="title">Produtos</span>
                            <span class="title-tooltip">Produtos</span>
                        </a>
                    </li>
                    <li>
                        <a class="tip-bottom" title="" target="_blank" href="<?= MAPOS_URL ?>index.php/servicos">
                            <i class='bx bx-wrench iconX'></i>
                            <span class="title">Serviços</span>
                            <span class="title-tooltip">Serviços</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="botton-content">
                <li class="">
                    <a class="tip-bottom" title="" href="<?= BASE_URL ?>logout.php">
                        <i class='bx bx-log-out-circle iconX'></i>
                        <span class="title">Sair</span>
                        <span class="title-tooltip">Sair</span>
                    </a>
                </li>
            </div>
        </div>
    </nav>
    <!--End sidebar-menu-->

    <!--main-container-part-->
    <div id="content">
        <div id="content-header">
            <div id="breadcrumb">
                <a href="<?= BASE_URL ?>index.php" title="Dashboard" class="tip-bottom"><i class="fas fa-home"></i>
                    Início</a>
                <?php if (isset($pagina_atual)): ?>
                    <a href="#" class="current"><?= $pagina_atual ?></a>
                <?php endif; ?>
            </div>
        </div>
        <div class="container-fluid">