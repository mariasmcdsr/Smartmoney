-- SmartMoney versão 1.7 - BANCO DO ZERO para XAMPP/local
-- Este arquivo apaga e recria o banco smartmoney.
-- Use somente em teste/local ou em banco vazio.

DROP DATABASE IF EXISTS smartmoney;
CREATE DATABASE smartmoney CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE smartmoney;

CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE controle_financeiro (
    id_controle INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    usuario VARCHAR(50) NULL,
    salario DECIMAL(10,2) NOT NULL,
    total_rendas DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_gastos DECIMAL(10,2) NOT NULL DEFAULT 0,
    sobra DECIMAL(10,2) NOT NULL DEFAULT 0,
    porcentagem_gasta DECIMAL(5,2) NOT NULL DEFAULT 0,
    situacao VARCHAR(20) NOT NULL,
    data_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_controle_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE gastos (
    id_gasto INT AUTO_INCREMENT PRIMARY KEY,
    id_controle INT NOT NULL,
    usuario VARCHAR(50) NULL,
    tipo_gasto VARCHAR(100) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    data_gasto DATE NOT NULL,
    CONSTRAINT fk_gastos_controle
        FOREIGN KEY (id_controle) REFERENCES controle_financeiro(id_controle)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE receitas (
    id_receita INT AUTO_INCREMENT PRIMARY KEY,
    id_controle INT NOT NULL,
    usuario VARCHAR(50) NULL,
    tipo_receita VARCHAR(100) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    data_receita DATE NOT NULL,
    CONSTRAINT fk_receitas_controle
        FOREIGN KEY (id_controle) REFERENCES controle_financeiro(id_controle)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE justificativas_edicao (
    id_justificativa INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    usuario VARCHAR(50) NULL,
    tipo_registro VARCHAR(20) NOT NULL,
    id_registro INT NOT NULL,
    campo_editado VARCHAR(100) NOT NULL,
    valor_antigo VARCHAR(255),
    valor_novo VARCHAR(255),
    justificativa TEXT NOT NULL,
    data_edicao DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_justificativas_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Consultas simples para conferir depois:
-- SELECT * FROM usuarios;
-- SELECT * FROM controle_financeiro;
-- SELECT * FROM gastos;
-- SELECT * FROM receitas;
-- SELECT * FROM justificativas_edicao;
