# SmartMoney

Sistema web de controle financeiro pessoal desenvolvido em **PHP** e **MySQL**, com cadastro de usuários, login, histórico individual, gráficos, edição, exclusão e justificativas de alterações.

## Direitos de uso

Este projeto foi desenvolvido para fins acadêmicos pelos autores Aedra Otaviano, Maria Clara Ramos, Beatriz Silva, Rafael Dias e Altemar Calixto.

O código-fonte está disponível apenas para visualização e apresentação acadêmica. Não é permitido copiar, modificar, redistribuir ou utilizar este projeto para fins comerciais sem autorização das autoras.

## Sobre o projeto

O **SmartMoney** é um sistema criado para auxiliar no controle financeiro pessoal. A aplicação permite que o usuário registre sua renda principal, cadastre receitas adicionais, informe gastos personalizados e acompanhe automaticamente sua situação financeira.

O sistema calcula o total de rendas, o total de gastos, a sobra disponível, a porcentagem da renda comprometida e classifica a situação financeira do usuário como:

- Controlado
- Alerta
- Endividado

O projeto foi desenvolvido inicialmente para fins acadêmicos, como parte do curso Técnico de Nível Médio em Informática.

## Objetivo

O objetivo do SmartMoney é facilitar a organização financeira do usuário, permitindo visualizar de forma simples quanto entra, quanto sai e qual é a situação financeira com base nos valores informados.

## Funcionalidades

- Cadastro de usuários
- Login e logout com sessão
- Senha criptografada
- Registro de renda principal
- Cadastro de receitas adicionais
- Cadastro de gastos personalizados
- Adição de vários gastos
- Data individual para cada gasto
- Histórico financeiro individual por usuário
- Visualização detalhada de receitas e gastos
- Edição de gastos
- Edição de receitas
- Remoção de gastos
- Remoção de receitas
- Remoção de histórico completo
- Justificativa obrigatória para edições e exclusões
- Registro de justificativas no banco de dados
- Gráfico financeiro
- Filtros por período
- Tema claro e tema escuro
- Banco de dados relacional em MySQL
- Publicação online pelo InfinityFree

## Tecnologias utilizadas

- HTML
- CSS
- JavaScript
- PHP
- MySQL
- phpMyAdmin
- InfinityFree

## Estrutura dos arquivos

```text
index.php
cadastro.php
login.php
logout.php
money.php
salario.php
historico.php
detalhes.php
editar_gasto.php
editar_receita.php
adicionar_gasto_historico.php
remover_historico.php
conexao.php