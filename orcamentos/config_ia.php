<?php
// Configurações para integração com IA (Gemini)
// Você pode adicionar múltiplas chaves aqui. O sistema tentará usar a primeira,
// se der "Cota Excedida", tentará a segunda, e assim por diante.
define('GEMINI_API_KEYS', [
	'AIzaSyD0KJoJP0jO-4-8QdCFv1233nQ522HnxVI', // Chave Principal exemplo
	'AIzaSyD0KJoJP0jO-4-8QdCFv4321nQ522HnxVI', // Chave secundaria exemplo
]);

// Mantém URL base
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent');

// INTERRUPTOR GERAL DA IA (ON/OFF)
// Defina como true para ATIVAR os recursos de IA.
// Defina como false para DESATIVAR (botões somem e acesso é bloqueado).
define('IA_ENABLED', false);
?>