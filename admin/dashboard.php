<?php
/**
 * Admin — Dashboard com estatísticas gerais
 */
define('PAGE_TITLE', 'Dashboard');
define('ADMIN_PAGE', 'dashboard');
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin', 'gestor']);

$db = getDB();

// Estatísticas gerais
$stats = [
    'products'  => $db->query("SELECT COUNT(*) FROM products WHERE active = 1")->fetchColumn(),
    'orders'    => $db->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'sales_pos' => $db->query("SELECT COUNT(*) FROM sales WHERE sale_type = 'loja'")->fetchColumn(),
    'revenue'   => $db->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != 'cancelada'")
                      ->fetchColumn()
                 + $db->query("SELECT COALESCE(SUM(total), 0) FROM sales")->fetchColumn(),
];

// Produtos com stock baixo (≤ 5 unidades por variante)
$lowStock = $db->query("
    SELECT p.brand, p.model, pv.color, pv.size, pv.stock
    FROM   product_variants pv
    JOIN   products p ON p.id = pv.product_id
    WHERE  pv.stock <= 5 AND pv.stock > 0
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

        <!-- Cards de estatísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Produtos Ativos</div>
                <div class="stat-value"><?= $stats['products'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Encomendas Online</div>
                <div class="stat-value"><?= $stats['orders'] ?></div>
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
                        <tr>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Estado</th>
                        </tr>
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

            <!-- Stock baixo -->
            <div class="table-card">
                <div class="table-card-header">
                    <h3>⚠️ Stock Baixo</h3>
                    <a href="/admin/relatorios.php" class="btn btn-sm btn-outline">Relatórios</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Cor / Tam.</th>
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lowStock)): ?>
                            <tr><td colspan="3" class="text-center text-muted" style="padding:24px">Tudo em ordem!</td></tr>
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
