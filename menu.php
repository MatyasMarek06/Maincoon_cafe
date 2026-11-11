<?php
/** Nabídka kavárny – produkty z tabulky products seskupené podle kategorie. */
require __DIR__ . '/includes/bootstrap.php';

$products = db()->fetchAll(
    'SELECT * FROM products WHERE is_available = 1 ORDER BY category, sort_order, name'
);

$byCategory = [];
foreach ($products as $p) {
    $byCategory[$p['category']][] = $p;
}

// Ikony známých kategorií (ostatní dostanou výchozí)
$categoryIcons = ['Káva' => '☕', 'Čaj a nápoje' => '🍵', 'Dezerty' => '🍰', 'Pro kočky' => '🐟'];

$pageTitle = 'Menu – Purrfect Maine';
$pageDescription = 'Výběrová káva, čaje, domácí dezerty a pochoutky pro naše kočky.';
$activePage = 'menu';
require __DIR__ . '/includes/header.php';
?>

  <main>
    <section class="page-hero">
      <div class="container">
        <span class="eyebrow eyebrow-green">Nabídka</span>
        <h1>Naše menu</h1>
        <p>Káva z&nbsp;malých českých pražíren, domácí dezerty pečené každé ráno a pár pochoutek, kterými můžete potěšit naše kočky.</p>
      </div>
    </section>

    <section class="section">
      <div class="container">
        <?php if (!$byCategory): ?>
          <div class="empty-state">
            <div class="icon" aria-hidden="true">☕</div>
            <p>Menu právě aktualizujeme.</p>
          </div>
        <?php endif; ?>

        <?php foreach ($byCategory as $category => $items): ?>
          <div class="menu-category reveal">
            <h2><span aria-hidden="true"><?= $categoryIcons[$category] ?? '🍽️' ?></span> <?= e($category) ?></h2>
            <div class="menu-grid">
              <?php foreach ($items as $item): ?>
                <div class="menu-item">
                  <div class="menu-item-head">
                    <h3><?= e($item['name']) ?></h3>
                    <span class="menu-dots" aria-hidden="true"></span>
                    <span class="menu-price"><?= e(format_price((int) $item['price'])) ?></span>
                  </div>
                  <?php if ($item['description']): ?>
                    <p><?= e($item['description']) ?></p>
                  <?php endif; ?>
                  <?php if ($item['is_vegan']): ?>
                    <span class="badge badge-green">🌱 Veganské</span>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>

        <p class="form-note" style="text-align:center;margin-top:2rem">Rostlinné mléko (ovesné, mandlové) zdarma. Informace o alergenech vám rád sdělí personál.</p>
      </div>
    </section>

    <section class="cta-band">
      <div class="container">
        <h2>Káva chutná nejlépe s&nbsp;předením</h2>
        <p>Rezervujte si místo a vychutnejte si menu ve společnosti našich koček.</p>
        <a href="<?= e(url('booking.php')) ?>" class="btn btn-primary">Rezervovat</a>
      </div>
    </section>
  </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
