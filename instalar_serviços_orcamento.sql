-- 1. Desativa a verificação de chaves estrangeiras
SET FOREIGN_KEY_CHECKS = 0;

-- 2. Limpa a tabela inteira e zera o Auto Incremento
TRUNCATE TABLE `servicos`;

-- 3. Reativa a verificação de chaves estrangeiras
SET FOREIGN_KEY_CHECKS = 1;

-- 4. Insere a lista completa e definitiva
INSERT INTO servicos (nome, descricao, preco) VALUES
-- --- ELÉTRICA: PROJETOS E OBRAS (NOVO) ---
('Instalação Elétrica Completa', 'Execução de projeto ou reforma completa', 1.00),
('Montagem Quadro QDC', 'Montagem técnica de quadro de distribuição', 350.00),
('Revitalização de Tomadas', 'Troca geral de tomadas/interrup. (pacote)', 300.00),

-- --- ELÉTRICA: PEQUENOS REPAROS ---
('Instalação Tomada Simples', 'Substituição tomada ou interruptor', 30.00),
('Ponto Elétrico Novo', 'Criação infra e fiação nova (ponto)', 70.00),
('Instalação Luminária LED', 'Inst. Painel, Plafon ou Lustre', 50.00),
('Instalação Refletor', 'Instalação e fixação refletor LED', 80.00),
('Instalação Ventilador', 'Montagem e inst. (teto/parede)', 80.00),
('Instalação Chuveiro', 'Instalação elétrica chuveiro', 60.00),
('Troca Fiação Chuveiro', 'Subst. fiação derretida/inadequada', 120.00),
('Troca de Resistência', 'Troca resistência chuveiro', 30.00),
('Troca de Disjuntor', 'Substituição disjuntor no quadro', 50.00),
('Instalação Fotocélula', 'Instalação sensor dia/noite', 40.00),
('Inst. Sensor Presença', 'Sensor para iluminação automática', 40.00),
('Identificação de Curto', 'Rastreio e isolamento de curto', 100.00),

-- --- INFORMÁTICA E MANUTENÇÃO ---
('Formatação Padrão', 'Instalação Windows/Drivers sem backup', 80.00),
('Formatação c/ Backup', 'Instalação Windows + Backup arquivos', 120.00),
('Formatação Dual Boot', 'Instalação dois sistemas (Win/Linux)', 150.00),
('Limpeza Física PC/Note', 'Limpeza interna e troca pasta térmica', 80.00),
('Troca de Teclado', 'Mão de obra para troca de teclado', 80.00),
('Troca de Tela', 'Mão de obra para troca de tela', 80.00),
('Recuperação de Carcaça', 'Reparo estrutural carcaça notebook', 80.00),
('Recup. Carcaça + Limpeza', 'Reparo carcaça + limpeza interna', 120.00),
('Manutenção Placa Mãe', 'Reparo eletrônico (Valor Orçamento)', 1.00),
('Troca Bateria BIOS', 'Substituição bateria CR2032', 30.00),
('Troca Fonte ATX', 'Substituição fonte alimentação PC', 50.00),
('Troca HD/SSD', 'Mão de obra substituição disco', 50.00),
('Instalação SSD + Sistema', 'Instalação física SSD + Formatação', 120.00),
('Regravação de BIOS', 'Regravação chip EPROM', 150.00),
('Desbloqueio de BIOS', 'Reset senha/Desbloqueio BIOS', 60.00),
('Restauração Carregador', 'Reparo cabo fonte notebook', 40.00),
('Recuperação de Dados', 'Recuperação lógica (Valor Inicial)', 150.00),
('Limpeza Console (Game)', 'Limpeza PS4/Xbox + Pasta Térmica', 150.00),

-- --- SOFTWARE ---
('Backup de Arquivos', 'Cópia segurança (até 50GB)', 50.00),
('Instalação Programas', 'Instalação softwares avulsos', 20.00),
('Ativação Sistema', 'Ativação Windows/Office', 40.00),
('Atualização Antivírus', 'Update vacinas e verificação', 40.00),

-- --- REDES E INTERNET (TI) ---
('Configuração Roteador', 'Config. PPPoE e Wi-Fi', 60.00),
('Instalação Roteador', 'Instalação física e lógica', 80.00),
('Config. Repetidor', 'Configurar roteador como repetidor', 50.00),
('Instalação Placa Wi-Fi', 'Instalação adaptador Wireless', 40.00),
('Ponto Rede Lógico', 'Passagem cabo e crimpagem (ponto)', 60.00),
('Crimpagem RJ45', 'Troca conector rede (unidade)', 15.00),
('Inst. Rack Informática', 'Montagem rack Switch/Patch Panel', 150.00),
('Org. Rack Informática', 'Cable management e identificação TI', 250.00),
('Passagem Cabo Rede', 'Lançamento infraestrutura rede', 70.00),

-- --- IMPRESSORAS ---
('Instalação Impressora', 'Configuração USB ou Wi-Fi', 60.00),
('Inst. Impressora Rede', 'Config. impressora em rede LAN', 80.00),
('Limpeza Impressora', 'Limpeza interna cabeçote/roletes', 120.00),
('Mão de Obra Impressora', 'Reparo mecânico impressora', 150.00),
('Troca Cabo Flat Epson', 'Subst. flat cable scanner/cabeça', 80.00),

-- --- CELULARES E TABLETS ---
('Hard Reset Celular', 'Restauração de fábrica', 40.00),
('Reinstalação ROM', 'Reinstalação sistema Android', 90.00),
('Desbloqueio Conta Google', 'Remoção FRP (Conta Google)', 80.00),

-- --- SEGURANÇA: ALARMES ---
('Instalação Central Alarme', 'Instalação e config. central', 150.00),
('Instalação Sensor Sem Fio', 'Fixação e pareamento sensor', 30.00),
('Instalação Sensor Com Fio', 'Passagem cabo e fixação sensor', 50.00),
('Instalação Sirene', 'Instalação sirene alarme', 40.00),
('Troca Bateria Central', 'Subst. bateria selada 12v', 30.00),
('Configuração Controle', 'Cadastrar controle remoto (unidade)', 15.00),
('Manutenção Alarme', 'Visita técnica reparo alarme', 80.00),

-- --- SEGURANÇA: CERCA ELÉTRICA ---
('Inst. Central Choque', 'Instalação energizador cerca', 180.00),
('Manutenção Cerca', 'Reparo fios/molas/isoladores', 150.00),
('Troca Haste Cerca', 'Substituição haste danificada', 40.00),
('Emenda Fio Cerca', 'Reparo rompimento fio aço', 50.00),
('Aterramento Haste', 'Instalação haste aterramento', 60.00),
('Poda Vegetação Cerca', 'Limpeza vegetação na cerca', 60.00),

-- --- SEGURANÇA: CFTV (CÂMERAS) ---
('Inst. Câmera HD/Analógica', 'Inst. câmera com cabo coaxial/UTP', 150.00),
('Instalação Câmera IP', 'Config. câmera Wi-Fi/IP', 100.00),
('Instalação DVR/NVR', 'Fixação e config. gravador', 100.00),
('Inst. Completa 04 Câmeras', 'Mão de obra instalação 04 câmeras', 500.00),
('Inst. Completa 08 Câmeras', 'Mão de obra instalação 08 câmeras', 900.00),
('Inst. Completa 16 Câmeras', 'Mão de obra instalação 16 câmeras', 1600.00),
('Inst. Completa 32 Câmeras', 'Mão de obra instalação 32 câmeras', 3000.00),
('Acesso Remoto DVR', 'Config. acesso celular/nuvem', 80.00),
('Inst. Fonte Colmeia', 'Subst. fonte central câmeras', 60.00),
('Troca Conector BNC/P4', 'Refazer conexão câmera (unidade)', 15.00),
('Passagem Cabo CFTV', 'Mão de obra avulsa por ponto', 60.00),
('Backup de Imagens', 'Extração gravações (PenDrive)', 50.00),
('Instalação Rack CFTV', 'Fixação rack p/ DVR e Fonte', 100.00),
('Org. Rack CFTV', 'Organização cabos e fonte CFTV', 180.00),

-- --- AUTOMAÇÃO DE PORTÕES ---
('Config. Placa Motor', 'Config. curso e rampa motor', 60.00),
('Codificação Controle', 'Cópia/cadastro controle portão', 20.00),
('Instalação Cremalheira', 'Ajuste/troca cremalheira (metro)', 45.00),

-- --- SERVIÇOS DIVERSOS ---
('Config. Perfil Google', 'Criação Google Meu Negócio', 80.00),
('Config. WhatsApp Business', 'Config. perfil comercial', 60.00),

-- --- TAXAS ---
('Taxa de Visita', 'Deslocamento (Perímetro Urbano)', 60.00),
('Visita Técnica Rural', 'Deslocamento fora perímetro urbano', 100.00),
('Diagnóstico Técnico', 'Análise técnica de bancada', 40.00);