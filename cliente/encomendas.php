<?php
/**
 * Cliente — Minhas Encomendas
 */
define('PAGE_TITLE', 'Minhas Encomendas');
require_once __DIR__ . '/../includes/functions.php';

requireLogin('Por favor, inicia sessão para ver as tuas encomendas.');
requireRole(['cliente', 'admin', 'gestor']);

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
            SELECT oi.*, pv.color, pv.size, p.brand, p.model, p.image, p.id AS product_id
            FROM   order_items oi
            JOIN   product_variants pv ON pv.id = oi.variant_id
            JOIN   products p ON p.id = pv.product_id
            WHERE  oi.order_id = ?
        ");
        $stmt->execute([$detailId]);
        $detailItems = $stmt->fetchAll();
    }
}

// Lista de encomendas do cliente (inclui campos de confirmação se a migração foi aplicada)
try {
    $stmtOrders = $db->prepare("
        SELECT o.id, o.status, o.total, o.created_at,
               o.cliente_confirmou, o.data_confirmacao, o.feedback_cliente, o.avaliacao,
               COUNT(oi.id) AS item_count
        FROM   orders o
        LEFT JOIN order_items oi ON oi.order_id = o.id
        WHERE  o.user_id = ?
        GROUP  BY o.id
        ORDER  BY o.created_at DESC
    ");
    $stmtOrders->execute([$userId]);
    $orders = $stmtOrders->fetchAll();
} catch (PDOException $e) {
    $stmtOrders = $db->prepare("
        SELECT o.id, o.status, o.total, o.created_at,
               0 AS cliente_confirmou, NULL AS data_confirmacao,
               NULL AS feedback_cliente, NULL AS avaliacao,
               COUNT(oi.id) AS item_count
        FROM   orders o
        LEFT JOIN order_items oi ON oi.order_id = o.id
        WHERE  o.user_id = ?
        GROUP  BY o.id
        ORDER  BY o.created_at DESC
    ");
    $stmtOrders->execute([$userId]);
    $orders = $stmtOrders->fetchAll();
}

// Popup: encomendas entregues à espera de confirmação
$pendingConfirm = getClientPendingConfirmCount($userId);

include __DIR__ . '/../includes/header.php';
?>

<?php if ($pendingConfirm > 0 && !isset($_GET['id'])): ?>
<!-- Popup: confirmar entrega pendente -->
<div class="modal-overlay" id="delivery-popup" role="dialog" aria-modal="true" aria-labelledby="popup-title">
    <div class="modal" style="max-width:440px">
        <div class="modal-header">
            <h3 id="popup-title">📦 Tens entregas para confirmar</h3>
            <button class="modal-close" onclick="document.getElementById('delivery-popup').remove()">×</button>
        </div>
        <div class="modal-body" style="text-align:center">
            <p style="font-size:15px; margin-bottom:20px;">
                Tens <strong><?= $pendingConfirm ?></strong> encomenda<?= $pendingConfirm > 1 ? 's' : '' ?>
                <?= $pendingConfirm > 1 ? 'marcadas' : 'marcada' ?> como <strong>Entregue</strong>
                à espera da tua confirmação.<br>
                Confirma a recepção para concluir a encomenda.
            </p>
            <button class="btn btn-ghost" onclick="document.getElementById('delivery-popup').remove()">
                Ver mais tarde
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

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
                                <a href="/produto.php?id=<?= $i['product_id'] ?>" style="font-weight:700">
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

            <!-- Confirmação de entrega (só se 'entregue' e não confirmada) -->
            <?php if ($detailOrder['status'] === 'entregue' && !($detailOrder['cliente_confirmou'] ?? 0)): ?>
            <div style="padding:24px; border-top:1px solid var(--border); background:#fefdf6;">
                <h4 style="font-size:15px; font-weight:800; margin-bottom:8px;">Confirmar receção da encomenda</h4>
                <p class="text-muted" style="font-size:13px; margin-bottom:16px;">
                    Recebeste a tua encomenda? Confirma a entrega abaixo. Podes deixar um comentário opcional.
                </p>
                <div style="margin-bottom:12px;">
                    <div class="selector-label">Avaliação (opcional)</div>
                    <div class="star-rating" id="star-rating">
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                            <button type="button" class="star-btn" data-val="<?= $s ?>" title="<?= $s ?> estrela<?= $s > 1 ? 's' : '' ?>">★</button>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" id="avaliacao-val" value="">
                </div>
                <textarea id="feedback-text" class="form-control" rows="3"
                    placeholder="Comentário sobre a encomenda (opcional)..."
                    style="max-width:540px; margin-bottom:12px;"></textarea>
                <button id="confirm-order-btn" class="btn btn-red"
                    data-order-id="<?= $detailOrder['id'] ?>">
                    ✓ Confirmar Entrega
                </button>
                <div id="confirm-result" style="margin-top:10px; font-size:14px;"></div>
            </div>
            <?php elseif ($detailOrder['status'] === 'concluida'): ?>
            <div style="padding:20px 24px; border-top:1px solid var(--border); background:#f0fdf4;">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                    <span style="font-size:20px;">✅</span>
                    <strong style="color:var(--success);">Entrega confirmada</strong>
                    <?php if ($detailOrder['data_confirmacao'] ?? null): ?>
                        <span class="text-muted" style="font-size:12px;">em <?= date('d/m/Y H:i', strtotime($detailOrder['data_confirmacao'])) ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($detailOrder['avaliacao'] ?? null): ?>
                    <div style="margin-bottom:6px;">
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                            <span style="color:<?= $s <= ($detailOrder['avaliacao'] ?? 0) ? '#f59e0b' : '#d1d5db' ?>; font-size:18px;">★</span>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
                <?php if ($detailOrder['feedback_cliente'] ?? null): ?>
                    <p style="font-size:14px; color:var(--mid); font-style:italic;">
                        "<?= e($detailOrder['feedback_cliente']) ?>"
                    </p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

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
                            <td>
                                <span class="badge <?= orderStatusBadge($o['status']) ?>"><?= orderStatusLabel($o['status']) ?></span>
                                <?php if ($o['status'] === 'entregue' && !($o['cliente_confirmou'] ?? 0)): ?>
                                    <span style="display:inline-block; margin-left:6px; font-size:10px; background:#fef3c7; color:#92400e; padding:2px 6px; border-radius:4px; font-weight:700;">Confirmar</span>
                                <?php endif; ?>
                            </td>
                            <td><a href="?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline">Ver detalhes</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
(function () {
    // Star rating
    var stars = document.querySelectorAll('.star-btn');
    var ratingInput = document.getElementById('avaliacao-val');
    if (stars.length) {
        stars.forEach(function (btn) {
            btn.addEventListener('mouseenter', function () {
                highlightStars(+this.dataset.val);
            });
            btn.addEventListener('mouseleave', function () {
                highlightStars(ratingInput ? +ratingInput.value : 0);
            });
            btn.addEventListener('click', function () {
                ratingInput.value = this.dataset.val;
                highlightStars(+this.dataset.val);
            });
        });
    }
    function highlightStars(n) {
        stars.forEach(function (b) {
            b.style.color = +b.dataset.val <= n ? '#f59e0b' : '#d1d5db';
        });
    }

    // Confirm order
    var confirmBtn = document.getElementById('confirm-order-btn');
    if (!confirmBtn) return;
    confirmBtn.addEventListener('click', async function () {
        var orderId   = +this.dataset.orderId;
        var feedback  = document.getElementById('feedback-text').value.trim();
        var avaliacao = document.getElementById('avaliacao-val').value;

        confirmBtn.disabled = true;
        confirmBtn.textContent = 'A confirmar...';

        try {
            var res  = await fetch('/api/confirmar_encomenda.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: orderId, feedback: feedback, avaliacao: avaliacao ? +avaliacao : null })
            });
            var data = await res.json();
            var resultEl = document.getElementById('confirm-result');
            if (data.success) {
                resultEl.innerHTML = '<span style="color:var(--success); font-weight:700;">✓ ' + data.message + '</span>';
                setTimeout(function () { location.reload(); }, 1200);
            } else {
                resultEl.innerHTML = '<span style="color:var(--danger);">✕ ' + data.message + '</span>';
                confirmBtn.disabled = false;
                confirmBtn.textContent = '✓ Confirmar Entrega';
            }
        } catch (e) {
            document.getElementById('confirm-result').innerHTML = '<span style="color:var(--danger);">Erro de ligação.</span>';
            confirmBtn.disabled = false;
            confirmBtn.textContent = '✓ Confirmar Entrega';
        }
    });
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
