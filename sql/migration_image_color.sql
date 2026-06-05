-- Adicionar coluna 'color' à tabela product_images
-- Correr: Get-Content sql/migration_image_color.sql | docker exec -i sneakerstock_db mysql -u sneaker_user -psneaker_pass sneakerstock

USE sneakerstock;

ALTER TABLE product_images ADD COLUMN color VARCHAR(50) NULL DEFAULT NULL AFTER filename;
