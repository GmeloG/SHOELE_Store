<?php
define('PAGE_TITLE', 'Sobre o Projeto');
require_once __DIR__ . '/includes/functions.php';
include __DIR__ . '/includes/header.php';
?>

<style>
.info-hero{padding:80px 0 60px;text-align:center;border-bottom:1px solid var(--border,#e5e5e5)}
.info-hero .icon{font-size:64px;margin-bottom:24px}
.info-hero h1{font-size:clamp(28px,4vw,48px);font-weight:900;letter-spacing:-.02em;margin-bottom:12px}
.info-hero p{font-size:17px;color:var(--muted,#888);max-width:560px;margin:0 auto}
.info-section{padding:60px 0}
.info-section h2{font-size:22px;font-weight:800;margin-bottom:16px;letter-spacing:-.01em}
.info-section p,
.info-section li{font-size:15px;line-height:1.8;color:var(--text-secondary,#444)}
.info-section ul{padding-left:20px;margin:0;display:flex;flex-direction:column;gap:6px}
.info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:24px;margin-top:48px}
.info-card{background:var(--surface,#f5f5f5);border:1px solid var(--border,#e5e5e5);padding:28px 24px}
.info-card .card-icon{font-size:32px;margin-bottom:12px}
.info-card h3{font-size:16px;font-weight:800;margin-bottom:8px}
.info-card p{font-size:14px;color:var(--muted,#888);line-height:1.7;margin:0}
.info-team{padding:60px 0;border-top:1px solid var(--border,#e5e5e5);text-align:center}
.info-team h2{font-size:22px;font-weight:800;margin-bottom:8px}
.info-team .sub{font-size:14px;color:var(--muted,#888);margin-bottom:40px}
.team-grid{display:flex;flex-wrap:wrap;justify-content:center;gap:20px}
.team-card{background:var(--surface,#f5f5f5);border:1px solid var(--border,#e5e5e5);padding:28px 32px;min-width:180px;text-align:center}
.team-card .avatar{font-size:40px;margin-bottom:12px}
.team-card .name{font-size:15px;font-weight:700}
.team-card .role{font-size:12px;color:var(--muted,#888);margin-top:4px}
.tech-badges{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.tech-badge{display:inline-block;background:#111;color:#fff;font-size:11px;font-weight:700;letter-spacing:.08em;padding:4px 12px}
</style>

<div class="info-hero">
    <div class="container">
        <div class="icon">👟</div>
        <h1>Shoele Store</h1>
        <p>Projeto académico desenvolvido no âmbito da unidade curricular de Modelação e Simulação Industrial — ISEP MEEC.</p>
    </div>
</div>

<div class="info-section">
    <div class="container">
        <div class="info-grid">
            <div class="info-card">
                <div class="card-icon">🎯</div>
                <h3>Objetivo</h3>
                <p>Desenvolver um sistema completo de gestão de stocks e ponto de venda para uma loja de calçado, simulando um ambiente real de retalho.</p>
            </div>
            <div class="info-card">
                <div class="card-icon">🏫</div>
                <h3>Contexto Académico</h3>
                <p>Unidade Curricular de Modelação e Simulação Industrial (MODSI), Mestrado em Engenharia Eletrotécnica e de Computadores — ISEP, 2025/2026.</p>
            </div>
            <div class="info-card">
                <div class="card-icon">⚙️</div>
                <h3>Funcionalidades</h3>
                <ul>
                    <li>Catálogo de produtos</li>
                    <li>Gestão de stock por variante</li>
                    <li>Ponto de venda (POS)</li>
                    <li>Encomendas online</li>
                    <li>Painel de administração</li>
                    <li>Relatórios de vendas</li>
                </ul>
            </div>
            <div class="info-card">
                <div class="card-icon">🛠️</div>
                <h3>Tecnologias</h3>
                <p style="margin-bottom:12px">Stack utilizada no desenvolvimento:</p>
                <div class="tech-badges">
                    <span class="tech-badge">PHP 8.3</span>
                    <span class="tech-badge">MySQL 8</span>
                    <span class="tech-badge">Apache</span>
                    <span class="tech-badge">Docker</span>
                    <span class="tech-badge">HTML/CSS</span>
                    <span class="tech-badge">JavaScript</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="info-team">
    <div class="container">
        <h2>Equipa</h2>
        <p class="sub">Desenvolvido por alunos do MEEC — ISEP</p>
        <div class="team-grid">
            <div class="team-card">
                <div class="avatar">👨‍💻</div>
                <div class="name">Gonçalo Gonçalves</div>
                <div class="role">Desenvolvimento</div>
            </div>
            <div class="team-card">
                <div class="avatar">👨‍💻</div>
                <div class="name">Job Tomé</div>
                <div class="role">Desenvolvimento</div>
            </div>
            <div class="team-card">
                <div class="avatar">👨‍💻</div>
                <div class="name">Tiago Bastos</div>
                <div class="role">Desenvolvimento</div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
