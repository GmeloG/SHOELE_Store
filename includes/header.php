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
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg">
</head>
<body>

<header class="site-header">
    <div class="container header-inner">
        <a href="<?= $role === 'admin' || $role === 'gestor' ? '/admin/dashboard.php' : '/' ?>" class="logo">
            <span class="logo-icon">👟</span>
            <span class="logo-text">SHOELE<strong>STORE</strong></span>
        </a>

        <nav class="main-nav">
            <?php if (!$user): ?>
                <!-- Visitante -->
                <a href="/" class="nav-link <?= $curScript === 'index.php' && $curDir !== 'admin' ? 'active' : '' ?>">Catálogo</a>
                <a href="/login.php" class="nav-link <?= $curScript === 'login.php' ? 'active' : '' ?>">Login</a>
                <a href="/registo.php" class="nav-link <?= $curScript === 'registo.php' ? 'active' : '' ?>">Registar</a>

            <?php elseif ($role === 'cliente'): ?>
                <!-- Cliente -->
                <a href="/" class="nav-link <?= $curScript === 'index.php' && $curDir !== 'admin' ? 'active' : '' ?>">Catálogo</a>
                <a href="/cliente/encomendas.php" class="nav-link <?= $curDir === 'cliente' ? 'active' : '' ?>">Minhas Encomendas</a>

            <?php elseif ($role === 'gestor'): ?>
                <!-- Gestor -->
                <a href="/admin/dashboard.php"   class="nav-link <?= $curScript === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
                <a href="/admin/produtos.php"    class="nav-link <?= in_array($curScript, ['produtos.php','adicionar_produto.php','editar_produto.php']) ? 'active' : '' ?>">Produtos</a>
                <a href="/admin/encomendas.php"  class="nav-link <?= $curScript === 'encomendas.php' ? 'active' : '' ?>">Encomendas</a>
                <a href="/admin/relatorios.php"  class="nav-link <?= $curScript === 'relatorios.php' ? 'active' : '' ?>">Relatórios</a>
                <a href="/pos/"                  class="nav-link <?= $curDir === 'pos' ? 'active' : '' ?>">POS</a>

            <?php elseif ($role === 'admin'): ?>
                <!-- Admin -->
                <a href="/admin/dashboard.php"    class="nav-link <?= $curScript === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
                <a href="/admin/produtos.php"     class="nav-link <?= in_array($curScript, ['produtos.php','adicionar_produto.php','editar_produto.php']) ? 'active' : '' ?>">Produtos</a>
                <a href="/admin/encomendas.php"   class="nav-link <?= $curScript === 'encomendas.php' ? 'active' : '' ?>">Encomendas</a>
                <a href="/admin/relatorios.php"   class="nav-link <?= $curScript === 'relatorios.php' ? 'active' : '' ?>">Relatórios</a>
                <a href="/pos/"                   class="nav-link <?= $curDir === 'pos' ? 'active' : '' ?>">POS</a>
                <a href="/admin/utilizadores.php" class="nav-link <?= $curScript === 'utilizadores.php' ? 'active' : '' ?>">Utilizadores</a>
            <?php endif; ?>
        </nav>

        <div class="header-actions">
            <?php if ($user): ?>
                <!-- Nome do utilizador + Logout -->
                <span style="font-size:13px; color:var(--muted); max-width:120px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= e($user['email']) ?>">
                    <?= e(explode(' ', $user['nome'])[0]) ?>
                </span>
                <?php if ($role === 'cliente'): ?>
                    <a href="/carrinho.php" class="cart-btn" title="Carrinho">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <?php if ($cartCount > 0): ?>
                            <span class="cart-badge"><?= $cartCount ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
                <a href="/logout.php" class="btn btn-sm btn-outline">Logout</a>
            <?php else: ?>
                <!-- Carrinho visível para visitantes também -->
                <a href="/carrinho.php" class="cart-btn" title="Carrinho">
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
