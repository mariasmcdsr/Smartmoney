-- SmartMoney 1.7
-- Objetivo: deixar o nome do usuário visível nas tabelas principais do banco,
-- para facilitar a apresentação e consulta pela banca no phpMyAdmin.

-- 1) Adicionar coluna usuario na tabela controle_financeiro.
ALTER TABLE controle_financeiro
ADD COLUMN usuario VARCHAR(50) NULL AFTER id_usuario;

-- 2) Preencher registros antigos de controle_financeiro com o nome do usuário.
UPDATE controle_financeiro c
INNER JOIN usuarios u ON c.id_usuario = u.id_usuario
SET c.usuario = u.usuario
WHERE c.usuario IS NULL OR c.usuario = '';

-- 3) Adicionar coluna usuario na tabela gastos.
ALTER TABLE gastos
ADD COLUMN usuario VARCHAR(50) NULL AFTER id_controle;

-- 4) Preencher registros antigos de gastos com o nome do usuário.
UPDATE gastos g
INNER JOIN controle_financeiro c ON g.id_controle = c.id_controle
SET g.usuario = c.usuario
WHERE g.usuario IS NULL OR g.usuario = '';

-- 5) Adicionar coluna usuario na tabela receitas.
ALTER TABLE receitas
ADD COLUMN usuario VARCHAR(50) NULL AFTER id_controle;

-- 6) Preencher registros antigos de receitas com o nome do usuário.
UPDATE receitas r
INNER JOIN controle_financeiro c ON r.id_controle = c.id_controle
SET r.usuario = c.usuario
WHERE r.usuario IS NULL OR r.usuario = '';

-- 7) Adicionar coluna usuario na tabela justificativas_edicao.
ALTER TABLE justificativas_edicao
ADD COLUMN usuario VARCHAR(50) NULL AFTER id_usuario;

-- 8) Preencher registros antigos de justificativas com o nome do usuário.
UPDATE justificativas_edicao j
INNER JOIN usuarios u ON j.id_usuario = u.id_usuario
SET j.usuario = u.usuario
WHERE j.usuario IS NULL OR j.usuario = '';
