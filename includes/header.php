<?php
if (!defined('PAGE_TITLE')) define('PAGE_TITLE', 'Shoele Store');
require_once __DIR__ . '/../includes/functions.php';

$cartCount = cartCount();
$user      = currentUser();
$role      = currentRole();
$flash     = getFlash();
$curScript = basename($_SERVER['PHP_SELF']);
$curDir    = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(PAGE_TITLE) ?> — Shoele Store</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">
    <script>var BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
</head>
<body>

<header class="site-header">
    <div class="container header-inner">
        <a href="<?= $role === 'admin' || $role === 'gestor' ? BASE_URL.'/admin/dashboard.php' : BASE_URL.'/' ?>" class="logo">
            <span class="logo-icon">👟</span>
            <span class="logo-text">SHOELE<strong>STORE</strong></span>
        </a>

        <nav class="main-nav">
            <?php if (!$user): ?>
                <!-- Visitante -->
                <a href="<?= BASE_URL ?>/catalogo.php" class="nav-link <?= $curScript === 'catalogo.php' ? 'active' : '' ?>">Catálogo</a>
                <a href="<?= BASE_URL ?>/login.php" class="nav-link <?= $curScript === 'login.php' ? 'active' : '' ?>">Login</a>
                <a href="<?= BASE_URL ?>/registo.php" class="nav-link <?= $curScript === 'registo.php' ? 'active' : '' ?>">Registar</a>

            <?php elseif ($role === 'cliente'): ?>
                <!-- Cliente -->
                <a href="<?= BASE_URL ?>/catalogo.php" class="nav-link <?= $curScript === 'catalogo.php' ? 'active' : '' ?>">Catálogo</a>
                <a href="<?= BASE_URL ?>/cliente/encomendas.php" class="nav-link <?= $curDir === 'cliente' ? 'active' : '' ?>">Minhas Encomendas</a>

            <?php elseif ($role === 'gestor'): ?>
                <!-- Gestor -->
                <a href="<?= BASE_URL ?>/admin/dashboard.php"   class="nav-link <?= $curScript === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
                <a href="<?= BASE_URL ?>/admin/produtos.php"    class="nav-link <?= in_array($curScript, ['produtos.php','adicionar_produto.php','editar_produto.php']) ? 'active' : '' ?>">Produtos</a>
                <a href="<?= BASE_URL ?>/admin/encomendas.php"  class="nav-link <?= $curScript === 'encomendas.php' ? 'active' : '' ?>">Encomendas</a>
                <a href="<?= BASE_URL ?>/admin/relatorios.php"  class="nav-link <?= $curScript === 'relatorios.php' ? 'active' : '' ?>">Relatórios</a>
                <a href="<?= BASE_URL ?>/pos/"                  class="nav-link <?= $curDir === 'pos' ? 'active' : '' ?>">POS</a>

            <?php elseif ($role === 'admin'): ?>
                <!-- Admin -->
                <a href="<?= BASE_URL ?>/admin/dashboard.php"    class="nav-link <?= $curScript === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
                <a href="<?= BASE_URL ?>/admin/produtos.php"     class="nav-link <?= in_array($curScript, ['produtos.php','adicionar_produto.php','editar_produto.php']) ? 'active' : '' ?>">Produtos</a>
                <a href="<?= BASE_URL ?>/admin/encomendas.php"   class="nav-link <?= $curScript === 'encomendas.php' ? 'active' : '' ?>">Encomendas</a>
                <a href="<?= BASE_URL ?>/admin/relatorios.php"   class="nav-link <?= $curScript === 'relatorios.php' ? 'active' : '' ?>">Relatórios</a>
                <a href="<?= BASE_URL ?>/pos/"                   class="nav-link <?= $curDir === 'pos' ? 'active' : '' ?>">POS</a>
                <a href="<?= BASE_URL ?>/admin/utilizadores.php" class="nav-link <?= $curScript === 'utilizadores.php' ? 'active' : '' ?>">Utilizadores</a>
            <?php endif; ?>
        </nav>

        <?php if ($curDir !== 'admin' && $curDir !== 'pos'): ?>
        <div class="header-search-wrap" id="search-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" class="header-search-icon" aria-hidden="true">
                <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
            </svg>
            <input type="search" id="header-search" class="header-search-input"
                   placeholder="Pesquisar..." autocomplete="off" spellcheck="false">
            <div id="search-dropdown" class="search-dropdown" hidden></div>
        </div>
        <?php endif; ?>

        <div class="header-actions">
            <?php if ($user): ?>
                <!-- Nome do utilizador + Logout -->
                <span style="font-size:13px; color:var(--muted); max-width:120px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= e($user['email']) ?>">
                    <?= e(explode(' ', $user['nome'])[0]) ?>
                </span>
                <?php if ($role === 'cliente'): ?>
                    <a href="<?= BASE_URL ?>/carrinho.php" class="cart-btn" title="Carrinho">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <?php if ($cartCount > 0): ?>
                            <span class="cart-badge"><?= $cartCount ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/logout.php" class="btn btn-sm btn-outline">Logout</a>
            <?php else: ?>
                <!-- Carrinho visível para visitantes também -->
                <a href="<?= BASE_URL ?>/carrinho.php" class="cart-btn" title="Carrinho">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <?php if ($cartCount > 0): ?>
                        <span class="cart-badge"><?= $cartCount ?></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="site-main">

<?php if ($flash): ?>
    <div class="container" style="padding-top:16px;">
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
    </div>
<?php endif; ?>
