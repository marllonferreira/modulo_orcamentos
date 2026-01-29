<?php
// ========================================
// MÓDULO DE ORÇAMENTOS - CONEXÃO
// ========================================

// 1. Carrega configurações gerais (caminhos e BASE_URL)
require_once __DIR__ . '/config_geral.php';

// 2. Inicia a sessão
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ========================================
// CARREGAMENTO DO .ENV DO MAPOS
// ========================================

// INICIO IMPLEMENTAÇÃO CSRF (Segurança)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
// FIM IMPLEMENTAÇÃO CSRF

function loadEnv($path)
{
    if (!file_exists($path)) {
        die("Erro Crítico: Arquivo <code>.env</code> não encontrado em: " . $path . "<br>Verifique se o Mapos está instalado corretamente.");
    }

    if (!is_readable($path)) {
        die("Erro Crítico: Arquivo <code>.env</code> existe mas não pode ser lido (Permissão negada).");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0)
            continue;

        if (strpos($line, '=') === false)
            continue;

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        $value = trim($value, '"\'');

        putenv(sprintf('%s=%s', $name, $value));
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

// Carrega o .env do Mapos
$envPath = __DIR__ . '/' . MAPOS_PATH . '/application/.env';
loadEnv($envPath);

// Helper para pegar variável de ambiente
function getEnvVar($key, $default = null)
{
    $val = getenv($key);
    if ($val === false || $val === '') {
        if (isset($_ENV[$key]))
            $val = $_ENV[$key];
        elseif (isset($_SERVER[$key]))
            $val = $_SERVER[$key];
    }
    return ($val !== false && $val !== '') ? $val : $default;
}

// Define informações do módulo
define('APP_NAME', MODULE_NAME);

// ========================================
// VERIFICAÇÃO DE LOGIN
// ========================================

$current_page = basename($_SERVER['PHP_SELF']);
$public_pages = ['login.php', 'logout.php', 'teste_conexao.php'];

// Primeiro conecta ao banco para poder validar sessão
$host = getEnvVar('DB_HOSTNAME', 'localhost');
$db = getEnvVar('DB_DATABASE');
$user = getEnvVar('DB_USERNAME', 'root');
$pass = getEnvVar('DB_PASSWORD', '');
$charset = 'utf8mb4';

if (empty($db)) {
    die("Erro Crítico: Nome do banco de dados não definido no .env do Mapos.");
}

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Falha na Conexão: " . $e->getMessage());
}

// Agora valida sessão do Mapos
if (!in_array($current_page, $public_pages)) {
    $session_valid = false;
    $user_name = null;

    $sess_cookie_name = getEnvVar('APP_SESS_COOKIE_NAME', 'MAPOS_SESSION');
    $sess_driver = getEnvVar('APP_SESS_DRIVER', 'files');

    if (isset($_COOKIE[$sess_cookie_name]) && $sess_driver === 'database') {
        $session_id = $_COOKIE[$sess_cookie_name];
        $sess_table = getEnvVar('APP_SESS_SAVE_PATH', 'ci_sessions');

        try {
            $stmt = $pdo->prepare("SELECT data FROM {$sess_table} WHERE id = :sid");
            $stmt->execute([':sid' => $session_id]);
            $session_data = $stmt->fetchColumn();

            if ($session_data) {
                // CodeIgniter usa formato próprio: chave|tipo:valor;
                // Verifica se usuário está logado (campo 'logado')
                if (preg_match('/logado\|b:1/', $session_data)) {
                    $session_valid = true;

                    // Extrai nome do usuário
                    if (preg_match('/nome_admin\|s:\d+:"([^"]+)"/', $session_data, $matches)) {
                        $user_name = $matches[1];
                    } else {
                        $user_name = 'Usuário';
                    }

                    // Extrai imagem do usuário
                    if (preg_match('/url_image_user_admin\|s:\d+:"([^"]+)"/', $session_data, $matches_img)) {
                        $user_image = $matches_img[1];
                    } else {
                        $user_image = null;
                    }
                }
            }
        } catch (PDOException $e) {
            $session_valid = false;
        }
    }

    if (!$session_valid) {
        // Redireciona para o Mapos (página inicial que vai redirecionar para login se necessário)
        header('Location: ' . MAPOS_URL);
        exit();
    }

    $_SESSION['usuario_logado'] = true;
    $_SESSION['user_name'] = $user_name;
    $_SESSION['url_image_user'] = $user_image ?? null;
}

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    $erro = $e->getMessage();
    die("Falha na Conexão com o Banco de Dados.<br>Host: <strong>$host</strong><br>Database: <strong>$db</strong><br>Erro: " . $erro);
}

// ========================================
// 🛡️ VERIFICAÇÃO DE INSTALAÇÃO
// ========================================
$current_script = basename($_SERVER['PHP_SELF']);
if ($current_script !== 'install.php') {
    $is_db_ok = false;
    try {
        // Verifica se AMBAS as tabelas existem
        $tables_needed = ['mod_orc_orcamentos', 'mod_orc_itens'];
        $tables_found = 0;

        foreach ($tables_needed as $table) {
            // Nota: SHOW TABLES LIKE com parâmetro (?) pode falhar em alguns drivers/versões PDO
            // Como a lista $tables_needed é hardcoded e segura, podemos interpolar direto.
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                $tables_found++;
            }
        }

        // Só está OK se encontrar TODAS as tabelas necessárias
        $is_db_ok = ($tables_found === count($tables_needed));
    } catch (Exception $e) {
        $is_db_ok = false;
    }

    $is_vendor_ok = file_exists(__DIR__ . '/vendor/autoload.php');

    if (!$is_db_ok || !$is_vendor_ok) {
        header('Location: ' . BASE_URL . 'install.php');
        exit();
    }
}
?>