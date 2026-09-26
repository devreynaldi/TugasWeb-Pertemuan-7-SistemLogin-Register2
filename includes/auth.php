<?php
/**
 * Req #7 — Guard proteksi halaman.
 * Sertakan file ini di awal SETIAP halaman yang wajib login
 * (dashboard.php & profile.php). Jika belum login → redirect ke login.php.
 */

// BONUS Remember Me: coba login otomatis lewat cookie
if (current_user() === null && !empty($_COOKIE['remember_token'])) {
    $hashed = hash('sha256', $_COOKIE['remember_token']);
    if ($user = find_user_by_remember_token($hashed)) {
        $_SESSION['user_id'] = $user['id'];
        session_regenerate_id(true);
    }
}

// Masih belum login? → tendang ke halaman login
if (current_user() === null) {
    set_flash('error', 'Silakan login terlebih dahulu.'); // Req #10
    header('Location: login.php');
    exit;
}