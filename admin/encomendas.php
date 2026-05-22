<?php
/**
 * Admin — Gestão de encomendas online
 * Permite alterar o estado e cancela com devolução de stock
 */
define('PAGE_TITLE', 'Encomendas');
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/admin_nav.php';
requireRole(['admin', 'gestor']);

$db = getDB();

// Alterar estado de encomenda
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $orderId   = (int)$_POST['order_id'];
    $newStatus = $_POST['status'];
    $allowed   = ['encomendada', 'em_armazem', 'entregue', 'cancelada'];

    if (in_array($newStatus, $allowed, true)) {
        $db->beginTransaction();
        try {
            // Obter estado atual
            $stmt = $db->prepare('SELECT status FROM orders WHERE id = ?');
            $stmt->execute([$orderId]);
            $currentStatus = $stmt->fetchColumn();

            // Se está a cancelar (e não estava já cancelada) → devolver stock
            if ($newStatus === 'cancelada' && $currentStatus !== 'cancelada') {
                $items = $db->prepare("SELECT variant_id, quantity FROM order_items WHERE order_id = ?");
                $items->execute([$orderId]);
                foreach ($items->fetchAll() as $item) {
                    $db->prepare('UPDATE product_variants SET stock = stock + ? WHERE id = ?')
                       ->execute([$item['quantity'], $item['variant_id']]);
                    $db->prepare('INSERT INTO stock_movements (variant_id, movement_type, quantity, reference_id, reference_type, notes) VALUES (?, ?, ?, ?, ?, ?)')
                       ->execute([$item['variant_id'], 'devolucao', $item['quantity'], $orderId, 'order', "Cancelamento da encomenda #{$orderId}"]);
                }
            }

            // Se estava cancelada e volta a ativo → debitar stock novamente
            if ($currentStatus === 'cancelada' && $newStatus !== 'cancelada') {
                $items = $db->prepare("SELECT variant_id, quantity FROM order_items WHERE order_id = ?");
                $items->execute([$orderId]);
                foreach ($items->fetchAll() as $item) {
                    $stmt2 = $db->prepare('SELECT stock FROM product_variants WHERE id = ?');
                    $stmt2->execute([$item['variant_id']]);
                    $available = (int)$stmt2->fetchColumn();
                    if ($available < $item['quantity']) {
                        throw new Exception("Stock insuficiente para reativar a encomenda #{$orderId}.");
                    }
                    $db->prepare('UPDATE product_variants SET stock = stock - ? WHERE id = ?')
                       ->execute([$item['quantity'], $item['variant_id']]);
                    $db->prepare('INSERT INTO stock_movements (variant_id, movement_type, quantity, reference_id, reference_type, notes) VALUES (?, ?, ?, ?, ?, ?)')
                       ->execute([$item['variant_id'], 'saida_venda', -$item['quantity'], $orderId, 'order', "Reativação da encomenda #{$orderId}"]);
                }
            }

            $db->prepare('UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?')
               ->execute([$newStatus, $orderId]);

            $db->commit();
            header('Location: /admin/encomendas.php?updated=1');
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $errorMsg = $e->getMessage();
        }
    }
}

// Filtro de estado
$filterStatus = $_GET['status'] ?? '';
$allowed      = ['encomendada', 'em_armazem', 'entregue', 'cancelada'];
$whereClause  = in_array($filterStatus, $allowed) ? "WHERE o.status = '$filterStatus'" : '';

$orders = $db->query("
    SELECT o.id, o.status, o.total, o.created_at, o.updated_at,
           c.name, c.email, c.phone
    FROM   orders o
    JOIN   customers c ON c.id = o.customer_id
    $whereClause
    ORDER  BY o.created_at DESC
")->fetchAll();

// Detalhe de uma encomenda específica
$detailOrder = null;
$detailItems = [];
if (isset($_GET['id'])) {
    $detailId = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT o.*, c.name, c.email, c.phone, c.address FROM orders o JOIN customers c ON c.id = o.customer_id WHERE o.id = ?");
    $stmt->execute([$detailId]);
    $detailOrder = $stmt->fetch();
    if ($detailOrder) {
        $stmt = $db->prepare("SELECT oi.*, pv.color, pv.size, p.brand, p.model FROM order_items oi JOIN product_variants pv ON pv.id = oi.variant_id JOIN products p ON p.id = pv.product_id WHERE oi.order_id = ?");
        $stmt->execute([$detailId]);
        $detailItems = $stmt->fetchAll();
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <?= adminNav('encomendas') ?>

    <div class="admin-content">
        <h1 class="admin-page-title">Encomendas Online</h1>
        <p class="admin-page-subtitle"><?= count($orders) ?> encomenda(s)</p>

        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success">Estado da encomenda atualizado com sucesso.</div>
        <?php endif; ?>
        <?php if (isset($errorMsg)): ?>
            <div class="alert alert-danger">✕ <?= e($errorMsg) ?></div>
        <?php endif; ?>

        <!-- Detalhe da encomenda -->
        <?php if ($detailOrder): ?>
            <div class="table-card" style="margin-bottom:32px; border-left:4px solid var(--red);">
                <div class="table-card-header">
                    <h3>Encomenda #<?= $detailOrder['id'] ?></h3>
                    <form method="POST" style="display:flex; gap:8px; align-items:center;">
                        <input type="hidden" name="order_id" value="<?= $detailOrder['id'] ?>">
                        <select name="status" class="form-control" style="width:auto">
                            <?php foreach (['encomendada','em_armazem','entregue','cancelada'] as $s): ?>
                                <option value="<?= $s ?>" <?= $s === $detailOrder['status'] ? 'selected' : '' ?>><?= orderStatusLabel($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Atualizar</button>
                    </form>
                </div>
                <div style="padding:20px 24px; display:grid; grid-template-columns:1fr 1fr; gap:24px;">
                    <div>
                        <div class="selector-label">Cliente</div>
                        <strong><?= e($detailOrder['name']) ?></strong><br>
                        <span class="text-muted"><?= e($detailOrder['email']) ?></span><br>
                        <?php if ($detailOrder['phone']): ?>
                            <span class="text-muted"><?= e($detailOrder['phone']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="selector-label">Morada</div>
                        <span class="text-muted"><?= nl2br(e($detailOrder['address'])) ?></span>
                    </div>
                </div>
                <table class="data-table">
                    <thead><tr><th>Produto</th><th>Cor / Tam.</th><th>Qty</th><th>Preço Unit.</th><th>Subtotal</th></tr></thead>
                    <tbody>
                        <?php foreach ($detailItems as $i): ?>
                            <tr>
                                <td><?= e($i['brand'] . ' ' . $i['model']) ?></td>
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
            </div>
        <?php endif; ?>

        <!-- Filtros -->
        <div style="display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap;">
            <a href="/admin/encomendas.php" class="btn btn-sm <?= !$filterStatus ? 'btn-primary' : 'btn-outline' ?>">Todas</a>
            <?php foreach (['encomendada','em_armazem','entregue','cancelada'] as $s): ?>
                <a href="?status=<?= $s ?>" class="btn btn-sm <?= $filterStatus === $s ? 'btn-primary' : 'btn-outline' ?>"><?= orderStatusLabel($s) ?></a>
            <?php endforeach; ?>
        </div>

        <!-- Tabela de encomendas -->
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cliente</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Data</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td>#<?= $o['id'] ?></td>
                            <td>
                                <strong><?= e($o['name']) ?></strong><br>
                                <span class="text-muted" style="font-size:12px"><?= e($o['email']) ?></span>
                            </td>
                            <td><?= formatPrice((float)$o['total']) ?></td>
                            <td><span class="badge <?= orderStatusBadge($o['status']) ?>"><?= orderStatusLabel($o['status']) ?></span></td>
                            <td class="text-muted" style="font-size:12px"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                            <td>
                                <a href="?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline">Detalhes</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
