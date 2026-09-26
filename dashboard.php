<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php'; // Req #7: proteksi halaman (redirect jika belum login)

 $user = current_user();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav class="navbar">
    <a class="brand" href="dashboard.php">🔐 Tugas Rutin 7</a>
    <div class="nav-right">
        <span class="nav-user">
            <span class="avatar-sm"><?= e(inisial($user['nama'])) ?></span>
            <span><?= e($user['nama']) ?></span>
        </span>
        <a class="nav-link" href="profile.php">Edit Profil</a>
        <form method="POST" action="logout.php" class="inline-form">
            <button type="submit" class="nav-link logout">Logout</button>
        </form>
    </div>
</nav>

<main class="container">
    <?php if ($flash = get_flash()): ?>
        <div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <section class="card dashboard-hero">
        <div class="avatar"><?= e(inisial($user['nama'])) ?></div>
        <div>
            <h1>Halo, <?= e($user['nama']) ?> 👋</h1>
            <p class="muted">Selamat datang di dashboard Anda. Halaman ini terproteksi —
               hanya bisa diakses setelah login.</p>
        </div>
    </section>

    <section class="card">
        <h2 class="card-title">Detail Akun</h2>
        <table class="data-table">
            <tr><th>Nama Lengkap</th><td><?= e($user['nama']) ?></td></tr>
            <tr><th>Email</th><td><?= e($user['email']) ?></td></tr>
            <tr><th>Terdaftar Sejak</th><td><?= e(tanggal_indo($user['created_at'])) ?></td></tr>
            <tr><th>ID User (di users.json)</th><td><code><?= e($user['id']) ?></code></td></tr>
        </table>
    </section>

    <section class="card">
        <h2 class="card-title">Cara Kerja Sistem Ini</h2>
        <ul class="info-list">
            <li>Data user disimpan di file <code>data/users.json</code></li>
            <li>Password disimpan sebagai hash bcrypt dari <code>password_hash()</code></li>
            <li>Status login disimpan dalam <code>$_SESSION</code></li>
            <li>Halaman ini dilindungi <code>includes/auth.php</code></li>
            <li>Semua data disanitasi dengan <code>htmlspecialchars()</code></li>
        </ul>
    </section>
</main>

</body>
</html>