<?php
/**
 * Admin — Lista de produtos
 */
define('PAGE_TITLE', 'Produtos');
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/admin_nav.php';
requireRole(['admin', 'gestor']);

// Ação: remover produto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $deleteId = (int)($_POST['product_id'] ?? 0);
    if ($deleteId) {
        $db = getDB();
        $db->prepare('UPDATE products SET active = 0 WHERE id = ?')->execute([$deleteId]);
    }
    header('Location: '.BASE_URL.'/admin/produtos.php?deleted=1');
    exit;
}

$db = getDB();
$products = $db->query("
    SELECT p.*,
           COALESCE(SUM(pv.stock), 0) AS total_stock,
           COUNT(DISTINCT CONCAT(pv.color,'|',pv.size)) AS variant_count,
           (SELECT filename FROM product_images WHERE product_id = p.id ORDER BY sort_order, id LIMIT 1) AS first_image
    FROM   products p
    LEFT JOIN product_variants pv ON pv.product_id = p.id
    WHERE  p.active = 1
    GROUP  BY p.id
    ORDER  BY p.brand, p.model
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <?= adminNav('produtos') ?>

    <div class="admin-content">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
            <h1 class="admin-page-title" style="margin-bottom:0">Produtos</h1>
            <a href="<?= BASE_URL ?>/admin/adicionar_produto.php" class="btn btn-red">+ Novo Produto</a>
        </div>
        <p class="admin-page-subtitle"><?= count($products) ?> produtos no catálogo</p>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">Produto removido com sucesso.</div>
        <?php endif; ?>
        <?php if (isset($_GET['saved'])): ?>
            <div class="alert alert-success">Produto guardado com sucesso.</div>
        <?php endif; ?>

        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Imagem</th>
                        <th>Marca / Modelo</th>
                        <th>Preço</th>
                        <th>Variantes</th>
                        <th>Stock Total</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td class="text-muted"><?= $p['id'] ?></td>
                            <td>
                                <div style="width:48px; height:48px; border-radius:6px; overflow:hidden; background:var(--bg);">
                                    <?php $thumb = $p['first_image'] ?? $p['image']; ?>
                                    <?php if ($thumb): ?>
                                        <img src="<?= BASE_URL ?>/uploads/produtos/<?= e($thumb) ?>" style="width:100%;height:100%;object-fit:cover" alt="">
                                    <?php else: ?>
                                        <div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:24px">👟</div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <strong><?= e($p['brand']) ?></strong><br>
                                <span style="font-size:13px; color:var(--muted)"><?= e($p['model']) ?></span>
                            </td>
                            <td><?= formatPrice((float)$p['base_price']) ?></td>
                            <td class="text-muted"><?= $p['variant_count'] ?></td>
                            <td>
                                <?php if ($p['total_stock'] == 0): ?>
                                    <span class="stock-badge-out">Sem stock</span>
                                <?php elseif ($p['total_stock'] <= 10): ?>
                                    <span class="stock-badge-low"><?= $p['total_stock'] ?> un.</span>
                                <?php else: ?>
                                    <?= $p['total_stock'] ?> un.
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="flex gap-2">
                                    <a href="<?= BASE_URL ?>/admin/editar_produto.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline">Editar</a>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Remover este produto?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-ghost text-danger">Remover</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
