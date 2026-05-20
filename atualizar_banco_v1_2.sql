-- SmartMoney versão 1.2
-- Use este arquivo no phpMyAdmin do banco já publicado.
-- NÃO use DROP TABLE e NÃO apague os dados antigos.
-- Esta atualização mantém os dados e adiciona consultas por usuário.

-- 1) Adiciona total_rendas se ainda não existir.
DELIMITER $$
CREATE PROCEDURE add_total_rendas_if_not_exists()
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'controle_financeiro'
          AND COLUMN_NAME = 'total_rendas'
    ) THEN
        ALTER TABLE controle_financeiro
        ADD COLUMN total_rendas DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER salario;
    END IF;
END $$
DELIMITER ;

CALL add_total_rendas_if_not_exists();
DROP PROCEDURE add_total_rendas_if_not_exists;

-- 2) Preenche registros antigos usando salário como total de rendas.
UPDATE controle_financeiro
SET total_rendas = salario
WHERE total_rendas = 0;

-- 3) Cria tabela de receitas adicionais, caso ainda não exista.
CREATE TABLE IF NOT EXISTS receitas (
    id_receita INT AUTO_INCREMENT PRIMARY KEY,
    id_controle INT NOT NULL,
    tipo_receita VARCHAR(100) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    data_receita DATE NOT NULL,
    FOREIGN KEY (id_controle) REFERENCES controle_financeiro(id_controle)
);

-- 4) Cria tabela de justificativas de edição, caso ainda não exista.
CREATE TABLE IF NOT EXISTS justificativas_edicao (
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

-- 5) View para pesquisar o resumo financeiro pelo nome de usuário.
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

-- 6) View para pesquisar gastos pelo nome de usuário.
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

-- 7) View para pesquisar receitas adicionais pelo nome de usuário.
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

-- 8) View para pesquisar justificativas de edição pelo nome de usuário.
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
