-- SmartMoney versão 1.1 - estrutura completa para banco novo
-- Use este arquivo somente se for criar outro banco do zero.

CREATE DATABASE smartmoney;
USE smartmoney;

CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE controle_financeiro (
    id_controle INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    salario DECIMAL(10,2) NOT NULL,
    total_rendas DECIMAL(10,2) NOT NULL,
    total_gastos DECIMAL(10,2) NOT NULL,
    sobra DECIMAL(10,2) NOT NULL,
    porcentagem_gasta DECIMAL(5,2) NOT NULL,
    situacao VARCHAR(20) NOT NULL,
    data_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

CREATE TABLE receitas (
    id_receita INT AUTO_INCREMENT PRIMARY KEY,
    id_controle INT NOT NULL,
    tipo_receita VARCHAR(100) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    data_receita DATE NOT NULL,
    FOREIGN KEY (id_controle) REFERENCES controle_financeiro(id_controle)
);

CREATE TABLE gastos (
    id_gasto INT AUTO_INCREMENT PRIMARY KEY,
    id_controle INT NOT NULL,
    tipo_gasto VARCHAR(100) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    data_gasto DATE NOT NULL,
    FOREIGN KEY (id_controle) REFERENCES controle_financeiro(id_controle)
);

CREATE TABLE justificativas_edicao (
    id_justificativa INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    tipo_registro VARCHAR(20) NOT NULL,
    id_registro INT NOT NULL,
    campo_editado VARCHAR(100) NOT NULL,
    valor_antigo VARCHAR(255),
    valor_novo VARCHAR(255),
    justificativa TEXT NOT NULL,
    data_edicao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- Views da versão 1.2 para facilitar consulta pelo nome de usuário.
CREATE OR REPLACE VIEW vw_resumo_por_usuario AS
SELECT
    u.id_usuario,
    u.usuario,
    c.id_controle,
    c.salario,
    c.total_rendas,
    c.total_gastos,
    c.sobra,
    c.porcentagem_gasta,
    c.situacao,
    c.data_registro
FROM usuarios u
INNER JOIN controle_financeiro c ON u.id_usuario = c.id_usuario;

CREATE OR REPLACE VIEW vw_gastos_por_usuario AS
SELECT
    u.id_usuario,
    u.usuario,
    c.id_controle,
    g.id_gasto,
    g.tipo_gasto,
    g.valor,
    g.data_gasto,
    c.salario,
    c.total_rendas,
    c.total_gastos,
    c.sobra,
    c.porcentagem_gasta,
    c.situacao,
    c.data_registro
FROM usuarios u
INNER JOIN controle_financeiro c ON u.id_usuario = c.id_usuario
INNER JOIN gastos g ON c.id_controle = g.id_controle;

CREATE OR REPLACE VIEW vw_receitas_por_usuario AS
SELECT
    u.id_usuario,
    u.usuario,
    c.id_controle,
    r.id_receita,
    r.tipo_receita,
    r.valor,
    r.data_receita,
    c.salario,
    c.total_rendas,
    c.total_gastos,
    c.sobra,
    c.porcentagem_gasta,
    c.situacao,
    c.data_registro
FROM usuarios u
INNER JOIN controle_financeiro c ON u.id_usuario = c.id_usuario
INNER JOIN receitas r ON c.id_controle = r.id_controle;

CREATE OR REPLACE VIEW vw_justificativas_por_usuario AS
SELECT
    u.id_usuario,
    u.usuario,
    j.id_justificativa,
    j.tipo_registro,
    j.id_registro,
    j.campo_editado,
    j.valor_antigo,
    j.valor_novo,
    j.justificativa,
    j.data_edicao
FROM usuarios u
INNER JOIN justificativas_edicao j ON u.id_usuario = j.id_usuario;
