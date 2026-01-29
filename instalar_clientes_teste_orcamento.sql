-- Inserção de 15 Clientes Fictícios para Map-OS
-- A senha de acesso para TODOS estes clientes é: 123456
-- O hash utilizado ($2y$10$...) corresponde a "123456" criptografado.

INSERT INTO `clientes` 
(`nomeCliente`, `sexo`, `pessoa_fisica`, `documento`, `telefone`, `celular`, `email`, `senha`, `dataCadastro`, `rua`, `numero`, `bairro`, `cidade`, `estado`, `cep`, `contato`) 
VALUES
('Carlos Silva Ribeiro', 'Masculino', 1, '123.456.789-01', '(98) 3222-1010', '(98) 99100-1010', 'carlos.silva@email.com', '$2y$10$Be4.1.2.3.4.5.6.7.8.9.0.a.b.c.d.e.f.g.h.i.j.k.l.m.n.o', '2024-01-15', 'Rua das Laranjeiras', '10', 'Renascença', 'São Luís', 'MA', '65075-000', 'Carlos'),

('Maria Oliveira Santos', 'Feminino', 1, '234.567.890-12', '(99) 3525-2020', '(99) 98111-2020', 'maria.santos@email.com', '$2y$10$Be4.1.2.3.4.5.6.7.8.9.0.a.b.c.d.e.f.g.h.i.j.k.l.m.n.o', '2024-02-10', 'Av. Bernardo Sayão', '500', 'Nova Imperatriz', 'Imperatriz', 'MA', '65907-000', 'Maria'),

('Tech Soluções Ltda', NULL, 0, '12.345.678/0001-90', '(98) 3232-3030', '(98) 98222-3030', 'contato@techsolucoes.com', '$2y$10$Be4.1.2.3.4.5.6.7.8.9.0.a.b.c.d.e.f.g.h.i.j.k.l.m.n.o', '2024-03-05', 'Av. Colares Moreira', '100', 'Calhau', 'São Luís', 'MA', '65071-000', 'Roberto Gerente'),

('Ana Pereira Costa', 'Feminino', 1, '345.678.901-23', '(99) 3421-4040', '(99) 98333-4040', 'ana.costa@email.com', '$2y$10$Be4.1.2.3.4.5.6.7.8.9.0.a.b.c.d.e.f.g.h.i.j.k.l.m.n.o', '2024-03-20', 'Rua 15 de Novembro', '25', 'Centro', 'Bacabal', 'MA', '65700-000', 'Ana'),

('João Mendes Rocha', 'Masculino', 1, '456.789.012-34', '(98) 3211-5050', '(98) 98444-5050', 'joao.rocha@email.com', '$2y$10$Be4.1.2.3.4.5.6.7.8.9.0.a.b.c.d.e.f.g.h.i.j.k.l.m.n.o', '2024-04-12', 'Rua Grande', '120', 'Centro', 'São Luís', 'MA', '65020-000', 'João'),

('Mercadinho O Barateiro', NULL, 0, '98.765.432/0001-10', '(99) 3661-6060', '(99) 98555-6060', 'compras@barateiro.com', '$2y$10$Be4.1.2.3.4.5.6.7.8.9.0.a.b.c.d.e.f.g.h.i.j.k.l.m.n.o', '2024-05-01', 'Av. Santos Dumont', '88', 'Volta Redonda', 'Caxias', 'MA', '65606-000', 'Sr. José'),

('Fernanda Lima Souza', 'Feminino', 1, '567.890.123-45', '(98) 3245-7070', '(98) 98666-7070', 'fernanda.lima@email.com', '$2y$10$Be4.1.2.3.4.5.6.7.8.9.0.a.b.c.d.e.f.g.h.i.j.k.l.m.n.o', '2024-05-15', 'Rua dos Canários', '45', 'Cohama', 'São Luís', 'MA', '65074-000', 'Nanda'),

('Paulo Ricardo Diniz', 'Masculino', 1, '678.901.234-56', '(99) 3535-8080', '(99) 98777-8080', 'paulo.diniz@email.com', '$2y$10$Be4.1.2.3.4.5.6.7.8.9.0.a.b.c.d.e.f.g.h.i.j.k.l.m.n.o', '2024-06-08', 'Rua Piauí', '302', 'Juçara', 'Imperatriz', 'MA', '65900-000', 'Paulo'),

('Clínica Saúde Total', NULL, 0, '45.123.789/0001-55', '(98) 3255-9090', '(98) 98888-9090', 'financeiro@saudetotal.com', '$2y$10$Be4.1.2.3.4.5.6.7.8.9.0.a.b.c.d.e.f.g.h.i.j.k.l.m.n.o', '2024-06-22', 'Rua das Hortas', '77', 'Centro', 'São Luís', 'MA', '65020-400', 'Dra. Cláudia'),

('Lucas Martins Ferreira', 'Masculino', 1, '789.012.345-67', '(99) 3777-1111', '(99) 98999-1111', 'lucas.ferreira@email.com', '$2y$10$Be4.1.2.3.4.5.6.7.8.9.0.a.b.c.d.e.f.g.h.i.j.k.l.m.n.o', '2024-07-01', 'Av. Presidente Vargas', '90', 'Centro', 'Balsas', 'MA', '65800-000', 'Lucas'),

('Juliana Alves Brito', 'Feminino', 1, '890.123.456-78', '(98) 3266-2222', '(98) 99123-2222', 'juliana.brito@email.com', '$2y$10$Be4.1.2.3.4.5.6.7.8.9.0.a.b.c.d.e.f.g.h.i.j.k.l.m.n.o', '2024-07-15', 'Rua 7', '14', 'Cohatrac IV', 'São Luís', 'MA', '65054-000', 'Ju'),

('Roberto Carlos Braga', 'Masculino', 1, '901.234.567-89', '(99) 3888-3333', '(99) 99234-3333', 'roberto.braga@email.com', '$2y$10$Be4.1.2.3.4.5.6.7.8.9.0.a.b.c.d.e.f.g.h.i.j.k.l.m.n.o', '2024-08-05', 'Rua Coronel Zeca', '555', 'Centro', 'Codó', 'MA', '65400-000', 'Beto'),

('Escola Saber Mais', NULL, 0, '76.543.210/0001-33', '(98) 3277-4444', '(98) 99345-4444', 'admin@sabermais.com', '$2y$10$Be4.1.2.3.4.5.6.7.8.9.0.a.b.c.d.e.f.g.h.i.j.k.l.m.n.o', '2024-08-20', 'Av. Jerônimo de Albuquerque', '12', 'Vinhais', 'São Luís', 'MA', '65074-220', 'Diretora Marta'),

('Camila Nunes Dias', 'Feminino', 1, '012.345.678-90', '(99) 3999-5555', '(99) 99456-5555', 'camila.dias@email.com', '$2y$10$Be4.1.2.3.4.5.6.7.8.9.0.a.b.c.d.e.f.g.h.i.j.k.l.m.n.o', '2024-09-10', 'Rua Magalhães de Almeida', '33', 'Centro', 'Pinheiro', 'MA', '65200-000', 'Camila'),

('Bruno Gomes Taveira', 'Masculino', 1, '123.555.777-88', '(98) 3288-6666', '(98) 99567-6666', 'bruno.taveira@email.com', '$2y$10$Be4.1.2.3.4.5.6.7.8.9.0.a.b.c.d.e.f.g.h.i.j.k.l.m.n.o', '2024-10-01', 'Rua dos Afogados', '89', 'Centro', 'São Luís', 'MA', '65010-020', 'Bruno');