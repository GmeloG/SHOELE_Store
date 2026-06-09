</main>

<footer class="site-footer" style="background:var(--surface,#f5f5f5);border-top:1px solid var(--border,#e5e5e5);margin-top:auto">
    <div class="container" style="display:flex;flex-direction:column;align-items:center;text-align:center;gap:12px;padding:40px 0 24px">
        <span class="logo-text-sm" style="font-size:18px;font-weight:900;letter-spacing:.06em">SHOELE<strong>STORE</strong></span>
        <p style="color:var(--muted,#888);font-size:13px;margin:0">Sistema de gestão de stocks para ponto de venda.</p>
        <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:8px 24px;margin-top:4px">
            <a href="<?= BASE_URL ?>" style="font-size:13px;color:var(--muted,#888);text-decoration:none">Loja</a>
            <a href="<?= BASE_URL ?>/carrinho.php" style="font-size:13px;color:var(--muted,#888);text-decoration:none">Carrinho</a>
            <a href="<?= BASE_URL ?>/login.php" style="font-size:13px;color:var(--muted,#888);text-decoration:none">Login</a>
            <a href="<?= BASE_URL ?>/info.php" style="font-size:13px;color:var(--muted,#888);text-decoration:none">Info</a>
        </div>
    </div>
    <div style="border-top:1px solid var(--border,#e5e5e5);padding:16px 0;text-align:center">
        <p style="font-size:12px;color:var(--muted,#888);margin:0">&copy; <?= date('Y') ?> Shoele Store. Projeto académico — ISEP MEEC.</p>
    </div>
</footer>

<script src="<?= BASE_URL ?>/assets/js/main.js?v=<?= filemtime(__DIR__.'/../assets/js/main.js') ?>"></script>
</body>

</html>
