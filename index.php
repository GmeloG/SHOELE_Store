<?php
/**
 * Página inicial — Catálogo de produtos
 */
define('PAGE_TITLE', 'Catálogo');
require_once __DIR__ . '/includes/functions.php';

$products = productsGetAll();

// Filtrar por marca (parâmetro GET opcional)
$brandFilter = isset($_GET['brand']) ? trim($_GET['brand']) : '';
if ($brandFilter) {
    $products = array_filter($products, fn($p) => strtolower($p['brand']) === strtolower($brandFilter));
}

// Todas as marcas disponíveis para o filtro
$db = getDB();
$brands = $db->query("SELECT DISTINCT brand FROM products WHERE active = 1 ORDER BY brand")->fetchAll(PDO::FETCH_COLUMN);

include __DIR__ . '/includes/header.php';
?>

<section class="catalog-hero">
    <div class="container">
        <h1>Shoele<br><span>Store</span></h1>
        <p>Descobre a nossa coleção de marcas premium — New Balance, Nike, Adidas e muito mais.</p>
    </div>
</section>

<div class="container">
    <!-- Filtros de marca -->
    <div class="section-header">
        <div>
            <h2>Todos os Produtos</h2>
            <p><?= count($products) ?> artigos encontrados</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="/" class="btn btn-sm <?= !$brandFilter ? 'btn-primary' : 'btn-outline' ?>">Todos</a>
            <?php foreach ($brands as $brand): ?>
                <a href="?brand=<?= urlencode($brand) ?>"
                   class="btn btn-sm <?= strtolower($brandFilter) === strtolower($brand) ? 'btn-primary' : 'btn-outline' ?>">
                    <?= e($brand) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Grelha de produtos -->
    <?php if (empty($products)): ?>
        <div class="empty-cart">
            <div class="icon">🔍</div>
            <h3>Nenhum produto encontrado</h3>
            <p>Tenta remover os filtros ou voltar mais tarde.</p>
            <a href="/" class="btn btn-primary">Ver todos</a>
        </div>
    <?php else: ?>
        <div class="products-grid">
            <?php foreach ($products as $p): ?>
                <?php $imgSrc = productImageSrc($p['image']); ?>
                <article class="product-card">
                    <a href="/produto.php?id=<?= $p['id'] ?>" class="card-image">
                        <?php if ($p['image'] && file_exists(__DIR__ . '/uploads/produtos/' . $p['image'])): ?>
                            <img src="<?= e($imgSrc) ?>" alt="<?= e($p['brand'] . ' ' . $p['model']) ?>" loading="lazy">
                        <?php else: ?>
                            <div class="card-placeholder">👟</div>
                        <?php endif; ?>
                    </a>

                    <div class="card-body">
                        <div class="card-brand"><?= e($p['brand']) ?></div>
                        <div class="card-model"><?= e($p['model']) ?></div>
                        <div class="card-price"><?= formatPrice((float)$p['base_price']) ?></div>
                    </div>

                    <div class="card-footer">
                        <a href="/produto.php?id=<?= $p['id'] ?>" class="btn btn-primary btn-full btn-sm">
                            Ver Produto
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
