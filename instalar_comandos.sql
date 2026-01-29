-- 1. Desativa a verificação de chaves estrangeiras
SET FOREIGN_KEY_CHECKS = 0;

-- 2. Limpa a tabela inteira e zera o Auto Incremento
TRUNCATE TABLE `produtos`;

-- 3. Reativa a verificação de chaves estrangeiras
SET FOREIGN_KEY_CHECKS = 1;