<?php
/** Hlavní stránka – představení kavárny. */
require __DIR__ . '/includes/bootstrap.php';

// Statistiky koček do úvodního banneru
$catStats = db()->fetchOne('SELECT COUNT(*) AS cnt, MAX(weight_kg) AS heaviest FROM cats WHERE is_active = 1');

// Pár kousků z menu jako ochutnávka
$menuPreview = db()->fetchAll(
    "SELECT name, description, price FROM products
     WHERE is_available = 1 AND category IN ('Káva', 'Dezerty')
     ORDER BY category, sort_order LIMIT 4"
);

$pageTitle = 'Purrfect Maine – kočičí kavárna s mainskými mývalími kočkami';
$activePage = 'home';
require __DIR__ . '/includes/header.php';
?>

  <main>

    <!-- ========== Úvodní banner ========== -->
    <section class="hero">
      <div class="container">
        <div class="hero-content">
          <span class="eyebrow">Kočičí kavárna · Praha</span>
          <h1>Káva v&nbsp;přítomnosti <em>něžných obrů</em></h1>
          <p>Jediná kavárna v&nbsp;Česku, kde vás přivítá výhradně Mainská mývalí kočka. Výběrová káva, domácí dezerty a chlupatí parťáci, kteří vědí, jak zpomalit čas.</p>
          <div class="hero-actions">
            <a href="<?= e(url('booking.php')) ?>" class="btn btn-primary">Rezervovat stůl</a>
            <a href="<?= e(url('cats.php')) ?>" class="btn btn-outline">Seznamte se s kočkami</a>
          </div>
          <div class="hero-stats">
            <div><strong><?= (int) $catStats['cnt'] ?></strong><span>mainských mývalích koček</span></div>
            <?php if ($catStats['heaviest']): ?>
              <div><strong><?= e(format_weight((float) $catStats['heaviest'])) ?></strong><span>váží náš největší parťák</span></div>
            <?php endif; ?>
            <div><strong>4,9 ★</strong><span>hodnocení hostů</span></div>
          </div>
        </div>
      </div>
    </section>

    <!-- ========== Mise kavárny ========== -->
    <section class="section" id="mise">
      <div class="container split">
        <div class="split-media reveal">
          <img src="https://images.unsplash.com/photo-1554118811-1e0d58224f24?auto=format&fit=crop&w=900&q=80" alt="Útulný interiér kavárny s dřevěným nábytkem a teplým osvětlením" loading="lazy">
          <div class="split-badge">
            <strong>Od 2021</strong>
            spojujeme lidi a mainské mývalí kočky
          </div>
        </div>
        <div class="reveal">
          <span class="eyebrow">Naše mise</span>
          <h2>Místo, kde se kočky cítí doma – a vy také</h2>
          <p>Purrfect Maine jsme založili s&nbsp;jednoduchou myšlenkou: vytvořit klidný prostor, kde si lidé odpočinou od shonu a kočky žijí spokojený život bez stresu. Naše kočky nejsou atrakce – jsou to členové rodiny, kteří tu bydlí.</p>
          <p>Proto máme omezený počet míst, rezervační systém a pravidla, která chrání pohodu zvířat. Každá kočka má svůj vlastní odpočinkový den a únikové zóny, kam za ní hosté nesmí.</p>

          <ul class="values">
            <li>
              <span class="icon" aria-hidden="true">🌿</span>
              <div>
                <h4>Welfare na prvním místě</h4>
                <p>Pravidelné veterinární prohlídky, prémiová strava a klidové místnosti jen pro kočky.</p>
              </div>
            </li>
            <li>
              <span class="icon" aria-hidden="true">☕</span>
              <div>
                <h4>Výběrová káva z&nbsp;malých pražíren</h4>
                <p>Spolupracujeme s&nbsp;českými pražírnami, mléko odebíráme z&nbsp;rodinné farmy.</p>
              </div>
            </li>
            <li>
              <span class="icon" aria-hidden="true">🍰</span>
              <div>
                <h4>Domácí dezerty každý den</h4>
                <p>Pečeme sami, bez polotovarů. Veganské varianty jsou samozřejmostí.</p>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </section>

    <!-- ========== O plemeni ========== -->
    <section class="section section-alt" id="plemeno">
      <div class="container">
        <div class="section-head reveal">
          <span class="eyebrow">O plemeni</span>
          <h2>Mainská mývalí kočka</h2>
          <p>Jedno z&nbsp;největších plemen domácích koček na světě. Pochází ze severovýchodu USA, ze státu Maine, kde se vyvinulo v&nbsp;drsném klimatu – a dodnes si zachovalo divoký vzhled a neuvěřitelně jemnou duši.</p>
        </div>

        <div class="feature-grid">
          <article class="feature-card reveal">
            <div class="icon" aria-hidden="true">🦁</div>
            <h3>Majestátní vzhled</h3>
            <p>Hustá polodlouhá srst, lví hříva kolem krku, štětičky na uších a košatý ocas, kterým se v&nbsp;zimě přikrývá jako šálou.</p>
          </article>
          <article class="feature-card reveal">
            <div class="icon" aria-hidden="true">💛</div>
            <h3>Něžný obr</h3>
            <p>Přes svou velikost je mimořádně klidná, přátelská a trpělivá. Vychází skvěle s&nbsp;dětmi, psy i&nbsp;dalšími kočkami.</p>
          </article>
          <article class="feature-card reveal">
            <div class="icon" aria-hidden="true">🎶</div>
            <h3>Cvrlikání místo mňoukání</h3>
            <p>Typický je pro ni tichý, trylkovitý „cvrlikavý“ hlas. S&nbsp;lidmi ráda „konverzuje“ a doprovází je po domě.</p>
          </article>
          <article class="feature-card reveal">
            <div class="icon" aria-hidden="true">💧</div>
            <h3>Milovnice vody</h3>
            <p>Na rozdíl od většiny koček ji voda fascinuje. Ráda si hraje s&nbsp;kapající vodou a namáčí tlapky do misky.</p>
          </article>
        </div>

        <div class="breed-facts reveal">
          <div><strong>5–11 kg</strong><span>hmotnost dospělého kocoura</span></div>
          <div><strong>až 100 cm</strong><span>délka včetně ocasu</span></div>
          <div><strong>3–5 let</strong><span>trvá, než plně doroste</span></div>
          <div><strong>12–15 let</strong><span>průměrná délka života</span></div>
        </div>
      </div>
    </section>

    <!-- ========== Ochutnávka menu ========== -->
    <?php if ($menuPreview): ?>
    <section class="section" id="menu-preview">
      <div class="container">
        <div class="section-head reveal">
          <span class="eyebrow">Z naší nabídky</span>
          <h2>Káva a dezerty, které stojí za návštěvu</h2>
        </div>
        <div class="menu-grid reveal">
          <?php foreach ($menuPreview as $item): ?>
            <div class="menu-item">
              <div class="menu-item-head">
                <h3><?= e($item['name']) ?></h3>
                <span class="menu-dots" aria-hidden="true"></span>
                <span class="menu-price"><?= e(format_price((int) $item['price'])) ?></span>
              </div>
              <p><?= e($item['description']) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
        <p style="text-align:center;margin-top:2rem">
          <a href="<?= e(url('menu.php')) ?>" class="btn btn-ghost">Celé menu →</a>
        </p>
      </div>
    </section>
    <?php endif; ?>

    <!-- ========== Fotogalerie ========== -->
    <section class="section section-alt" id="galerie">
      <div class="container">
        <div class="section-head reveal">
          <span class="eyebrow">Galerie</span>
          <h2>Atmosféra Purrfect Maine</h2>
          <p>Teplé světlo, vůně čerstvé kávy a tiché předení. Klikněte na fotku pro zvětšení.</p>
        </div>

        <div class="gallery reveal">
          <button type="button" class="gallery-item wide tall">
            <img src="https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?auto=format&fit=crop&w=900&q=75" data-full="https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?auto=format&fit=crop&w=1600&q=80" alt="Interiér kavárny s dřevěnými stoly" loading="lazy">
            <span class="caption">Hlavní sál s výhledem do zahrady</span>
          </button>
          <button type="button" class="gallery-item">
            <img src="<?= e(url('images/gallery/strom.jpg')) ?>" alt="Stříbrně mourovatý mainský mývalí kocour na kočičím stromě" loading="lazy">
            <span class="caption">Kočičí stromy až ke stropu</span>
          </button>
          <button type="button" class="gallery-item">
            <img src="https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=600&q=75" data-full="https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=1600&q=80" alt="Šálek cappuccina s latte art" loading="lazy">
            <span class="caption">Cappuccino z&nbsp;výběrové kávy</span>
          </button>
          <button type="button" class="gallery-item">
            <img src="<?= e(url('images/gallery/stetky.jpg')) ?>" alt="Detail hlavy mainské mývalí kočky se štětičkami na uších" loading="lazy">
            <span class="caption">Rysí štětičky na uších</span>
          </button>
          <button type="button" class="gallery-item">
            <img src="https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=600&q=75" data-full="https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=1600&q=80" alt="Šálek kávy na dřevěném stole" loading="lazy">
            <span class="caption">Ranní filtr u&nbsp;okna</span>
          </button>
          <button type="button" class="gallery-item wide">
            <img src="<?= e(url('images/gallery/hriva.jpg')) ?>" alt="Mainská mývalí kočka s hustou hřívou kolem krku na zahradě" loading="lazy">
            <span class="caption">Lví hříva, která v zimě hřeje</span>
          </button>
          <button type="button" class="gallery-item">
            <img src="<?= e(url('images/gallery/ocas.jpg')) ?>" alt="Mainská mývalí kočka odpočívá s dlouhým huňatým ocasem" loading="lazy">
            <span class="caption">Odpolední siesta s ocasem jako šála</span>
          </button>
          <button type="button" class="gallery-item">
            <img src="<?= e(url('images/gallery/velikost.jpg')) ?>" alt="Dospělý zrzavý mainský mývalí kocour v náručí – ukázka velikosti" loading="lazy">
            <span class="caption">Dospělý kocour měří i přes metr</span>
          </button>
        </div>
      </div>
    </section>

    <!-- Lightbox pro galerii -->
    <div class="lightbox" role="dialog" aria-modal="true" aria-label="Zvětšená fotografie">
      <button type="button" class="lightbox-close" aria-label="Zavřít">&times;</button>
      <div class="lightbox-inner">
        <img src="" alt="">
        <p></p>
      </div>
    </div>

    <!-- ========== Kontakty ========== -->
    <section class="section" id="kontakt">
      <div class="container">
        <div class="section-head reveal">
          <span class="eyebrow">Kontakt</span>
          <h2>Zastavte se u nás</h2>
          <p>Doporučujeme rezervaci předem – kvůli pohodě koček máme kapacitu omezenou na <?= CAFE_CAPACITY ?> hostů.</p>
        </div>

        <div class="contact-grid">
          <div class="contact-card reveal">
            <h3>Kde nás najdete</h3>
            <ul class="contact-list">
              <li>
                <span class="icon" aria-hidden="true">📍</span>
                <div><strong>Adresa</strong>Mývalí 12, 120 00 Praha 2 – Vinohrady</div>
              </li>
              <li>
                <span class="icon" aria-hidden="true">📞</span>
                <div><strong>Telefon</strong><a href="tel:+420777123456">+420 777 123 456</a></div>
              </li>
              <li>
                <span class="icon" aria-hidden="true">✉️</span>
                <div><strong>E-mail</strong><a href="mailto:ahoj@purrfectmaine.cz">ahoj@purrfectmaine.cz</a></div>
              </li>
              <li>
                <span class="icon" aria-hidden="true">🚋</span>
                <div><strong>Doprava</strong>Tramvaj 10, 16 – zastávka Šumavská (3 min pěšky)</div>
              </li>
            </ul>
            <div class="map-placeholder" aria-hidden="true">🗺️ Mývalí 12, Praha 2</div>
          </div>

          <div class="contact-card reveal">
            <h3>Otevírací doba</h3>
            <table class="hours">
              <tbody>
                <tr data-days="1,2,3,4"><td>Pondělí – Čtvrtek</td><td><?= OPENING_HOURS[1][0] ?>:00 – <?= OPENING_HOURS[1][1] ?>:00</td></tr>
                <tr data-days="5"><td>Pátek</td><td><?= OPENING_HOURS[5][0] ?>:00 – <?= OPENING_HOURS[5][1] ?>:00</td></tr>
                <tr data-days="6"><td>Sobota</td><td><?= OPENING_HOURS[6][0] ?>:00 – <?= OPENING_HOURS[6][1] ?>:00</td></tr>
                <tr data-days="0"><td>Neděle</td><td><?= OPENING_HOURS[0][0] ?>:00 – <?= OPENING_HOURS[0][1] ?>:00</td></tr>
              </tbody>
            </table>
            <h3 style="margin-top:2rem">Pravidla návštěvy</h3>
            <ul class="contact-list">
              <li><span class="icon" aria-hidden="true">🤲</span><div>Kočky hladíme jen, když samy přijdou.</div></li>
              <li><span class="icon" aria-hidden="true">📸</span><div>Fotit můžete, ale bez blesku.</div></li>
              <li><span class="icon" aria-hidden="true">🧒</span><div>Děti do 8 let v&nbsp;doprovodu dospělého.</div></li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <!-- ========== CTA ========== -->
    <section class="cta-band">
      <div class="container">
        <h2>Připraveni na chvíli s&nbsp;předením?</h2>
        <p>Zarezervujte si stůl online během minuty. Můžete si dokonce vybrat, se kterou kočkou chcete strávit čas.</p>
        <a href="<?= e(url('booking.php')) ?>" class="btn btn-primary">Vytvořit rezervaci</a>
      </div>
    </section>
  </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
