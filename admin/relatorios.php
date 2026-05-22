<?php
/**
 * Admin — Relatórios de vendas e stock
 */
define('PAGE_TITLE', 'Relatórios');
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/admin_nav.php';
requireRole(['admin', 'gestor']);

$db = getDB();

// Datas do filtro (por defeito: mês atual)
$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo   = $_GET['to']   ?? date('Y-m-t');

// Validar datas
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) $dateFrom = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo))   $dateTo   = date('Y-m-t');

$dtFrom = $dateFrom . ' 00:00:00';
$dtTo   = $dateTo   . ' 23:59:59';

// ── Total de vendas online
$onlineStats = $db->prepare("
    SELECT COUNT(*)           AS total_orders,
           COALESCE(SUM(o.total), 0) AS revenue
    FROM   orders o
    WHERE  o.status != 'cancelada'
      AND  o.created_at BETWEEN ? AND ?
");
$onlineStats->execute([$dtFrom, $dtTo]);
$onlineStats = $onlineStats->fetch();

// ── Total de vendas POS
$posStats = $db->prepare("
    SELECT COUNT(*)           AS total_sales,
           COALESCE(SUM(total), 0) AS revenue
    FROM   sales
    WHERE  created_at BETWEEN ? AND ?
");
$posStats->execute([$dtFrom, $dtTo]);
$posStats = $posStats->fetch();

// ── Total geral
$totalRevenue = (float)$onlineStats['revenue'] + (float)$posStats['revenue'];
$totalSales   = (int)$onlineStats['total_orders'] + (int)$posStats['total_sales'];

// ── Produtos mais vendidos no período
$topProducts = $db->prepare("
    SELECT p.brand, p.model, pv.color, pv.size,
           SUM(oi.quantity) AS qty_sold,
           SUM(oi.quantity * oi.unit_price) AS revenue
    FROM   order_items oi
    JOIN   orders o ON o.id = oi.order_id
    JOIN   product_variants pv ON pv.id = oi.variant_id
    JOIN   products p ON p.id = pv.product_id
    WHERE  o.status != 'cancelada'
      AND  o.created_at BETWEEN ? AND ?
    GROUP  BY oi.variant_id
    UNION ALL
    SELECT p.brand, p.model, pv.color, pv.size,
           SUM(si.quantity) AS qty_sold,
           SUM(si.quantity * si.unit_price) AS revenue
    FROM   sale_items si
    JOIN   sales s ON s.id = si.sale_id
    JOIN   product_variants pv ON pv.id = si.variant_id
    JOIN   products p ON p.id = pv.product_id
    WHERE  s.created_at BETWEEN ? AND ?
    GROUP  BY si.variant_id
    ORDER  BY qty_sold DESC
    LIMIT  10
");
$topProducts->execute([$dtFrom, $dtTo, $dtFrom, $dtTo]);
$topProducts = $topProducts->fetchAll();

// ── Stock atual por produto
$stockSummary = $db->query("
    SELECT p.brand, p.model, p.base_price,
           COALESCE(SUM(pv.stock), 0) AS total_stock,
           COUNT(DISTINCT pv.color)   AS colors
    FROM   products p
    LEFT JOIN product_variants pv ON pv.product_id = p.id
    WHERE  p.active = 1
    GROUP  BY p.id
    ORDER  BY total_stock ASC
")->fetchAll();

// ── Produtos com stock crítico (≤ 5)
$lowStockItems = $db->query("
    SELECT p.brand, p.model, pv.color, pv.size, pv.stock
    FROM   product_variants pv
    JOIN   products p ON p.id = pv.product_id
    WHERE  pv.stock <= 5 AND p.active = 1
    ORDER  BY pv.stock ASC
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <?= adminNav('relatorios') ?>

    <div class="admin-content">
        <h1 class="admin-page-title">Relatórios</h1>
        <p class="admin-page-subtitle">Análise de vendas e stocks</p>

        <!-- Filtro de datas -->
        <div class="report-filters">
            <form method="GET">
                <div class="form-group">
                    <label>Data Inicial</label>
                    <input type="date" name="from" class="form-control" value="<?= e($dateFrom) ?>">
                </div>
                <div class="form-group">
                    <label>Data Final</label>
                    <input type="date" name="to" class="form-control" value="<?= e($dateTo) ?>">
                </div>
                <button type="submit" class="btn btn-primary">Gerar Relatório</button>
            </form>
        </div>

        <!-- Resumo do período -->
        <div class="report-grid">
            <div class="stat-card red">
                <div class="stat-label">Receita Total</div>
                <div class="stat-value"><?= formatPrice($totalRevenue) ?></div>
                <div class="stat-sub"><?= date('d/m/Y', strtotime($dateFrom)) ?> — <?= date('d/m/Y', strtotime($dateTo)) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Vendas Online</div>
                <div class="stat-value"><?= $onlineStats['total_orders'] ?></div>
                <div class="stat-sub"><?= formatPrice((float)$onlineStats['revenue']) ?> receita</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Vendas POS</div>
                <div class="stat-value"><?= $posStats['total_sales'] ?></div>
                <div class="stat-sub"><?= formatPrice((float)$posStats['revenue']) ?> receita</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Stock Crítico</div>
                <div class="stat-value text-danger"><?= count($lowStockItems) ?></div>
                <div class="stat-sub">variantes com ≤ 5 unidades</div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:32px;">
            <!-- Mais vendidos -->
            <div class="table-card">
                <div class="table-card-header">
                    <h3>🏆 Mais Vendidos no Período</h3>
                </div>
                <table class="data-table">
                    <thead>
                        <tr><th>Produto</th><th>Cor/Tam</th><th>Vendas</th><th>Receita</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topProducts)): ?>
                            <tr><td colspan="4" class="text-center text-muted" style="padding:24px">Sem vendas neste período.</td></tr>
                        <?php else: ?>
                            <?php foreach ($topProducts as $tp): ?>
                                <tr>
                                    <td><strong><?= e($tp['brand']) ?></strong> <?= e($tp['model']) ?></td>
                                    <td><?= e($tp['color']) ?> / <?= e($tp['size']) ?></td>
                                    <td><strong><?= $tp['qty_sold'] ?></strong></td>
                                    <td><?= formatPrice((float)$tp['revenue']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Stock crítico -->
            <div class="table-card">
                <div class="table-card-header">
                    <h3>⚠️ Variantes com Stock Crítico</h3>
                </div>
                <table class="data-table">
                    <thead>
                        <tr><th>Produto</th><th>Cor / Tam.</th><th>Stock</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lowStockItems)): ?>
                            <tr><td colspan="3" class="text-center text-muted" style="padding:24px">Tudo em ordem! ✅</td></tr>
                        <?php else: ?>
                            <?php foreach ($lowStockItems as $item): ?>
                                <tr>
                                    <td><?= e($item['brand'] . ' ' . $item['model']) ?></td>
                                    <td><?= e($item['color']) ?> / Nº<?= e($item['size']) ?></td>
                                    <td>
                                        <?php if ($item['stock'] == 0): ?>
                                            <span class="stock-badge-out">Sem stock</span>
                                        <?php else: ?>
                                            <span class="stock-badge-low"><?= $item['stock'] ?> un.</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Stock atual por produto -->
        <div class="table-card">
            <div class="table-card-header">
                <h3>📦 Stock Atual por Produto</h3>
            </div>
            <table class="data-table">
                <thead>
                    <tr><th>Marca</th><th>Modelo</th><th>Preço</th><th>Cores</th><th>Stock Total</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($stockSummary as $p): ?>
                        <tr>
                            <td><?= e($p['brand']) ?></td>
                            <td><?= e($p['model']) ?></td>
                            <td><?= formatPrice((float)$p['base_price']) ?></td>
                            <td><?= $p['colors'] ?></td>
                            <td>
                                <?php if ($p['total_stock'] == 0): ?>
                                    <span class="stock-badge-out">Sem stock</span>
                                <?php elseif ($p['total_stock'] <= 10): ?>
                                    <span class="stock-badge-low"><?= $p['total_stock'] ?> un.</span>
                                <?php else: ?>
                                    <span class="text-success"><?= $p['total_stock'] ?> un.</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
