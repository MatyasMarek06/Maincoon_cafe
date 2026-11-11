  <!-- ========== Patička ========== -->
  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div>
          <a href="<?= e(url('index.php')) ?>" class="logo">
            <span class="logo-mark" aria-hidden="true">🐾</span>
            <span>Purrfect Maine</span>
          </a>
          <p>Prémiová kočičí kavárna specializovaná výhradně na Mainskou mývalí kočku.</p>
        </div>
        <div>
          <h4>Stránky</h4>
          <ul>
            <li><a href="<?= e(url('index.php')) ?>">Domů</a></li>
            <li><a href="<?= e(url('cats.php')) ?>">Naše kočky</a></li>
            <li><a href="<?= e(url('menu.php')) ?>">Menu</a></li>
            <li><a href="<?= e(url('booking.php')) ?>">Rezervace</a></li>
          </ul>
        </div>
        <div>
          <h4>Kontakt</h4>
          <ul>
            <li>Mývalí 12, Praha 2</li>
            <li><a href="tel:+420777123456">+420 777 123 456</a></li>
            <li><a href="mailto:ahoj@purrfectmaine.cz">ahoj@purrfectmaine.cz</a></li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        © <?= date('Y') ?> Purrfect Maine · Vyrobeno s&nbsp;láskou ke kočkám 🐾 · <a href="<?= e(url('credits.php')) ?>">Zdroje fotografií</a>
      </div>
    </div>
  </footer>

  <script src="<?= e(url('script.js')) ?>"></script>
</body>
</html>
