CREATE DATABASE IF NOT EXISTS gasolina_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE gasolina_db;

-- Tabela de abastecimentos
CREATE TABLE abastecimentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data_abastecimento DATE NOT NULL,
    km_atual INT NOT NULL,
    valor_pago DECIMAL(10,2) NOT NULL,
    litros DECIMAL(10,3) NULL,
    observacao TEXT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_data (data_abastecimento),
    INDEX idx_km (km_atual)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de trocas de óleo
CREATE TABLE trocas_oleo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data_troca DATE NOT NULL,
    km_troca INT NOT NULL,
    valor_pago DECIMAL(10,2) NOT NULL,
    tipo_oleo VARCHAR(100) NULL,
    observacao TEXT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_data (data_troca),
    INDEX idx_km (km_troca)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
