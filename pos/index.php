<?php
/**
 * Ponto de Venda (POS) — Interface para vendas em loja
 */
define('PAGE_TITLE', 'Ponto de Venda');
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin', 'gestor']);

$db = getDB();

// Produtos ativos com stock
$products = $db->query("
    SELECT p.id, p.brand, p.model, p.base_price, p.image,
           COALESCE(SUM(pv.stock), 0) AS total_stock
    FROM   products p
    LEFT JOIN product_variants pv ON pv.product_id = p.id
    WHERE  p.active = 1
    GROUP  BY p.id
    HAVING total_stock > 0
    ORDER  BY p.brand, p.model
")->fetchAll();

// Vendas recentes (últimas 10)
$recentSales = $db->query("
    SELECT s.id, s.total, s.created_at,
           COUNT(si.id) AS item_count
    FROM   sales s
    LEFT JOIN sale_items si ON si.sale_id = s.id
    WHERE  s.sale_type = 'loja'
    GROUP  BY s.id
    ORDER  BY s.created_at DESC
    LIMIT  8
")->fetchAll();

// Incluir apenas o header (sem nav padrão, layout especial)
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS — Shoele Store</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg">
</head>
<body>

<header class="site-header">
    <div class="container header-inner">
        <a href="/" class="logo">
            <span class="logo-icon">👟</span>
            <span class="logo-text">SHOELE<strong>STORE</strong></span>
        </a>
        <nav class="main-nav">
            <span style="font-size:13px; font-weight:700; color:var(--red); background:rgba(204,0,0,.08); padding:4px 12px; border-radius:100px;">🖥️ Shoele Store · POS</span>
        </nav>
        <div class="header-actions">
            <a href="/admin/" class="btn btn-sm btn-outline">Admin</a>
            <a href="/" class="btn btn-sm btn-ghost">Loja</a>
        </div>
    </div>
</header>

<div class="pos-layout">
    <!-- Coluna esquerda: produtos -->
    <div class="pos-products">
        <!-- Barra de pesquisa -->
        <div class="pos-search-bar">
            <input type="text" id="pos-search" class="pos-search" placeholder="🔍 Pesquisar por marca ou modelo...">
        </div>

        <div class="pos-products-grid">
            <?php foreach ($products as $p): ?>
                <div class="pos-product-card"
                     data-product-id="<?= $p['id'] ?>"
                     data-name="<?= e($p['brand'] . ' ' . $p['model']) ?>"
                     data-price="<?= $p['base_price'] ?>"
                     data-search="<?= e(strtolower($p['brand'] . ' ' . $p['model'])) ?>">

                    <div class="pos-card-img">
                        <?php if ($p['image'] && file_exists(__DIR__ . '/../uploads/produtos/' . $p['image'])): ?>
                            <img src="/uploads/produtos/<?= e($p['image']) ?>" alt="">
                        <?php else: ?>
                            👟
                        <?php endif; ?>
                    </div>
                    <div class="pos-card-info">
                        <div class="pos-card-brand"><?= e($p['brand']) ?></div>
                        <div class="pos-card-model"><?= e($p['model']) ?></div>
                        <div class="pos-card-price"><?= formatPrice((float)$p['base_price']) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Histórico recente -->
        <?php if (!empty($recentSales)): ?>
        <div style="margin-top:32px;">
            <h3 style="font-size:14px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:var(--muted); margin-bottom:12px;">Vendas Recentes</h3>
            <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:10px;">
                <?php foreach ($recentSales as $s): ?>
                    <div style="background:var(--white); border-radius:var(--radius); padding:12px; box-shadow:var(--shadow-sm); font-size:13px;">
                        <div style="font-weight:700;">#<?= $s['id'] ?></div>
                        <div class="text-muted"><?= $s['item_count'] ?> artigos</div>
                        <div style="font-weight:800; color:var(--dark);"><?= formatPrice((float)$s['total']) ?></div>
                        <div class="text-muted" style="font-size:11px;"><?= date('d/m H:i', strtotime($s['created_at'])) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Coluna direita: carrinho da venda -->
    <div class="pos-cart">
        <div class="pos-header">
            <h2>Venda Atual</h2>
        </div>

        <div class="pos-cart-items" id="pos-cart-items">
            <!-- Preenchido via JS -->
        </div>

        <div class="pos-empty" id="pos-empty">
            <span class="icon">🛒</span>
            <p>Seleciona um produto para começar</p>
        </div>

        <div class="pos-cart-footer">
            <div class="pos-total-row">
                <span class="pos-total-label">Total</span>
                <span class="pos-total-value" id="pos-total">0,00 €</span>
            </div>
            <button id="pos-finalize" class="btn btn-red btn-full btn-lg" disabled>
                Finalizar Venda
            </button>
            <button onclick="window.location.reload()" class="btn btn-ghost btn-full btn-sm mt-2">
                Nova Venda
            </button>
        </div>
    </div>
</div>

<!-- Modal de seleção de variante -->
<div id="variant-modal" class="modal-overlay" style="display:none;">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modal-product-name">Selecionar Variante</h3>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body" id="modal-variants">
            <!-- Preenchido via JS -->
        </div>
    </div>
</div>

<script src="/assets/js/main.js"></script>
</body>
</html>
