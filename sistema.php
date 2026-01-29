<?php
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/config_geral.php';

// Salvar Configurações
if ($_POST) {
    $theme = $_POST['app_theme'];
    // Validação básica
    $allowedThemes = ['default', 'white', 'puredark', 'darkorange', 'darkviolet', 'whitegreen', 'whiteblack'];
    if (in_array($theme, $allowedThemes)) {
        $config = ['app_theme' => $theme];
        file_put_contents(__DIR__ . '/tema/config_theme.json', json_encode($config, JSON_PRETTY_PRINT));
        $sucesso = true;
    }
}

// Ler config atual
$configFile = __DIR__ . '/tema/config_theme.json';
$currentTheme = 'white';
if (file_exists($configFile)) {
    $conf = json_decode(file_get_contents($configFile), true);
    $currentTheme = $conf['app_theme'] ?? 'white';
}

// Definir nome da página para o breadcrumb
$pagina_atual = 'Configurações do Sistema';

require_once __DIR__ . '/tema/header.php';
?>

<div class="row-fluid" style="margin-top:0">
    <div class="span12">
        <div class="widget-box">
            <div class="widget-title">
                <span class="icon">
                    <i class="bx bx-cog"></i>
                </span>
                <h5>Configurações do Módulo de Orçamentos</h5>
            </div>
            <div class="widget-content nopadding">
                <?php if (isset($sucesso)) { ?>
                    <div class="alert alert-success">
                        <button class="close" data-dismiss="alert">×</button>
                        Configurações salvas com sucesso!
                    </div>
                <?php } ?>

                <form action="" method="post" class="form-horizontal">
                    <div class="control-group">
                        <label class="control-label">Tema do Módulo</label>
                        <div class="controls">
                            <select name="app_theme">
                                <option value="default" <?= $currentTheme == 'default' ? 'selected' : '' ?>>Escuro</option>
                                <option value="white" <?= $currentTheme == 'white' ? 'selected' : '' ?>>Claro</option>
                                <option value="puredark" <?= $currentTheme == 'puredark' ? 'selected' : '' ?>>Pure Dark
                                </option>
                                <option value="darkorange" <?= $currentTheme == 'darkorange' ? 'selected' : '' ?>>Dark
                                    Orange</option>
                                <option value="darkviolet" <?= $currentTheme == 'darkviolet' ? 'selected' : '' ?>>Dark
                                    Violet</option>
                                <option value="whitegreen" <?= $currentTheme == 'whitegreen' ? 'selected' : '' ?>>White
                                    Green</option>
                                <option value="whiteblack" <?= $currentTheme == 'whiteblack' ? 'selected' : '' ?>>White
                                    Black</option>
                            </select>
                            <span class="help-block">Este tema será aplicado apenas a este módulo.</span>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Salvar Configurações</button>
                        <a href="index.php" class="btn">Voltar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/tema/footer.php'; ?>