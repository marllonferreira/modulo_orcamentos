<?php
// install.php - Instalador Automático do Módulo
require 'conexao.php';

// Verifica se foi postado
$action = $_POST['action'] ?? null;
$messages = [];

if ($action === 'install') {
    try {
        // 1. Ler arquivo SQL
        $sqlFile = __DIR__ . '/instalar_tabelas_orcamento.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("Arquivo SQL 'instalar_tabelas_orcamento.sql' não encontrado!");
        }
        $sqlContent = file_get_contents($sqlFile);

        // 2. Executar SQL (Divide por ;)
        // Como o PDO não executa múltiplos statements de uma vez em alguns drivers, vamos separar
        // Mas CREATE TABLE geralmente é seguro. Vamos tentar direto primeiro ou separar.
        // O SQL tem comentários e quebras. Vamos separar de forma simples.

        // $pdo->beginTransaction(); // REMOVIDO: DDL causa commit implícito no MySQL

        // Remove comentários SQL simples (-- ...)
        $sqlContent = preg_replace('/^--.*$/m', '', $sqlContent);

        // Separa por ponto e vírgula
        $statements = array_filter(array_map('trim', explode(';', $sqlContent)));

        foreach ($statements as $stmt) {
            if (!empty($stmt)) {
                $pdo->exec($stmt);
            }
        }

        // $pdo->commit(); // REMOVIDO
        $messages[] = ['type' => 'success', 'text' => 'Banco de dados configurado com sucesso! Tabelas criadas.'];

    } catch (Exception $e) {
        // if ($pdo->inTransaction()) $pdo->rollBack(); // REMOVIDO
        $messages[] = ['type' => 'error', 'text' => 'Erro ao configurar banco: ' . $e->getMessage()];
    }
}

// Verifica Status Atual
$statusDB = false;
try {
    $resOrc = $pdo->query("SHOW TABLES LIKE 'mod_orc_orcamentos'");
    $resItens = $pdo->query("SHOW TABLES LIKE 'mod_orc_itens'");
    $statusDB = ($resOrc->rowCount() > 0) && ($resItens->rowCount() > 0);
} catch (Exception $e) {
}

$statusVendor = file_exists(__DIR__ . '/vendor/autoload.php');

include 'tema/header.php';
?>

<div class="row-fluid" style="margin-top: 20px;">
    <div class="span12">
        <div class="widget-box">
            <div class="widget-title">
                <span class="icon"><i class="icon-cog"></i></span>
                <h5>Instalação do Módulo de Orçamentos</h5>
            </div>
            <div class="widget-content">

                <?php foreach ($messages as $msg): ?>
                    <div class="alert alert-<?= $msg['type'] ?>">
                        <button class="close" data-dismiss="alert">×</button>
                        <?= $msg['text'] ?>
                    </div>
                <?php endforeach; ?>

                <div class="row-fluid">
                    <div class="span6">
                        <h4>1. Banco de Dados</h4>
                        <p>Status:
                            <?php if ($statusDB): ?>
                                <span class="badge badge-success">INSTALADO</span>
                            <?php else: ?>
                                <span class="badge badge-important">PENDENTE</span>
                            <?php endif; ?>
                        </p>
                        <p>Cria as tabelas necessárias (<code>mod_orc_orcamentos</code> e <code>mod_orc_itens</code>).
                        </p>

                        <?php if (!$statusDB): ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="install">
                                <button type="submit" class="btn btn-primary"><i class="icon-hdd"></i> Instalar
                                    Tabelas</button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info">As tabelas já existem no banco de dados.</div>
                        <?php endif; ?>
                    </div>

                    <div class="span6">
                        <h4>2. Dependências (PDF)</h4>
                        <p>Status:
                            <?php if ($statusVendor): ?>
                                <span class="badge badge-success">INSTALADO</span>
                            <?php else: ?>
                                <span class="badge badge-important">PENDENTE</span>
                            <?php endif; ?>
                        </p>
                        <p>Biblioteca <strong>DomPDF</strong> necessária para gerar orçamentos.</p>

                        <?php if (!$statusVendor): ?>
                            <div class="alert alert-error">
                                <strong>Atenção:</strong> A biblioteca de PDF não foi encontrada.
                                <br><br>
                                <a href="#modalInstrucoes" role="button" class="btn btn-danger" data-toggle="modal">
                                    <i class="icon-info-sign"></i> Ver Instruções de Instalação
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">Dependências encontradas corretamente.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row-fluid">
                    <div class="span12" style="text-align: center;">
                        <br>
                        <p>Precisa de ajuda? <a href="https://github.com/marllonferreira/modulo_orcamentos"
                                target="_blank" style="color: #2f9d2f;"><i class="icon-book"></i> Ver Documentação no
                                GitHub</a></p>
                    </div>
                </div>

                <hr>
                <hr>
                <div class="alert alert-info" style="margin-top: 20px;">
                    <strong>Tudo Pronto?</strong> Se as luzes acima estiverem verdes, você já pode acessar o sistema.
                </div>

                <?php if ($statusDB && $statusVendor): ?>
                    <div style="text-align: center;">
                        <a href="orcamentos/listar_orcamentos.php" class="btn btn-large btn-success">
                            <i class="icon-ok-sign"></i> Acessar Módulo
                        </a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<!-- Modal de Instruções -->
<div id="modalInstrucoes" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
    aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="myModalLabel">Instalando Dependências (PDF)</h3>
    </div>
    <div class="modal-body">
        <p>Para gerar PDFs, este módulo precisa da biblioteca <strong>DomPDF</strong>. Escolha um dos métodos abaixo:
        </p>

        <h4>Opção 1: Automática (Recomendada)</h4>
        <p>Se você tem acesso ao terminal/SSH do servidor:</p>
        <ol>
            <li>Acesse a pasta do módulo: <code>/modulos/orcamentos</code></li>
            <li>Rode o comando:</li>
        </ol>
        <pre>composer install</pre>

        <hr>

        <h4>Opção 2: Manual (Sem Composer)</h4>
        <p>Se você não tem acesso ao terminal ou está em uma hospedagem comum:</p>
        <ol>
            <li>Baixe a biblioteca no link: <a href="https://github.com/dompdf/dompdf/releases" target="_blank"
                    style="color: #2f9d2f;">DomPDF Releases</a> (ex: <code>dompdf-3.1.4.zip</code>).</li>
            <li>Extraia o arquivo no seu computador.</li>
            <li>Dentro da pasta extraída, você verá uma pasta chamada <strong>vendor</strong>.</li>
            <li>Copie essa pasta <strong>vendor</strong> inteira para dentro da pasta do módulo
                (<code>/modulos/orcamentos/</code>).</li>
            <li>O caminho final deve ser: <code>modulos/orcamentos/vendor/</code>.</li>
        </ol>
        <div class="alert alert-info">
            Consulte para mais detalhes: <a href="https://github.com/marllonferreira/modulo_orcamentos" target="_blank"
                style="color: #2f9d2f;"><strong>Módulo de Orçamentos</strong></a>.
        </div>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Fechar</button>
    </div>
</div>

<?php include 'tema/footer.php'; ?>