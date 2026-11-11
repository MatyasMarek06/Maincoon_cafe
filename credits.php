<?php
/** Zdroje fotografií – licence fotek z Wikimedia Commons vyžadují uvedení autora. */
require __DIR__ . '/includes/bootstrap.php';

// [soubor v projektu, název na Commons, autor, licence, odkaz na licenci, úprava]
$photos = [
    ['images/hero.jpg', 'Mco male, Aratan (2952345856).jpg', 'Elma from Reykjavík', 'CC BY 2.0', 'https://creativecommons.org/licenses/by/2.0/', 'zrcadlově otočeno, zmenšeno'],
    ['images/cats/thor.jpg', 'Red and Golden Maine Coon Males.jpg', 'Krh315', 'Public domain', '', 'oříznuto'],
    ['images/cats/freya.jpg', 'Amity 20190320.jpg', 'ThisNickIsTaken', 'CC BY-SA 4.0', 'https://creativecommons.org/licenses/by-sa/4.0/', 'zmenšeno'],
    ['images/cats/oskar.jpg', 'Old Maine Coon.JPG', 'Mueller-rech.muenchen', 'CC BY-SA 3.0', 'https://creativecommons.org/licenses/by-sa/3.0/', 'zmenšeno'],
    ['images/cats/luna.jpg', 'Maine coon ung hane svart smoke.jpg', 'Eepiphany', 'CC0', 'https://creativecommons.org/publicdomain/zero/1.0/', 'zmenšeno'],
    ['images/cats/medvidek.jpg', 'Juvenile Maine Coon.JPG', 'Mueller-rech.muenchen', 'CC BY-SA 3.0', 'https://creativecommons.org/licenses/by-sa/3.0/', 'zmenšeno'],
    ['images/gallery/strom.jpg', 'Black Silver Tabby 7 months old male Maine Coon.jpg', 'YouryDW', 'CC BY-SA 4.0', 'https://creativecommons.org/licenses/by-sa/4.0/', 'zmenšeno'],
    ['images/gallery/stetky.jpg', 'MCO Napoleon XXXL*RU (15023848553).jpg', 'Nickolas Titkov', 'CC BY-SA 2.0', 'https://creativecommons.org/licenses/by-sa/2.0/', 'zmenšeno'],
    ['images/gallery/hriva.jpg', 'MaineCoon-Lucky.JPG', 'Julius Krauss', 'CC BY-SA 3.0', 'https://creativecommons.org/licenses/by-sa/3.0/', 'zmenšeno'],
    ['images/gallery/ocas.jpg', 'Mainecoontaileahh.jpg', 'neuveden', 'Public domain', '', 'zmenšeno'],
    ['images/gallery/velikost.jpg', 'Maine coon red tabby white of 10 kg.jpg', 'Alexandra Thiry', 'CC BY-SA 3.0', 'https://creativecommons.org/licenses/by-sa/3.0/', 'zmenšeno'],
];

$pageTitle = 'Zdroje fotografií – Purrfect Maine';
require __DIR__ . '/includes/header.php';
?>

  <main class="section">
    <div class="container" style="max-width:900px">
      <h1 style="font-size:2rem">Zdroje fotografií</h1>
      <p class="form-note">Fotografie mainských mývalích koček pocházejí z <a href="https://commons.wikimedia.org/wiki/Category:Maine_Coon_cats" target="_blank" rel="noopener">Wikimedia Commons</a>. Ostatní ilustrační fotografie jsou z <a href="https://unsplash.com" target="_blank" rel="noopener">Unsplash</a>.</p>

      <ul class="credits-list">
        <?php foreach ($photos as [$file, $title, $author, $license, $licenseUrl, $change]): ?>
          <li>
            <img src="<?= e(url($file)) ?>" alt="" loading="lazy">
            <div>
              <a href="https://commons.wikimedia.org/wiki/File:<?= e(rawurlencode(str_replace(' ', '_', $title))) ?>" target="_blank" rel="noopener"><?= e($title) ?></a>
              <p>Autor: <?= e($author) ?> · Licence:
                <?php if ($licenseUrl): ?><a href="<?= e($licenseUrl) ?>" target="_blank" rel="noopener"><?= e($license) ?></a><?php else: ?><?= e($license) ?><?php endif; ?>
                · Úprava: <?= e($change) ?></p>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
