-- SmartMoney v1.9
-- Melhorias: controle opcional de casal e moeda por histórico.
-- Execute uma vez no banco da versão 1.8.

ALTER TABLE controle_financeiro
    ADD COLUMN nome_parceiro VARCHAR(100) NULL AFTER usuario,
    ADD COLUMN renda_parceiro DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER salario,
    ADD COLUMN moeda VARCHAR(3) NOT NULL DEFAULT 'BRL' AFTER renda_parceiro;

-- Registros antigos continuam válidos:
-- renda_parceiro = 0 e moeda = BRL.
