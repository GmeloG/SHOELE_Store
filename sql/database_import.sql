-- ============================================================
-- Shoele Store — Base de dados completa
-- Inclui: produtos, variantes, imagens, encomendas,
--         confirmação de entrega pelo cliente, feedback,
--         vendas POS, notificações
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';



-- ------------------------------------------------------------
-- products
-- ------------------------------------------------------------
DROP TABLE IF EXISTS products;
CREATE TABLE products (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    brand       VARCHAR(100)  NOT NULL,
    model       VARCHAR(100)  NOT NULL,
    description TEXT          NULL,
    base_price  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    image       VARCHAR(255)  NULL     COMMENT 'Thumbnail principal (sincronizado automaticamente)',
    active      TINYINT(1)    NOT NULL DEFAULT 1,
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- product_variants — stock por cor + tamanho
-- ------------------------------------------------------------
DROP TABLE IF EXISTS product_variants;
CREATE TABLE product_variants (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT           NOT NULL,
    color      VARCHAR(50)   NOT NULL,
    size       VARCHAR(10)   NOT NULL,
    price      DECIMAL(10,2) NULL     COMMENT 'Sobrepõe base_price se definido',
    stock      INT           NOT NULL DEFAULT 0,
    created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_variant_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY uq_variant (product_id, color, size)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- product_images — múltiplas imagens por produto/cor
-- ------------------------------------------------------------
DROP TABLE IF EXISTS product_images;
CREATE TABLE product_images (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT          NOT NULL,
    filename   VARCHAR(255) NOT NULL,
    color      VARCHAR(50)  NULL DEFAULT NULL COMMENT 'NULL = todas as cores',
    sort_order INT          NOT NULL DEFAULT 0,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pi_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- customers — dados de contacto e entrega
-- ------------------------------------------------------------
DROP TABLE IF EXISTS customers;
CREATE TABLE customers (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(150) NOT NULL,
    email      VARCHAR(150) NOT NULL,
    phone      VARCHAR(20)  NULL,
    address    TEXT         NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- users — utilizadores registados
-- Credenciais padrão:
--   admin@shoelestore.test   / admin123
--   gestor@shoelestore.test  / gestor123
--   cliente@shoelestore.test / cliente123
-- ------------------------------------------------------------
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nome       VARCHAR(150) NOT NULL,
    email      VARCHAR(150) NOT NULL,
    password   VARCHAR(255) NOT NULL COMMENT 'Hash bcrypt',
    role       ENUM('admin','gestor','cliente') NOT NULL DEFAULT 'cliente',
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (nome, email, password, role) VALUES
('Administrador',  'admin@shoelestore.test',   '$2y$10$lzrFYu7j79qcgNzSrZYvM.P.C0EO5pbVx4vALeD5PFiHkQM7XCqO6', 'admin'),
('Gestor',         'gestor@shoelestore.test',  '$2y$10$wZDyqX/WFWGL6cCQ4SNw6eUX07PzMsVZhB9cL4RgifMfOBtSF4.b.', 'gestor'),
('Cliente Teste',  'cliente@shoelestore.test', '$2y$10$WfSwrUgzUV7onSEJn/cxueC.lZRwCFIQsjIQLd34QvNxN6PXkIAnO', 'cliente');

-- ------------------------------------------------------------
-- orders — encomendas online
-- ------------------------------------------------------------
DROP TABLE IF EXISTS orders;
CREATE TABLE orders (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    customer_id         INT           NOT NULL,
    user_id             INT           NULL     COMMENT 'NULL = encomenda como convidado',
    status              ENUM('encomendada','em_armazem','entregue','cancelada','concluida')
                                      NOT NULL DEFAULT 'encomendada',
    cliente_confirmou   TINYINT(1)    NOT NULL DEFAULT 0     COMMENT '1 = cliente confirmou receção',
    data_confirmacao    DATETIME      NULL     DEFAULT NULL,
    feedback_cliente    TEXT          NULL,
    avaliacao           TINYINT       NULL     DEFAULT NULL  COMMENT '1-5 estrelas',
    total               DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    notes               TEXT          NULL,
    created_at          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_order_customer FOREIGN KEY (customer_id) REFERENCES customers(id),
    CONSTRAINT fk_order_user     FOREIGN KEY (user_id)     REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- order_items
-- ------------------------------------------------------------
DROP TABLE IF EXISTS order_items;
CREATE TABLE order_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    order_id   INT           NOT NULL,
    variant_id INT           NOT NULL,
    quantity   INT           NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_oi_order   FOREIGN KEY (order_id)   REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_oi_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- sales — vendas POS / loja física
-- ------------------------------------------------------------
DROP TABLE IF EXISTS sales;
CREATE TABLE sales (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    sale_type  ENUM('loja','online') NOT NULL DEFAULT 'loja',
    total      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    notes      TEXT          NULL,
    created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- sale_items
-- ------------------------------------------------------------
DROP TABLE IF EXISTS sale_items;
CREATE TABLE sale_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    sale_id    INT           NOT NULL,
    variant_id INT           NOT NULL,
    quantity   INT           NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_si_sale    FOREIGN KEY (sale_id)    REFERENCES sales(id) ON DELETE CASCADE,
    CONSTRAINT fk_si_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- stock_movements — auditoria de movimentos de stock
-- ------------------------------------------------------------
DROP TABLE IF EXISTS stock_movements;
CREATE TABLE stock_movements (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    variant_id     INT  NOT NULL,
    movement_type  ENUM('entrada','saida_venda','saida_pos','devolucao','ajuste') NOT NULL,
    quantity       INT  NOT NULL COMMENT 'Positivo = entrada, Negativo = saída',
    reference_id   INT  NULL,
    reference_type ENUM('order','sale','manual') NULL,
    notes          TEXT NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sm_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- notifications — painel de notificações admin/gestor
-- ------------------------------------------------------------
DROP TABLE IF EXISTS notifications;
CREATE TABLE notifications (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    type         VARCHAR(50)  NOT NULL COMMENT 'stock_critico | nova_encomenda | feedback | cancelamento',
    reference_id INT          NULL,
    message      VARCHAR(500) NOT NULL,
    seen         TINYINT(1)   NOT NULL DEFAULT 0,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_seen    (seen),
    INDEX idx_type    (type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DADOS DE EXEMPLO
-- ============================================================

-- Produtos
INSERT INTO products (brand, model, description, base_price, active) VALUES
('New Balance', '530',         'Inspirada nos anos 90, a NB 530 combina tecidos premium com a tecnologia ABZORB para absorção de impacto. Um ícone atemporal com silhueta elegante e estilo desportivo.', 109.99, 1),
('New Balance', '574',         'Uma das sapatilhas mais icónicas da New Balance. A 574 apresenta um design clássico com tecnologia ENCAP na sola, combinando estilo retro com conforto moderno.', 89.99,  1),
('New Balance', '327',         'Inspirada nos anos 70, a 327 destaca-se pela sola ondulada única e pelo design minimalista. Perfeita para quem procura um look diferente e irreverente.', 99.99,  1),
('New Balance', '2002R',       'Sapatilha premium com tecnologia ABZORB SBS e N-ergy para máximo conforto. Design sofisticado que combina materiais de alta qualidade com estética moderna.', 149.99, 1),
('Nike',        'Air Force 1', 'Uma lenda do basquetebol transformada em ícone de moda. A Nike Air Force 1 apresenta a tecnologia Air na sola e um design limpo que nunca passa de moda.', 119.99, 1),
('Nike',        'Air Max 90',  'Com a janela Air visível na sola traseira, a Air Max 90 é uma das sapatilhas mais reconhecíveis do mundo. Conforto excepcional com estética bold dos anos 90.', 129.99, 1),
('Adidas',      'Samba',       'A lendária sapatilha de futsal dos anos 50 reinventada como ícone de streetwear. A Adidas Samba tem uma silhueta inconfundível com detalhes em camurça.', 99.99,  1),
('Adidas',      'Gazelle',     'Criada em 1966, a Adidas Gazelle é uma das sapatilhas mais vendidas de sempre. Design minimalista em camurça com tiras de borracha clássicas.', 89.99,  1),
('Puma',        'Suede Classic','A Puma Suede é uma das sapatilhas mais emblemáticas da história do desporto. Desde 1968, o seu design simples em camurça é sinónimo de estilo urbano.', 79.99,  1),
('Vans',        'Old Skool',   'O primeiro calçado da Vans a exibir a famosa Jazz Stripe. A Old Skool combina cabedal de lona e camurça com sola Waffle para durabilidade e aderência.', 74.99,  1);

-- Variantes: New Balance 530 (id=1)
INSERT INTO product_variants (product_id, color, size, stock) VALUES
(1,'Branco','38',6),(1,'Branco','39',8),(1,'Branco','40',10),(1,'Branco','41',7),(1,'Branco','42',5),(1,'Branco','43',4),(1,'Branco','44',3),
(1,'Cinzento','38',4),(1,'Cinzento','39',6),(1,'Cinzento','40',8),(1,'Cinzento','41',6),(1,'Cinzento','42',4),(1,'Cinzento','43',2),(1,'Cinzento','44',1),
(1,'Azul','39',3),(1,'Azul','40',5),(1,'Azul','41',7),(1,'Azul','42',4),(1,'Azul','43',2);

-- Variantes: New Balance 574 (id=2)
INSERT INTO product_variants (product_id, color, size, stock) VALUES
(2,'Verde','37',5),(2,'Verde','38',7),(2,'Verde','39',9),(2,'Verde','40',8),(2,'Verde','41',6),(2,'Verde','42',4),(2,'Verde','43',2),
(2,'Azul Marinho','38',4),(2,'Azul Marinho','39',6),(2,'Azul Marinho','40',7),(2,'Azul Marinho','41',5),(2,'Azul Marinho','42',3),
(2,'Preto','38',3),(2,'Preto','39',5),(2,'Preto','40',6),(2,'Preto','41',5),(2,'Preto','42',3),(2,'Preto','43',1);

-- Variantes: New Balance 327 (id=3)
INSERT INTO product_variants (product_id, color, size, stock) VALUES
(3,'Bege','37',4),(3,'Bege','38',6),(3,'Bege','39',8),(3,'Bege','40',7),(3,'Bege','41',5),(3,'Bege','42',3),
(3,'Preto','38',5),(3,'Preto','39',7),(3,'Preto','40',9),(3,'Preto','41',6),(3,'Preto','42',4),(3,'Preto','43',2);

-- Variantes: New Balance 2002R (id=4)
INSERT INTO product_variants (product_id, color, size, stock) VALUES
(4,'Branco','39',4),(4,'Branco','40',6),(4,'Branco','41',5),(4,'Branco','42',3),(4,'Branco','43',2),
(4,'Castanho','39',3),(4,'Castanho','40',5),(4,'Castanho','41',4),(4,'Castanho','42',2),
(4,'Cinzento','40',2),(4,'Cinzento','41',3),(4,'Cinzento','42',2),(4,'Cinzento','43',1);

-- Variantes: Nike Air Force 1 (id=5)
INSERT INTO product_variants (product_id, color, size, stock) VALUES
(5,'Branco','37',5),(5,'Branco','38',8),(5,'Branco','39',10),(5,'Branco','40',9),(5,'Branco','41',7),(5,'Branco','42',5),(5,'Branco','43',3),(5,'Branco','44',2),
(5,'Preto','38',4),(5,'Preto','39',6),(5,'Preto','40',7),(5,'Preto','41',5),(5,'Preto','42',3),(5,'Preto','43',2);

-- Variantes: Nike Air Max 90 (id=6)
INSERT INTO product_variants (product_id, color, size, stock) VALUES
(6,'Branco/Vermelho','38',3),(6,'Branco/Vermelho','39',5),(6,'Branco/Vermelho','40',6),(6,'Branco/Vermelho','41',4),(6,'Branco/Vermelho','42',3),(6,'Branco/Vermelho','43',1),
(6,'Preto','38',4),(6,'Preto','39',6),(6,'Preto','40',7),(6,'Preto','41',5),(6,'Preto','42',4),(6,'Preto','43',2),(6,'Preto','44',1);

-- Variantes: Adidas Samba (id=7)
INSERT INTO product_variants (product_id, color, size, stock) VALUES
(7,'Branco/Preto','36',4),(7,'Branco/Preto','37',6),(7,'Branco/Preto','38',8),(7,'Branco/Preto','39',9),(7,'Branco/Preto','40',7),(7,'Branco/Preto','41',5),(7,'Branco/Preto','42',3),
(7,'Preto/Branco','37',3),(7,'Preto/Branco','38',5),(7,'Preto/Branco','39',6),(7,'Preto/Branco','40',6),(7,'Preto/Branco','41',4),(7,'Preto/Branco','42',2);

-- Variantes: Adidas Gazelle (id=8)
INSERT INTO product_variants (product_id, color, size, stock) VALUES
(8,'Azul','36',3),(8,'Azul','37',5),(8,'Azul','38',7),(8,'Azul','39',8),(8,'Azul','40',6),(8,'Azul','41',4),(8,'Azul','42',2),
(8,'Verde','36',2),(8,'Verde','37',4),(8,'Verde','38',5),(8,'Verde','39',6),(8,'Verde','40',5),(8,'Verde','41',3),
(8,'Vermelho','37',2),(8,'Vermelho','38',4),(8,'Vermelho','39',5),(8,'Vermelho','40',4),(8,'Vermelho','41',2);

-- Variantes: Puma Suede Classic (id=9)
INSERT INTO product_variants (product_id, color, size, stock) VALUES
(9,'Preto','36',4),(9,'Preto','37',6),(9,'Preto','38',7),(9,'Preto','39',8),(9,'Preto','40',6),(9,'Preto','41',4),(9,'Preto','42',3),(9,'Preto','43',1),
(9,'Vermelho','37',2),(9,'Vermelho','38',4),(9,'Vermelho','39',5),(9,'Vermelho','40',4),(9,'Vermelho','41',2),
(9,'Azul Marinho','38',3),(9,'Azul Marinho','39',5),(9,'Azul Marinho','40',4),(9,'Azul Marinho','41',3);

-- Variantes: Vans Old Skool (id=10)
INSERT INTO product_variants (product_id, color, size, stock) VALUES
(10,'Preto/Branco','36',5),(10,'Preto/Branco','37',7),(10,'Preto/Branco','38',9),(10,'Preto/Branco','39',10),(10,'Preto/Branco','40',8),(10,'Preto/Branco','41',6),(10,'Preto/Branco','42',4),(10,'Preto/Branco','43',2),
(10,'Branco','36',3),(10,'Branco','37',5),(10,'Branco','38',6),(10,'Branco','39',7),(10,'Branco','40',5),(10,'Branco','41',3);

-- Clientes de exemplo
INSERT INTO customers (name, email, phone, address) VALUES
('Ana Silva',    'ana.silva@email.com',   '912345678', 'Rua das Flores, 10, 4000-001 Porto'),
('João Santos',  'joao.santos@email.com', '923456789', 'Av. da República, 55, 1050-001 Lisboa'),
('Maria Costa',  'maria.costa@email.com', '934567890', 'Rua do Comércio, 22, 3000-001 Coimbra');

-- Encomendas de exemplo (sem user_id — utilizadores criados pelo auto-seed)
INSERT INTO orders (customer_id, status, total) VALUES
(1, 'entregue',   109.99),
(2, 'em_armazem', 219.98),
(3, 'encomendada', 89.99);

INSERT INTO order_items (order_id, variant_id, quantity, unit_price) VALUES
(1,  3, 1, 109.99),
(2, 45, 1, 119.99),
(2, 62, 1,  99.99),
(3, 21, 1,  89.99);

INSERT INTO stock_movements (variant_id, movement_type, quantity, reference_id, reference_type, notes) VALUES
( 3, 'saida_venda', -1, 1, 'order', 'Encomenda #1'),
(45, 'saida_venda', -1, 2, 'order', 'Encomenda #2'),
(62, 'saida_venda', -1, 2, 'order', 'Encomenda #2'),
(21, 'saida_venda', -1, 3, 'order', 'Encomenda #3');

-- Vendas POS de exemplo
INSERT INTO sales (sale_type, total, notes) VALUES
('loja', 74.99,  'Venda em loja — pagamento em dinheiro'),
('loja', 189.98, 'Venda em loja — pagamento com cartão');

INSERT INTO sale_items (sale_id, variant_id, quantity, unit_price) VALUES
(1, 80, 1,  74.99),
(2,  5, 1, 109.99),
(2, 25, 1,  79.99);

INSERT INTO stock_movements (variant_id, movement_type, quantity, reference_id, reference_type, notes) VALUES
(80, 'saida_pos', -1, 1, 'sale', 'Venda POS #1'),
( 5, 'saida_pos', -1, 2, 'sale', 'Venda POS #2'),
(25, 'saida_pos', -1, 2, 'sale', 'Venda POS #2');

SET foreign_key_checks = 1;
