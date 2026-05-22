<?php
/**
 * Admin — Editar produto e variantes
 */
define('PAGE_TITLE', 'Editar Produto');
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/admin_nav.php';
requireRole(['admin', 'gestor']);

$id      = (int)($_GET['id'] ?? 0);
$db      = getDB();
$product = $id ? $db->prepare('SELECT * FROM products WHERE id = ?') : null;
if ($product) {
    $product->execute([$id]);
    $product = $product->fetch();
}

if (!$product) {
    header('Location: /admin/produtos.php');
    exit;
}

$errors  = [];
$variants = productVariants($id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $brand       = trim($_POST['brand']       ?? '');
    $model       = trim($_POST['model']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $basePrice   = str_replace(',', '.', trim($_POST['base_price'] ?? '0'));
    $newVariants = $_POST['variants'] ?? [];

    if (!$brand)       $errors[] = 'A marca é obrigatória.';
    if (!$model)       $errors[] = 'O modelo é obrigatório.';
    if (!is_numeric($basePrice) || $basePrice < 0) $errors[] = 'Preço inválido.';

    // Upload de nova imagem (opcional)
    $imageName = $product['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $mime    = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['image']['tmp_name']);

        if (!in_array($mime, $allowed)) {
            $errors[] = 'Formato de imagem não suportado.';
        } else {
            $ext       = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $newName   = uniqid('prod_') . '.' . strtolower($ext);
            $dest      = __DIR__ . '/../uploads/produtos/' . $newName;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                // Apagar imagem anterior
                if ($imageName && file_exists(__DIR__ . '/../uploads/produtos/' . $imageName)) {
                    unlink(__DIR__ . '/../uploads/produtos/' . $imageName);
                }
                $imageName = $newName;
            }
        }
    }

    if (empty($errors)) {
        $db->beginTransaction();
        try {
            // Atualizar produto
            $db->prepare('UPDATE products SET brand=?, model=?, description=?, base_price=?, image=?, updated_at=NOW() WHERE id=?')
               ->execute([$brand, $model, $description, (float)$basePrice, $imageName, $id]);

            // Atualizar variantes existentes e inserir novas
            foreach ($newVariants as $v) {
                $variantId = (int)($v['id'] ?? 0);
                $color     = trim($v['color'] ?? '');
                $size      = trim($v['size']  ?? '');
                $stock     = max(0, (int)($v['stock'] ?? 0));

                if (!$color || !$size) continue;

                if ($variantId > 0) {
                    // Obter stock anterior para registar movimento
                    $oldStock = (int)$db->prepare('SELECT stock FROM product_variants WHERE id = ?')
                                        ->execute([$variantId]) ?: 0;
                    $stmt = $db->prepare('SELECT stock FROM product_variants WHERE id = ?');
                    $stmt->execute([$variantId]);
                    $oldStock = (int)$stmt->fetchColumn();

                    $db->prepare('UPDATE product_variants SET color=?, size=?, stock=?, updated_at=NOW() WHERE id=? AND product_id=?')
                       ->execute([$color, $size, $stock, $variantId, $id]);

                    $diff = $stock - $oldStock;
                    if ($diff !== 0) {
                        $type = $diff > 0 ? 'entrada' : 'ajuste';
                        $db->prepare('INSERT INTO stock_movements (variant_id, movement_type, quantity, reference_type, notes) VALUES (?, ?, ?, ?, ?)')
                           ->execute([$variantId, $type, $diff, 'manual', "Ajuste manual — Produto #{$id}"]);
                    }
                } else {
                    // Nova variante
                    $db->prepare('INSERT INTO product_variants (product_id, color, size, stock) VALUES (?, ?, ?, ?)')
                       ->execute([$id, $color, $size, $stock]);
                    if ($stock > 0) {
                        $newVId = (int)$db->lastInsertId();
                        $db->prepare('INSERT INTO stock_movements (variant_id, movement_type, quantity, reference_type, notes) VALUES (?, ?, ?, ?, ?)')
                           ->execute([$newVId, 'entrada', $stock, 'manual', "Stock inicial — Produto #{$id}"]);
                    }
                }
            }

            // Remover variantes marcadas para remoção
            $toDelete = array_filter(array_map('intval', $_POST['delete_variants'] ?? []));
            if ($toDelete) {
                $ph = implode(',', array_fill(0, count($toDelete), '?'));
                $db->prepare("DELETE FROM product_variants WHERE id IN ($ph) AND product_id = ?")
                   ->execute([...$toDelete, $id]);
            }

            $db->commit();
            header('Location: /admin/produtos.php?saved=1');
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Erro ao guardar: ' . $e->getMessage();
        }
    }

    // Reload variants after failed POST
    $variants = productVariants($id);
}

include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <?= adminNav('produtos') ?>

    <div class="admin-content">
        <h1 class="admin-page-title">Editar Produto</h1>
        <p class="admin-page-subtitle"><?= e($product['brand'] . ' ' . $product['model']) ?></p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $err): ?><div>✕ <?= e($err) ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="form-card">
            <h2>Informação do Produto</h2>

            <div class="form-section">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Marca *</label>
                        <input type="text" name="brand" class="form-control"
                               value="<?= e($_POST['brand'] ?? $product['brand']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Modelo *</label>
                        <input type="text" name="model" class="form-control"
                               value="<?= e($_POST['model'] ?? $product['model']) ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Descrição</label>
                    <textarea name="description" class="form-control" rows="4"><?= e($_POST['description'] ?? $product['description']) ?></textarea>
                </div>
                <div class="form-group">
                    <label>Preço Base (€) *</label>
                    <input type="number" name="base_price" class="form-control" step="0.01" min="0"
                           value="<?= e($_POST['base_price'] ?? $product['base_price']) ?>" style="max-width:180px">
                </div>
                <div class="form-group">
                    <label>Imagem do Produto</label>
                    <?php if ($product['image'] && file_exists(__DIR__ . '/../uploads/produtos/' . $product['image'])): ?>
                        <div style="margin-bottom:8px">
                            <img src="/uploads/produtos/<?= e($product['image']) ?>" style="width:80px; height:80px; object-fit:cover; border-radius:var(--radius);" alt="">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <small class="text-muted">Deixar em branco para manter a imagem atual.</small>
                </div>
            </div>

            <!-- Variantes -->
            <div class="form-section">
                <div class="form-section-title">Variantes (Cor + Tamanho + Stock)</div>

                <table class="variants-table">
                    <thead>
                        <tr>
                            <th>Cor</th>
                            <th>Tamanho (EU)</th>
                            <th>Stock</th>
                            <th>Remover</th>
                        </tr>
                    </thead>
                    <tbody id="variants-tbody">
                        <?php foreach ($variants as $idx => $v): ?>
                            <tr>
                                <td>
                                    <input type="hidden" name="variants[<?= $idx ?>][id]" value="<?= $v['id'] ?>">
                                    <input type="text" name="variants[<?= $idx ?>][color]" value="<?= e($v['color']) ?>" class="form-control" style="width:140px" required>
                                </td>
                                <td><input type="text" name="variants[<?= $idx ?>][size]"  value="<?= e($v['size'])  ?>" class="form-control" style="width:90px" required></td>
                                <td><input type="number" name="variants[<?= $idx ?>][stock]" value="<?= (int)$v['stock'] ?>" min="0" class="form-control" style="width:90px"></td>
                                <td>
                                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                                        <input type="checkbox" name="delete_variants[]" value="<?= $v['id'] ?>">
                                        <span style="font-size:13px; color:var(--danger)">Remover</span>
                                    </label>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <button type="button" id="add-variant-btn" class="btn btn-outline btn-sm mt-2">
                    + Adicionar variante
                </button>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="btn btn-red">Guardar Alterações</button>
                <a href="/admin/produtos.php" class="btn btn-ghost">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
