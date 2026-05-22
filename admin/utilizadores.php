<?php
/**
 * Admin — Gestão de utilizadores (apenas Administrador)
 */
define('PAGE_TITLE', 'Utilizadores');
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/admin_nav.php';

// Apenas admins podem gerir utilizadores
requireRole(['admin']);

$db     = getDB();
$errors = [];
$action = $_REQUEST['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);

// ── Processar formulários ─────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = $_POST['form_action'] ?? '';

    // Criar utilizador
    if ($formAction === 'create') {
        $nome     = trim($_POST['nome']     ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');
        $role     = $_POST['role'] ?? 'cliente';
        $validRoles = ['admin', 'gestor', 'cliente'];

        if (!$nome)                                    $errors[] = 'O nome é obrigatório.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';
        if (mb_strlen($password) < 6)                  $errors[] = 'Password mínimo 6 caracteres.';
        if (!in_array($role, $validRoles))             $errors[] = 'Role inválido.';

        if (empty($errors)) {
            $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Este email já existe.';
            } else {
                $db->prepare('INSERT INTO users (nome, email, password, role) VALUES (?, ?, ?, ?)')
                   ->execute([$nome, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
                setFlash('success', "Utilizador {$nome} criado com sucesso.");
                header('Location: /admin/utilizadores.php');
                exit;
            }
        }
    }

    // Editar utilizador
    if ($formAction === 'edit') {
        $editId   = (int)$_POST['user_id'];
        $nome     = trim($_POST['nome']     ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');
        $role     = $_POST['role'] ?? 'cliente';
        $validRoles = ['admin', 'gestor', 'cliente'];

        if (!$nome)                                    $errors[] = 'O nome é obrigatório.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';
        if (!in_array($role, $validRoles))             $errors[] = 'Role inválido.';

        if (empty($errors)) {
            // Verificar email duplicado (excluindo o próprio)
            $stmt = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $stmt->execute([$email, $editId]);
            if ($stmt->fetch()) {
                $errors[] = 'Este email já está a ser usado por outro utilizador.';
            } else {
                if ($password) {
                    if (mb_strlen($password) < 6) {
                        $errors[] = 'Nova password mínimo 6 caracteres.';
                    } else {
                        $db->prepare('UPDATE users SET nome=?, email=?, password=?, role=? WHERE id=?')
                           ->execute([$nome, $email, password_hash($password, PASSWORD_DEFAULT), $role, $editId]);
                    }
                } else {
                    $db->prepare('UPDATE users SET nome=?, email=?, role=? WHERE id=?')
                       ->execute([$nome, $email, $role, $editId]);
                }

                if (empty($errors)) {
                    // Atualizar sessão se for o utilizador logado
                    if ($editId === (int)currentUser()['id']) {
                        $_SESSION['user']['nome']  = $nome;
                        $_SESSION['user']['email'] = $email;
                        $_SESSION['user']['role']  = $role;
                    }
                    setFlash('success', 'Utilizador atualizado com sucesso.');
                    header('Location: /admin/utilizadores.php');
                    exit;
                }
            }
        }
        $action = 'edit';
    }

    // Eliminar utilizador
    if ($formAction === 'delete') {
        $deleteId = (int)$_POST['user_id'];
        if ($deleteId === (int)currentUser()['id']) {
            setFlash('warning', 'Não podes eliminar a tua própria conta.');
        } else {
            $db->prepare('DELETE FROM users WHERE id = ?')->execute([$deleteId]);
            setFlash('success', 'Utilizador eliminado.');
        }
        header('Location: /admin/utilizadores.php');
        exit;
    }
}

// ── Dados para formulário de edição ──────────────────────────────────────────

$editUser = null;
if ($action === 'edit' && $editId) {
    $stmt = $db->prepare('SELECT id, nome, email, role FROM users WHERE id = ?');
    $stmt->execute([$editId]);
    $editUser = $stmt->fetch();
    if (!$editUser) { $action = 'list'; }
}

// ── Lista de utilizadores ────────────────────────────────────────────────────

$users = $db->query("SELECT id, nome, email, role, created_at FROM users ORDER BY role, nome")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <?= adminNav('utilizadores') ?>

    <div class="admin-content">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
            <h1 class="admin-page-title" style="margin-bottom:0">Utilizadores</h1>
            <?php if ($action !== 'create'): ?>
                <a href="?action=create" class="btn btn-red">+ Novo Utilizador</a>
            <?php endif; ?>
        </div>
        <p class="admin-page-subtitle"><?= count($users) ?> utilizador(es) registados</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $err): ?><div>✕ <?= e($err) ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Formulário de criação -->
        <?php if ($action === 'create'): ?>
            <div class="form-card mb-4">
                <h2>Novo Utilizador</h2>
                <form method="POST">
                    <input type="hidden" name="form_action" value="create">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nome *</label>
                            <input type="text" name="nome" class="form-control"
                                   value="<?= e($_POST['nome'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= e($_POST['email'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Password * (mín. 6 caracteres)</label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label>Role *</label>
                            <select name="role" class="form-control">
                                <option value="cliente" <?= ($_POST['role'] ?? '') === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                                <option value="gestor"  <?= ($_POST['role'] ?? '') === 'gestor'  ? 'selected' : '' ?>>Gestor</option>
                                <option value="admin"   <?= ($_POST['role'] ?? '') === 'admin'   ? 'selected' : '' ?>>Administrador</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" class="btn btn-red">Criar Utilizador</button>
                        <a href="/admin/utilizadores.php" class="btn btn-ghost">Cancelar</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Formulário de edição -->
        <?php if ($action === 'edit' && $editUser): ?>
            <div class="form-card mb-4">
                <h2>Editar Utilizador — <?= e($editUser['nome']) ?></h2>
                <form method="POST">
                    <input type="hidden" name="form_action" value="edit">
                    <input type="hidden" name="user_id" value="<?= $editUser['id'] ?>">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nome *</label>
                            <input type="text" name="nome" class="form-control"
                                   value="<?= e($_POST['nome'] ?? $editUser['nome']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= e($_POST['email'] ?? $editUser['email']) ?>" required>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nova Password <small class="text-muted">(deixar em branco para manter)</small></label>
                            <input type="password" name="password" class="form-control" minlength="6">
                        </div>
                        <div class="form-group">
                            <label>Role *</label>
                            <select name="role" class="form-control" <?= $editUser['id'] === (int)currentUser()['id'] ? 'disabled' : '' ?>>
                                <?php
                                $currentEditRole = $_POST['role'] ?? $editUser['role'];
                                ?>
                                <option value="cliente" <?= $currentEditRole === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                                <option value="gestor"  <?= $currentEditRole === 'gestor'  ? 'selected' : '' ?>>Gestor</option>
                                <option value="admin"   <?= $currentEditRole === 'admin'   ? 'selected' : '' ?>>Administrador</option>
                            </select>
                            <?php if ($editUser['id'] === (int)currentUser()['id']): ?>
                                <input type="hidden" name="role" value="<?= e($editUser['role']) ?>">
                                <small class="text-muted">Não podes alterar o teu próprio role.</small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" class="btn btn-red">Guardar Alterações</button>
                        <a href="/admin/utilizadores.php" class="btn btn-ghost">Cancelar</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Tabela de utilizadores -->
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Criado em</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <?php
                        $isSelf = $u['id'] === (int)currentUser()['id'];
                        $roleBadge = ['admin' => 'badge-danger', 'gestor' => 'badge-info', 'cliente' => 'badge-secondary'];
                        $roleLabel = ['admin' => 'Administrador', 'gestor' => 'Gestor', 'cliente' => 'Cliente'];
                        ?>
                        <tr <?= $isSelf ? 'style="background:rgba(0,0,0,.03)"' : '' ?>>
                            <td class="text-muted"><?= $u['id'] ?></td>
                            <td>
                                <strong><?= e($u['nome']) ?></strong>
                                <?php if ($isSelf): ?> <span class="badge badge-secondary" style="font-size:10px">Tu</span><?php endif; ?>
                            </td>
                            <td class="text-muted"><?= e($u['email']) ?></td>
                            <td><span class="badge <?= $roleBadge[$u['role']] ?? 'badge-secondary' ?>"><?= $roleLabel[$u['role']] ?? $u['role'] ?></span></td>
                            <td class="text-muted" style="font-size:12px"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <div class="flex gap-2">
                                    <a href="?action=edit&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline">Editar</a>
                                    <?php if (!$isSelf): ?>
                                        <form method="POST" style="display:inline" onsubmit="return confirm('Eliminar o utilizador <?= e(addslashes($u['nome'])) ?>?')">
                                            <input type="hidden" name="form_action" value="delete">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-ghost text-danger">Eliminar</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
