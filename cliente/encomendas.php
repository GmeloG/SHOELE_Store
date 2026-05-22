<?php
/**
 * Cliente — Minhas Encomendas
 */
define('PAGE_TITLE', 'Minhas Encomendas');
require_once __DIR__ . '/../includes/functions.php';

requireLogin('Por favor, inicia sessão para ver as tuas encomendas.');
requireRole(['cliente', 'admin', 'gestor']); // Clientes, admins e gestores podem ver

$userId = (int)currentUser()['id'];
$db     = getDB();

// Detalhe de uma encomenda específica
$detailOrder = null;
$detailItems = [];
if (isset($_GET['id'])) {
    $detailId = (int)$_GET['id'];
    $stmt = $db->prepare("
        SELECT o.*, c.name, c.email, c.phone, c.address
        FROM   orders o JOIN customers c ON c.id = o.customer_id
        WHERE  o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$detailId, $userId]);
    $detailOrder = $stmt->fetch();

    if ($detailOrder) {
        $stmt = $db->prepare("
            SELECT oi.*, pv.color, pv.size, p.brand, p.model, p.image
            FROM   order_items oi
            JOIN   product_variants pv ON pv.id = oi.variant_id
            JOIN   products p ON p.id = pv.product_id
            WHERE  oi.order_id = ?
        ");
        $stmt->execute([$detailId]);
        $detailItems = $stmt->fetchAll();
    }
}

// Lista de encomendas do cliente
$orders = $db->prepare("
    SELECT o.id, o.status, o.total, o.created_at,
           COUNT(oi.id) AS item_count
    FROM   orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE  o.user_id = ?
    GROUP  BY o.id
    ORDER  BY o.created_at DESC
");
$orders->execute([$userId]);
$orders = $orders->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding: 40px 0 80px;">
    <h1 style="font-size:1.8rem; font-weight:900; margin-bottom:8px;">Minhas Encomendas</h1>
    <p class="text-muted mb-4"><?= count($orders) ?> encomenda(s) registada(s).</p>

    <!-- Detalhe da encomenda -->
    <?php if ($detailOrder): ?>
        <div class="table-card mb-4" style="border-left:4px solid var(--red);">
            <div class="table-card-header">
                <h3>Encomenda #<?= $detailOrder['id'] ?></h3>
                <span class="badge <?= orderStatusBadge($detailOrder['status']) ?>"><?= orderStatusLabel($detailOrder['status']) ?></span>
            </div>
            <div style="padding:20px 24px; display:grid; grid-template-columns:1fr 1fr; gap:24px;">
                <div>
                    <div class="selector-label">Entrega para</div>
                    <strong><?= e($detailOrder['name']) ?></strong><br>
                    <span class="text-muted"><?= nl2br(e($detailOrder['address'])) ?></span>
                </div>
                <div>
                    <div class="selector-label">Contacto</div>
                    <span class="text-muted"><?= e($detailOrder['email']) ?></span><br>
                    <?php if ($detailOrder['phone']): ?>
                        <span class="text-muted"><?= e($detailOrder['phone']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <table class="data-table">
                <thead>
                    <tr><th>Produto</th><th>Cor / Tam.</th><th>Qty</th><th>Preço Unit.</th><th>Subtotal</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($detailItems as $i): ?>
                        <tr>
                            <td>
                                <a href="/produto.php?id=<?= $i['product_id'] ?? '' ?>" style="font-weight:700">
                                    <?= e($i['brand'] . ' ' . $i['model']) ?>
                                </a>
                            </td>
                            <td><?= e($i['color']) ?> / Nº<?= e($i['size']) ?></td>
                            <td><?= $i['quantity'] ?></td>
                            <td><?= formatPrice((float)$i['unit_price']) ?></td>
                            <td><?= formatPrice((float)$i['unit_price'] * $i['quantity']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr style="background:var(--bg)">
                        <td colspan="4"><strong>Total</strong></td>
                        <td><strong><?= formatPrice((float)$detailOrder['total']) ?></strong></td>
                    </tr>
                </tbody>
            </table>
            <div style="padding:16px 24px;">
                <a href="/cliente/encomendas.php" class="btn btn-outline btn-sm">← Voltar às encomendas</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Lista de encomendas -->
    <?php if (empty($orders)): ?>
        <div class="empty-cart">
            <div class="icon">📦</div>
            <h3>Ainda não fizeste nenhuma encomenda</h3>
            <p>Explora o nosso catálogo e faz a tua primeira compra!</p>
            <a href="/" class="btn btn-red btn-lg mt-3">Ver Catálogo</a>
        </div>
    <?php else: ?>
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Data</th>
                        <th>Artigos</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td>#<?= $o['id'] ?></td>
                            <td class="text-muted" style="font-size:13px;"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                            <td><?= $o['item_count'] ?> artigo(s)</td>
                            <td><strong><?= formatPrice((float)$o['total']) ?></strong></td>
                            <td><span class="badge <?= orderStatusBadge($o['status']) ?>"><?= orderStatusLabel($o['status']) ?></span></td>
                            <td><a href="?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline">Ver detalhes</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
