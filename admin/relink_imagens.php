<?php
/**
 * Admin — Religar imagens órfãs a produtos
 * Ferramenta única para recuperar imagens após reset da base de dados.
 */
define('PAGE_TITLE', 'Religar Imagens');
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/admin_nav.php';
requireRole(['admin', 'gestor']);

$db = getDB();

// Busca imagens já registadas na BD
$linked = [];
foreach ($db->query("SELECT filename FROM product_images")->fetchAll(PDO::FETCH_COLUMN) as $f) {
    $linked[$f] = true;
}

// Imagens no disco ainda não ligadas
$allFiles = glob(__DIR__ . '/../uploads/produtos/*') ?: [];
sort($allFiles);
$orphans = array_filter($allFiles, fn($f) => !isset($linked[basename($f)]));
$orphans = array_values($orphans);

// Lista de produtos
$products = $db->query("SELECT id, brand, model FROM products ORDER BY id")->fetchAll();

$message = '';

// --- POST: guardar associações ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assignments'])) {
    $inserted = 0;
    foreach ($_POST['assignments'] as $filename => $productId) {
        $productId = (int)$productId;
        if (!$productId) continue;
        $filename = basename($filename); // segurança
        if (!file_exists(__DIR__ . '/../uploads/produtos/' . $filename)) continue;
        // Verificar se já existe
        $exists = $db->prepare("SELECT COUNT(*) FROM product_images WHERE filename = ?");
        $exists->execute([$filename]);
        if ($exists->fetchColumn()) continue;
        $db->prepare("INSERT INTO product_images (product_id, filename, sort_order) VALUES (?, ?, ?)")
           ->execute([$productId, $filename, 0]);
        $inserted++;
    }
    // Sincronizar thumbnails para todos os produtos afectados
    foreach ($products as $p) {
        productSyncThumbnail($p['id']);
    }
    $message = "✓ $inserted imagem(ns) ligada(s) com sucesso.";
    // Recarregar lista de órfãs
    $linked = [];
    foreach ($db->query("SELECT filename FROM product_images")->fetchAll(PDO::FETCH_COLUMN) as $f) {
        $linked[$f] = true;
    }
    $orphans = array_values(array_filter($allFiles, fn($f) => !isset($linked[basename($f)])));
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding:40px 0 80px;">
    <h1 style="font-size:1.6rem; font-weight:900; margin-bottom:4px;">Religar Imagens</h1>
    <p class="text-muted mb-4">
        <?= count($orphans) ?> imagem(ns) no disco sem ligação à base de dados.
        Atribui cada imagem ao produto correto e clica em <strong>Guardar</strong>.
    </p>

    <?php if ($message): ?>
        <div class="alert alert-success mb-4" style="padding:12px 16px; background:#d1fae5; border-radius:8px; color:#065f46; font-weight:600;">
            <?= e($message) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($orphans)): ?>
        <div style="padding:40px; text-align:center; background:var(--bg); border-radius:12px;">
            <div style="font-size:48px; margin-bottom:12px;">✅</div>
            <h3>Todas as imagens estão ligadas!</h3>
            <p class="text-muted">Não existem imagens órfãs no disco.</p>
            <a href="<?= BASE_URL ?>/admin/produtos.php" class="btn btn-outline mt-3">← Voltar aos produtos</a>
        </div>
    <?php else: ?>

    <!-- Atalho rápido: atribuir grupo completo -->
    <div class="table-card mb-4" style="padding:16px 20px;">
        <div style="font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); margin-bottom:10px;">
            Atribuição rápida por grupo de upload
        </div>
        <div id="quick-assign" style="display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
            <?php
            // Agrupar por segundo de upload
            $groups = [];
            foreach ($orphans as $f) {
                preg_match('/prod_([0-9a-f]{8})/i', basename($f), $m);
                $key = $m[1] ?? 'outros';
                $groups[$key][] = $f;
            }
            foreach ($groups as $ts => $files):
            ?>
            <div style="display:flex; align-items:center; gap:6px; background:var(--bg); padding:6px 10px; border-radius:8px; font-size:13px;">
                <span style="color:var(--muted);"><?= count($files) ?> imgs</span>
                <select class="form-control form-control-sm" style="width:auto; font-size:12px;"
                    onchange="assignGroup('<?= $ts ?>', this.value)">
                    <option value="">— atribuir grupo —</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= e($p['brand'] . ' ' . $p['model']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <form method="post">
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(160px, 1fr)); gap:12px; margin-bottom:24px;">
            <?php foreach ($orphans as $f):
                $fname = basename($f);
                $imgUrl = BASE_URL.'/uploads/produtos/' . $fname;
                preg_match('/prod_([0-9a-f]{8})/i', $fname, $m);
                $groupKey = $m[1] ?? 'outros';
            ?>
            <div class="img-card" data-group="<?= $groupKey ?>" style="border:2px solid var(--border); border-radius:10px; overflow:hidden; background:var(--bg);">
                <div style="height:120px; overflow:hidden; background:#f3f4f6;">
                    <img src="<?= e($imgUrl) ?>" alt=""
                         style="width:100%; height:100%; object-fit:cover;"
                         onerror="this.parentElement.innerHTML='<div style=\'display:flex;align-items:center;justify-content:center;height:100%;font-size:32px;\'>📷</div>'">
                </div>
                <div style="padding:8px;">
                    <div style="font-size:10px; color:var(--muted); margin-bottom:4px; word-break:break-all;"><?= e($fname) ?></div>
                    <select name="assignments[<?= e($fname) ?>]"
                            class="form-control form-control-sm prod-select"
                            data-group="<?= $groupKey ?>"
                            style="width:100%; font-size:11px;">
                        <option value="">— sem produto —</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= e($p['brand'] . ' ' . $p['model']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="display:flex; gap:12px; align-items:center;">
            <button type="submit" class="btn btn-red btn-lg">Guardar ligações</button>
            <a href="<?= BASE_URL ?>/admin/produtos.php" class="btn btn-outline">Cancelar</a>
            <span style="font-size:13px; color:var(--muted);"><?= count($orphans) ?> imagens para ligar</span>
        </div>
    </form>
    <?php endif; ?>
</div>

<script>
function assignGroup(groupKey, productId) {
    if (!productId) return;
    document.querySelectorAll('.prod-select[data-group="' + groupKey + '"]').forEach(function(sel) {
        sel.value = productId;
        // Visual feedback
        sel.closest('.img-card').style.borderColor = '#10b981';
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
