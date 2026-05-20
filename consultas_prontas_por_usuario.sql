-- Consultas prontas da versão 1.2
-- Troque Maria pelo nome de usuário cadastrado no sistema.

-- Ver resumo financeiro de uma pessoa:
SELECT * FROM vw_resumo_por_usuario
WHERE usuario = 'Maria';

-- Ver gastos de uma pessoa:
SELECT * FROM vw_gastos_por_usuario
WHERE usuario = 'Maria';

-- Ver receitas adicionais de uma pessoa:
SELECT * FROM vw_receitas_por_usuario
WHERE usuario = 'Maria';

-- Ver justificativas de edição de uma pessoa:
SELECT * FROM vw_justificativas_por_usuario
WHERE usuario = 'Maria';

-- Pesquisar sem saber o nome completo:
SELECT * FROM vw_gastos_por_usuario
WHERE usuario LIKE '%maria%';

-- Ver gastos de uma pessoa em um mês e ano:
SELECT * FROM vw_gastos_por_usuario
WHERE usuario = 'Maria'
AND MONTH(data_gasto) = 5
AND YEAR(data_gasto) = 2026;

-- Ver receitas de uma pessoa em um mês e ano:
SELECT * FROM vw_receitas_por_usuario
WHERE usuario = 'Maria'
AND MONTH(data_receita) = 5
AND YEAR(data_receita) = 2026;
