<?php
/**
 * Página de detalhe do produto
 */
require_once __DIR__ . '/includes/functions.php';

$id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = $id ? productGetById($id) : null;

if (!$product) {
    http_response_code(404);
    define('PAGE_TITLE', 'Produto não encontrado');
    include __DIR__ . '/includes/header.php';
    echo '<div class="container text-center" style="padding:80px 0"><h1>Produto não encontrado</h1><a href="'.BASE_URL.'" class="btn btn-primary mt-4">Voltar à loja</a></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

define('PAGE_TITLE', $product['brand'] . ' ' . $product['model']);

// Imagens do produto
$productImages  = productGetImages($id);
if (empty($productImages) && $product['image']) {
    $productImages = [['filename' => $product['image'], 'id' => 0, 'color' => null]];
}
$colorImageMap = productGetColorMap($id);

// Cores disponíveis (com stock > 0)
$colors = productColors($id);

// Primeira cor como pré-seleção
$defaultColor = $colors[0] ?? null;
$defaultSizes = $defaultColor ? productSizes($id, $defaultColor) : [];


include __DIR__ . '/includes/header.php';
?>

<div class="container product-detail">
    <!-- Breadcrumb -->
    <nav style="font-size:13px; color:var(--muted); margin-bottom:32px;">
        <a href="<?= BASE_URL ?>" style="color:var(--muted)">Loja</a>
        <span style="margin:0 8px">›</span>
        <a href="<?= BASE_URL ?>/?brand=<?= urlencode($product['brand']) ?>" style="color:var(--muted)"><?= e($product['brand']) ?></a>
        <span style="margin:0 8px">›</span>
        <span style="color:var(--dark); font-weight:600"><?= e($product['model']) ?></span>
    </nav>

    <div class="product-detail-grid">
        <!-- Galeria de imagens -->
        <div class="product-gallery">
            <?php if (!empty($productImages)): ?>
                <div class="gallery-main-wrap">
                    <img id="gallery-main"
                         src="<?= BASE_URL ?>/uploads/produtos/<?= e($productImages[0]['filename']) ?>"
                         alt="<?= e($product['brand'] . ' ' . $product['model']) ?>">
                    <button class="gallery-nav-btn gallery-nav-prev" id="gallery-prev" aria-label="Imagem anterior">&#10094;</button>
                    <button class="gallery-nav-btn gallery-nav-next" id="gallery-next" aria-label="Imagem seguinte">&#10095;</button>
                </div>
                <?php if (count($productImages) > 1): ?>
                    <div class="gallery-thumbs" id="gallery-thumbs">
                        <?php foreach ($productImages as $i => $img): ?>
                            <img src="<?= BASE_URL ?>/uploads/produtos/<?= e($img['filename']) ?>"
                                 class="gallery-thumb <?= $i === 0 ? 'active' : '' ?>"
                                 alt="">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="product-gallery-placeholder">&#128095;</div>
            <?php endif; ?>
        </div>

        <!-- Informação -->
        <div class="product-info">
            <div class="product-brand"><?= e($product['brand']) ?></div>
            <h1 class="product-name"><?= e($product['model']) ?></h1>
            <div class="product-price-display"><?= formatPrice((float)$product['base_price']) ?></div>

            <?php if ($product['description']): ?>
                <p class="product-desc"><?= nl2br(e($product['description'])) ?></p>
            <?php endif; ?>

            <!-- Seleção de Cor -->
            <?php if (!empty($colors)): ?>
                <div class="selector-label">Cor</div>
                <div class="color-selector">
                    <?php foreach ($colors as $color): ?>
                        <button class="color-option <?= $color === $defaultColor ? 'selected' : '' ?>"
                                data-color="<?= e($color) ?>"
                                data-product-id="<?= $id ?>">
                            <?= e($color) ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- Seleção de Tamanho -->
                <div class="selector-label">Tamanho (EU)</div>
                <div class="size-selector">
                    <?php foreach ($defaultSizes as $v): ?>
                        <button class="size-option <?= $v['stock'] == 0 ? 'out-of-stock' : '' ?>"
                                data-size="<?= e($v['size']) ?>"
                                data-variant-id="<?= $v['variant_id'] ?>"
                                data-stock="<?= $v['stock'] ?>">
                            <?= e($v['size']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <p id="stock-info" class="stock-info"></p>

                <button id="add-to-cart-btn" class="btn btn-red btn-lg btn-full" disabled>
                    Adicionar ao Carrinho
                </button>
            <?php else: ?>
                <div class="alert alert-warning">Produto temporariamente sem stock.</div>
            <?php endif; ?>

            <div style="margin-top:24px;">
                <a href="<?= BASE_URL ?>/carrinho.php" class="btn btn-outline btn-full">Ver Carrinho</a>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var colorMap   = <?= json_encode($colorImageMap, JSON_UNESCAPED_UNICODE) ?>;
    var defColor   = <?= json_encode($defaultColor) ?>;
    var mainImg    = document.getElementById('gallery-main');
    var thumbsWrap = document.getElementById('gallery-thumbs');
    var prevBtn    = document.getElementById('gallery-prev');
    var nextBtn    = document.getElementById('gallery-next');
    var allUrls    = [];
    var current    = 0;

    function updateNav() {
        var multi = allUrls.length > 1;
        if (prevBtn) {
            prevBtn.style.display = multi ? '' : 'none';
            prevBtn.disabled = current === 0;
        }
        if (nextBtn) {
            nextBtn.style.display = multi ? '' : 'none';
            nextBtn.disabled = current >= allUrls.length - 1;
        }
    }

    function setActive(idx) {
        if (!thumbsWrap) return;
        thumbsWrap.querySelectorAll('.gallery-thumb').forEach(function (t, i) {
            t.classList.toggle('active', i === idx);
            if (i === idx) t.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        });
    }

    function goTo(idx) {
        if (!allUrls.length || !mainImg) return;
        current = Math.max(0, Math.min(idx, allUrls.length - 1));
        mainImg.src = allUrls[current];
        setActive(current);
        updateNav();
    }

    function setGallery(urls) {
        if (!urls || !urls.length || !mainImg) return;
        allUrls = urls;
        current = 0;
        mainImg.src = urls[0];
        if (!thumbsWrap) { updateNav(); return; }
        thumbsWrap.style.display = urls.length > 1 ? '' : 'none';
        thumbsWrap.innerHTML = '';
        if (urls.length > 1) {
            urls.forEach(function (url, i) {
                var t = document.createElement('img');
                t.src = url;
                t.className = 'gallery-thumb' + (i === 0 ? ' active' : '');
                t.onclick = function () { goTo(i); };
                thumbsWrap.appendChild(t);
            });
        }
        updateNav();
    }

    // Inicializar a partir das miniaturas renderizadas pelo PHP
    if (thumbsWrap) {
        var initThumbs = thumbsWrap.querySelectorAll('.gallery-thumb');
        allUrls = Array.from(initThumbs).map(function (t) { return t.src; });
        initThumbs.forEach(function (t, i) { t.onclick = function () { goTo(i); }; });
    } else if (mainImg) {
        allUrls = [mainImg.src];
    }

    // Se a cor padrão tiver imagens específicas no colorMap, usar essas
    if (defColor && colorMap[defColor] && colorMap[defColor].length) {
        setGallery(colorMap[defColor]);
    } else {
        updateNav();
    }

    if (prevBtn) prevBtn.addEventListener('click', function () { goTo(current - 1); });
    if (nextBtn) nextBtn.addEventListener('click', function () { goTo(current + 1); });

    // Suporte a swipe em dispositivos móveis
    var touchStartX = 0;
    if (mainImg) {
        mainImg.addEventListener('touchstart', function (e) {
            touchStartX = e.touches[0].clientX;
        }, { passive: true });
        mainImg.addEventListener('touchend', function (e) {
            var dx = e.changedTouches[0].clientX - touchStartX;
            if (Math.abs(dx) > 50) goTo(current + (dx < 0 ? 1 : -1));
        }, { passive: true });
    }

    document.querySelectorAll('.color-option').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var color = this.dataset.color;
            var urls  = colorMap[color] || colorMap['__default__'] || [];
            if (urls.length) setGallery(urls);
        });
    });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
