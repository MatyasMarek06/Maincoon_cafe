<?php
/**
 * Hlavička administrace – vlastní jednoduchý vzhled, oddělený od veřejného webu.
 * Před vložením nastavte $pageTitle a $adminTab (overview | cats | products).
 */
$user = current_user();
$adminTab = $adminTab ?? '';
$tabs = [
    'overview' => ['admin/index.php', '📅', 'Přehled'],
    'cats'     => ['admin/cats.php', '🐈', 'Kočky'],
    'products' => ['admin/products.php', '☕', 'Produkty'],
];
?>
<!DOCTYPE html>
<html lang="cs">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'Administrace') ?> – Purrfect Maine</title>
  <meta name="robots" content="noindex">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🐾</text></svg>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,500;0,600;0,700;1,500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(url('style.css')) ?>">
</head>
<body class="admin-body">

  <header class="admin-bar">
    <div class="container admin-bar-inner">
      <a href="<?= e(url('admin/index.php')) ?>" class="logo admin-logo">
        <span class="logo-mark" aria-hidden="true">🐾</span>
        <span>Purrfect Maine <small>Administrace</small></span>
      </a>
      <nav class="admin-nav" aria-label="Administrace">
        <?php foreach ($tabs as $key => [$path, $icon, $label]): ?>
          <a href="<?= e(url($path)) ?>"<?= $adminTab === $key ? ' aria-current="page"' : '' ?>>
            <span aria-hidden="true"><?= $icon ?></span> <?= $label ?>
          </a>
        <?php endforeach; ?>
      </nav>
      <div class="admin-user">
        <a href="<?= e(url('index.php')) ?>" class="admin-link" target="_blank" rel="noopener">Zobrazit web ↗</a>
        <form method="post" action="<?= e(url('logout.php')) ?>">
          <?= csrf_field() ?>
          <button type="submit" class="admin-logout" title="Odhlásit <?= e($user['email']) ?>">Odhlásit</button>
        </form>
      </div>
    </div>
  </header>

  <main class="admin-main">
    <div class="container">
      <?php foreach (take_flashes() as $f): ?>
        <div class="flash flash-<?= e($f['type']) ?>" role="status"><?= e($f['message']) ?></div>
      <?php endforeach; ?>
