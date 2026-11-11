<?php
/** Odhlášení (pouze POST s CSRF tokenem). */
require __DIR__ . '/includes/bootstrap.php';

if (!is_post()) {
    redirect('index.php');
}
verify_csrf();

$_SESSION = [];
session_regenerate_id(true);
flash('success', 'Byli jste odhlášeni. Těšíme se na další návštěvu!');
redirect('index.php');
