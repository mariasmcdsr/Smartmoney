-- SmartMoney v1.10
-- Adiciona um nome personalizado para cada controle/histórico financeiro.
-- Execute uma única vez depois de atualizar o banco para a v1.9.

ALTER TABLE controle_financeiro
    ADD COLUMN nome_controle VARCHAR(150) NOT NULL DEFAULT 'Controle financeiro' AFTER usuario;

-- Registros antigos recebem automaticamente o nome padrão "Controle financeiro".
