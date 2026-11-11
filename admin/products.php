<?php
/** Administrace – seznam produktů. */
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

/* ---------- Akce ---------- */
if (is_post()) {
    verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);

    if (($_POST['action'] ?? '') === 'toggle') {
        db()->execute('UPDATE products SET is_available = 1 - is_available WHERE id = ?', [$id]);
        flash('success', 'Dostupnost produktu byla změněna.');
    }
    if (($_POST['action'] ?? '') === 'delete') {
        db()->execute('DELETE FROM products WHERE id = ?', [$id]);
        flash('success', 'Produkt byl odstraněn.');
    }
    redirect('admin/products.php');
}

$products = db()->fetchAll('SELECT * FROM products ORDER BY category, sort_order, name');
$byCategory = [];
foreach ($products as $p) {
    $byCategory[$p['category']][] = $p;
}

$pageTitle = 'Produkty';
$adminTab = 'products';
require __DIR__ . '/../includes/admin_header.php';
?>

    <div class="admin-toolbar">
      <div>
        <h1 class="admin-title">Produkty v menu</h1>
        <p class="form-note">Celkem <?= count($products) ?> produktů. Skryté produkty se v menu nezobrazují.</p>
      </div>
      <a href="<?= e(url('admin/product-form.php')) ?>" class="btn btn-primary">+ Přidat produkt</a>
    </div>

    <?php if (!$products): ?>
      <div class="empty-state">
        <div class="icon" aria-hidden="true">☕</div>
        <p>Zatím tu nejsou žádné produkty.</p>
      </div>
    <?php endif; ?>

    <?php foreach ($byCategory as $category => $items): ?>
      <h2 class="admin-subheading"><?= e($category) ?> <span class="form-note">(<?= count($items) ?>)</span></h2>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Název</th>
              <th>Popis</th>
              <th>Cena</th>
              <th>Stav</th>
              <th><span class="visually-hidden">Akce</span></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $p): ?>
              <tr class="<?= $p['is_available'] ? '' : 'row-muted' ?>">
                <td>
                  <strong><?= e($p['name']) ?></strong>
                  <?php if ($p['is_vegan']): ?><small>🌱 veganské</small><?php endif; ?>
                </td>
                <td class="cell-note"><?= e($p['description'] ?: '—') ?></td>
                <td class="nowrap"><?= e(format_price((int) $p['price'])) ?></td>
                <td>
                  <?php if ($p['is_available']): ?>
                    <span class="badge badge-green">V nabídce</span>
                  <?php else: ?>
                    <span class="badge">Nedostupné</span>
                  <?php endif; ?>
                </td>
                <td class="cell-actions">
                  <a href="<?= e(url('admin/product-form.php?id=' . (int) $p['id'])) ?>" class="btn btn-small btn-ghost">Upravit</a>
                  <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                    <button type="submit" name="action" value="toggle" class="btn btn-small btn-ghost"><?= $p['is_available'] ? 'Skrýt' : 'Zobrazit' ?></button>
                  </form>
                  <form method="post" data-confirm="Opravdu odstranit produkt <?= e($p['name']) ?>?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                    <button type="submit" name="action" value="delete" class="btn btn-small btn-danger">Smazat</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endforeach; ?>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
