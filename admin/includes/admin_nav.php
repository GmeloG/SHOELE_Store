<?php
/**
 * Navegação lateral da área administrativa
 */
function adminNav(string $current = ''): string {
    require_once __DIR__ . '/../../includes/functions.php';

    $role  = currentRole();
    $links = [
        'dashboard'  => ['icon' => '📊', 'label' => 'Dashboard',    'href' => BASE_URL.'/admin/dashboard.php'],
        'produtos'   => ['icon' => '👟', 'label' => 'Produtos',     'href' => BASE_URL.'/admin/produtos.php'],
        'adicionar'  => ['icon' => '➕', 'label' => 'Novo Produto', 'href' => BASE_URL.'/admin/adicionar_produto.php'],
        'encomendas' => ['icon' => '📦', 'label' => 'Encomendas',   'href' => BASE_URL.'/admin/encomendas.php'],
        'relatorios' => ['icon' => '📈', 'label' => 'Relatórios',   'href' => BASE_URL.'/admin/relatorios.php'],
    ];

    // Utilizadores: apenas admin
    if ($role === 'admin') {
        $links['utilizadores'] = ['icon' => '👥', 'label' => 'Utilizadores', 'href' => BASE_URL.'/admin/utilizadores.php'];
    }

    $html  = '<aside class="admin-sidebar">';
    $html .= '<div class="sidebar-section">';
    $html .= '<div class="sidebar-label">Shoele Store</div>';

    foreach ($links as $key => $link) {
        $active = ($key === $current) ? ' active' : '';
        $html  .= '<a href="' . $link['href'] . '" class="sidebar-link' . $active . '">';
        $html  .= '<span class="icon">' . $link['icon'] . '</span>';
        $html  .= $link['label'];
        $html  .= '</a>';
    }

    $html .= '</div>';
    $html .= '<div class="sidebar-section" style="margin-top:auto; padding-top:16px;">';
    $html .= '<div class="sidebar-label">Sistema</div>';
    $html .= '<a href="' . BASE_URL . '/pos/"    class="sidebar-link"><span class="icon">🖥️</span>Ponto de Venda</a>';
    $html .= '<a href="' . BASE_URL . '"        class="sidebar-link"><span class="icon">🛍️</span>Ver Loja</a>';
    $html .= '<a href="' . BASE_URL . '/logout.php" class="sidebar-link"><span class="icon">🚪</span>Logout</a>';
    $html .= '</div>';
    $html .= '</aside>';

    return $html;
}
