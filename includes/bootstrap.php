<?php
/**
 * Společný start každé stránky:
 * konfigurace, session, připojení k databázi a pomocné funkce.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

/* ---------- Session ---------- */
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_name('PURRFECTSESS');
session_start();

/* ---------- Základní URL aplikace ----------
 * Odvozuje se z adresy právě spuštěného skriptu, takže web funguje
 * v htdocs/maincoon, přes Alias v Apachi i v kořeni serveru. */
(function () {
    $appRoot = rtrim(str_replace('\\', '/', realpath(__DIR__ . '/..')), '/');
    $scriptDir = rtrim(str_replace('\\', '/', realpath(dirname($_SERVER['SCRIPT_FILENAME']))), '/');
    $subPath = stripos($scriptDir, $appRoot) === 0 ? substr($scriptDir, strlen($appRoot)) : ''; // např. "/admin"
    $urlDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    define('BASE_URL', $subPath !== '' ? substr($urlDir, 0, -strlen($subPath)) : $urlDir);
})();

/* ---------- Databáze ----------
 * Připojení zajišťuje třída connector v conn.php (proměnná $db). */
require_once __DIR__ . '/../conn.php';

/** Sdílené připojení k databázi. */
function db(): connector
{
    global $db;
    return $db;
}

/* ---------- Výstup a URL ---------- */

/** Ošetření textu pro HTML výstup. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Absolutní URL v rámci aplikace, např. url('admin/cats.php'). */
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/** URL fotky – podporuje externí adresy i nahrané soubory. */
function photo_url(?string $photo): string
{
    if (!$photo) {
        return '';
    }
    return preg_match('~^https?://~i', $photo) ? $photo : url($photo);
}

/** Přesměrování. Relativní cesta ("booking.php") se doplní o BASE_URL, absolutní ("/…", "http…") se použije beze změny. */
function redirect(string $path): never
{
    $absolute = str_starts_with($path, '/') || preg_match('~^https?://~', $path);
    header('Location: ' . ($absolute ? $path : url($path)));
    exit;
}

/* ---------- Flash zprávy (přežijí jedno přesměrování) ---------- */

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/* ---------- CSRF ochrana formulářů ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Ukončí požadavek, pokud POST neobsahuje platný CSRF token. */
function verify_csrf(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(400);
        exit('Neplatný bezpečnostní token formuláře. Vraťte se zpět a odešlete formulář znovu.');
    }
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/* ---------- Uživatelé a oprávnění ---------- */

/** Přihlášený uživatel (nebo null). Načítá se z DB, aby se projevila změna role či smazání. */
function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['user_id'])) {
            $user = db()->fetchOne('SELECT id, name, email, role FROM users WHERE id = ?', [$_SESSION['user_id']]);
            if (!$user) {
                unset($_SESSION['user_id']);
            }
        }
    }
    return $user;
}

function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

function login_user(int $userId): void
{
    session_regenerate_id(true); // ochrana proti session fixation
    $_SESSION['user_id'] = $userId;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash('info', 'Pro pokračování se prosím přihlaste.');
        redirect('login.php?next=' . urlencode($_SERVER['REQUEST_URI']));
    }
    return $user;
}

function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        exit('Přístup odepřen – tato stránka je pouze pro majitele kavárny.');
    }
    return $user;
}

/** Bezpečná návratová adresa po přihlášení (jen v rámci webu). */
function safe_next(?string $next): string
{
    if ($next && str_starts_with($next, '/') && !str_starts_with($next, '//')) {
        return $next;
    }
    return url('index.php');
}

/* ---------- Nahrávání fotek ---------- */

/**
 * Uloží nahranou fotku do uploads/cats a vrátí relativní cestu.
 * Ověřuje skutečný typ souboru (ne jen příponu) a generuje náhodný název.
 * @throws RuntimeException se srozumitelnou chybou pro uživatele
 */
function save_uploaded_photo(array $file): string
{
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        throw new RuntimeException('Fotka je příliš velká.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Fotku se nepodařilo nahrát.');
    }
    if ($file['size'] > UPLOAD_MAX_BYTES) {
        throw new RuntimeException('Fotka může mít nejvýše ' . (UPLOAD_MAX_BYTES / 1024 / 1024) . ' MB.');
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($allowed[$mime]) || @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('Povolené formáty fotek jsou JPG, PNG, WEBP a GIF.');
    }

    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0775, true)) {
        throw new RuntimeException('Nelze vytvořit složku pro fotky.');
    }

    $name = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . '/' . $name)) {
        throw new RuntimeException('Fotku se nepodařilo uložit.');
    }
    return 'uploads/cats/' . $name;
}

/** Smaže dříve nahranou fotku (externí URL ignoruje). */
function delete_uploaded_photo(?string $photo): void
{
    if ($photo && str_starts_with($photo, 'uploads/cats/')) {
        $path = UPLOAD_DIR . '/' . basename($photo);
        if (is_file($path)) {
            unlink($path);
        }
    }
}

/* ---------- Formátování ---------- */

const DAY_NAMES = ['neděle', 'pondělí', 'úterý', 'středa', 'čtvrtek', 'pátek', 'sobota'];
const DAY_SHORT = ['Ne', 'Po', 'Út', 'St', 'Čt', 'Pá', 'So'];
const MONTHS_SHORT = ['led', 'úno', 'bře', 'dub', 'kvě', 'čvn', 'čvc', 'srp', 'zář', 'říj', 'lis', 'pro'];

// Povahové štítky koček používané ve filtrech
const CAT_TAGS = [
    'mazlivy' => '💛 Mazliví',
    'hravy'   => '🧶 Hraví',
    'klidny'  => '😌 Klidní',
    'deti'    => '🧒 Vhodní pro děti',
];

function format_price(int|float $amount): string
{
    return number_format((float) $amount, 0, ',', ' ') . ' Kč';
}

function format_date_long(string $date): string
{
    $ts = strtotime($date);
    return DAY_NAMES[(int) date('w', $ts)] . ' ' . date('j. n. Y', $ts);
}

/** Věk kočky česky: 1 rok, 2 roky, 5 let, 1,5 roku. */
function format_age(float $years): string
{
    if (floor($years) != $years) {
        return str_replace('.', ',', rtrim(rtrim(number_format($years, 1, '.', ''), '0'), '.')) . ' roku';
    }
    $n = (int) $years;
    if ($n === 1) return '1 rok';
    if ($n >= 2 && $n <= 4) return "$n roky";
    return "$n let";
}

function format_weight(float $kg): string
{
    return str_replace('.', ',', rtrim(rtrim(number_format($kg, 1, '.', ''), '0'), '.')) . ' kg';
}

function people_label(int $n): string
{
    if ($n === 1) return '1 osoba';
    if ($n >= 2 && $n <= 4) return "$n osoby";
    return "$n osob";
}

/** "1,3" => [1, 3] */
function parse_days(?string $csv): array
{
    if ($csv === null || $csv === '') {
        return [];
    }
    return array_map('intval', explode(',', $csv));
}

/** [2, 4] => "úterý a čtvrtek" */
function days_label(array $days): string
{
    $names = array_map(fn ($d) => DAY_NAMES[$d] ?? '', $days);
    if (count($names) <= 1) {
        return $names[0] ?? '';
    }
    $last = array_pop($names);
    return implode(', ', $names) . ' a ' . $last;
}

/** Konec návštěvy jako "HH:MM". */
function end_time(string $time, int $hours): string
{
    return date('H:i', strtotime($time) + $hours * 3600);
}
