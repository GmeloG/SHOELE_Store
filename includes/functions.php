<?php
/**
 * Funções utilitárias do Shoele Store
 */

require_once __DIR__ . '/../config/db.php';

// ─── Sessão ───────────────────────────────────────────────────────────────────

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─── Auto-seed de utilizadores de teste ──────────────────────────────────────
// Cria os utilizadores de teste na primeira execução (quando a tabela está vazia)

(function () {
    static $done = false;
    if ($done) return;
    $done = true;

    try {
        $db    = getDB();
        $count = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        if ($count === 0) {
            $stmt = $db->prepare("INSERT INTO users (nome, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute(['Administrador',  'admin@shoele_store.test',  password_hash('admin123',   PASSWORD_DEFAULT), 'admin']);
            $stmt->execute(['Gestor',         'gestor@shoele_store.test', password_hash('gestor123',  PASSWORD_DEFAULT), 'gestor']);
            $stmt->execute(['Cliente Teste',  'cliente@shoele_store.test',password_hash('cliente123', PASSWORD_DEFAULT), 'cliente']);
        }
    } catch (Exception $e) {
        // Tabela ainda não existe (primeira inicialização do Docker)
    }
})();

// ─── Autenticação ─────────────────────────────────────────────────────────────

/** Devolve true se existe uma sessão de utilizador ativa */
function isLoggedIn(): bool {
    return isset($_SESSION['user']['id']);
}

/** Devolve o array do utilizador atual ou null */
function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

/** Devolve o role do utilizador atual (ou '' se não autenticado) */
function currentRole(): string {
    return $_SESSION['user']['role'] ?? '';
}

/** Verifica se o utilizador tem um dos roles indicados */
function hasRole(string ...$roles): bool {
    if (!isLoggedIn()) return false;
    return in_array(currentRole(), $roles, true);
}

/**
 * Exige que o utilizador esteja autenticado.
 * Se não estiver, redireciona para login com mensagem flash e parâmetro ?next=.
 */
function requireLogin(string $msg = 'Por favor, inicie sessão para continuar.'): void {
    if (!isLoggedIn()) {
        setFlash('warning', $msg);
        $next = urlencode($_SERVER['REQUEST_URI'] ?? '/');
        header("Location: /login.php?next={$next}");
        exit;
    }
}

/**
 * Exige que o utilizador tenha um dos roles indicados.
 * Redireciona para login se não autenticado, ou mostra 403 se autenticado sem permissão.
 */
function requireRole(array $roles): void {
    requireLogin();
    if (!hasRole(...$roles)) {
        http_response_code(403);
        define('PAGE_TITLE', 'Acesso Negado');
        include __DIR__ . '/header.php';
        echo '<div class="container text-center" style="padding:80px 0">';
        echo '<div style="font-size:64px;margin-bottom:16px">🔒</div>';
        echo '<h1 style="font-size:1.8rem;font-weight:900;">Acesso Negado</h1>';
        echo '<p class="text-muted mt-2">Não tens permissão para aceder a esta área.</p>';
        echo '<a href="/" class="btn btn-primary mt-4">Voltar à Loja</a>';
        echo '</div>';
        include __DIR__ . '/footer.php';
        exit;
    }
}

// ─── Flash Messages ───────────────────────────────────────────────────────────

/** Guarda uma mensagem flash na sessão */
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

/** Lê e remove a mensagem flash da sessão */
function getFlash(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

// ─── Carrinho ─────────────────────────────────────────────────────────────────

/** Devolve o conteúdo atual do carrinho da sessão */
function cartGet(): array {
    return $_SESSION['cart'] ?? [];
}

/** Guarda o carrinho na sessão */
function cartSave(array $cart): void {
    $_SESSION['cart'] = $cart;
}

/** Adiciona ou incrementa uma variante no carrinho */
function cartAdd(int $variantId, int $qty = 1): array {
    $cart = cartGet();
    $key  = (string)$variantId;

    $db   = getDB();
    $stmt = $db->prepare('SELECT stock FROM product_variants WHERE id = ?');
    $stmt->execute([$variantId]);
    $variant = $stmt->fetch();

    if (!$variant) {
        return ['success' => false, 'message' => 'Variante não encontrada.'];
    }

    $currentQty = $cart[$key] ?? 0;
    $newQty     = $currentQty + $qty;

    if ($newQty > $variant['stock']) {
        return [
            'success' => false,
            'message' => "Stock insuficiente. Disponível: {$variant['stock']} unidades."
        ];
    }

    $cart[$key] = $newQty;
    cartSave($cart);

    return ['success' => true, 'message' => 'Produto adicionado ao carrinho.', 'cart_count' => cartCount()];
}

/** Atualiza a quantidade de uma variante no carrinho */
function cartUpdate(int $variantId, int $qty): array {
    $cart = cartGet();
    $key  = (string)$variantId;

    if ($qty <= 0) {
        unset($cart[$key]);
        cartSave($cart);
        return ['success' => true, 'message' => 'Produto removido.'];
    }

    $db   = getDB();
    $stmt = $db->prepare('SELECT stock FROM product_variants WHERE id = ?');
    $stmt->execute([$variantId]);
    $variant = $stmt->fetch();

    if (!$variant || $qty > $variant['stock']) {
        return ['success' => false, 'message' => 'Quantidade solicitada excede o stock disponível.'];
    }

    $cart[$key] = $qty;
    cartSave($cart);

    return ['success' => true, 'message' => 'Carrinho atualizado.'];
}

/** Remove uma variante do carrinho */
function cartRemove(int $variantId): void {
    $cart = cartGet();
    unset($cart[(string)$variantId]);
    cartSave($cart);
}

/** Limpa o carrinho */
function cartClear(): void {
    $_SESSION['cart'] = [];
}

/** Conta o total de itens no carrinho */
function cartCount(): int {
    return array_sum(cartGet());
}

/** Devolve os itens do carrinho com informação completa */
function cartItems(): array {
    $cart = cartGet();
    if (empty($cart)) return [];

    $db           = getDB();
    $placeholders = implode(',', array_fill(0, count($cart), '?'));
    $stmt         = $db->prepare("
        SELECT pv.id AS variant_id, pv.color, pv.size, pv.stock,
               COALESCE(pv.price, p.base_price) AS price,
               p.id AS product_id, p.brand, p.model, p.image
        FROM   product_variants pv
        JOIN   products p ON p.id = pv.product_id
        WHERE  pv.id IN ($placeholders)
    ");
    $stmt->execute(array_keys($cart));
    $variants = $stmt->fetchAll();

    $items = [];
    foreach ($variants as $v) {
        $qty     = $cart[(string)$v['variant_id']];
        $items[] = array_merge($v, [
            'quantity' => $qty,
            'subtotal' => round($v['price'] * $qty, 2),
        ]);
    }

    return $items;
}

/** Calcula o total do carrinho */
function cartTotal(): float {
    $total = 0.0;
    foreach (cartItems() as $item) {
        $total += $item['subtotal'];
    }
    return round($total, 2);
}

// ─── Produtos ─────────────────────────────────────────────────────────────────

function productsGetBestSellers(int $limit = 6): array {
    $db   = getDB();
    $stmt = $db->prepare("
        SELECT p.*,
               COALESCE(SUM(pv.stock), 0)  AS total_stock,
               COALESCE(s.total_sold, 0)   AS total_sold
        FROM   products p
        LEFT   JOIN product_variants pv ON pv.product_id = p.id
        LEFT   JOIN (
            SELECT pv2.product_id, SUM(qty) AS total_sold
            FROM (
                SELECT oi.variant_id, oi.quantity AS qty
                FROM   order_items oi
                JOIN   orders o ON o.id = oi.order_id
                WHERE  o.status != 'cancelada'
                UNION ALL
                SELECT si.variant_id, si.quantity AS qty
                FROM   sale_items si
            ) all_sales
            JOIN product_variants pv2 ON pv2.id = all_sales.variant_id
            GROUP BY pv2.product_id
        ) s ON s.product_id = p.id
        WHERE  p.active = 1
        GROUP  BY p.id
        ORDER  BY total_sold DESC, p.created_at DESC
        LIMIT  ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function productsGetAll(): array {
    $db = getDB();
    return $db->query("
        SELECT p.*,
               COALESCE(SUM(pv.stock), 0) AS total_stock,
               COUNT(DISTINCT pv.color)   AS color_count
        FROM   products p
        LEFT JOIN product_variants pv ON pv.product_id = p.id
        WHERE  p.active = 1
        GROUP  BY p.id
        ORDER  BY p.brand, p.model
    ")->fetchAll();
}

function productGetById(int $id): ?array {
    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM products WHERE id = ? AND active = 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function productColors(int $productId): array {
    $db   = getDB();
    $stmt = $db->prepare("
        SELECT DISTINCT color FROM product_variants
        WHERE product_id = ? AND stock > 0 ORDER BY color
    ");
    $stmt->execute([$productId]);
    return array_column($stmt->fetchAll(), 'color');
}

function productSizes(int $productId, string $color): array {
    $db   = getDB();
    $stmt = $db->prepare("
        SELECT id AS variant_id, size, stock FROM product_variants
        WHERE product_id = ? AND color = ?
        ORDER BY CAST(size AS UNSIGNED)
    ");
    $stmt->execute([$productId, $color]);
    return $stmt->fetchAll();
}

function productVariants(int $productId): array {
    $db   = getDB();
    $stmt = $db->prepare("
        SELECT * FROM product_variants WHERE product_id = ?
        ORDER BY color, CAST(size AS UNSIGNED)
    ");
    $stmt->execute([$productId]);
    return $stmt->fetchAll();
}

// ─── Encomendas ───────────────────────────────────────────────────────────────

/**
 * Cria uma encomenda, debita stock e associa ao utilizador (se autenticado).
 * Devolve o ID da encomenda criada.
 */
function orderCreate(array $customer, array $items, ?int $userId = null): int {
    $db = getDB();
    $db->beginTransaction();

    try {
        // Criar ou atualizar cliente
        $stmt = $db->prepare('SELECT id FROM customers WHERE email = ?');
        $stmt->execute([$customer['email']]);
        $existing = $stmt->fetch();

        if ($existing) {
            $customerId = $existing['id'];
            $db->prepare('UPDATE customers SET name=?, phone=?, address=? WHERE id=?')
               ->execute([$customer['name'], $customer['phone'], $customer['address'], $customerId]);
        } else {
            $db->prepare('INSERT INTO customers (name, email, phone, address) VALUES (?, ?, ?, ?)')
               ->execute([$customer['name'], $customer['email'], $customer['phone'], $customer['address']]);
            $customerId = (int)$db->lastInsertId();
        }

        // Verificar stock e calcular total
        $total = 0.0;
        foreach ($items as $item) {
            $stmt = $db->prepare('
                SELECT pv.stock, COALESCE(pv.price, p.base_price) AS price
                FROM product_variants pv
                JOIN products p ON p.id = pv.product_id
                WHERE pv.id = ? FOR UPDATE
            ');
            $stmt->execute([$item['variant_id']]);
            $variant = $stmt->fetch();

            if (!$variant || $variant['stock'] < $item['quantity']) {
                throw new Exception("Stock insuficiente para a variante ID {$item['variant_id']}.");
            }
            $total += $variant['price'] * $item['quantity'];
        }

        // Criar encomenda (com user_id se autenticado)
        $db->prepare('INSERT INTO orders (customer_id, user_id, total) VALUES (?, ?, ?)')
           ->execute([$customerId, $userId, round($total, 2)]);
        $orderId = (int)$db->lastInsertId();

        // Inserir itens e debitar stock
        foreach ($items as $item) {
            $stmt = $db->prepare('
                SELECT COALESCE(pv.price, p.base_price) AS price
                FROM product_variants pv JOIN products p ON p.id = pv.product_id
                WHERE pv.id = ?
            ');
            $stmt->execute([$item['variant_id']]);
            $price = (float)$stmt->fetchColumn();

            $db->prepare('INSERT INTO order_items (order_id, variant_id, quantity, unit_price) VALUES (?, ?, ?, ?)')
               ->execute([$orderId, $item['variant_id'], $item['quantity'], $price]);

            $db->prepare('UPDATE product_variants SET stock = stock - ? WHERE id = ?')
               ->execute([$item['quantity'], $item['variant_id']]);

            $db->prepare('INSERT INTO stock_movements (variant_id, movement_type, quantity, reference_id, reference_type, notes) VALUES (?, ?, ?, ?, ?, ?)')
               ->execute([$item['variant_id'], 'saida_venda', -$item['quantity'], $orderId, 'order', "Encomenda #{$orderId}"]);
        }

        $db->commit();
        return $orderId;

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

// ─── Stock ────────────────────────────────────────────────────────────────────

function stockGet(int $variantId): int {
    $db   = getDB();
    $stmt = $db->prepare('SELECT stock FROM product_variants WHERE id = ?');
    $stmt->execute([$variantId]);
    return (int)($stmt->fetchColumn() ?: 0);
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function formatPrice(float $price): string {
    return number_format($price, 2, ',', '.') . ' €';
}

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function baseUrl(string $path = ''): string {
    return '/' . ltrim($path, '/');
}

function productImageSrc(?string $image): string {
    if ($image && file_exists(__DIR__ . '/../uploads/produtos/' . $image)) {
        return '/uploads/produtos/' . $image;
    }
    return '/assets/images/placeholder.svg';
}

function orderStatusBadge(string $status): string {
    return [
        'encomendada' => 'badge-warning',
        'em_armazem'  => 'badge-info',
        'entregue'    => 'badge-success',
        'cancelada'   => 'badge-danger',
    ][$status] ?? 'badge-secondary';
}

function orderStatusLabel(string $status): string {
    return [
        'encomendada' => 'Encomendada',
        'em_armazem'  => 'Em Armazém',
        'entregue'    => 'Entregue',
        'cancelada'   => 'Cancelada',
    ][$status] ?? $status;
}
