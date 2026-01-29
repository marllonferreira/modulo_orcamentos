<?php
// backup_modulo.php
// Realiza o backup (dump SQL + ZIP) das tabelas do módulo de orçamentos

require 'conexao.php';

// Limpa buffer de saída para evitar corrupção do arquivo
if (ob_get_level())
    ob_end_clean();

// Configurações
$tables = ['mod_orc_orcamentos', 'mod_orc_itens'];
$filename_base = 'backup_orcamentos_' . date('Y-m-d_H-i');
$sql_filename = $filename_base . '.sql';
$zip_filename = $filename_base . '.zip';

// Cabeçalho do SQL
$sql = "-- BACKUP MÓDULO ORÇAMENTOS\n";
$sql .= "-- Gerado em: " . date('d/m/Y H:i:s') . "\n";
$sql .= "-- Por: " . ($_SESSION['user_name'] ?? 'Usuario') . "\n";
$sql .= "-- \n\n";
$sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

try {
    foreach ($tables as $table) {
        $sql .= "-- --------------------------------------------------------\n\n";
        $sql .= "-- Estrutura da tabela `{$table}`\n\n";

        // Estrutura (CREATE TABLE)
        $stmt = $pdo->query("SHOW CREATE TABLE {$table}");
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $sql .= $row[1] . ";\n\n";

        // Dados (INSERT INTO)
        $sql .= "-- Extraindo dados da tabela `{$table}`\n\n";

        $stmt = $pdo->query("SELECT * FROM {$table}");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sql .= "INSERT INTO `{$table}` VALUES (";
            $values = [];
            foreach ($row as $value) {
                if ($value === null) {
                    $values[] = "NULL";
                } else {
                    $value = addslashes($value);
                    $value = str_replace("\n", "\\n", $value);
                    $values[] = "'{$value}'";
                }
            }
            $sql .= implode(', ', $values);
            $sql .= ");\n";
        }
        $sql .= "\n";
    }

    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

    // Criar ZIP
    $zip = new ZipArchive();
    $tmp_file = tempnam(sys_get_temp_dir(), 'orc_bkp');

    if ($zip->open($tmp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        throw new Exception("Não foi possível criar o arquivo ZIP temporário.");
    }

    $zip->addFromString($sql_filename, $sql);
    $zip->close();

    // Forçar Download
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
    header('Content-Length: ' . filesize($tmp_file));
    header('Pragma: no-cache');
    header('Expires: 0');

    readfile($tmp_file);

    // Limpeza
    unlink($tmp_file);
    exit;

} catch (Exception $e) {
    die("Erro ao gerar backup: " . $e->getMessage());
}
