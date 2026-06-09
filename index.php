<?php
/**
 * Página inicial
 */
define('PAGE_TITLE', 'Início');
require_once __DIR__ . '/includes/functions.php';

$searchQuery = trim($_GET['q'] ?? '');
$searchResults = [];

if ($searchQuery !== '') {
    $db   = getDB();
    $term = '%' . $searchQuery . '%';
    $stmt = $db->prepare("
        SELECT p.id, p.brand, p.model, p.base_price,
               COALESCE(SUM(pv.stock), 0) AS total_stock,
               COUNT(DISTINCT pv.color)   AS color_count,
               (SELECT filename FROM product_images WHERE product_id = p.id ORDER BY sort_order, id LIMIT 1) AS first_image
        FROM   products p
        LEFT JOIN product_variants pv ON pv.product_id = p.id
        WHERE  p.active = 1
          AND  (p.brand LIKE ? OR p.model LIKE ? OR CONCAT(p.brand,' ',p.model) LIKE ?)
        GROUP  BY p.id
        ORDER  BY
          CASE WHEN CONCAT(p.brand,' ',p.model) LIKE ? THEN 0 ELSE 1 END,
          p.brand, p.model
    ");
    $stmt->execute([$term, $term, $term, $term]);
    $searchResults = $stmt->fetchAll();
}

$bestSellers = $searchQuery === '' ? productsGetBestSellers(6) : [];

$db     = getDB();
$brands = $searchQuery === '' ? $db->query("
    SELECT brand, COUNT(*) AS total FROM products WHERE active = 1 GROUP BY brand ORDER BY brand
")->fetchAll() : [];

include __DIR__ . '/includes/header.php';
?>

<?php if ($searchQuery !== ''): ?>
<!-- Resultados da pesquisa inline -->
<div class="container" style="padding:40px 0 80px">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
        <div>
            <h1 style="font-size:1.6rem; font-weight:900; margin-bottom:4px;">
                Resultados para "<?= e($searchQuery) ?>"
            </h1>
            <p class="text-muted"><?= count($searchResults) ?> produto<?= count($searchResults) != 1 ? 's' : '' ?> encontrado<?= count($searchResults) != 1 ? 's' : '' ?></p>
        </div>
        <a href="<?= BASE_URL ?>" class="btn btn-outline btn-sm">← Voltar ao início</a>
    </div>

    <?php if (empty($searchResults)): ?>
        <div class="empty-cart">
            <div class="icon">🔍</div>
            <h3>Nenhum produto encontrado</h3>
            <p>Tenta pesquisar por marca ou modelo.</p>
            <a href="<?= BASE_URL ?>" class="btn btn-red mt-3">Ver todos os produtos</a>
        </div>
    <?php else: ?>
        <div class="products-grid">
            <?php foreach ($searchResults as $p):
                $imgSrc = $p['first_image']
                    ? BASE_URL.'/uploads/produtos/' . $p['first_image']
                    : (($p['image'] ?? null) ? BASE_URL.'/uploads/produtos/' . $p['image'] : null);
            ?>
            <a href="<?= BASE_URL ?>/produto.php?id=<?= $p['id'] ?>" class="product-card">
                <div class="card-image">
                    <?php if ($imgSrc): ?>
                        <img src="<?= e($imgSrc) ?>" alt="<?= e($p['brand'] . ' ' . $p['model']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="card-placeholder">👟</div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="card-brand"><?= e($p['brand']) ?></div>
                    <div class="card-model"><?= e($p['model']) ?></div>
                    <div class="card-price"><?= formatPrice((float)$p['base_price']) ?></div>
                </div>
                <div class="card-footer">
                    <div class="btn btn-outline btn-sm btn-full">Ver Produto</div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php else: ?>

<?php if (!empty($bestSellers)): ?>
<style>
.hero-carousel{position:relative;width:100%;height:70vh;min-height:420px;max-height:700px;background:#111;overflow:hidden;user-select:none}
.carousel-track{display:flex;height:100%;transition:transform .5s cubic-bezier(.4,0,.2,1)}
.carousel-slide{min-width:100%;height:100%;display:flex;align-items:center;position:relative;overflow:hidden}
.slide-bg{position:absolute;inset:0;background-size:cover;background-position:center;filter:brightness(.45)}
.slide-bg img{width:100%;height:100%;object-fit:cover}
.slide-layout{position:relative;z-index:2;display:flex;align-items:center;justify-content:space-between;width:100%;max-width:1200px;margin:0 auto;padding:0 80px;gap:40px}
.slide-text{flex:1;color:#fff}
.slide-badge{display:inline-block;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:#fff;font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;padding:4px 12px;border-radius:20px;margin-bottom:20px}
.slide-brand{font-size:clamp(14px,2vw,18px);font-weight:700;letter-spacing:.15em;text-transform:uppercase;opacity:.7;margin-bottom:8px}
.slide-model{font-size:clamp(32px,5.5vw,72px);font-weight:900;line-height:1;letter-spacing:-.02em;margin-bottom:16px}
.slide-price{font-size:clamp(18px,2.5vw,28px);font-weight:700;opacity:.9;margin-bottom:32px}
.slide-cta{display:inline-block;background:#fff;color:#111;font-size:14px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:14px 36px;text-decoration:none;transition:background .2s,color .2s}
.slide-cta:hover{background:#111;color:#fff;outline:2px solid #fff}
.slide-image{flex:0 0 45%;max-width:45%;display:flex;align-items:center;justify-content:center}
.slide-image img{max-height:55vh;max-width:100%;object-fit:contain;filter:drop-shadow(0 20px 60px rgba(0,0,0,.5));animation:floatImg 3s ease-in-out infinite}
.slide-placeholder{font-size:140px;opacity:.3;animation:floatImg 3s ease-in-out infinite}
@keyframes floatImg{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
.carousel-btn{position:absolute;top:50%;transform:translateY(-50%);z-index:10;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.25);color:#fff;width:52px;height:52px;border-radius:50%;font-size:26px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .2s;backdrop-filter:blur(4px)}
.carousel-btn:hover{background:rgba(255,255,255,.25)}
.carousel-prev{left:20px}.carousel-next{right:20px}
.carousel-dots{position:absolute;bottom:20px;left:50%;transform:translateX(-50%);display:flex;gap:8px;z-index:10}
.carousel-dot{width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,.4);border:none;cursor:pointer;padding:0;transition:background .2s,transform .2s}
.carousel-dot.active{background:#fff;transform:scale(1.3)}
@media(max-width:768px){
  .slide-layout{padding:0 60px;flex-direction:column;justify-content:flex-end;padding-bottom:60px;gap:20px}
  .slide-image{display:none}
  .slide-model{font-size:clamp(28px,8vw,48px)}
  .hero-carousel{height:60vh}
}
</style>

<section class="hero-carousel" aria-label="Mais Vendidos">
    <div class="carousel-track" id="carousel-track">
        <?php foreach ($bestSellers as $i => $p):
            $fi     = $p['first_image'] ?? $p['image'];
            $imgSrc = $fi ? BASE_URL.'/uploads/produtos/' . $fi : null;
            $hasImg = (bool)$imgSrc;
        ?>
        <div class="carousel-slide" role="group" aria-label="Slide <?= $i+1 ?> de <?= count($bestSellers) ?>">
            <div class="slide-bg">
                <?php if ($hasImg): ?>
                    <img src="<?= e($imgSrc) ?>" alt="" aria-hidden="true">
                <?php endif; ?>
            </div>
            <div class="slide-layout">
                <div class="slide-text">
                    <div class="slide-badge"><?= $i === 0 ? 'N.º 1 Mais Vendido' : 'Mais Vendido' ?></div>
                    <div class="slide-brand"><?= e($p['brand']) ?></div>
                    <div class="slide-model"><?= e($p['model']) ?></div>
                    <div class="slide-price"><?= formatPrice((float)$p['base_price']) ?></div>
                    <a href="<?= BASE_URL ?>/produto.php?id=<?= $p['id'] ?>" class="slide-cta">Ver Produto</a>
                </div>
                <div class="slide-image" aria-hidden="true">
                    <?php if ($hasImg): ?>
                        <img src="<?= e($imgSrc) ?>" alt="<?= e($p['brand'] . ' ' . $p['model']) ?>">
                    <?php else: ?>
                        <div class="slide-placeholder">👟</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (count($bestSellers) > 1): ?>
    <button class="carousel-btn carousel-prev" id="carousel-prev" aria-label="Anterior">&#8249;</button>
    <button class="carousel-btn carousel-next" id="carousel-next" aria-label="Seguinte">&#8250;</button>
    <div class="carousel-dots" id="carousel-dots">
        <?php foreach ($bestSellers as $i => $_): ?>
            <button class="carousel-dot <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>" aria-label="Slide <?= $i+1 ?>"></button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

<script>
(function () {
    var track  = document.getElementById('carousel-track');
    var dots   = document.querySelectorAll('.carousel-dot');
    var total  = <?= count($bestSellers) ?>;
    var cur    = 0, timer;

    function go(n) {
        cur = (n + total) % total;
        track.style.transform = 'translateX(-' + cur * 100 + '%)';
        dots.forEach(function (d, i) { d.classList.toggle('active', i === cur); });
    }

    function next() { go(cur + 1); }
    function prev() { go(cur - 1); }

    function startAuto() { timer = setInterval(next, 5000); }
    function resetAuto()  { clearInterval(timer); startAuto(); }

    var btnNext = document.getElementById('carousel-next');
    var btnPrev = document.getElementById('carousel-prev');
    if (btnNext) btnNext.addEventListener('click', function () { next(); resetAuto(); });
    if (btnPrev) btnPrev.addEventListener('click', function () { prev(); resetAuto(); });
    dots.forEach(function (d) {
        d.addEventListener('click', function () { go(+this.dataset.index); resetAuto(); });
    });

    var startX = 0;
    track.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; }, {passive:true});
    track.addEventListener('touchend',   function (e) {
        var dx = e.changedTouches[0].clientX - startX;
        if (Math.abs(dx) > 50) { dx < 0 ? next() : prev(); resetAuto(); }
    });

    if (total > 1) startAuto();
})();
</script>
<?php endif; ?>

<style>
.brands-section{padding:60px 0 80px}
.brands-section h2{font-size:clamp(22px,3vw,32px);font-weight:900;letter-spacing:-.02em;margin-bottom:8px}
.brands-section .section-sub{color:var(--muted,#888);font-size:15px;margin-bottom:40px}
.brands-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px}
.brand-card{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;padding:36px 20px;background:var(--surface,#f5f5f5);border:1px solid transparent;text-decoration:none;color:inherit;transition:border-color .2s,box-shadow .2s,transform .2s}
.brand-card:hover{border-color:var(--primary,#111);box-shadow:0 4px 20px rgba(0,0,0,.08);transform:translateY(-3px)}
.brand-icon{font-size:40px;line-height:1}
.brand-name{font-size:15px;font-weight:800;letter-spacing:.06em;text-transform:uppercase}
.brand-count{font-size:12px;color:var(--muted,#888)}
</style>

<div class="brands-section">
    <div class="container">
        <h2>Explorar por Marca</h2>
        <p class="section-sub">Escolhe a tua marca favorita</p>

        <?php if (empty($brands)): ?>
            <div class="empty-cart">
                <div class="icon">👟</div>
                <h3>Sem marcas disponíveis</h3>
                <p>Ainda não existem produtos na loja.</p>
            </div>
        <?php else: ?>
            <div class="brands-grid">
                <?php foreach ($brands as $b): ?>
                    <a href="<?= BASE_URL ?>/catalogo.php?brand=<?= urlencode($b['brand']) ?>" class="brand-card">
                        <div class="brand-icon">👟</div>
                        <div class="brand-name"><?= e($b['brand']) ?></div>
                        <div class="brand-count"><?= $b['total'] ?> produto<?= $b['total'] != 1 ? 's' : '' ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
