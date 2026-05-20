-- SmartMoney 1.7 - consultas manuais sem CREATE VIEW
-- Troque maria pelo nome de usuário que deseja pesquisar.

-- Resumo financeiro por usuário:
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
INNER JOIN controle_financeiro c ON u.id_usuario = c.id_usuario
WHERE u.usuario LIKE '%maria%';

-- Gastos por usuário:
SELECT
    u.id_usuario,
    u.usuario,
    c.id_controle,
    g.id_gasto,
    g.tipo_gasto,
    g.valor,
    g.data_gasto,
    c.total_gastos,
    c.sobra,
    c.situacao
FROM usuarios u
INNER JOIN controle_financeiro c ON u.id_usuario = c.id_usuario
INNER JOIN gastos g ON c.id_controle = g.id_controle
WHERE u.usuario LIKE '%maria%';

-- Receitas por usuário:
SELECT
    u.id_usuario,
    u.usuario,
    c.id_controle,
    r.id_receita,
    r.tipo_receita,
    r.valor,
    r.data_receita,
    c.total_rendas,
    c.sobra,
    c.situacao
FROM usuarios u
INNER JOIN controle_financeiro c ON u.id_usuario = c.id_usuario
INNER JOIN receitas r ON c.id_controle = r.id_controle
WHERE u.usuario LIKE '%maria%';

-- Justificativas por usuário:
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
INNER JOIN justificativas_edicao j ON u.id_usuario = j.id_usuario
WHERE u.usuario LIKE '%maria%';
