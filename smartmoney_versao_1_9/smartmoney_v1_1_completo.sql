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
