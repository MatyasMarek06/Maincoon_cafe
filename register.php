<?php
/** Registrace nového zákaznického účtu. */
require __DIR__ . '/includes/bootstrap.php';

$next = $_GET['next'] ?? $_POST['next'] ?? '';
if (current_user()) {
    redirect(safe_next($next));
}

$old = ['name' => '', 'email' => ''];
$errors = [];

if (is_post()) {
    verify_csrf();
    $old['name'] = trim($_POST['name'] ?? '');
    $old['email'] = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (mb_strlen($old['name']) < 2 || mb_strlen($old['name']) > 100) {
        $errors['name'] = 'Zadejte jméno (2–100 znaků).';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 190) {
        $errors['email'] = 'Zadejte platný e-mail.';
    }
    if (strlen($password) < 8) {
        $errors['password'] = 'Heslo musí mít alespoň 8 znaků.';
    } elseif ($password !== $password2) {
        $errors['password2'] = 'Hesla se neshodují.';
    }

    if (!$errors) {
        if (db()->fetchValue('SELECT 1 FROM users WHERE email = ?', [$old['email']])) {
            $errors['email'] = 'Účet s tímto e-mailem už existuje. Zkuste se přihlásit.';
        }
    }

    if (!$errors) {
        db()->execute('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)',
            [$old['name'], $old['email'], password_hash($password, PASSWORD_DEFAULT)]);
        login_user(db()->lastId());
        flash('success', 'Účet byl vytvořen. Vítejte v Purrfect Maine!');
        redirect(safe_next($next ?: url('booking.php')));
    }
}

$pageTitle = 'Registrace – Purrfect Maine';
$activePage = 'login';
require __DIR__ . '/includes/header.php';

/** Pomocník pro CSS třídu chybného pole. */
$invalid = fn (string $field) => isset($errors[$field]) ? ' invalid' : '';
?>

<main class="section auth-section">
  <div class="container auth-wrap">
    <div class="card">
      <h1 class="auth-title">Vytvořit účet</h1>
      <p class="form-note" style="margin-bottom:1.5rem">S účtem si můžete rezervovat návštěvu a mít přehled o svých rezervacích.</p>

      <form method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">
        <div class="form-grid">
          <div class="form-group full">
            <label for="name">Jméno a příjmení</label>
            <input type="text" id="name" name="name" class="form-control<?= $invalid('name') ?>" value="<?= e($old['name']) ?>" autocomplete="name" required>
            <span class="error-msg"><?= e($errors['name'] ?? '') ?></span>
          </div>
          <div class="form-group full">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" class="form-control<?= $invalid('email') ?>" value="<?= e($old['email']) ?>" autocomplete="email" required>
            <span class="error-msg"><?= e($errors['email'] ?? '') ?></span>
          </div>
          <div class="form-group">
            <label for="password">Heslo <span class="optional">(min. 8 znaků)</span></label>
            <input type="password" id="password" name="password" class="form-control<?= $invalid('password') ?>" minlength="8" autocomplete="new-password" required>
            <span class="error-msg"><?= e($errors['password'] ?? '') ?></span>
          </div>
          <div class="form-group">
            <label for="password2">Heslo znovu</label>
            <input type="password" id="password2" name="password2" class="form-control<?= $invalid('password2') ?>" autocomplete="new-password" required>
            <span class="error-msg"><?= e($errors['password2'] ?? '') ?></span>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block" style="margin-top:1rem">Zaregistrovat se</button>
      </form>

      <p class="auth-switch">Už máte účet? <a href="<?= e(url('login.php' . ($next ? '?next=' . urlencode($next) : ''))) ?>">Přihlaste se</a></p>
    </div>
  </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
