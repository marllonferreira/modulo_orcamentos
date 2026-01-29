<?php
/**
 * Configuração Geral do Módulo de Orçamentos
 * 
 * Este arquivo define os caminhos e configurações principais do módulo.
 * Ele permite que o módulo funcione independentemente da pasta onde está instalado.
 */

// ========================================
// CONFIGURAÇÃO DE TIMEZONE E CAMINHOS
// ========================================

// Define fuso horário padrão para todo o módulo
date_default_timezone_set('America/Sao_Paulo');

/**
 * Caminho relativo para a pasta do Mapos
 * Ajustado para nova estrutura: mapos/modulos/orcamentos -> mapos/application
 * Se o mapos está em ../../../mapos (não faz sentido se está DENTRO)
 * Se estamos em mapos/modulos/orcamentos, o root do mapos é ../../
 */
define('MAPOS_PATH', '../../');
define('MAPOS_ROOT_PATH', realpath(__DIR__ . '/../../') . '/');

/**
 * Detecção automática da URL base do módulo
 * Usa o caminho do arquivo config_geral.php para determinar a raiz do módulo
 */
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];

// Caminho absoluto do arquivo atual na disca
$currentDir = str_replace('\\', '/', __DIR__);

// Document Root do servidor
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);

// Caminho relativo a partir do Document Root (ex: /orcamentos)
$webPath = str_replace($docRoot, '', $currentDir);

// Garante barra no final
define('BASE_URL', $protocol . $host . $webPath . '/');

// Define URL do Mapos dinamicamente
// Se estamos em /mapos/modulos/orcamentos/, o Mapos está em /mapos/
define('MAPOS_URL', $protocol . $host . '/mapos/');

// ========================================
// INFORMAÇÕES DO MÓDULO
// ========================================

define('MODULE_NAME', 'Módulo de Orçamentos');
define('MODULE_VERSION', '1.0.0');
