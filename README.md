# Purrfect Maine – kočičí kavárna

Webová aplikace v PHP 8 a MySQL: prezentace kavárny, profily koček, menu, uživatelské účty, rezervace a administrace pro majitele.

## Spuštění (XAMPP)

1. V **XAMPP Control Panelu** spusťte **Apache** a **MySQL**.
2. V **phpMyAdmin** (<http://localhost/phpmyadmin>) vytvořte databázi `maincoon_cafe` (porovnávání `utf8mb4_czech_ci`).
3. Klikněte na databázi `maincoon_cafe` → záložka **SQL** → vložte celý obsah `database/maincoon_cafe.sql` → **Proveď**.
4. Zkontrolujte přihlašovací údaje na posledním řádku `conn.php` (výchozí XAMPP: `root` bez hesla).
5. Zpřístupněte projekt Apachi:
   - **buď** složku zkopírujte do `C:\xampp\htdocs\maincoon`,
   - **nebo** ji nechte v `C:\maincoon` a na konec `C:\xampp\apache\conf\httpd.conf` přidejte
     (pak restartujte Apache):

     ```apache
     Alias /maincoon "C:/maincoon"
     <Directory "C:/maincoon">
         AllowOverride All
         Require all granted
     </Directory>
     ```
6. Otevřete <http://localhost/maincoon/>.

**Účet majitele** (vytvoří ho SQL skript): `admin@maincoon.cz` / `admin123`. Administrace je na `/maincoon/admin/` (odkaz „Administrace“ v menu po přihlášení).

Pozor: opakovaný import SQL skriptu smaže a znovu vytvoří všechny tabulky včetně rezervací a účtů.

Otevírací doba, kapacita a ceny se nastavují v `config.php`.

## Struktura

| Soubor / složka | Účel |
|---|---|
| `index.php` | Hlavní stránka (mise, plemeno, ukázka menu, galerie, kontakt) |
| `cats.php` | Profily koček z databáze, filtr podle povahy |
| `menu.php` | Nabídka produktů podle kategorií |
| `booking.php` | Nová rezervace + „Moje rezervace“ (vyžaduje přihlášení) |
| `login.php`, `register.php`, `logout.php` | Uživatelské účty |
| `admin/index.php` | Přehled dne: počet hostů, graf obsazenosti, seznam rezervací, souhrn 7 dní |
| `credits.php` | Zdroje a licence fotografií koček |
| `images/` | Fotky mainských mývalích koček (Wikimedia Commons) |
| `admin/cats.php`, `admin/cat-form.php` | Přidání / úprava / skrytí / smazání koček včetně nahrání fotky |
| `admin/products.php`, `admin/product-form.php` | Správa produktů v menu |
| `conn.php` | Připojení k databázi (třída `connector`, mysqli) – zde jsou přihlašovací údaje |
| `includes/` | Session, pomocné funkce, hlavička a patička |
| `database/maincoon_cafe.sql` | Tabulky, ukázková data a účet majitele – import přes phpMyAdmin |
| `uploads/cats/` | Nahrané fotky koček |

## Databáze

- `users` – zákazníci a majitel (`role` = `customer` / `admin`), hesla hashovaná přes `password_hash`
- `cats` – profily koček (popis, povahové rysy, dny volna, fotka)
- `products` – položky menu
- `reservations` – rezervace navázané na uživatele a volitelně na kočku

## Pravidla rezervací (kontroluje server)

- jen v otevírací době, nejvýše 60 dní dopředu, sloty po 30 minutách
- kapacita kavárny (výchozí 24 hostů současně)
- vybraná kočka nesmí mít v daný den volno a v daném čase jiné hosty
- zákazník nemůže mít dvě překrývající se rezervace

## Bezpečnost

Připravené dotazy (mysqli s parametry), CSRF tokeny u všech formulářů, ošetřený výstup (`htmlspecialchars`), kontrola typu nahrávaných souborů, obnova session ID po přihlášení. Před nasazením na veřejný server nastavte silné heslo k databázi a používejte HTTPS.

Druhý majitel se přidá tak, že se zaregistruje jako zákazník a v databázi (např. v phpMyAdmin) se mu nastaví `role = 'admin'`.
