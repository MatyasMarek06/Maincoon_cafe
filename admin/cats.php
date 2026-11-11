<?php
/** Administrace – seznam koček. */
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

/* ---------- Akce ---------- */
if (is_post()) {
    verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);

    if (($_POST['action'] ?? '') === 'toggle') {
        db()->execute('UPDATE cats SET is_active = 1 - is_active WHERE id = ?', [$id]);
        flash('success', 'Viditelnost kočky byla změněna.');
    }

    if (($_POST['action'] ?? '') === 'delete') {
        if ($cat = db()->fetchOne('SELECT name, photo FROM cats WHERE id = ?', [$id])) {
            // Rezervace s touto kočkou zůstanou zachované (cat_id se nastaví na NULL)
            db()->execute('DELETE FROM cats WHERE id = ?', [$id]);
            delete_uploaded_photo($cat['photo']);
            flash('success', 'Kočka ' . $cat['name'] . ' byla odstraněna.');
        }
    }
    redirect('admin/cats.php');
}

$cats = db()->fetchAll(
    "SELECT c.*,
       (SELECT COUNT(*) FROM reservations r
        WHERE r.cat_id = c.id AND r.status = 'confirmed' AND r.visit_date >= CURDATE()) AS upcoming
     FROM cats c ORDER BY c.sort_order, c.name"
);

$pageTitle = 'Kočky';
$adminTab = 'cats';
require __DIR__ . '/../includes/admin_header.php';
?>

    <div class="admin-toolbar">
      <div>
        <h1 class="admin-title">Kočky v kavárně</h1>
        <p class="form-note">Celkem <?= count($cats) ?> koček. Skryté kočky se nezobrazují na webu ani v rezervacích.</p>
      </div>
      <a href="<?= e(url('admin/cat-form.php')) ?>" class="btn btn-primary">+ Přidat kočku</a>
    </div>

    <?php if (!$cats): ?>
      <div class="empty-state">
        <div class="icon" aria-hidden="true">🐈</div>
        <p>Zatím tu nejsou žádné kočky.</p>
      </div>
    <?php else: ?>
      <div class="admin-cards">
        <?php foreach ($cats as $cat): ?>
          <article class="admin-card<?= $cat['is_active'] ? '' : ' row-muted' ?>">
            <div class="admin-card-photo">
              <?php if ($cat['photo']): ?>
                <img src="<?= e(photo_url($cat['photo'])) ?>" alt="" loading="lazy">
              <?php else: ?>
                <span aria-hidden="true">🐈</span>
              <?php endif; ?>
            </div>
            <div class="admin-card-body">
              <h3><?= e($cat['name']) ?>
                <?php if (!$cat['is_active']): ?><span class="badge">Skrytá</span><?php endif; ?>
              </h3>
              <p class="form-note">
                <?= e(format_age((float) $cat['age_years'])) ?> · <?= e($cat['sex']) ?> · <?= e(format_weight((float) $cat['weight_kg'])) ?> · <?= e($cat['color']) ?><br>
                Volno: <?= e(days_label(parse_days($cat['rest_days'])) ?: 'žádné') ?> ·
                Nadcházející rezervace: <strong><?= (int) $cat['upcoming'] ?></strong>
              </p>
              <div class="admin-card-actions">
                <a href="<?= e(url('admin/cat-form.php?id=' . (int) $cat['id'])) ?>" class="btn btn-small btn-ghost">Upravit</a>
                <form method="post">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $cat['id'] ?>">
                  <button type="submit" name="action" value="toggle" class="btn btn-small btn-ghost"><?= $cat['is_active'] ? 'Skrýt' : 'Zobrazit' ?></button>
                </form>
                <form method="post" data-confirm="Opravdu odstranit kočku <?= e($cat['name']) ?>? Profil i fotka budou smazány.">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $cat['id'] ?>">
                  <button type="submit" name="action" value="delete" class="btn btn-small btn-danger">Smazat</button>
                </form>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
