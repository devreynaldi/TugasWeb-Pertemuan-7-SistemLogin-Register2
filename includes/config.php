<?php
/**
 * TUGAS RUTIN 7 — Sistem Login/Register (PHP Native + JSON)
 * File konfigurasi utama.
 */

// Tampilkan error saat development (matikan di production)
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Setelan cookie session yang lebih aman
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,   // JavaScript tidak bisa mencuri cookie session
    'samesite' => 'Lax',
]);
session_start();

define('APP_NAME', 'Tugas Rutin 7 — Sistem Login/Register');

// Req #4 — lokasi file JSON sebagai "database"
define('DATA_DIR',   __DIR__ . '/../data');
define('USERS_FILE', DATA_DIR . '/users.json');

// Bonus — umur cookie Remember Me (30 hari)
define('REMEMBER_EXPIRY', 60 * 60 * 24 * 30);

date_default_timezone_set('Asia/Jakarta');

// Pastikan folder data/ dan file users.json tersedia
if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0775, true);
}
if (!file_exists(USERS_FILE)) {
    file_put_contents(USERS_FILE, "[]", LOCK_EX);
}