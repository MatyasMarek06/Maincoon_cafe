<?php
/** Přihlášení uživatele. */
require __DIR__ . '/includes/bootstrap.php';

$next = $_GET['next'] ?? $_POST['next'] ?? '';
if (current_user()) {
    redirect(safe_next($next));
}

$email = '';
$error = '';

if (is_post()) {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = db()->fetchOne('SELECT id, name, password_hash, role FROM users WHERE email = ?', [$email]);

    if ($user && password_verify($password, $user['password_hash'])) {
        login_user((int) $user['id']);
        flash('success', 'Vítejte zpět, ' . $user['name'] . '!');
        // Majitel jde rovnou do administrace (pokud nemíří jinam)
        redirect($next ? safe_next($next) : ($user['role'] === 'admin' ? 'admin/index.php' : 'index.php'));
    }
    // Stejná hláška pro neexistující e-mail i špatné heslo (neprozrazujeme, které účty existují)
    $error = 'Nesprávný e-mail nebo heslo.';
}

$pageTitle = 'Přihlášení – Purrfect Maine';
$activePage = 'login';
require __DIR__ . '/includes/header.php';
?>

<main class="section auth-section">
  <div class="container auth-wrap">
    <div class="card">
      <h1 class="auth-title">Přihlášení</h1>
      <p class="form-note" style="margin-bottom:1.5rem">Přihlaste se a spravujte své rezervace.</p>

      <?php if ($error): ?>
        <div class="flash flash-error"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">
        <div class="form-group">
          <label for="email">E-mail</label>
          <input type="email" id="email" name="email" class="form-control" value="<?= e($email) ?>" autocomplete="email" required autofocus>
        </div>
        <div class="form-group" style="margin-top:1rem">
          <label for="password">Heslo</label>
          <input type="password" id="password" name="password" class="form-control" autocomplete="current-password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block" style="margin-top:1.5rem">Přihlásit se</button>
      </form>

      <p class="auth-switch">Ještě nemáte účet? <a href="<?= e(url('register.php' . ($next ? '?next=' . urlencode($next) : ''))) ?>">Zaregistrujte se</a></p>
    </div>
  </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
