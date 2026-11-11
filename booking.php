<?php
/**
 * Rezervace – vytvoření nové rezervace a přehled "Moje rezervace".
 * Vyžaduje přihlášení; veškerá validace probíhá na serveru (JS jen zpříjemňuje vyplňování).
 */
require __DIR__ . '/includes/bootstrap.php';

$user = require_login();

$cats = db()->fetchAll('SELECT id, name, photo, rest_days FROM cats WHERE is_active = 1 ORDER BY sort_order, name');
$catsById = array_column($cats, null, 'id');

/** Volné začátky návštěv (po 30 min) pro dané datum a délku podle otevírací doby. */
function available_slots(string $date, int $duration): array
{
    $ts = strtotime($date);
    if (!$ts) {
        return [];
    }
    [$open, $close] = OPENING_HOURS[(int) date('w', $ts)];
    $isToday = $date === date('Y-m-d');
    $nowMinutes = (int) date('G') * 60 + (int) date('i');

    $slots = [];
    for ($m = $open * 60; $m + $duration * 60 <= $close * 60; $m += 30) {
        if ($isToday && $m < $nowMinutes + 30) {
            continue; // dnes jen časy alespoň 30 min od teď
        }
        $slots[] = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
    }
    return $slots;
}

function calculate_price(int $people, int $hours, bool $withCat): int
{
    return $people * $hours * PRICE_PER_PERSON_HOUR + ($withCat ? CAT_SESSION_FEE : 0);
}

/** Podmínka SQL pro rezervace, které se časově překrývají s intervalem [start, end). */
const OVERLAP_SQL = "visit_date = ? AND status = 'confirmed'
    AND start_time < ? AND ADDTIME(start_time, SEC_TO_TIME(duration * 3600)) > ?";

// Výchozí hodnoty formuláře
$form = [
    'date'     => date('Y-m-d'),
    'time'     => '',
    'duration' => 2,
    'people'   => 2,
    'cat_id'   => isset($_GET['cat'], $catsById[(int) $_GET['cat']]) ? (int) $_GET['cat'] : 0,
    'note'     => '',
];
$errors = [];

/* ---------- Zpracování formulářů ---------- */
if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    // --- Zrušení rezervace ---
    if ($action === 'cancel') {
        $cancelled = db()->execute(
            "UPDATE reservations SET status = 'cancelled'
             WHERE id = ? AND user_id = ? AND status = 'confirmed' AND visit_date >= CURDATE()",
            [(int) ($_POST['id'] ?? 0), $user['id']]
        );
        flash($cancelled ? 'success' : 'error', $cancelled
            ? 'Rezervace byla zrušena.'
            : 'Rezervaci se nepodařilo zrušit (už proběhla nebo neexistuje).');
        redirect('booking.php#moje-rezervace');
    }

    // --- Nová rezervace ---
    if ($action === 'create') {
        $form = [
            'date'     => trim($_POST['date'] ?? ''),
            'time'     => trim($_POST['time'] ?? ''),
            'duration' => (int) ($_POST['duration'] ?? 0),
            'people'   => (int) ($_POST['people'] ?? 0),
            'cat_id'   => (int) ($_POST['cat_id'] ?? 0),
            'note'     => trim($_POST['note'] ?? ''),
        ];

        // Datum
        $dateObj = DateTime::createFromFormat('!Y-m-d', $form['date']);
        $maxDate = date('Y-m-d', strtotime('+' . MAX_DAYS_AHEAD . ' days'));
        if (!$dateObj || $dateObj->format('Y-m-d') !== $form['date']) {
            $errors['date'] = 'Vyberte platné datum.';
        } elseif ($form['date'] < date('Y-m-d')) {
            $errors['date'] = 'Datum nesmí být v minulosti.';
        } elseif ($form['date'] > $maxDate) {
            $errors['date'] = 'Rezervovat lze nejvýše ' . MAX_DAYS_AHEAD . ' dní dopředu.';
        }

        // Délka a počet osob
        if (!in_array($form['duration'], [1, 2, 3], true)) {
            $errors['duration'] = 'Vyberte délku návštěvy.';
        }
        if ($form['people'] < 1 || $form['people'] > MAX_PEOPLE) {
            $errors['people'] = 'Počet osob musí být 1–' . MAX_PEOPLE . '.';
        }

        // Čas – musí odpovídat nabízenému slotu
        if (!isset($errors['date']) && !isset($errors['duration'])
            && !in_array($form['time'], available_slots($form['date'], $form['duration']), true)) {
            $errors['time'] = 'Vyberte čas v rámci otevírací doby.';
        }

        // Kočka (nepovinná) – musí existovat a nemít v daný den volno
        if ($form['cat_id']) {
            $cat = $catsById[$form['cat_id']] ?? null;
            if (!$cat) {
                $errors['cat_id'] = 'Vybraná kočka neexistuje.';
            } elseif (!isset($errors['date']) && in_array((int) date('w', strtotime($form['date'])), parse_days($cat['rest_days']), true)) {
                $errors['cat_id'] = $cat['name'] . ' má v tento den volno.';
            }
        }

        if (mb_strlen($form['note']) > 200) {
            $errors['note'] = 'Poznámka může mít nejvýše 200 znaků.';
        }

        // Kontroly obsazenosti v transakci (zamkneme rezervace daného dne)
        if (!$errors) {
            $start = $form['time'] . ':00';
            $end = end_time($form['time'], $form['duration']) . ':00';
            db()->begin();
            try {
                // Kapacita kavárny
                $occupied = (int) db()->fetchValue(
                    'SELECT COALESCE(SUM(people), 0) FROM reservations WHERE ' . OVERLAP_SQL . ' FOR UPDATE',
                    [$form['date'], $end, $start]
                );
                if ($occupied + $form['people'] > CAFE_CAPACITY) {
                    $free = max(0, CAFE_CAPACITY - $occupied);
                    $errors['time'] = $free
                        ? "V tomto čase zbývá jen $free volných míst."
                        : 'V tomto čase je kavárna plně obsazená. Zkuste jiný čas.';
                }

                // Vybraná kočka už má jiného hosta
                if (!$errors && $form['cat_id']) {
                    if (db()->fetchValue('SELECT COUNT(*) FROM reservations WHERE ' . OVERLAP_SQL . ' AND cat_id = ?',
                        [$form['date'], $end, $start, $form['cat_id']])) {
                        $errors['cat_id'] = $catsById[$form['cat_id']]['name'] . ' už má v tomto čase jiné hosty. Zvolte jiný čas nebo kočku.';
                    }
                }

                // Uživatel už má rezervaci, která se překrývá
                if (!$errors) {
                    if (db()->fetchValue('SELECT COUNT(*) FROM reservations WHERE ' . OVERLAP_SQL . ' AND user_id = ?',
                        [$form['date'], $end, $start, $user['id']])) {
                        $errors['time'] = 'V tomto čase už máte jinou rezervaci.';
                    }
                }

                if (!$errors) {
                    // Unikátní kód rezervace
                    do {
                        $code = 'PM-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
                    } while (db()->fetchValue('SELECT 1 FROM reservations WHERE code = ?', [$code]));

                    db()->execute(
                        'INSERT INTO reservations (code, user_id, cat_id, visit_date, start_time, duration, people, note, price)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        [
                            $code,
                            $user['id'],
                            $form['cat_id'] ?: null,
                            $form['date'],
                            $start,
                            $form['duration'],
                            $form['people'],
                            $form['note'],
                            calculate_price($form['people'], $form['duration'], (bool) $form['cat_id']),
                        ]
                    );
                }
                db()->commit();
            } catch (Throwable $ex) {
                db()->rollback();
                throw $ex;
            }

            if (!$errors) {
                // PRG: přesměrování zabrání dvojímu odeslání při obnovení stránky
                redirect('booking.php?ok=' . urlencode($code) . '#potvrzeni');
            }
        }
    }
}

/* ---------- Data pro zobrazení ---------- */

// Potvrzení právě vytvořené rezervace
$confirmation = null;
if (!empty($_GET['ok'])) {
    $confirmation = db()->fetchOne(
        'SELECT r.*, c.name AS cat_name FROM reservations r
         LEFT JOIN cats c ON c.id = r.cat_id
         WHERE r.code = ? AND r.user_id = ?',
        [$_GET['ok'], $user['id']]
    );
}

// Moje rezervace – nadcházející a historie
$myReservations = db()->fetchAll(
    'SELECT r.*, c.name AS cat_name FROM reservations r
     LEFT JOIN cats c ON c.id = r.cat_id
     WHERE r.user_id = ?
     ORDER BY r.visit_date, r.start_time',
    [$user['id']]
);
$upcoming = [];
$history = [];
foreach ($myReservations as $r) {
    if ($r['status'] === 'confirmed' && $r['visit_date'] >= date('Y-m-d')) {
        $upcoming[] = $r;
    } else {
        $history[] = $r;
    }
}
$history = array_reverse($history);

$slots = available_slots($form['date'], max(1, $form['duration']));

$pageTitle = 'Rezervace – Purrfect Maine';
$pageDescription = 'Zarezervujte si stůl v kočičí kavárně Purrfect Maine a vyberte si mainskou mývalí kočku, se kterou chcete strávit čas.';
$activePage = 'booking';
require __DIR__ . '/includes/header.php';

/** Vykreslí chybovou hlášku a třídu pro pole. */
$err = fn (string $field) => '<span class="error-msg" id="' . $field . '-error">' . e($errors[$field] ?? '') . '</span>';
$invalid = fn (string $field) => isset($errors[$field]) ? ' invalid' : '';

/** Jedna položka v seznamu rezervací. */
function render_reservation(array $r, bool $canCancel): void
{
    $ts = strtotime($r['visit_date']);
    $start = substr($r['start_time'], 0, 5);
    $cancelled = $r['status'] === 'cancelled';
    ?>
    <li class="reservation<?= $canCancel ? '' : ' past' ?>">
      <div class="res-date" aria-hidden="true">
        <span class="day"><?= date('j', $ts) ?></span>
        <span class="month"><?= MONTHS_SHORT[(int) date('n', $ts) - 1] ?></span>
      </div>
      <div class="res-info">
        <h4>
          <?= e(format_date_long($r['visit_date'])) ?>, <?= e($start) ?> – <?= e(end_time($start, (int) $r['duration'])) ?>
          <?php if ($cancelled): ?><span class="badge badge-red">Zrušeno</span><?php elseif (!$canCancel): ?><span class="badge">Proběhlo</span><?php endif; ?>
        </h4>
        <p>Číslo rezervace <strong><?= e($r['code']) ?></strong></p>
        <div class="res-badges">
          <span>👥 <?= e(people_label((int) $r['people'])) ?></span>
          <span>⏱ <?= (int) $r['duration'] ?> h</span>
          <span>🐈 <?= e($r['cat_name'] ?? 'Kterákoli kočka') ?></span>
          <span>💰 <?= e(format_price((int) $r['price'])) ?></span>
          <?php if ($r['note']): ?><span>📝 <?= e($r['note']) ?></span><?php endif; ?>
        </div>
      </div>
      <?php if ($canCancel): ?>
        <form method="post" data-confirm="Opravdu zrušit rezervaci na <?= e(format_date_long($r['visit_date'])) ?> v <?= e($start) ?>?">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="cancel">
          <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
          <button type="submit" class="btn btn-danger">Zrušit</button>
        </form>
      <?php endif; ?>
    </li>
    <?php
}
?>

  <main>
    <section class="page-hero">
      <div class="container">
        <span class="eyebrow eyebrow-green">Online rezervace</span>
        <h1>Rezervujte si chvíli s předením</h1>
        <p>Vyberte datum, čas a počet hostů. Pokud máte favorita, můžete si rovnou zvolit kočku, se kterou chcete strávit čas.</p>
      </div>
    </section>

    <section class="section">
      <div class="container">

        <?php if ($confirmation): ?>
          <!-- Potvrzení rezervace -->
          <div id="potvrzeni" class="confirmation show" role="status" tabindex="-1">
            <h3>🎉 Rezervace potvrzena!</h3>
            <p>Děkujeme, <?= e($user['name']) ?>. Těšíme se na vás – a kočky taky. Číslo rezervace: <strong><?= e($confirmation['code']) ?></strong></p>
            <dl>
              <dt>Datum</dt><dd><?= e(format_date_long($confirmation['visit_date'])) ?></dd>
              <dt>Čas</dt><dd><?= e(substr($confirmation['start_time'], 0, 5)) ?> – <?= e(end_time($confirmation['start_time'], (int) $confirmation['duration'])) ?></dd>
              <dt>Počet osob</dt><dd><?= e(people_label((int) $confirmation['people'])) ?></dd>
              <dt>Kočka</dt><dd><?= e($confirmation['cat_name'] ?? 'Kterákoli – nechte se překvapit') ?></dd>
              <dt>Cena</dt><dd><?= e(format_price((int) $confirmation['price'])) ?> (platí se na místě)</dd>
            </dl>
          </div>
        <?php endif; ?>

        <?php if ($errors): ?>
          <div class="flash flash-error" role="alert">Rezervaci se nepodařilo vytvořit – zkontrolujte prosím zvýrazněná pole.</div>
        <?php endif; ?>

        <div class="booking-layout">

          <!-- ===== Rezervační formulář ===== -->
          <form id="booking-form" class="card" method="post" novalidate
                data-hours='<?= e(json_encode(OPENING_HOURS)) ?>'
                data-price="<?= PRICE_PER_PERSON_HOUR ?>"
                data-cat-fee="<?= CAT_SESSION_FEE ?>"
                data-max-people="<?= MAX_PEOPLE ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <h2 style="font-size:1.7rem">Nová rezervace</h2>
            <p class="form-note" style="margin-bottom:1.4rem">Rezervujete jako <strong><?= e($user['name']) ?></strong> (<?= e($user['email']) ?>)</p>

            <div class="form-grid">
              <div class="form-group">
                <label for="date">Datum návštěvy</label>
                <input type="date" id="date" name="date" class="form-control<?= $invalid('date') ?>" value="<?= e($form['date']) ?>"
                       min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+' . MAX_DAYS_AHEAD . ' days')) ?>" required aria-describedby="date-error">
                <?= $err('date') ?>
              </div>

              <div class="form-group">
                <label for="duration">Délka návštěvy</label>
                <select id="duration" name="duration" class="form-control<?= $invalid('duration') ?>">
                  <?php foreach ([1 => '1 hodina', 2 => '2 hodiny', 3 => '3 hodiny'] as $h => $label): ?>
                    <option value="<?= $h ?>"<?= $form['duration'] === $h ? ' selected' : '' ?>><?= $label ?></option>
                  <?php endforeach; ?>
                </select>
                <?= $err('duration') ?>
              </div>

              <div class="form-group">
                <label for="time">Čas příchodu</label>
                <select id="time" name="time" class="form-control<?= $invalid('time') ?>" required aria-describedby="time-error">
                  <?php if ($slots): ?>
                    <option value="">Vyberte čas</option>
                    <?php foreach ($slots as $slot): ?>
                      <option value="<?= $slot ?>"<?= $form['time'] === $slot ? ' selected' : '' ?>><?= $slot ?> – <?= end_time($slot, max(1, $form['duration'])) ?></option>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <option value="">Pro tento den už nejsou volné časy</option>
                  <?php endif; ?>
                </select>
                <?= $err('time') ?>
              </div>

              <div class="form-group">
                <label for="people">Počet osob</label>
                <div class="stepper<?= $invalid('people') ?>">
                  <button type="button" data-step="-1" aria-label="Ubrat osobu">−</button>
                  <input type="number" id="people" name="people" value="<?= (int) $form['people'] ?>" min="1" max="<?= MAX_PEOPLE ?>" inputmode="numeric" aria-describedby="people-error">
                  <button type="button" data-step="1" aria-label="Přidat osobu">+</button>
                </div>
                <?= $err('people') ?>
              </div>

              <fieldset class="form-group full fieldset-plain">
                <legend>Kočka, se kterou chcete trávit čas <span class="optional">(nepovinné)</span></legend>
                <div class="cat-picker" id="cat-picker">
                  <div class="cat-option">
                    <input type="radio" name="cat_id" id="cat-0" value="0" data-rest-days=""<?= !$form['cat_id'] ? ' checked' : '' ?>>
                    <label for="cat-0"><span class="any-cat" aria-hidden="true">🐾</span>Kterákoli</label>
                  </div>
                  <?php foreach ($cats as $cat): ?>
                    <div class="cat-option">
                      <input type="radio" name="cat_id" id="cat-<?= (int) $cat['id'] ?>" value="<?= (int) $cat['id'] ?>"
                             data-name="<?= e($cat['name']) ?>" data-rest-days="<?= e($cat['rest_days']) ?>"<?= $form['cat_id'] === (int) $cat['id'] ? ' checked' : '' ?>>
                      <label for="cat-<?= (int) $cat['id'] ?>">
                        <?php if ($cat['photo']): ?>
                          <img src="<?= e(photo_url($cat['photo'])) ?>" alt="" loading="lazy">
                        <?php else: ?>
                          <span class="any-cat" aria-hidden="true">🐈</span>
                        <?php endif; ?>
                        <span><?= e($cat['name']) ?></span>
                      </label>
                    </div>
                  <?php endforeach; ?>
                </div>
                <?= $err('cat_id') ?>
              </fieldset>

              <div class="form-group full">
                <label for="note">Poznámka <span class="optional">(nepovinné)</span></label>
                <textarea id="note" name="note" class="form-control<?= $invalid('note') ?>" maxlength="200" placeholder="Např. oslava narozenin, alergie, dětská židlička…"><?= e($form['note']) ?></textarea>
                <?= $err('note') ?>
              </div>
            </div>

            <div class="form-actions">
              <p class="form-note">Platí se na místě. Rezervaci můžete kdykoli zrušit níže.</p>
              <button type="submit" class="btn btn-primary">Potvrdit rezervaci</button>
            </div>
          </form>

          <!-- ===== Informační panel ===== -->
          <aside class="card info-panel">
            <h3>Shrnutí ceny</h3>
            <div class="price-box" aria-live="polite">
              <div class="row"><span>Vstupné (<?= PRICE_PER_PERSON_HOUR ?> Kč / os. / hod)</span><span id="price-base">–</span></div>
              <div class="row" id="price-cat-row" hidden><span>Vyhrazený čas s kočkou</span><span id="price-cat">–</span></div>
              <div class="row total"><span>Celkem</span><span id="price-total">–</span></div>
            </div>

            <h3 style="margin-top:2rem">Dobré vědět</h3>
            <ul>
              <li>Vstupné zahrnuje neomezený čaj a filtrovanou kávu.</li>
              <li>Každá kočka může mít volné dny – v&nbsp;tyto dny ji nelze vybrat.</li>
              <li>S&nbsp;vybranou kočkou může být v&nbsp;jednu chvíli jen jedna skupina.</li>
              <li>Rezervovat lze až <?= MAX_DAYS_AHEAD ?> dní dopředu.</li>
              <li>Pro skupiny nad <?= MAX_PEOPLE ?> osob nám prosím zavolejte na <a href="tel:+420777123456">+420 777 123 456</a>.</li>
            </ul>
          </aside>
        </div>

        <!-- ===== Moje rezervace ===== -->
        <div id="moje-rezervace" style="margin-top:4rem">
          <div class="reservations-head">
            <h2>Moje rezervace <span class="res-count"><?= count($upcoming) ?></span></h2>
          </div>

          <ul class="reservation-list">
            <?php if (!$upcoming): ?>
              <li class="empty-state">
                <div class="icon" aria-hidden="true">🐾</div>
                <p><strong>Nemáte žádnou nadcházející rezervaci.</strong></p>
                <p>Vyplňte formulář výše a zarezervujte si chvíli s našimi mývalími obry.</p>
              </li>
            <?php endif; ?>
            <?php foreach ($upcoming as $r) render_reservation($r, true); ?>
          </ul>

          <?php if ($history): ?>
            <details class="history">
              <summary>Historie a zrušené rezervace (<?= count($history) ?>)</summary>
              <ul class="reservation-list">
                <?php foreach ($history as $r) render_reservation($r, false); ?>
              </ul>
            </details>
          <?php endif; ?>
        </div>

      </div>
    </section>
  </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
