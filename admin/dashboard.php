<?php
/**
 * Admin — Dashboard
 */
define('PAGE_TITLE', 'Dashboard');
define('ADMIN_PAGE', 'dashboard');
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin', 'gestor']);

$db = getDB();

$stats = [
    'products'   => $db->query("SELECT COUNT(*) FROM products WHERE active = 1")->fetchColumn(),
    'orders'     => $db->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'sales_pos'  => $db->query("SELECT COUNT(*) FROM sales WHERE sale_type = 'loja'")->fetchColumn(),
    'revenue'    => $db->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status NOT IN ('cancelada')")->fetchColumn()
                 + $db->query("SELECT COALESCE(SUM(total), 0) FROM sales")->fetchColumn(),
];

// Notificações
$notif = [
    'pending_orders'  => getPendingOrdersCount(),
    'critical_stock'  => getCriticalStockCount(3),
    'new_feedback'    => getNewFeedbackCount(),
    'cancelled_today' => (int)$db->query("SELECT COUNT(*) FROM orders WHERE status = 'cancelada' AND DATE(updated_at) = CURDATE()")->fetchColumn(),
];

// Stock crítico (≤ 3)
$lowStock = $db->query("
    SELECT p.id, p.brand, p.model, pv.color, pv.size, pv.stock
    FROM   product_variants pv
    JOIN   products p ON p.id = pv.product_id
    WHERE  pv.stock <= 3 AND pv.stock > 0 AND p.active = 1
    ORDER  BY pv.stock ASC
    LIMIT  10
")->fetchAll();

// Encomendas recentes
$recentOrders = $db->query("
    SELECT o.id, o.status, o.total, o.created_at, c.name
    FROM   orders o
    JOIN   customers c ON c.id = o.customer_id
    ORDER  BY o.created_at DESC
    LIMIT  5
")->fetchAll();

include __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/includes/admin_nav.php';
?>

<div class="admin-layout">
    <?= adminNav('dashboard') ?>

    <div class="admin-content">
        <h1 class="admin-page-title">Dashboard</h1>
        <p class="admin-page-subtitle">Resumo das atividades da loja</p>

        <!-- Painel de notificações -->
        <?php $hasNotif = $notif['pending_orders'] > 0 || $notif['critical_stock'] > 0 || $notif['new_feedback'] > 0 || $notif['cancelled_today'] > 0; ?>
        <?php if ($hasNotif): ?>
        <div class="notif-panel mb-4">
            <div class="notif-panel-title">🔔 Notificações</div>
            <div class="notif-items">
                <?php if ($notif['pending_orders'] > 0): ?>
                <a href="/admin/encomendas.php?status=encomendada" class="notif-item notif-warning">
                    <span class="notif-icon">📦</span>
                    <div>
                        <strong><?= $notif['pending_orders'] ?> encomenda<?= $notif['pending_orders'] > 1 ? 's' : '' ?> pendente<?= $notif['pending_orders'] > 1 ? 's' : '' ?></strong>
                        <div class="notif-sub">Aguardam processamento</div>
                    </div>
                    <span class="notif-badge notif-badge-warning"><?= $notif['pending_orders'] ?></span>
                </a>
                <?php endif; ?>

                <?php if ($notif['critical_stock'] > 0): ?>
                <a href="/admin/relatorios.php" class="notif-item notif-danger">
                    <span class="notif-icon">⚠️</span>
                    <div>
                        <strong><?= $notif['critical_stock'] ?> variante<?= $notif['critical_stock'] > 1 ? 's' : '' ?> com stock crítico</strong>
                        <div class="notif-sub">Stock ≤ 3 unidades</div>
                    </div>
                    <span class="notif-badge notif-badge-danger"><?= $notif['critical_stock'] ?></span>
                </a>
                <?php endif; ?>

                <?php if ($notif['new_feedback'] > 0): ?>
                <a href="/admin/encomendas.php?status=concluida" class="notif-item notif-info">
                    <span class="notif-icon">💬</span>
                    <div>
                        <strong><?= $notif['new_feedback'] ?> avaliação<?= $notif['new_feedback'] > 1 ? 'ões' : '' ?> de cliente<?= $notif['new_feedback'] > 1 ? 's' : '' ?></strong>
                        <div class="notif-sub">Feedback em encomendas concluídas</div>
                    </div>
                    <span class="notif-badge notif-badge-info"><?= $notif['new_feedback'] ?></span>
                </a>
                <?php endif; ?>

                <?php if ($notif['cancelled_today'] > 0): ?>
                <a href="/admin/encomendas.php?status=cancelada" class="notif-item notif-secondary">
                    <span class="notif-icon">✕</span>
                    <div>
                        <strong><?= $notif['cancelled_today'] ?> cancelamento<?= $notif['cancelled_today'] > 1 ? 's' : '' ?> hoje</strong>
                        <div class="notif-sub">Encomendas canceladas hoje</div>
                    </div>
                    <span class="notif-badge notif-badge-secondary"><?= $notif['cancelled_today'] ?></span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Cards de estatísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Produtos Ativos</div>
                <div class="stat-value"><?= $stats['products'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Encomendas Online</div>
                <div class="stat-value"><?= $stats['orders'] ?></div>
                <?php if ($notif['pending_orders'] > 0): ?>
                    <div class="stat-sub" style="color:var(--warning)"><?= $notif['pending_orders'] ?> pendentes</div>
                <?php endif; ?>
            </div>
            <div class="stat-card">
                <div class="stat-label">Vendas POS</div>
                <div class="stat-value"><?= $stats['sales_pos'] ?></div>
            </div>
            <div class="stat-card red">
                <div class="stat-label">Receita Total</div>
                <div class="stat-value"><?= formatPrice((float)$stats['revenue']) ?></div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;">
            <!-- Encomendas recentes -->
            <div class="table-card">
                <div class="table-card-header">
                    <h3>Encomendas Recentes</h3>
                    <a href="/admin/encomendas.php" class="btn btn-sm btn-outline">Ver todas</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr><th>#</th><th>Cliente</th><th>Total</th><th>Estado</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $o): ?>
                            <tr>
                                <td><a href="/admin/encomendas.php?id=<?= $o['id'] ?>">#<?= $o['id'] ?></a></td>
                                <td><?= e($o['name']) ?></td>
                                <td><?= formatPrice((float)$o['total']) ?></td>
                                <td><span class="badge <?= orderStatusBadge($o['status']) ?>"><?= orderStatusLabel($o['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Stock crítico -->
            <div class="table-card">
                <div class="table-card-header">
                    <h3>⚠️ Stock Crítico (≤ 3 un.)</h3>
                    <a href="/admin/relatorios.php" class="btn btn-sm btn-outline">Relatórios</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr><th>Produto</th><th>Cor / Tam.</th><th>Stock</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lowStock)): ?>
                            <tr><td colspan="3" class="text-center text-muted" style="padding:24px">Tudo em ordem! ✅</td></tr>
                        <?php else: ?>
                            <?php foreach ($lowStock as $item): ?>
                                <tr>
                                    <td><?= e($item['brand'] . ' ' . $item['model']) ?></td>
                                    <td><?= e($item['color']) ?> / <?= e($item['size']) ?></td>
                                    <td><span class="stock-badge-low"><?= $item['stock'] ?> un.</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Popup de stock crítico (apenas se existir) -->
<?php if ($notif['critical_stock'] > 0): ?>
<div class="modal-overlay" id="critical-stock-popup" role="dialog" aria-modal="true">
    <div class="modal" style="max-width:420px">
        <div class="modal-header" style="border-left:4px solid var(--danger);">
            <h3>⚠️ Stock Crítico</h3>
            <button class="modal-close" onclick="dismissCriticalPopup()">×</button>
        </div>
        <div class="modal-body" style="text-align:center">
            <p style="font-size:15px; margin-bottom:20px;">
                Existem <strong style="color:var(--danger)"><?= $notif['critical_stock'] ?></strong>
                variante<?= $notif['critical_stock'] > 1 ? 's' : '' ?> com stock crítico (≤ 3 unidades).
                Repõe o stock o mais brevemente possível.
            </p>
            <div style="display:flex; gap:10px; justify-content:center">
                <a href="/admin/relatorios.php" class="btn btn-red btn-sm">Ver Stock</a>
                <button class="btn btn-outline btn-sm" onclick="dismissCriticalPopup()">Ignorar</button>
            </div>
        </div>
    </div>
</div>
<script>
function dismissCriticalPopup() {
    document.getElementById('critical-stock-popup').remove();
    sessionStorage.setItem('criticalStockDismissed', '1');
}
(function () {
    if (sessionStorage.getItem('criticalStockDismissed')) {
        var el = document.getElementById('critical-stock-popup');
        if (el) el.remove();
    }
})();
</script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
