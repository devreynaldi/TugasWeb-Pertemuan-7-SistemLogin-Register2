<?php
// logout.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Req #8 — session_destroy() dipanggil di dalam logout_user()
logout_user();

// Mulai session baru hanya untuk menampilkan notifikasi
session_start();
set_flash('success', 'Anda berhasil logout.');
header('Location: login.php');
exit;