<?php
/** Administrace – přidání nebo úprava produktu. */
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$product = ['category' => '', 'name' => '', 'description' => '', 'price' => '', 'is_vegan' => 0, 'is_available' => 1, 'sort_order' => 0];

if ($id) {
    $product = db()->fetchOne('SELECT * FROM products WHERE id = ?', [$id]);
    if (!$product) {
        flash('error', 'Produkt nebyl nalezen.');
        redirect('admin/products.php');
    }
}

// Existující kategorie pro našeptávač
$categories = db()->fetchColumn('SELECT DISTINCT category FROM products ORDER BY category');

$errors = [];

if (is_post()) {
    verify_csrf();
    $product = array_merge($product, [
        'category'     => trim($_POST['category'] ?? ''),
        'name'         => trim($_POST['name'] ?? ''),
        'description'  => trim($_POST['description'] ?? ''),
        'price'        => trim($_POST['price'] ?? ''),
        'is_vegan'     => isset($_POST['is_vegan']) ? 1 : 0,
        'is_available' => isset($_POST['is_available']) ? 1 : 0,
        'sort_order'   => (int) ($_POST['sort_order'] ?? 0),
    ]);

    if ($product['category'] === '' || mb_strlen($product['category']) > 60) $errors['category'] = 'Zadejte kategorii (max. 60 znaků).';
    if ($product['name'] === '' || mb_strlen($product['name']) > 120) $errors['name'] = 'Zadejte název (max. 120 znaků).';
    if (mb_strlen($product['description']) > 500) $errors['description'] = 'Popis může mít nejvýše 500 znaků.';
    if (!ctype_digit((string) $product['price']) || (int) $product['price'] > 100000) $errors['price'] = 'Zadejte cenu v celých korunách.';

    if (!$errors) {
        $values = [$product['category'], $product['name'], $product['description'], (int) $product['price'],
            $product['is_vegan'], $product['is_available'], $product['sort_order']];
        if ($id) {
            db()->execute('UPDATE products SET category = ?, name = ?, description = ?, price = ?, is_vegan = ?, is_available = ?, sort_order = ? WHERE id = ?',
                [...$values, $id]);
            flash('success', 'Produkt ' . $product['name'] . ' byl uložen.');
        } else {
            db()->execute('INSERT INTO products (category, name, description, price, is_vegan, is_available, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)',
                $values);
            flash('success', 'Produkt ' . $product['name'] . ' byl přidán.');
        }
        redirect('admin/products.php');
    }
}

$pageTitle = $id ? 'Upravit produkt' : 'Přidat produkt';
$adminTab = 'products';
require __DIR__ . '/../includes/admin_header.php';

$err = fn (string $f) => isset($errors[$f]) ? '<span class="error-msg">' . e($errors[$f]) . '</span>' : '';
$inv = fn (string $f) => isset($errors[$f]) ? ' invalid' : '';
?>

  <div style="max-width:760px">
    <p class="back-link"><a href="<?= e(url('admin/products.php')) ?>">← Zpět na seznam produktů</a></p>
    <h1 class="admin-title"><?= e($id ? 'Upravit: ' . $product['name'] : 'Přidat nový produkt') ?></h1>

    <form method="post" class="card" novalidate>
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="form-group">
          <label for="name">Název *</label>
          <input id="name" name="name" class="form-control<?= $inv('name') ?>" value="<?= e($product['name']) ?>" maxlength="120" required>
          <?= $err('name') ?>
        </div>
        <div class="form-group">
          <label for="category">Kategorie *</label>
          <input id="category" name="category" class="form-control<?= $inv('category') ?>" value="<?= e($product['category']) ?>" list="category-list" maxlength="60" placeholder="Např. Káva" required>
          <datalist id="category-list">
            <?php foreach ($categories as $c): ?>
              <option value="<?= e($c) ?>">
            <?php endforeach; ?>
          </datalist>
          <?= $err('category') ?>
        </div>
        <div class="form-group full">
          <label for="description">Popis</label>
          <textarea id="description" name="description" class="form-control<?= $inv('description') ?>" maxlength="500" rows="3"><?= e($product['description']) ?></textarea>
          <?= $err('description') ?>
        </div>
        <div class="form-group">
          <label for="price">Cena (Kč) *</label>
          <input type="number" id="price" name="price" class="form-control<?= $inv('price') ?>" value="<?= e((string) $product['price']) ?>" min="0" step="1" required>
          <?= $err('price') ?>
        </div>
        <div class="form-group">
          <label for="sort_order">Pořadí v kategorii</label>
          <input type="number" id="sort_order" name="sort_order" class="form-control" value="<?= (int) $product['sort_order'] ?>">
        </div>
        <div class="form-group full check-row">
          <label class="check"><input type="checkbox" name="is_vegan" value="1"<?= $product['is_vegan'] ? ' checked' : '' ?>> 🌱 Veganské</label>
          <label class="check"><input type="checkbox" name="is_available" value="1"<?= $product['is_available'] ? ' checked' : '' ?>> V nabídce</label>
        </div>
      </div>

      <div class="admin-form-actions">
        <a href="<?= e(url('admin/products.php')) ?>" class="btn btn-ghost">Zrušit</a>
        <button type="submit" class="btn btn-primary"><?= $id ? 'Uložit změny' : 'Přidat produkt' ?></button>
      </div>
    </form>
  </div>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
