<?php
/**
 * Purrfect Maine – konfigurace
 * Přihlašovací údaje k databázi jsou v souboru conn.php.
 */

// Provoz kavárny
const CAFE_CAPACITY = 24;           // max. hostů současně
const PRICE_PER_PERSON_HOUR = 150;  // Kč
const CAT_SESSION_FEE = 50;         // Kč – příplatek za vyhrazený čas s vybranou kočkou
const MAX_PEOPLE = 8;               // max. osob na jednu rezervaci
const MAX_DAYS_AHEAD = 60;          // jak daleko dopředu lze rezervovat

// Otevírací doba: index = den v týdnu (0 = neděle) => [otevření, zavření] v hodinách
const OPENING_HOURS = [
    0 => [9, 21],
    1 => [10, 20],
    2 => [10, 20],
    3 => [10, 20],
    4 => [10, 20],
    5 => [10, 22],
    6 => [9, 22],
];

// Nahrávání fotek
const UPLOAD_DIR = __DIR__ . '/uploads/cats';
const UPLOAD_MAX_BYTES = 5 * 1024 * 1024; // 5 MB

// Časové pásmo
date_default_timezone_set('Europe/Prague');
