<?php
/**
 * Shoele Store — Registo de novo cliente
 */
define('PAGE_TITLE', 'Criar Conta');
require_once __DIR__ . '/includes/functions.php';

// Já autenticado
if (isLoggedIn()) {
    header('Location: '.BASE_URL.'/');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome     = trim($_POST['nome']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');

    // Validações
    if (mb_strlen($nome) < 2)                      $errors[] = 'O nome deve ter pelo menos 2 caracteres.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';
    if (mb_strlen($password) < 6)                  $errors[] = 'A password deve ter pelo menos 6 caracteres.';
    if ($password !== $confirm)                    $errors[] = 'As passwords não coincidem.';

    if (empty($errors)) {
        $db = getDB();

        // Verificar se o email já existe
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Este email já está registado. Tenta fazer login.';
        } else {
            // Criar utilizador com role=cliente
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $db->prepare('INSERT INTO users (nome, email, password, role) VALUES (?, ?, ?, ?)')
               ->execute([$nome, $email, $hash, 'cliente']);
            $userId = (int)$db->lastInsertId();

            // Login automático após registo
            $_SESSION['user'] = [
                'id'    => $userId,
                'nome'  => $nome,
                'email' => $email,
                'role'  => 'cliente',
            ];
            session_regenerate_id(true);

            setFlash('success', "Conta criada com sucesso! Bem-vindo(a), {$nome}.");
            header('Location: '.BASE_URL.'/');
            exit;
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 60px 0 80px; max-width: 440px;">
    <h1 style="font-size:2rem; font-weight:900; margin-bottom:8px;">Criar Conta</h1>
    <p class="text-muted mb-4">Junta-te à Shoele Store.</p>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $err): ?><div>✕ <?= e($err) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="checkout-form-card">
        <div class="form-group">
            <label for="nome">Nome completo *</label>
            <input type="text" id="nome" name="nome" class="form-control"
                   value="<?= e($_POST['nome'] ?? '') ?>"
                   placeholder="João Silva" required>
        </div>

        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" class="form-control"
                   value="<?= e($_POST['email'] ?? '') ?>"
                   placeholder="joao@email.com" required>
        </div>

        <div class="form-group">
            <label for="password">Password *</label>
            <input type="password" id="password" name="password" class="form-control"
                   placeholder="Mínimo 6 caracteres" required minlength="6">
        </div>

        <div class="form-group">
            <label for="confirm">Confirmar Password *</label>
            <input type="password" id="confirm" name="confirm" class="form-control"
                   placeholder="Repetir password" required minlength="6">
        </div>

        <button type="submit" class="btn btn-red btn-full btn-lg">Criar Conta</button>

        <div style="text-align:center; margin-top:20px; font-size:14px; color:var(--muted);">
            Já tens conta?
            <a href="<?= BASE_URL ?>/login.php" style="color:var(--dark); font-weight:600;">Iniciar sessão</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
