<?php
define('PAGE_TITLE', 'Catálogo');
require_once __DIR__ . '/includes/functions.php';

$brandFilter = isset($_GET['brand']) ? trim($_GET['brand']) : '';
$products    = productsGetAll();

if ($brandFilter) {
    $products = array_filter($products, fn($p) => strtolower($p['brand']) === strtolower($brandFilter));
}

$db     = getDB();
$brands = $db->query("SELECT DISTINCT brand FROM products WHERE active = 1 ORDER BY brand")->fetchAll(PDO::FETCH_COLUMN);

include __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top:40px">
    <div class="section-header">
        <div>
            <h2><?= $brandFilter ? e($brandFilter) : 'Todos os Produtos' ?></h2>
            <p id="product-count"><?= count($products) ?> artigo<?= count($products) != 1 ? 's' : '' ?> encontrado<?= count($products) != 1 ? 's' : '' ?></p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="/catalogo.php" class="btn btn-sm <?= !$brandFilter ? 'btn-primary' : 'btn-outline' ?>">Todos</a>
            <?php foreach ($brands as $brand): ?>
                <a href="/catalogo.php?brand=<?= urlencode($brand) ?>"
                   class="btn btn-sm <?= strtolower($brandFilter) === strtolower($brand) ? 'btn-primary' : 'btn-outline' ?>">
                    <?= e($brand) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (empty($products)): ?>
        <div class="empty-cart">
            <div class="icon">🔍</div>
            <h3>Nenhum produto encontrado</h3>
            <p>Tenta remover os filtros ou voltar mais tarde.</p>
            <a href="/catalogo.php" class="btn btn-primary">Ver todos</a>
        </div>
    <?php else: ?>
        <div class="products-grid" id="products-grid">
            <?php foreach ($products as $p): ?>
                <?php $imgSrc = productImageSrc($p['image']); ?>
                <article class="product-card" data-search="<?= strtolower($p['brand'] . ' ' . $p['model']) ?>">
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
                        <a href="/produto.php?id=<?= $p['id'] ?>" class="btn btn-primary btn-full btn-sm">Ver Produto</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div id="no-results" class="empty-cart" style="display:none">
            <div class="icon">🔍</div>
            <h3>Sem resultados</h3>
            <p>Não encontrámos nenhum produto para "<span id="no-results-query"></span>".</p>
            <button class="btn btn-primary" onclick="var s=document.getElementById('header-search');s.value='';s.dispatchEvent(new Event('input'))">Limpar pesquisa</button>
        </div>
    <?php endif; ?>
</div>

<script>
(function () {
    const input  = document.getElementById('header-search');
    const cards  = document.querySelectorAll('#products-grid .product-card');
    const counter= document.getElementById('product-count');
    const grid   = document.getElementById('products-grid');
    const noRes  = document.getElementById('no-results');
    const noResQ = document.getElementById('no-results-query');

    if (!input || !cards.length) return;

    function filter() {
        const q = input.value.trim().toLowerCase();
        let visible = 0;
        cards.forEach(function (card) {
            const match = !q || card.dataset.search.includes(q);
            card.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        if (counter) counter.textContent = visible + ' artigo' + (visible !== 1 ? 's' : '') + ' encontrado' + (visible !== 1 ? 's' : '');
        if (noRes && grid) {
            const empty = visible === 0 && q !== '';
            noRes.style.display = empty ? '' : 'none';
            grid.style.display  = empty ? 'none' : '';
            if (noResQ) noResQ.textContent = input.value.trim();
        }
    }

    input.addEventListener('input', filter);

    const urlQ = new URLSearchParams(location.search).get('q');
    if (urlQ) { input.value = urlQ; filter(); }
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
