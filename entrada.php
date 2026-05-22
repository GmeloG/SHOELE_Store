<?php
/**
 * Shoele Store — Página de entrada
 * Ponto de partida com seleção de área por tipo de utilizador
 */

// Ola
require_once __DIR__ . '/includes/functions.php';

// Redirecionar utilizadores já autenticados para a sua área
if (isLoggedIn()) {
    $role = currentRole();
    header('Location: ' . ($role === 'cliente' ? '/' : '/admin/dashboard.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shoele Store</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg">
    <style>
        body { background: var(--black); min-height: 100vh; display: flex; flex-direction: column; }

        .entry-page {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 24px;
            text-align: center;
        }

        .entry-logo {
            font-size: 3rem;
            font-weight: 900;
            color: var(--white);
            text-transform: uppercase;
            letter-spacing: -.04em;
            margin-bottom: 8px;
        }
        .entry-logo strong { color: var(--red); }
        .entry-logo .icon { display: block; font-size: 64px; margin-bottom: 16px; }

        .entry-tagline {
            color: rgba(255,255,255,.45);
            font-size: 15px;
            margin-bottom: 64px;
        }

        .entry-cards {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
            justify-content: center;
            max-width: 900px;
            width: 100%;
        }

        .entry-card {
            background: rgba(255,255,255,.05);
            border: 1.5px solid rgba(255,255,255,.1);
            border-radius: 16px;
            padding: 40px 32px;
            width: 260px;
            cursor: pointer;
            text-decoration: none;
            color: var(--white);
            transition: background .2s, border-color .2s, transform .2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
        }
        .entry-card:hover {
            background: rgba(255,255,255,.1);
            border-color: rgba(255,255,255,.3);
            transform: translateY(-6px);
            color: var(--white);
        }
        .entry-card.red-card {
            background: rgba(204,0,0,.15);
            border-color: rgba(204,0,0,.4);
        }
        .entry-card.red-card:hover {
            background: rgba(204,0,0,.25);
            border-color: var(--red);
        }

        .entry-card-icon { font-size: 48px; }
        .entry-card-title { font-size: 1.1rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; }
        .entry-card-desc  { font-size: 13px; color: rgba(255,255,255,.5); line-height: 1.5; }
        .entry-card-btn {
            margin-top: 8px;
            padding: 8px 24px;
            border-radius: 100px;
            border: 1.5px solid rgba(255,255,255,.3);
            font-size: 13px;
            font-weight: 600;
            color: var(--white);
        }
        .entry-card:hover .entry-card-btn {
            background: var(--white);
            color: var(--black);
            border-color: var(--white);
        }
        .entry-card.red-card .entry-card-btn { border-color: var(--red); }
        .entry-card.red-card:hover .entry-card-btn {
            background: var(--red);
            color: var(--white);
            border-color: var(--red);
        }

        .entry-footer {
            color: rgba(255,255,255,.2);
            font-size: 12px;
            padding: 24px;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="entry-page">
    <div class="entry-logo">
        <span class="icon">👟</span>
        SHOELE<strong>STORE</strong>
    </div>
    <p class="entry-tagline">Sistema de gestão de stocks · Seleciona a tua área</p>

    <div class="entry-cards">
        <!-- Administrador -->
        <a href="/login.php?hint=admin" class="entry-card">
            <span class="entry-card-icon">🔑</span>
            <span class="entry-card-title">Administrador</span>
            <span class="entry-card-desc">Gestão completa da loja, utilizadores, relatórios e ponto de venda.</span>
            <span class="entry-card-btn">Entrar como Admin</span>
        </a>

        <!-- Gestor -->
        <a href="/login.php?hint=gestor" class="entry-card">
            <span class="entry-card-icon">📦</span>
            <span class="entry-card-title">Gestor</span>
            <span class="entry-card-desc">Gestão de produtos, stock, encomendas, relatórios e ponto de venda.</span>
            <span class="entry-card-btn">Entrar como Gestor</span>
        </a>

        <!-- Cliente -->
        <a href="/login.php?hint=cliente" class="entry-card red-card">
            <span class="entry-card-icon">🛍️</span>
            <span class="entry-card-title">Cliente</span>
            <span class="entry-card-desc">Navega no catálogo, adiciona ao carrinho e acompanha as tuas encomendas.</span>
            <span class="entry-card-btn">Entrar como Cliente</span>
        </a>
    </div>
</div>

<p class="entry-footer">&copy; <?= date('Y') ?> Shoele Store · Projeto académico ISEP MEEC</p>

<script src="/assets/js/main.js"></script>
</body>
</html>
