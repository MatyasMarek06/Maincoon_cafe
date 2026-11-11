<?php
/**
 * Administrace – přehled dne: kolik hostů přijde, kdy bude nejvíc plno
 * a seznam rezervací. Vpravo souhrn následujících 7 dní.
 */
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

// Vybraný den (výchozí dnes)
$day = $_GET['den'] ?? date('Y-m-d');
$dayObj = DateTime::createFromFormat('!Y-m-d', $day);
if (!$dayObj || $dayObj->format('Y-m-d') !== $day) {
    $day = date('Y-m-d');
}
$ts = strtotime($day);

/* ---------- Zrušení rezervace ---------- */
if (is_post()) {
    verify_csrf();
    if (($_POST['action'] ?? '') === 'cancel') {
        db()->execute("UPDATE reservations SET status = 'cancelled' WHERE id = ?", [(int) ($_POST['id'] ?? 0)]);
        flash('success', 'Rezervace byla zrušena.');
    }
    redirect('admin/index.php?den=' . $day);
}

/* ---------- Data vybraného dne ---------- */
$reservations = db()->fetchAll(
    "SELECT r.*, u.name AS user_name, u.email AS user_email, c.name AS cat_name
     FROM reservations r
     JOIN users u ON u.id = r.user_id
     LEFT JOIN cats c ON c.id = r.cat_id
     WHERE r.visit_date = ? AND r.status = 'confirmed'
     ORDER BY r.start_time, r.id",
    [$day]
);
$cancelledCount = (int) db()->fetchValue(
    "SELECT COUNT(*) FROM reservations WHERE visit_date = ? AND status = 'cancelled'",
    [$day]
);

$totalGuests = array_sum(array_column($reservations, 'people'));
$withCat = count(array_filter($reservations, fn ($r) => $r['cat_id']));

// Obsazenost po půlhodinách v rámci otevírací doby
[$open, $close] = OPENING_HOURS[(int) date('w', $ts)];
$slots = [];
for ($m = $open * 60; $m < $close * 60; $m += 30) {
    $guests = 0;
    foreach ($reservations as $r) {
        $start = (int) substr($r['start_time'], 0, 2) * 60 + (int) substr($r['start_time'], 3, 2);
        if ($m >= $start && $m < $start + $r['duration'] * 60) {
            $guests += (int) $r['people'];
        }
    }
    $slots[] = ['time' => sprintf('%02d:%02d', intdiv($m, 60), $m % 60), 'guests' => $guests];
}
$peak = $slots ? max(array_column($slots, 'guests')) : 0;
$peakSlot = null;
foreach ($slots as $s) {
    if ($peak > 0 && $s['guests'] === $peak) {
        $peakSlot = $s['time'];
        break;
    }
}

/* ---------- Následujících 7 dní ---------- */
$weekRows = db()->fetchAll(
    "SELECT visit_date, COUNT(*) AS cnt, SUM(people) AS guests
     FROM reservations
     WHERE status = 'confirmed' AND visit_date BETWEEN CURDATE() AND CURDATE() + INTERVAL 6 DAY
     GROUP BY visit_date",
    []
);
$weekByDate = array_column($weekRows, null, 'visit_date');
$week = [];
for ($i = 0; $i < 7; $i++) {
    $d = date('Y-m-d', strtotime("+$i days"));
    $week[] = ['date' => $d, 'cnt' => (int) ($weekByDate[$d]['cnt'] ?? 0), 'guests' => (int) ($weekByDate[$d]['guests'] ?? 0)];
}
$weekMax = max(1, max(array_column($week, 'guests')));

// Popisek dne
$dayLabel = match ($day) {
    date('Y-m-d') => 'Dnes',
    date('Y-m-d', strtotime('+1 day')) => 'Zítra',
    date('Y-m-d', strtotime('-1 day')) => 'Včera',
    default => mb_convert_case(DAY_NAMES[(int) date('w', $ts)], MB_CASE_TITLE),
};

$pageTitle = 'Přehled';
$adminTab = 'overview';
require __DIR__ . '/../includes/admin_header.php';
?>

<div class="overview-layout">
  <section class="overview-main">

    <!-- Výběr dne -->
    <div class="day-switch">
      <a class="day-arrow" href="?den=<?= date('Y-m-d', strtotime('-1 day', $ts)) ?>" aria-label="Předchozí den">←</a>
      <div class="day-title">
        <h1><?= e($dayLabel) ?></h1>
        <p><?= e(DAY_NAMES[(int) date('w', $ts)]) ?> <?= date('j. n. Y', $ts) ?> · otevřeno <?= $open ?>:00–<?= $close ?>:00</p>
      </div>
      <a class="day-arrow" href="?den=<?= date('Y-m-d', strtotime('+1 day', $ts)) ?>" aria-label="Další den">→</a>
      <form method="get" class="day-picker">
        <label for="den" class="visually-hidden">Vybrat den</label>
        <input type="date" id="den" name="den" value="<?= e($day) ?>" class="form-control" onchange="this.form.submit()">
        <?php if ($day !== date('Y-m-d')): ?>
          <a href="<?= e(url('admin/index.php')) ?>" class="btn btn-small btn-ghost">Dnes</a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Souhrn dne -->
    <div class="stat-grid">
      <div class="stat-card"><strong><?= $totalGuests ?></strong><span>hostů celkem</span></div>
      <div class="stat-card"><strong><?= count($reservations) ?></strong><span>rezervací</span></div>
      <div class="stat-card">
        <strong><?= $peak ?><small> / <?= CAFE_CAPACITY ?></small></strong>
        <span><?= $peakSlot ? 'nejvíc hostů najednou (od ' . e($peakSlot) . ')' : 'nejvíc hostů najednou' ?></span>
      </div>
      <div class="stat-card"><strong><?= $withCat ?></strong><span>s vybranou kočkou</span></div>
    </div>

    <!-- Obsazenost během dne -->
    <div class="card occ-card">
      <div class="occ-head">
        <h2>Hostů v kavárně během dne</h2>
        <span class="occ-cap"><span class="occ-cap-line" aria-hidden="true"></span> kapacita <?= CAFE_CAPACITY ?></span>
      </div>
      <div class="occ-chart" role="img" aria-label="Počet hostů po půlhodinách, nejvíce <?= $peak ?> z <?= CAFE_CAPACITY ?>">
        <div class="occ-axis" aria-hidden="true">
          <span><?= CAFE_CAPACITY ?></span><span><?= intdiv(CAFE_CAPACITY, 2) ?></span><span>0</span>
        </div>
        <div class="occ-plot">
          <div class="occ-gridline" style="bottom:50%"></div>
          <div class="occ-capline"></div>
          <?php foreach ($slots as $s): $pct = min(100, $s['guests'] / CAFE_CAPACITY * 100); ?>
            <div class="occ-col" tabindex="0" data-tip="<?= e($s['time']) ?> · <?= $s['guests'] ?> <?= $s['guests'] === 1 ? 'host' : (($s['guests'] >= 2 && $s['guests'] <= 4) ? 'hosté' : 'hostů') ?>">
              <span class="occ-bar" style="height:<?= $pct ?>%"></span>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="occ-xlabels" aria-hidden="true">
          <?php foreach ($slots as $i => $s): ?>
            <span><?= $i % 2 === 0 ? (int) substr($s['time'], 0, 2) : '' ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Seznam rezervací -->
    <h2 class="admin-subheading">Rezervace<?= $cancelledCount ? ' <span class="form-note">(' . $cancelledCount . ' zrušených se nezobrazuje)</span>' : '' ?></h2>

    <?php if (!$reservations): ?>
      <div class="empty-state">
        <div class="icon" aria-hidden="true">☕</div>
        <p>Na tento den zatím nikdo nemá rezervaci.</p>
      </div>
    <?php else: ?>
      <ul class="visit-list">
        <?php foreach ($reservations as $r): $start = substr($r['start_time'], 0, 5); ?>
          <li class="visit">
            <div class="visit-time">
              <strong><?= e($start) ?></strong>
              <span>do <?= e(end_time($start, (int) $r['duration'])) ?></span>
            </div>
            <div class="visit-people" title="Počet osob">
              <strong><?= (int) $r['people'] ?></strong>
              <span><?= (int) $r['people'] === 1 ? 'osoba' : ((int) $r['people'] <= 4 ? 'osoby' : 'osob') ?></span>
            </div>
            <div class="visit-info">
              <h3><?= e($r['user_name']) ?></h3>
              <p><a href="mailto:<?= e($r['user_email']) ?>"><?= e($r['user_email']) ?></a> · <?= e($r['code']) ?></p>
              <div class="res-badges">
                <?php if ($r['cat_name']): ?><span>🐈 <?= e($r['cat_name']) ?></span><?php endif; ?>
                <span>💰 <?= e(format_price((int) $r['price'])) ?></span>
                <?php if ($r['note']): ?><span class="visit-note">📝 <?= e($r['note']) ?></span><?php endif; ?>
              </div>
            </div>
            <form method="post" data-confirm="Zrušit rezervaci <?= e($r['code']) ?> (<?= e($r['user_name']) ?>)?">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="cancel">
              <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
              <button type="submit" class="btn btn-small btn-ghost">Zrušit</button>
            </form>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <!-- Následujících 7 dní -->
  <aside class="card week-card">
    <h2>Příštích 7 dní</h2>
    <ul class="week-list">
      <?php foreach ($week as $w): $wts = strtotime($w['date']); ?>
        <li>
          <a href="?den=<?= $w['date'] ?>"<?= $w['date'] === $day ? ' aria-current="date"' : '' ?>>
            <span class="week-day">
              <strong><?= DAY_SHORT[(int) date('w', $wts)] ?> <?= date('j. n.', $wts) ?></strong>
              <small><?= $w['cnt'] ? $w['cnt'] . ' rez.' : 'bez rezervací' ?></small>
            </span>
            <span class="week-bar" aria-hidden="true"><span style="width:<?= $w['guests'] / $weekMax * 100 ?>%"></span></span>
            <span class="week-guests"><?= $w['guests'] ?> <small>hostů</small></span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>

    <div class="quick-actions">
      <a href="<?= e(url('admin/cat-form.php')) ?>" class="btn btn-primary btn-block">+ Přidat kočku</a>
      <a href="<?= e(url('admin/product-form.php')) ?>" class="btn btn-ghost btn-block">+ Přidat produkt</a>
    </div>
  </aside>
</div>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
