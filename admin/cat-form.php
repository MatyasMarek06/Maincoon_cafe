<?php
/** Administrace – přidání nebo úprava kočky (včetně nahrání fotky). */
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$traitFields = [
    'trait_cuddly'    => 'Mazlivost',
    'trait_playful'   => 'Hravost',
    'trait_talkative' => 'Upovídanost',
    'trait_energy'    => 'Energie',
];

// Výchozí hodnoty pro novou kočku
$cat = [
    'name' => '', 'nickname' => '', 'tag_label' => '', 'age_years' => '', 'sex' => 'kocour',
    'weight_kg' => '', 'color' => '', 'personality' => '', 'favorite_activity' => '',
    'favorite_treat' => '', 'dislikes' => '', 'story' => '', 'rest_days' => '', 'tags' => '',
    'highlights' => '', 'photo' => '', 'is_active' => 1, 'sort_order' => 0,
    'trait_cuddly' => 5, 'trait_playful' => 5, 'trait_talkative' => 5, 'trait_energy' => 5,
];

if ($id) {
    $cat = db()->fetchOne('SELECT * FROM cats WHERE id = ?', [$id]);
    if (!$cat) {
        flash('error', 'Kočka nebyla nalezena.');
        redirect('admin/cats.php');
    }
}

$errors = [];

if (is_post()) {
    verify_csrf();
    $oldPhoto = $cat['photo'];

    // Načtení a očištění vstupů
    $text = fn (string $k) => trim((string) ($_POST[$k] ?? ''));
    $cat = array_merge($cat, [
        'name'              => $text('name'),
        'nickname'          => $text('nickname'),
        'tag_label'         => $text('tag_label'),
        'age_years'         => str_replace(',', '.', $text('age_years')),
        'sex'               => $text('sex'),
        'weight_kg'         => str_replace(',', '.', $text('weight_kg')),
        'color'             => $text('color'),
        'personality'       => $text('personality'),
        'favorite_activity' => $text('favorite_activity'),
        'favorite_treat'    => $text('favorite_treat'),
        'dislikes'          => $text('dislikes'),
        'story'             => $text('story'),
        'highlights'        => $text('highlights'),
        'is_active'         => isset($_POST['is_active']) ? 1 : 0,
        'sort_order'        => (int) ($_POST['sort_order'] ?? 0),
        // Dny volna a štítky: jen povolené hodnoty
        'rest_days'         => implode(',', array_intersect(range(0, 6), array_map('intval', (array) ($_POST['rest_days'] ?? [])))),
        'tags'              => implode(' ', array_intersect(array_keys(CAT_TAGS), (array) ($_POST['tags'] ?? []))),
    ]);
    foreach ($traitFields as $field => $_) {
        $cat[$field] = max(0, min(10, (int) ($_POST[$field] ?? 5)));
    }

    // Validace
    if (mb_strlen($cat['name']) < 1 || mb_strlen($cat['name']) > 60) $errors['name'] = 'Zadejte jméno (max. 60 znaků).';
    if (!is_numeric($cat['age_years']) || $cat['age_years'] < 0 || $cat['age_years'] > 30) $errors['age_years'] = 'Zadejte věk v letech (např. 3 nebo 1,5).';
    if (!in_array($cat['sex'], ['kocour', 'kočka'], true)) $errors['sex'] = 'Vyberte pohlaví.';
    if (!is_numeric($cat['weight_kg']) || $cat['weight_kg'] <= 0 || $cat['weight_kg'] > 20) $errors['weight_kg'] = 'Zadejte váhu v kg (např. 6,5).';
    if ($cat['color'] === '') $errors['color'] = 'Zadejte zbarvení.';
    if ($cat['personality'] === '') $errors['personality'] = 'Popište povahu.';
    if ($cat['favorite_activity'] === '') $errors['favorite_activity'] = 'Zadejte oblíbenou činnost.';

    // Fotka: nový soubor > URL > odstranění > ponechat stávající
    $photoUrl = $text('photo_url');
    $newUpload = null;
    if (!empty($_FILES['photo']['name'])) {
        if (!$errors) {
            try {
                $newUpload = save_uploaded_photo($_FILES['photo']);
                $cat['photo'] = $newUpload;
            } catch (RuntimeException $ex) {
                $errors['photo'] = $ex->getMessage();
            }
        } else {
            $errors['photo'] = 'Kvůli chybám ve formuláři vyberte prosím fotku znovu.';
        }
    } elseif ($photoUrl !== '' && $photoUrl !== $oldPhoto) {
        if (filter_var($photoUrl, FILTER_VALIDATE_URL) && preg_match('~^https?://~i', $photoUrl)) {
            $cat['photo'] = $photoUrl;
        } else {
            $errors['photo'] = 'Zadejte platnou adresu fotky začínající http(s)://';
        }
    } elseif (isset($_POST['remove_photo'])) {
        $cat['photo'] = '';
    }

    if (!$errors) {
        $columns = ['name', 'nickname', 'tag_label', 'age_years', 'sex', 'weight_kg', 'color', 'personality',
            'favorite_activity', 'favorite_treat', 'dislikes', 'story', 'trait_cuddly', 'trait_playful',
            'trait_talkative', 'trait_energy', 'rest_days', 'tags', 'highlights', 'photo', 'is_active', 'sort_order'];
        $values = array_map(fn ($c) => $cat[$c], $columns);

        if ($id) {
            $set = implode(', ', array_map(fn ($c) => "$c = ?", $columns));
            db()->execute("UPDATE cats SET $set WHERE id = ?", [...$values, $id]);
            flash('success', 'Profil kočky ' . $cat['name'] . ' byl uložen.');
        } else {
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));
            db()->execute('INSERT INTO cats (' . implode(', ', $columns) . ") VALUES ($placeholders)", $values);
            flash('success', 'Kočka ' . $cat['name'] . ' byla přidána.');
        }

        // Starou nahranou fotku smažeme, pokud byla nahrazena
        if ($oldPhoto !== $cat['photo']) {
            delete_uploaded_photo($oldPhoto);
        }
        redirect('admin/cats.php');
    } elseif ($newUpload) {
        delete_uploaded_photo($newUpload); // formulář neprošel, nahraný soubor nepotřebujeme
        $cat['photo'] = $oldPhoto;
    }
}

$restDays = parse_days($cat['rest_days']);
$tags = array_filter(explode(' ', $cat['tags']));

$pageTitle = $id ? 'Upravit kočku' : 'Přidat kočku';
$adminTab = 'cats';
require __DIR__ . '/../includes/admin_header.php';

$err = fn (string $f) => isset($errors[$f]) ? '<span class="error-msg">' . e($errors[$f]) . '</span>' : '';
$inv = fn (string $f) => isset($errors[$f]) ? ' invalid' : '';
$val = fn (string $f) => e((string) $cat[$f]);
?>

    <p class="back-link"><a href="<?= e(url('admin/cats.php')) ?>">← Zpět na seznam koček</a></p>
    <h1 class="admin-title"><?= e($id ? 'Upravit: ' . $cat['name'] : 'Přidat novou kočku') ?></h1>

    <?php if ($errors): ?>
      <div class="flash flash-error" role="alert">Formulář obsahuje chyby – zkontrolujte zvýrazněná pole.</div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="admin-form" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="MAX_FILE_SIZE" value="<?= UPLOAD_MAX_BYTES ?>">

      <div class="admin-form-grid">
        <div class="card">
          <h2 class="card-title">Základní údaje</h2>
          <div class="form-grid">
            <div class="form-group">
              <label for="name">Jméno *</label>
              <input id="name" name="name" class="form-control<?= $inv('name') ?>" value="<?= $val('name') ?>" maxlength="60" required>
              <?= $err('name') ?>
            </div>
            <div class="form-group">
              <label for="nickname">Přezdívka</label>
              <input id="nickname" name="nickname" class="form-control" value="<?= $val('nickname') ?>" maxlength="100" placeholder="Např. Velký zrzavý medvěd">
            </div>
            <div class="form-group">
              <label for="age_years">Věk (roky) *</label>
              <input id="age_years" name="age_years" class="form-control<?= $inv('age_years') ?>" value="<?= $val('age_years') ?>" inputmode="decimal" placeholder="3 nebo 1,5">
              <?= $err('age_years') ?>
            </div>
            <div class="form-group">
              <label for="sex">Pohlaví *</label>
              <select id="sex" name="sex" class="form-control<?= $inv('sex') ?>">
                <option value="kocour"<?= $cat['sex'] === 'kocour' ? ' selected' : '' ?>>Kocour</option>
                <option value="kočka"<?= $cat['sex'] === 'kočka' ? ' selected' : '' ?>>Kočka</option>
              </select>
              <?= $err('sex') ?>
            </div>
            <div class="form-group">
              <label for="weight_kg">Váha (kg) *</label>
              <input id="weight_kg" name="weight_kg" class="form-control<?= $inv('weight_kg') ?>" value="<?= $val('weight_kg') ?>" inputmode="decimal" placeholder="6,5">
              <?= $err('weight_kg') ?>
            </div>
            <div class="form-group">
              <label for="color">Zbarvení *</label>
              <input id="color" name="color" class="form-control<?= $inv('color') ?>" value="<?= $val('color') ?>" maxlength="80" placeholder="Např. Stříbrná kouřová">
              <?= $err('color') ?>
            </div>
            <div class="form-group full">
              <label for="tag_label">Štítek na fotce</label>
              <input id="tag_label" name="tag_label" class="form-control" value="<?= $val('tag_label') ?>" maxlength="60" placeholder="Např. 👑 Šéf kavárny">
            </div>
          </div>

          <h2 class="card-title" style="margin-top:2rem">Popis</h2>
          <div class="form-grid">
            <div class="form-group full">
              <label for="personality">Povaha *</label>
              <textarea id="personality" name="personality" class="form-control<?= $inv('personality') ?>" rows="3"><?= $val('personality') ?></textarea>
              <?= $err('personality') ?>
            </div>
            <div class="form-group full">
              <label for="favorite_activity">Oblíbená činnost *</label>
              <textarea id="favorite_activity" name="favorite_activity" class="form-control<?= $inv('favorite_activity') ?>" rows="2"><?= $val('favorite_activity') ?></textarea>
              <?= $err('favorite_activity') ?>
            </div>
            <div class="form-group">
              <label for="favorite_treat">Oblíbená pochoutka</label>
              <input id="favorite_treat" name="favorite_treat" class="form-control" value="<?= $val('favorite_treat') ?>" maxlength="255">
            </div>
            <div class="form-group">
              <label for="dislikes">Nemá rád/a</label>
              <input id="dislikes" name="dislikes" class="form-control" value="<?= $val('dislikes') ?>" maxlength="255">
            </div>
            <div class="form-group full">
              <label for="story">Příběh</label>
              <textarea id="story" name="story" class="form-control" rows="3"><?= $val('story') ?></textarea>
            </div>
            <div class="form-group full">
              <label for="highlights">Zajímavosti <span class="optional">(oddělené čárkou)</span></label>
              <input id="highlights" name="highlights" class="form-control" value="<?= $val('highlights') ?>" maxlength="255" placeholder="Miluje drbání brady, Kamarád dětí">
            </div>
          </div>
        </div>

        <div class="admin-side">
          <div class="card">
            <h2 class="card-title">Fotka</h2>
            <div class="photo-preview" id="photo-preview">
              <?php if ($cat['photo']): ?>
                <img src="<?= e(photo_url($cat['photo'])) ?>" alt="Aktuální fotka">
              <?php else: ?>
                <span aria-hidden="true">🐈</span>
              <?php endif; ?>
            </div>
            <div class="form-group" style="margin-top:1rem">
              <label for="photo">Nahrát fotku <span class="optional">(JPG, PNG, WEBP, max. <?= UPLOAD_MAX_BYTES / 1024 / 1024 ?> MB)</span></label>
              <input type="file" id="photo" name="photo" class="form-control<?= $inv('photo') ?>" accept="image/jpeg,image/png,image/webp,image/gif" data-preview="#photo-preview">
            </div>
            <div class="form-group" style="margin-top:.8rem">
              <label for="photo_url">…nebo adresa fotky z internetu</label>
              <input type="url" id="photo_url" name="photo_url" class="form-control" value="<?= preg_match('~^https?://~', (string) $cat['photo']) ? $val('photo') : '' ?>" placeholder="https://…">
            </div>
            <?= $err('photo') ?>
            <?php if ($cat['photo']): ?>
              <label class="check" style="margin-top:.8rem"><input type="checkbox" name="remove_photo" value="1"> Odstranit fotku</label>
            <?php endif; ?>
          </div>

          <div class="card">
            <h2 class="card-title">Povahové rysy</h2>
            <?php foreach ($traitFields as $field => $label): ?>
              <div class="form-group range-group">
                <label for="<?= $field ?>"><?= $label ?> <output for="<?= $field ?>"><?= (int) $cat[$field] ?></output>/10</label>
                <input type="range" id="<?= $field ?>" name="<?= $field ?>" min="0" max="10" value="<?= (int) $cat[$field] ?>">
              </div>
            <?php endforeach; ?>
          </div>

          <div class="card">
            <h2 class="card-title">Rozvrh a zařazení</h2>
            <fieldset class="fieldset-plain">
              <legend>Dny volna <span class="optional">(nelze rezervovat)</span></legend>
              <div class="check-row">
                <?php foreach ([1, 2, 3, 4, 5, 6, 0] as $d): ?>
                  <label class="check-pill"><input type="checkbox" name="rest_days[]" value="<?= $d ?>"<?= in_array($d, $restDays, true) ? ' checked' : '' ?>><span><?= DAY_SHORT[$d] ?></span></label>
                <?php endforeach; ?>
              </div>
            </fieldset>
            <fieldset class="fieldset-plain" style="margin-top:1.2rem">
              <legend>Štítky pro filtr</legend>
              <div class="check-row">
                <?php foreach (CAT_TAGS as $key => $label): ?>
                  <label class="check-pill"><input type="checkbox" name="tags[]" value="<?= e($key) ?>"<?= in_array($key, $tags, true) ? ' checked' : '' ?>><span><?= e($label) ?></span></label>
                <?php endforeach; ?>
              </div>
            </fieldset>
            <div class="form-grid" style="margin-top:1.2rem">
              <div class="form-group">
                <label for="sort_order">Pořadí</label>
                <input type="number" id="sort_order" name="sort_order" class="form-control" value="<?= (int) $cat['sort_order'] ?>">
              </div>
              <div class="form-group" style="justify-content:flex-end">
                <label class="check"><input type="checkbox" name="is_active" value="1"<?= $cat['is_active'] ? ' checked' : '' ?>> Zobrazit na webu</label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="admin-form-actions">
        <a href="<?= e(url('admin/cats.php')) ?>" class="btn btn-ghost">Zrušit</a>
        <button type="submit" class="btn btn-primary"><?= $id ? 'Uložit změny' : 'Přidat kočku' ?></button>
      </div>
    </form>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
