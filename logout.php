<?php
// Inclui o arquivo de conexão para iniciar a sessão
// e ter acesso à constante BASE_URL, mas o conexao.php 
// não vai redirecionar pois 'logout.php' está na lista de exceções.
require 'conexao.php';

// 1. Destrói todas as variáveis da sessão
$_SESSION = array();

// 2. Se for necessário destruir completamente a sessão (cookies, etc.)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. Finalmente, destrói a sessão
// 3. Finalmente, destrói a sessão local
session_destroy();

// 3.1 Destruição Forçada do Cookie do MapOS (Para evitar re-login automágico)
// A função 'sair' do MapOS redireciona para o Referer, o que causaria loop.
// Por isso, apagamos o cookie manualmente.
$mapos_cookie_name = getEnvVar('APP_SESS_COOKIE_NAME', 'MAPOS_SESSION');
if ($mapos_cookie_name) {
    // Tenta expirar o cookie em caminhos comuns para garantir
    setcookie($mapos_cookie_name, '', time() - 3600, '/');
    setcookie($mapos_cookie_name, '', time() - 3600, '/mapos');
    // Se o domínio for localhost, às vezes precisa setar vazio ou explícito
    setcookie($mapos_cookie_name, '', time() - 3600, '/', $_SERVER['HTTP_HOST']);
}

// 4. Redirecionamento
// Usa a constante MAPOS_URL já definida confiavelmente em config_geral.php
header('Location: ' . MAPOS_URL . 'index.php/login');

exit();
?>