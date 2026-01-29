-- Script de Instalação das Tabelas do Módulo de Orçamentos
-- Prefixo utilizado: mod_orc_

-- 1. Tabela Principal de Orçamentos
CREATE TABLE IF NOT EXISTS `mod_orc_orcamentos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL, -- Referência à tabela clientes.idClientes do Mapos
  `data_criacao` datetime DEFAULT CURRENT_TIMESTAMP,
  `valor_total` decimal(10,2) DEFAULT NULL,
  `observacoes` text,
  `anotacoes_internas` text,
  `status` enum('Rascunho','Em Revisão','Emitido','Aguardando Aprovação','Aprovado','Rejeitado','Cancelado') DEFAULT 'Rascunho',
  `validade_dias` int DEFAULT '7',
  PRIMARY KEY (`id`),
  KEY `fk_mod_orc_cliente` (`cliente_id`),
  CONSTRAINT `fk_mod_orc_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`idClientes`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 2. Tabela de Itens do Orçamento
CREATE TABLE IF NOT EXISTS `mod_orc_itens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `orcamento_id` int NOT NULL,
  `produto_id` int(11) DEFAULT NULL, -- Referência à tabela produtos.idProdutos do Mapos (pode ser NULL se for serviço ou livre)
  `servico_id` int(11) DEFAULT NULL, -- Nova coluna para referência explícita a servicos.idServicos (opcional, mas recomendado)
  `tipo_item` varchar(10) NOT NULL DEFAULT 'P' COMMENT 'P=Produto, S=Serviço, M=Manual',
  `descricao` varchar(255) NOT NULL,
  `quantidade` decimal(10,2) NOT NULL,
  `unidade` varchar(10) NOT NULL DEFAULT 'UN',
  `preco_unitario` decimal(10,2) NOT NULL,
  `taxa` decimal(10,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_mod_orc_item_orcamento` (`orcamento_id`),
  KEY `fk_mod_orc_item_produto` (`produto_id`),
  KEY `fk_mod_orc_item_servico` (`servico_id`),
  CONSTRAINT `fk_mod_orc_item_orcamento` FOREIGN KEY (`orcamento_id`) REFERENCES `mod_orc_orcamentos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mod_orc_item_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`idProdutos`) ON DELETE SET NULL,
  CONSTRAINT `fk_mod_orc_item_servico` FOREIGN KEY (`servico_id`) REFERENCES `servicos` (`idServicos`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
