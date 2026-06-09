<?php
/**
 * Admin — Adicionar novo produto com variantes
 */
define('PAGE_TITLE', 'Novo Produto');
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/admin_nav.php';
requireRole(['admin', 'gestor']);

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $brand       = trim($_POST['brand']       ?? '');
    $model       = trim($_POST['model']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $basePrice   = str_replace(',', '.', trim($_POST['base_price'] ?? '0'));
    $variants    = $_POST['variants'] ?? [];

    // Validações
    if (!$brand)       $errors[] = 'A marca é obrigatória.';
    if (!$model)       $errors[] = 'O modelo é obrigatório.';
    if (!is_numeric($basePrice) || $basePrice < 0) $errors[] = 'Preço inválido.';

    // Validar imagens antes de guardar
    $uploadedFiles = [];
    if (!empty($_FILES['images']['name'][0])) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {
            if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
            if (!in_array($mime, $allowed)) {
                $errors[] = 'Imagem ' . ($i + 1) . ': formato não suportado (use JPG, PNG ou WebP).';
            } else {
                $uploadedFiles[] = [
                    'tmp_name' => $tmp,
                    'name'     => $_FILES['images']['name'][$i],
                ];
            }
        }
    }

    if (empty($errors)) {
        $db = getDB();
        $db->beginTransaction();
        try {
            $db->prepare('INSERT INTO products (brand, model, description, base_price) VALUES (?, ?, ?, ?)')
               ->execute([$brand, $model, $description, (float)$basePrice]);
            $productId = (int)$db->lastInsertId();

            // Guardar imagens
            foreach ($uploadedFiles as $order => $file) {
                productSaveImage($productId, $file, $order);
            }
            productSyncThumbnail($productId);

            // Inserir variantes
            $stmtV = $db->prepare('INSERT INTO product_variants (product_id, color, size, stock) VALUES (?, ?, ?, ?)');
            foreach ($variants as $v) {
                $color = trim($v['color'] ?? '');
                $size  = trim($v['size']  ?? '');
                $stock = max(0, (int)($v['stock'] ?? 0));
                if ($color && $size) {
                    $stmtV->execute([$productId, $color, $size, $stock]);
                    if ($stock > 0) {
                        $db->prepare('INSERT INTO stock_movements (variant_id, movement_type, quantity, reference_type, notes) VALUES (LAST_INSERT_ID(), ?, ?, ?, ?)')
                           ->execute(['entrada', $stock, 'manual', "Stock inicial — Produto #{$productId}"]);
                    }
                }
            }

            $db->commit();
            header('Location: '.BASE_URL.'/admin/produtos.php?saved=1');
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Erro ao guardar: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <?= adminNav('adicionar') ?>

    <div class="admin-content">
        <h1 class="admin-page-title">Novo Produto</h1>
        <p class="admin-page-subtitle">Adiciona uma nova sapatilha ao catálogo</p>

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
                        <input type="text" name="brand" class="form-control" value="<?= e($_POST['brand'] ?? '') ?>" placeholder="New Balance" required>
                    </div>
                    <div class="form-group">
                        <label>Modelo *</label>
                        <input type="text" name="model" class="form-control" value="<?= e($_POST['model'] ?? '') ?>" placeholder="530" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Descrição</label>
                    <textarea name="description" class="form-control" rows="4" placeholder="Descrição detalhada do produto..."><?= e($_POST['description'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Preço Base (€) *</label>
                    <input type="number" name="base_price" class="form-control" step="0.01" min="0"
                           value="<?= e($_POST['base_price'] ?? '0') ?>" placeholder="99.99" required style="max-width:180px">
                </div>
                <div class="form-group">
                    <label>Imagens do Produto</label>
                    <input type="file" name="images[]" class="form-control" accept="image/jpeg,image/png,image/webp" multiple>
                    <small class="text-muted">JPG, PNG ou WebP. Podes selecionar várias imagens de uma vez. Recomendado: 800×800px.</small>
                </div>
            </div>

            <!-- Variantes -->
            <div class="form-section">
                <div class="form-section-title">Variantes (Cor + Tamanho + Stock)</div>

                <table class="variants-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Cor</th>
                            <th>Tamanho (EU)</th>
                            <th>Stock</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="variants-tbody">
                        <?php
                        $savedVariants = $_POST['variants'] ?? [];
                        if (empty($savedVariants)) {
                            // Linha inicial em branco
                            $savedVariants = [['color' => '', 'size' => '', 'stock' => '0']];
                        }
                        foreach ($savedVariants as $idx => $v):
                        ?>
                            <tr>
                                <td><input type="text" name="variants[<?= $idx ?>][color]" value="<?= e($v['color'] ?? '') ?>" placeholder="Branco" class="form-control" style="width:140px" required></td>
                                <td><input type="text" name="variants[<?= $idx ?>][size]"  value="<?= e($v['size']  ?? '') ?>" placeholder="42"     class="form-control" style="width:90px" required></td>
                                <td><input type="number" name="variants[<?= $idx ?>][stock]" value="<?= (int)($v['stock'] ?? 0) ?>" min="0" class="form-control" style="width:90px"></td>
                                <td><button type="button" class="btn btn-ghost btn-sm remove-variant-btn">✕</button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <button type="button" id="add-variant-btn" class="btn btn-outline btn-sm mt-2">
                    + Adicionar variante
                </button>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="btn btn-red">Guardar Produto</button>
                <a href="<?= BASE_URL ?>/admin/produtos.php" class="btn btn-ghost">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
