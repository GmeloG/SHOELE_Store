-- ============================================================
-- Shoele Store — Migração de autenticação
-- Executar apenas uma vez em bases de dados já existentes:
--   docker compose exec db mysql -u sneaker_user -psneaker_pass sneakerstock < /sql/migration_auth.sql
-- ============================================================

USE sneakerstock;

-- Tabela de utilizadores
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nome       VARCHAR(150)   NOT NULL,
    email      VARCHAR(150)   NOT NULL,
    password   VARCHAR(255)   NOT NULL,
    role       ENUM('admin','gestor','cliente') NOT NULL DEFAULT 'cliente',
    created_at TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar user_id à tabela de encomendas (se não existir)
ALTER TABLE orders
    ADD COLUMN user_id INT NULL COMMENT 'Utilizador registado (null = convidado)' AFTER customer_id,
    ADD CONSTRAINT fk_order_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;
