<?php
/** Seznam koček – data z tabulky cats. */
require __DIR__ . '/includes/bootstrap.php';

$cats = db()->fetchAll('SELECT * FROM cats WHERE is_active = 1 ORDER BY sort_order, name');

$traitLabels = [
    'trait_cuddly'    => 'Mazlivost',
    'trait_playful'   => 'Hravost',
    'trait_talkative' => 'Upovídanost',
    'trait_energy'    => 'Energie',
];

$pageTitle = 'Naše kočky – Purrfect Maine';
$pageDescription = 'Seznamte se s mainskými mývalími kočkami, které žijí v kavárně Purrfect Maine. Detailní profily, povaha a oblíbené činnosti.';
$activePage = 'cats';
require __DIR__ . '/includes/header.php';
?>

  <main>
    <section class="page-hero">
      <div class="container">
        <span class="eyebrow eyebrow-green">Rezidenti kavárny</span>
        <h1>Seznamte se s našimi kočkami</h1>
        <p><?= count($cats) ?> mainských mývalích koček, <?= count($cats) ?> úplně jiných osobností. Všechny u nás bydlí natrvalo, jsou kastrované, očkované a pravidelně kontrolované veterinářem.</p>
      </div>
    </section>

    <section class="section">
      <div class="container">

        <?php if (!$cats): ?>
          <div class="empty-state">
            <div class="icon" aria-hidden="true">🐾</div>
            <p>Profily koček právě připravujeme. Zastavte se brzy!</p>
          </div>
        <?php else: ?>

        <!-- Filtry podle povahy -->
        <div class="cat-filters" role="group" aria-label="Filtrovat kočky podle povahy">
          <button type="button" class="filter-btn active" data-filter="all" aria-pressed="true">Všechny</button>
          <?php foreach (CAT_TAGS as $key => $label): ?>
            <button type="button" class="filter-btn" data-filter="<?= e($key) ?>" aria-pressed="false"><?= e($label) ?></button>
          <?php endforeach; ?>
        </div>

        <div class="cat-list">
          <?php foreach ($cats as $cat):
              $restDays = parse_days($cat['rest_days']);
              $highlights = array_filter(array_map('trim', explode(',', $cat['highlights'])));
              $restLabel = $restDays ? 'Volno: ' . days_label($restDays) : 'Bez volného dne';
          ?>
          <article class="cat-card reveal" id="kocka-<?= (int) $cat['id'] ?>" data-tags="<?= e($cat['tags']) ?>">
            <div class="cat-photo"<?= $cat['photo'] ? ' style="--photo: url(&quot;' . e(photo_url($cat['photo'])) . '&quot;)"' : '' ?>>
              <?php if ($cat['photo']): ?>
                <!-- Fotka vždy celá, uprostřed sloupce; okolí vyplní rozmazaná kopie (CSS ::before) -->
                <div class="cat-photo-frame">
                  <img src="<?= e(photo_url($cat['photo'])) ?>" alt="<?= e($cat['name'] . ' – ' . $cat['color']) ?>" loading="lazy">
                </div>
              <?php else: ?>
                <div class="photo-placeholder" role="img" aria-label="<?= e($cat['name']) ?> – fotka zatím chybí">🐈</div>
              <?php endif; ?>
              <?php if ($cat['tag_label']): ?>
                <span class="cat-tag"><?= e($cat['tag_label']) ?></span>
              <?php endif; ?>
            </div>
            <div class="cat-body">
              <div class="cat-header">
                <h3><?= e($cat['name']) ?></h3>
                <?php if ($cat['nickname']): ?>
                  <span class="cat-nickname">„<?= e($cat['nickname']) ?>“</span>
                <?php endif; ?>
              </div>
              <div class="cat-meta">
                <div>Věk<strong><?= e(format_age((float) $cat['age_years'])) ?></strong></div>
                <div>Pohlaví<strong><?= e(mb_convert_case($cat['sex'], MB_CASE_TITLE)) ?></strong></div>
                <div>Váha<strong><?= e(format_weight((float) $cat['weight_kg'])) ?></strong></div>
                <div>Zbarvení<strong><?= e($cat['color']) ?></strong></div>
              </div>

              <div class="cat-details">
                <div>
                  <h4>Povaha</h4>
                  <p><?= e($cat['personality']) ?></p>
                </div>
                <div>
                  <h4>Oblíbená činnost</h4>
                  <p><?= e($cat['favorite_activity']) ?></p>
                </div>
                <?php if ($cat['favorite_treat']): ?>
                <div>
                  <h4>Oblíbená pochoutka</h4>
                  <p><?= e($cat['favorite_treat']) ?></p>
                </div>
                <?php endif; ?>
                <?php if ($cat['dislikes']): ?>
                <div>
                  <h4><?= $cat['sex'] === 'kočka' ? 'Nemá ráda' : 'Nemá rád' ?></h4>
                  <p><?= e($cat['dislikes']) ?></p>
                </div>
                <?php endif; ?>
              </div>

              <?php if ($cat['story']): ?>
                <p class="cat-story"><?= e($cat['story']) ?></p>
              <?php endif; ?>

              <div class="traits" aria-label="Povahové rysy">
                <?php foreach ($traitLabels as $field => $label): $v = (int) $cat[$field]; ?>
                  <div class="trait">
                    <span><?= e($label) ?></span>
                    <div class="trait-bar"><span data-value="<?= $v ?>"></span></div>
                    <span class="trait-value"><?= $v ?>/10</span>
                  </div>
                <?php endforeach; ?>
              </div>

              <ul class="chips">
                <li><?= e($restLabel) ?></li>
                <?php foreach ($highlights as $h): ?>
                  <li><?= e($h) ?></li>
                <?php endforeach; ?>
              </ul>

              <a href="<?= e(url('booking.php?cat=' . (int) $cat['id'])) ?>" class="btn btn-primary">Rezervovat čas – <?= e($cat['name']) ?></a>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </section>

    <section class="cta-band">
      <div class="container">
        <h2>Už máte favorita?</h2>
        <p>Při rezervaci si můžete vybrat konkrétní kočku. Postaráme se, aby měla v&nbsp;době vaší návštěvy službu.</p>
        <a href="<?= e(url('booking.php')) ?>" class="btn btn-primary">Přejít k rezervaci</a>
      </div>
    </section>
  </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
