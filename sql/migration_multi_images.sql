-- Migração: suporte a múltiplas imagens por produto
-- Correr em phpMyAdmin ou: docker exec -i sneakerstock_db mysql -u sneaker_user -psneaker_pass sneakerstock < migration_multi_images.sql

USE sneakerstock;

CREATE TABLE IF NOT EXISTS product_images (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT           NOT NULL,
    filename   VARCHAR(255)  NOT NULL,
    sort_order INT           NOT NULL DEFAULT 0,
    created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pi_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrar imagens já existentes em products.image para a nova tabela (apenas as que ainda não foram migradas)
INSERT INTO product_images (product_id, filename, sort_order)
SELECT id, image, 0
FROM   products
WHERE  image IS NOT NULL
  AND  image != ''
  AND  id NOT IN (SELECT DISTINCT product_id FROM product_images);
