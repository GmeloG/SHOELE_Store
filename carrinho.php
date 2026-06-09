<?php
/**
 * Carrinho de compras
 */
define('PAGE_TITLE', 'Carrinho');
require_once __DIR__ . '/includes/functions.php';

$items = cartItems();
$total = cartTotal();

include __DIR__ . '/includes/header.php';
?>

<div class="container cart-section">
    <h1 style="font-size:1.8rem; font-weight:900; margin-bottom:8px;">Carrinho</h1>
    <p class="text-muted mb-4"><?= count($items) ?> artigo(s)</p>

    <?php if (empty($items)): ?>
        <div class="empty-cart">
            <div class="icon">🛒</div>
            <h3>O teu carrinho está vazio</h3>
            <p>Ainda não adicionaste nenhum produto. Explora a nossa coleção!</p>
            <a href="<?= BASE_URL ?>" class="btn btn-red btn-lg">Ver Produtos</a>
        </div>
    <?php else: ?>
        <div class="cart-layout">
            <!-- Lista de itens -->
            <div>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Cor / Tamanho</th>
                            <th>Preço</th>
                            <th>Quantidade</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr id="cart-row-<?= $item['variant_id'] ?>">
                                <td>
                                    <div class="cart-item-info">
                                        <div class="cart-item-img">
                                            <?php if ($item['image'] && file_exists(__DIR__ . BASE_URL.'/uploads/produtos/' . $item['image'])): ?>
                                                <img src="<?= BASE_URL ?>/uploads/produtos/<?= e($item['image']) ?>" alt="">
                                            <?php else: ?>
                                                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:28px;background:var(--bg)">👟</div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="cart-item-name">
                                                <a href="<?= BASE_URL ?>/produto.php?id=<?= $item['product_id'] ?>"><?= e($item['brand'] . ' ' . $item['model']) ?></a>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="cart-item-variant"><?= e($item['color']) ?> / Nº<?= e($item['size']) ?></div>
                                </td>
                                <td><?= formatPrice((float)$item['price']) ?></td>
                                <td>
                                    <div class="qty-control">
                                        <button class="qty-btn" data-variant-id="<?= $item['variant_id'] ?>" data-delta="-1">−</button>
                                        <span class="qty-value"><?= $item['quantity'] ?></span>
                                        <button class="qty-btn" data-variant-id="<?= $item['variant_id'] ?>" data-delta="1">+</button>
                                    </div>
                                </td>
                                <td><strong><?= formatPrice($item['subtotal']) ?></strong></td>
                                <td>
                                    <button onclick="removeItem(<?= $item['variant_id'] ?>)" class="btn btn-ghost btn-sm" title="Remover">✕</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="margin-top:16px; display:flex; gap:12px;">
                    <a href="<?= BASE_URL ?>" class="btn btn-outline">← Continuar a comprar</a>
                </div>
            </div>

            <!-- Resumo -->
            <div class="cart-summary">
                <h3>Resumo da Encomenda</h3>

                <?php foreach ($items as $item): ?>
                    <div class="summary-row">
                        <span><?= e($item['brand'] . ' ' . $item['model']) ?> × <?= $item['quantity'] ?></span>
                        <span><?= formatPrice($item['subtotal']) ?></span>
                    </div>
                <?php endforeach; ?>

                <div class="summary-row">
                    <span class="text-muted">Envio</span>
                    <span class="text-muted">Gratuito</span>
                </div>

                <div class="summary-row total">
                    <span>Total</span>
                    <span><?= formatPrice($total) ?></span>
                </div>

                <a href="<?= BASE_URL ?>/checkout.php" class="btn btn-red btn-full btn-lg mt-3">
                    Finalizar Encomenda
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
const BASE_URL = <?= json_encode(BASE_URL) ?>;
async function removeItem(variantId) {
    if (!confirm('Remover este produto do carrinho?')) return;
    await fetch(BASE_URL + '/api/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'remove', variant_id: variantId })
    });
    location.reload();
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
