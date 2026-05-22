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
    echo '<div class="container text-center" style="padding:80px 0"><h1>Produto não encontrado</h1><a href="/" class="btn btn-primary mt-4">Voltar à loja</a></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

define('PAGE_TITLE', $product['brand'] . ' ' . $product['model']);

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
        <a href="/" style="color:var(--muted)">Loja</a>
        <span style="margin:0 8px">›</span>
        <a href="/?brand=<?= urlencode($product['brand']) ?>" style="color:var(--muted)"><?= e($product['brand']) ?></a>
        <span style="margin:0 8px">›</span>
        <span style="color:var(--dark); font-weight:600"><?= e($product['model']) ?></span>
    </nav>

    <div class="product-detail-grid">
        <!-- Imagem -->
        <div class="product-gallery">
            <?php if ($product['image'] && file_exists(__DIR__ . '/uploads/produtos/' . $product['image'])): ?>
                <img src="/uploads/produtos/<?= e($product['image']) ?>"
                     alt="<?= e($product['brand'] . ' ' . $product['model']) ?>">
            <?php else: ?>
                <div class="product-gallery-placeholder">👟</div>
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
                <a href="/carrinho.php" class="btn btn-outline btn-full">Ver Carrinho</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
