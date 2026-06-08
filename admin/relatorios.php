<?php
/**
 * Admin — Relatórios de vendas e stock
 */
define('PAGE_TITLE', 'Relatórios');
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/admin_nav.php';
requireRole(['admin', 'gestor']);

$db = getDB();

$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo   = $_GET['to']   ?? date('Y-m-t');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) $dateFrom = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo))   $dateTo   = date('Y-m-t');

$dtFrom = $dateFrom . ' 00:00:00';
$dtTo   = $dateTo   . ' 23:59:59';

// ── Vendas online no período
$onlineStats = $db->prepare("
    SELECT COUNT(*) AS total_orders, COALESCE(SUM(total), 0) AS revenue
    FROM   orders
    WHERE  status NOT IN ('cancelada') AND created_at BETWEEN ? AND ?
");
$onlineStats->execute([$dtFrom, $dtTo]);
$onlineStats = $onlineStats->fetch();

// ── Vendas POS no período
$posStats = $db->prepare("
    SELECT COUNT(*) AS total_sales, COALESCE(SUM(total), 0) AS revenue
    FROM   sales
    WHERE  created_at BETWEEN ? AND ?
");
$posStats->execute([$dtFrom, $dtTo]);
$posStats = $posStats->fetch();

$totalRevenue = (float)$onlineStats['revenue'] + (float)$posStats['revenue'];
$totalSales   = (int)$onlineStats['total_orders'] + (int)$posStats['total_sales'];

// ── Produtos mais vendidos (agrupados por produto, top 8)
$topProducts = $db->prepare("
    SELECT p.brand, p.model, SUM(qty) AS qty_sold, SUM(rev) AS revenue
    FROM (
        SELECT pv.product_id, oi.quantity AS qty, oi.quantity * oi.unit_price AS rev
        FROM   order_items oi
        JOIN   orders o ON o.id = oi.order_id
        JOIN   product_variants pv ON pv.id = oi.variant_id
        WHERE  o.status NOT IN ('cancelada') AND o.created_at BETWEEN ? AND ?
        UNION ALL
        SELECT pv.product_id, si.quantity AS qty, si.quantity * si.unit_price AS rev
        FROM   sale_items si
        JOIN   sales s ON s.id = si.sale_id
        JOIN   product_variants pv ON pv.id = si.variant_id
        WHERE  s.created_at BETWEEN ? AND ?
    ) t
    JOIN products p ON p.id = t.product_id
    GROUP BY p.id
    ORDER BY qty_sold DESC
    LIMIT 8
");
$topProducts->execute([$dtFrom, $dtTo, $dtFrom, $dtTo]);
$topProducts = $topProducts->fetchAll();

// ── Vendas por dia (últimos dias do período — para gráfico de linha)
$dailySales = $db->prepare("
    SELECT DATE(created_at) AS day, SUM(total) AS revenue, COUNT(*) AS orders
    FROM   orders
    WHERE  status NOT IN ('cancelada') AND created_at BETWEEN ? AND ?
    GROUP  BY DATE(created_at)
    ORDER  BY day ASC
");
$dailySales->execute([$dtFrom, $dtTo]);
$dailySalesRows = $dailySales->fetchAll();

// ── Stock crítico (≤ 3)
$lowStockItems = $db->query("
    SELECT p.brand, p.model, pv.color, pv.size, pv.stock
    FROM   product_variants pv
    JOIN   products p ON p.id = pv.product_id
    WHERE  pv.stock <= 3 AND p.active = 1
    ORDER  BY pv.stock ASC
")->fetchAll();

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

// ── Preparar dados JSON para os gráficos
$chartDays    = array_column($dailySalesRows, 'day');
$chartRevenue = array_map(fn($r) => round((float)$r['revenue'], 2), $dailySalesRows);
$chartOrders  = array_column($dailySalesRows, 'orders');

$chartTopLabels  = array_map(fn($p) => $p['brand'] . ' ' . $p['model'], $topProducts);
$chartTopSales   = array_column($topProducts, 'qty_sold');

include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <?= adminNav('relatorios') ?>

    <div class="admin-content">
        <h1 class="admin-page-title">Relatórios</h1>
        <p class="admin-page-subtitle">Análise de vendas e stocks</p>

        <!-- Filtro -->
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

        <!-- Resumo -->
        <div class="report-grid" style="grid-template-columns:repeat(4,1fr)">
            <div class="stat-card red">
                <div class="stat-label">Receita Total</div>
                <div class="stat-value"><?= formatPrice($totalRevenue) ?></div>
                <div class="stat-sub"><?= date('d/m/Y', strtotime($dateFrom)) ?> — <?= date('d/m/Y', strtotime($dateTo)) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Vendas Online</div>
                <div class="stat-value"><?= $onlineStats['total_orders'] ?></div>
                <div class="stat-sub"><?= formatPrice((float)$onlineStats['revenue']) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Vendas POS</div>
                <div class="stat-value"><?= $posStats['total_sales'] ?></div>
                <div class="stat-sub"><?= formatPrice((float)$posStats['revenue']) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Stock Crítico</div>
                <div class="stat-value text-danger"><?= count($lowStockItems) ?></div>
                <div class="stat-sub">variantes com ≤ 3 unidades</div>
            </div>
        </div>

        <!-- Gráficos -->
        <div style="display:grid; grid-template-columns:2fr 1fr; gap:24px; margin-bottom:32px;">
            <!-- Vendas por dia (linha) -->
            <div class="table-card" style="padding:20px 24px;">
                <div style="font-size:15px; font-weight:800; margin-bottom:16px;">📈 Receita por Dia</div>
                <?php if (empty($dailySalesRows)): ?>
                    <p class="text-muted text-center" style="padding:40px 0">Sem vendas no período selecionado.</p>
                <?php else: ?>
                    <canvas id="chart-daily" height="120"></canvas>
                <?php endif; ?>
            </div>

            <!-- Online vs POS (donut) -->
            <div class="table-card" style="padding:20px 24px;">
                <div style="font-size:15px; font-weight:800; margin-bottom:16px;">🏪 Online vs POS</div>
                <?php if ($totalRevenue > 0): ?>
                    <canvas id="chart-channel" height="200"></canvas>
                <?php else: ?>
                    <p class="text-muted text-center" style="padding:40px 0">Sem dados.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Mais vendidos (barras) -->
        <?php if (!empty($topProducts)): ?>
        <div class="table-card" style="padding:20px 24px; margin-bottom:32px;">
            <div style="font-size:15px; font-weight:800; margin-bottom:16px;">🏆 Mais Vendidos no Período</div>
            <canvas id="chart-top" height="80"></canvas>
        </div>
        <?php endif; ?>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:32px;">
            <!-- Mais vendidos (tabela) -->
            <div class="table-card">
                <div class="table-card-header"><h3>🏆 Mais Vendidos</h3></div>
                <table class="data-table">
                    <thead><tr><th>Produto</th><th>Vendas</th><th>Receita</th></tr></thead>
                    <tbody>
                        <?php if (empty($topProducts)): ?>
                            <tr><td colspan="3" class="text-center text-muted" style="padding:24px">Sem vendas neste período.</td></tr>
                        <?php else: ?>
                            <?php foreach ($topProducts as $tp): ?>
                                <tr>
                                    <td><strong><?= e($tp['brand']) ?></strong> <?= e($tp['model']) ?></td>
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
                <div class="table-card-header"><h3>⚠️ Stock Crítico (≤ 3 un.)</h3></div>
                <table class="data-table">
                    <thead><tr><th>Produto</th><th>Cor / Tam.</th><th>Stock</th></tr></thead>
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

        <!-- Stock por produto -->
        <div class="table-card">
            <div class="table-card-header"><h3>📦 Stock Atual por Produto</h3></div>
            <table class="data-table">
                <thead><tr><th>Marca</th><th>Modelo</th><th>Preço</th><th>Cores</th><th>Stock Total</th></tr></thead>
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

<?php if (!empty($dailySalesRows) || !empty($topProducts) || $totalRevenue > 0): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    Chart.defaults.font.family = "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
    Chart.defaults.color       = '#888';

    // Gráfico diário (linha)
    var dailyCtx = document.getElementById('chart-daily');
    if (dailyCtx) {
        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels:   <?= json_encode($chartDays) ?>,
                datasets: [{
                    label:           'Receita (€)',
                    data:            <?= json_encode($chartRevenue) ?>,
                    borderColor:     '#cc0000',
                    backgroundColor: 'rgba(204,0,0,.08)',
                    borderWidth:     2,
                    fill:            true,
                    tension:         0.35,
                    pointRadius:     3,
                    pointHoverRadius: 5,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f0f0f0' }, ticks: { callback: v => v.toLocaleString('pt-PT') + ' €' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // Donut Online vs POS
    var channelCtx = document.getElementById('chart-channel');
    if (channelCtx) {
        new Chart(channelCtx, {
            type: 'doughnut',
            data: {
                labels:   ['Online', 'POS / Loja'],
                datasets: [{
                    data: [<?= (float)$onlineStats['revenue'] ?>, <?= (float)$posStats['revenue'] ?>],
                    backgroundColor: ['#cc0000', '#1a1a1a'],
                    borderWidth: 0,
                    hoverOffset: 6,
                }]
            },
            options: {
                cutout:    '68%',
                plugins:   { legend: { position: 'bottom' } },
                animation: { animateScale: true }
            }
        });
    }

    // Barras — mais vendidos
    var topCtx = document.getElementById('chart-top');
    if (topCtx) {
        new Chart(topCtx, {
            type: 'bar',
            data: {
                labels:   <?= json_encode($chartTopLabels) ?>,
                datasets: [{
                    label:           'Unidades vendidas',
                    data:            <?= json_encode(array_map('intval', $chartTopSales)) ?>,
                    backgroundColor: '#cc0000',
                    borderRadius:    4,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins:    { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, grid: { color: '#f0f0f0' }, ticks: { precision: 0 } },
                    y: { grid: { display: false } }
                }
            }
        });
    }
})();
</script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
