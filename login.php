<?php
/**
 * Shoele Store — Login
 */
define('PAGE_TITLE', 'Iniciar Sessão');
require_once __DIR__ . '/includes/functions.php';

// Já autenticado → redirecionar
if (isLoggedIn()) {
    header('Location: ' . (hasRole('admin', 'gestor') ? '/admin/dashboard.php' : '/'));
    exit;
}

$hint   = $_GET['hint'] ?? '';                    // admin | gestor | cliente
$next   = $_GET['next']  ?? '';                   // URL de destino após login
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $next     = trim($_POST['next']     ?? '');

    if (!$email || !$password) {
        $errors[] = 'Preenche o email e a password.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $errors[] = 'Email ou password incorretos.';
        } else {
            // Sessão criada com sucesso
            $_SESSION['user'] = [
                'id'    => $user['id'],
                'nome'  => $user['nome'],
                'email' => $user['email'],
                'role'  => $user['role'],
            ];
            session_regenerate_id(true); // Prevenir session fixation

            // Redirecionar conforme o role
            if ($next && str_starts_with($next, '/')) {
                header("Location: {$next}");
            } elseif ($user['role'] === 'admin' || $user['role'] === 'gestor') {
                header('Location: /admin/dashboard.php');
            } else {
                header('Location: /');
            }
            exit;
        }
    }
}

// Labels do hint
$hintLabels = [
    'admin'   => ['icon' => '🔑', 'title' => 'Área de Administrador'],
    'gestor'  => ['icon' => '📦', 'title' => 'Área de Gestor'],
    'cliente' => ['icon' => '🛍️', 'title' => 'Área de Cliente'],
];
$hintData = $hintLabels[$hint] ?? null;

include __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 60px 0 80px; max-width: 440px;">
    <?php if ($hintData): ?>
        <div style="text-align:center; margin-bottom:32px;">
            <div style="font-size:48px; margin-bottom:8px;"><?= $hintData['icon'] ?></div>
            <h2 style="font-size:1.1rem; font-weight:700; color:var(--muted);"><?= $hintData['title'] ?></h2>
        </div>
    <?php endif; ?>

    <h1 style="font-size:2rem; font-weight:900; margin-bottom:8px;">Iniciar Sessão</h1>
    <p class="text-muted mb-4">Bem-vindo de volta à Shoele Store.</p>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $err): ?><div>✕ <?= e($err) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="checkout-form-card">
        <input type="hidden" name="next" value="<?= e($next) ?>">

        <div class="form-group">
            <label for="email">Email</label>
            <input type="text" id="email" name="email" class="form-control"
                   value="<?= e($_POST['email'] ?? '') ?>"
                   placeholder="email@exemplo.com" required autofocus>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control"
                   placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn btn-red btn-full btn-lg">Iniciar Sessão</button>

        <div style="text-align:center; margin-top:20px; font-size:14px; color:var(--muted);">
            Não tens conta?
            <a href="/registo.php" style="color:var(--dark); font-weight:600;">Criar conta de cliente</a>
        </div>

        <?php if ($hint !== 'cliente'): ?>
            <div style="text-align:center; margin-top:8px; font-size:12px; color:var(--muted);">
                As contas de admin e gestor são criadas pelo administrador.
            </div>
        <?php endif; ?>
    </form>

    <div style="text-align:center; margin-top:24px;">
        <a href="/entrada.php" class="text-muted" style="font-size:13px;">← Voltar à página inicial</a>
    </div>

    <!-- Credenciais de teste (apenas para desenvolvimento) -->
    <details style="margin-top:32px; background:var(--bg); border-radius:var(--radius); padding:16px; font-size:12px; color:var(--muted);">
        <summary style="cursor:pointer; font-weight:700; color:var(--mid);">🔧 Credenciais de teste</summary>
        <div style="margin-top:12px; display:flex; flex-direction:column; gap:6px;">
            <div><strong>Admin:</strong> admin@shoele_store.test / admin123</div>
            <div><strong>Gestor:</strong> gestor@shoele_store.test / gestor123</div>
            <div><strong>Cliente:</strong> cliente@shoele_store.test / cliente123</div>
        </div>
    </details>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
