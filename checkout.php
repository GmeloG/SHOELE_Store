<?php
/**
 * Shoele Store — Checkout
 * Requer sessão ativa. Associa a encomenda ao utilizador autenticado.
 */
define('PAGE_TITLE', 'Finalizar Encomenda');
require_once __DIR__ . '/includes/functions.php';

// Redirecionar para login se não autenticado
requireLogin('Para finalizar a compra, tem de iniciar sessão ou criar uma conta.');

// Redirecionar se carrinho vazio
$items = cartItems();
$total = cartTotal();
if (empty($items)) {
    header('Location: /carrinho.php');
    exit;
}

$errors  = [];
$user    = currentUser();

// Pré-preencher com dados do utilizador
$defaultName  = $user['nome']  ?? '';
$defaultEmail = $user['email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $phone   = trim($_POST['phone']   ?? '');
    $address = trim($_POST['address'] ?? '');

    if (!$name)                                        $errors[] = 'O nome é obrigatório.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))    $errors[] = 'Email inválido.';
    if (!$address)                                     $errors[] = 'A morada é obrigatória.';

    if (empty($errors)) {
        try {
            $cartData = array_map(fn($i) => [
                'variant_id' => $i['variant_id'],
                'quantity'   => $i['quantity'],
            ], $items);

            $orderId = orderCreate(
                ['name' => $name, 'email' => $email, 'phone' => $phone, 'address' => $address],
                $cartData,
                (int)$user['id']          // Associar ao utilizador autenticado
            );

            cartClear();
            header("Location: /encomenda_sucesso.php?id={$orderId}");
            exit;

        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="container checkout-section">
    <h1 style="font-size:1.8rem; font-weight:900; margin-bottom:8px;">Finalizar Encomenda</h1>
    <p class="text-muted mb-4">Confirma os teus dados de entrega.</p>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $err): ?><div>✕ <?= e($err) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="checkout-layout">
        <!-- Formulário -->
        <div>
            <form method="POST" class="checkout-form-card">
                <h2>Dados de Entrega</h2>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Nome completo *</label>
                        <input type="text" id="name" name="name" class="form-control"
                               value="<?= e($_POST['name'] ?? $defaultName) ?>" required placeholder="João Silva">
                    </div>
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" class="form-control"
                               value="<?= e($_POST['email'] ?? $defaultEmail) ?>" required placeholder="joao@email.com">
                    </div>
                </div>

                <div class="form-group">
                    <label for="phone">Telefone</label>
                    <input type="tel" id="phone" name="phone" class="form-control"
                           value="<?= e($_POST['phone'] ?? '') ?>" placeholder="912 345 678">
                </div>

                <div class="form-group">
                    <label for="address">Morada completa *</label>
                    <textarea id="address" name="address" class="form-control" rows="3"
                              required placeholder="Rua, nº, código postal, cidade"><?= e($_POST['address'] ?? '') ?></textarea>
                </div>

                <div style="background:var(--bg); border-radius:var(--radius); padding:16px; margin-bottom:24px; font-size:13px; color:var(--muted);">
                    ℹ️ Encomenda associada à conta: <strong><?= e($user['email']) ?></strong>
                </div>

                <button type="submit" class="btn btn-red btn-lg btn-full">
                    Confirmar Encomenda — <?= formatPrice($total) ?>
                </button>
            </form>
        </div>

        <!-- Resumo -->
        <div class="order-summary-card">
            <h3>A tua encomenda</h3>

            <?php foreach ($items as $item): ?>
                <div class="summary-item">
                    <div class="summary-item-img">
                        <?php if ($item['image'] && file_exists(__DIR__ . '/uploads/produtos/' . $item['image'])): ?>
                            <img src="/uploads/produtos/<?= e($item['image']) ?>" alt="">
                        <?php else: ?>
                            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:24px;background:var(--bg)">👟</div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="summary-item-name"><?= e($item['brand'] . ' ' . $item['model']) ?></div>
                        <div class="summary-item-sub"><?= e($item['color']) ?> / Nº<?= e($item['size']) ?> × <?= $item['quantity'] ?></div>
                    </div>
                    <div class="summary-item-price"><?= formatPrice($item['subtotal']) ?></div>
                </div>
            <?php endforeach; ?>

            <div class="summary-row" style="padding-top:16px; margin-top:8px; border-top:1px solid var(--border);">
                <span class="text-muted">Envio</span>
                <span class="text-muted">Gratuito</span>
            </div>
            <div class="summary-row total">
                <span>Total</span>
                <span><?= formatPrice($total) ?></span>
            </div>

            <a href="/carrinho.php" class="btn btn-ghost btn-full btn-sm mt-2" style="justify-content:center;">
                ← Editar carrinho
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
