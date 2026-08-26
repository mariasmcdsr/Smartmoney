-- SmartMoney versão 1.1
-- Use este arquivo no phpMyAdmin do banco já publicado.
-- NÃO use DROP TABLE e NÃO apague os dados antigos.

-- 1) Adiciona o total de rendas no resumo financeiro.
-- Se aparecer erro dizendo que a coluna já existe, ignore e continue para os próximos comandos.
ALTER TABLE controle_financeiro
ADD COLUMN total_rendas DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER salario;

-- 2) Preenche os registros antigos usando o salário como total de rendas.
UPDATE controle_financeiro
SET total_rendas = salario
WHERE total_rendas = 0;

-- 3) Cria tabela para receitas adicionais, como hora extra, salubridade, bico, empréstimo recebido etc.
CREATE TABLE IF NOT EXISTS receitas (
    id_receita INT AUTO_INCREMENT PRIMARY KEY,
    id_controle INT NOT NULL,
    tipo_receita VARCHAR(100) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    data_receita DATE NOT NULL,
    FOREIGN KEY (id_controle) REFERENCES controle_financeiro(id_controle)
);

-- 4) Cria tabela para guardar justificativas de edição.
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
