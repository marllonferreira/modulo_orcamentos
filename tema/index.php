<?php
/**
 * index.php - Redirecionamento de segurança para a raiz do sistema.
 * Impede que o conteúdo do diretório seja listado por um navegador.
 */

// Define o cabeçalho de redirecionamento HTTP 301 (Moved Permanently)
header('Location: ../index.php', true, 301); 

// Garante que o script pare de ser executado imediatamente após o redirecionamento
exit; 
?>