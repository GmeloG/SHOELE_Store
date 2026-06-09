<?php
/**
 * Página de confirmação de encomenda
 */
define('PAGE_TITLE', 'Encomenda Confirmada');
require_once __DIR__ . '/includes/functions.php';

$orderId = (int)($_GET['id'] ?? 0);
if (!$orderId) {
    header('Location: '.BASE_URL.'/');
    exit;
}

$db    = getDB();
$stmt  = $db->prepare("
    SELECT o.*, c.name, c.email, c.address
    FROM   orders o
    JOIN   customers c ON c.id = o.customer_id
    WHERE  o.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: '.BASE_URL.'/');
    exit;
}

$stmt = $db->prepare("
    SELECT oi.*, pv.color, pv.size, p.brand, p.model
    FROM   order_items oi
    JOIN   product_variants pv ON pv.id = oi.variant_id
    JOIN   products p ON p.id = pv.product_id
    WHERE  oi.order_id = ?
");
$stmt->execute([$orderId]);
$orderItems = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="container success-section">
    <div class="success-icon">✅</div>
    <h1>Encomenda confirmada!</h1>
    <p>Obrigado, <strong><?= e($order['name']) ?></strong>! A tua encomenda <strong>#<?= $orderId ?></strong> foi recebida com sucesso.</p>
    <p class="text-muted">Receberás a confirmação em <strong><?= e($order['email']) ?></strong>.</p>

    <div style="max-width:500px; margin:32px auto; background:var(--white); border-radius:var(--radius-lg); padding:24px; text-align:left; box-shadow:var(--shadow-sm);">
        <h3 style="font-size:15px; font-weight:800; margin-bottom:16px; padding-bottom:12px; border-bottom:1px solid var(--border);">Detalhes da Encomenda</h3>

        <?php foreach ($orderItems as $item): ?>
            <div class="summary-row" style="font-size:14px;">
                <span><?= e($item['brand'] . ' ' . $item['model']) ?> — <?= e($item['color']) ?> / Nº<?= e($item['size']) ?> × <?= $item['quantity'] ?></span>
                <span><?= formatPrice((float)$item['unit_price'] * $item['quantity']) ?></span>
            </div>
        <?php endforeach; ?>

        <div class="summary-row total">
            <span>Total pago</span>
            <span><?= formatPrice((float)$order['total']) ?></span>
        </div>

        <div style="margin-top:16px; font-size:13px; color:var(--muted);">
            <strong>Morada de entrega:</strong><br>
            <?= nl2br(e($order['address'])) ?>
        </div>
    </div>

    <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
        <a href="<?= BASE_URL ?>" class="btn btn-red btn-lg">Continuar a comprar</a>
        <a href="<?= BASE_URL ?>/cliente/encomendas.php" class="btn btn-outline btn-lg">Minhas Encomendas</a>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
