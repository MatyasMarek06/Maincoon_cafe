<?php
/**
 * Společná hlavička webu.
 * Před vložením nastavte: $pageTitle, volitelně $pageDescription a $activePage
 * (home | cats | menu | booking | login | admin).
 */
$pageTitle = $pageTitle ?? 'Purrfect Maine';
$pageDescription = $pageDescription ?? 'Prémiová kočičí kavárna specializovaná výhradně na plemeno Mainská mývalí kočka.';
$activePage = $activePage ?? '';
$user = current_user();

/** Atribut aria-current pro aktivní položku menu. */
$current = fn (string $page) => $activePage === $page ? ' aria-current="page"' : '';
?>
<!DOCTYPE html>
<html lang="cs">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="<?= e($pageDescription) ?>">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🐈</text></svg>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,500;0,600;0,700;1,500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(url('style.css')) ?>">
</head>
<body>

  <!-- ========== Hlavička / navigace ========== -->
  <header class="site-header">
    <nav class="container nav" aria-label="Hlavní navigace">
      <a href="<?= e(url('index.php')) ?>" class="logo">
        <span class="logo-mark" aria-hidden="true">🐾</span>
        <span>Purrfect <em>Maine</em></span>
      </a>
      <button class="nav-toggle" aria-label="Otevřít menu" aria-expanded="false" aria-controls="nav-links">
        <span></span>
      </button>
      <ul class="nav-links" id="nav-links">
        <li><a href="<?= e(url('index.php')) ?>"<?= $current('home') ?>>Domů</a></li>
        <li><a href="<?= e(url('cats.php')) ?>"<?= $current('cats') ?>>Naše kočky</a></li>
        <li><a href="<?= e(url('menu.php')) ?>"<?= $current('menu') ?>>Menu</a></li>
        <li><a href="<?= e(url('index.php#kontakt')) ?>">Kontakt</a></li>
        <?php if ($user): ?>
          <li class="nav-user">
            <form method="post" action="<?= e(url('logout.php')) ?>">
              <?= csrf_field() ?>
              <span class="nav-user-name" title="<?= e($user['email']) ?>">👤 <?= e($user['name']) ?></span>
              <button type="submit" class="nav-logout">Odhlásit</button>
            </form>
          </li>
        <?php else: ?>
          <li><a href="<?= e(url('login.php')) ?>"<?= $current('login') ?>>Přihlásit</a></li>
        <?php endif; ?>
        <?php if ($user && $user['role'] === 'admin'): ?>
          <li><a href="<?= e(url('admin/index.php')) ?>" class="nav-cta">Administrace</a></li>
        <?php else: ?>
          <li><a href="<?= e(url('booking.php')) ?>" class="nav-cta"<?= $current('booking') ?>>Rezervovat</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>

  <?php $flashes = take_flashes(); if ($flashes): ?>
    <div class="flash-wrap container">
      <?php foreach ($flashes as $f): ?>
        <div class="flash flash-<?= e($f['type']) ?>" role="status"><?= e($f['message']) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
