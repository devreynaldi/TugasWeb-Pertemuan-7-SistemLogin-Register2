<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Sudah login? Langsung ke dashboard
if (current_user()) {
    header('Location: dashboard.php');
    exit;
}

 $nama = $email = '';
 $errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify()) {
        $errors['general'] = 'Sesi kedaluwarsa, silakan coba lagi.';
    } else {
        /* ---- Req #9: sanitasi input dengan htmlspecialchars() ---- */
        $nama       = htmlspecialchars(trim($_POST['nama'] ?? ''), ENT_QUOTES, 'UTF-8');
        $email      = strtolower(trim($_POST['email'] ?? ''));
        $password   = $_POST['password'] ?? '';            // TIDAK perlu disanitasi
        $konfirmasi = $_POST['konfirmasi_password'] ?? ''; // karena akan di-hash

        /* ---- Req #1: validasi form registrasi ---- */
        if ($nama === '') {
            $errors['nama'] = 'Nama wajib diisi.';
        } elseif (mb_strlen($nama) < 3) {
            $errors['nama'] = 'Nama minimal 3 karakter.';
        }

        /* ---- Req #2: validasi email dengan filter_var() ---- */
        if ($email === '') {
            $errors['email'] = 'Email wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format email tidak valid.';
        }

        if ($password === '') {
            $errors['password'] = 'Password wajib diisi.';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password minimal 8 karakter.';
        }

        if ($password !== $konfirmasi) {
            $errors['konfirmasi_password'] = 'Konfirmasi password tidak cocok.';
        }

        /* ---- Req #5: cek duplikasi email saat registrasi ---- */
        if (empty($errors) && find_user_by_email($email) !== null) {
            $errors['email'] = 'Email sudah terdaftar. Gunakan email lain.';
        }

        /* ---- Req #3 + #4: hash password & simpan ke file JSON ---- */
        if (empty($errors)) {
            $users   = get_users();
            $users[] = [
                'id'         => bin2hex(random_bytes(16)), // ID unik
                'nama'       => $nama,
                'email'      => $email,
                'password'   => password_hash($password, PASSWORD_DEFAULT), // bcrypt
                'created_at' => date('Y-m-d H:i:s'),
            ];

            if (save_users($users)) {
                set_flash('success', 'Registrasi berhasil! Silakan login.'); // Req #10
                header('Location: login.php');
                exit;
            }
            $errors['general'] = 'Gagal menyimpan data. Periksa izin folder data/.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="card">
        <div class="card-header">
            <div class="logo">📝</div>
            <h1>Buat Akun Baru</h1>
            <p>Isi data di bawah untuk mendaftar</p>
        </div>

        <?php if ($flash = get_flash()): ?>
            <div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert error"><?= e($errors['general']) ?></div>
        <?php endif; ?>

        <!-- Validasi client-side: required, minlength, type=email + JS di bawah -->
        <form method="POST" action="register.php">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="nama">Nama Lengkap</label>
                <input type="text" id="nama" name="nama" value="<?= e($nama) ?>"
                       placeholder="cth: Budi Santoso" required minlength="3">
                <?php if (!empty($errors['nama'])): ?>
                    <span class="field-error"><?= e($errors['nama']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= e($email) ?>"
                       placeholder="cth: budi@email.com" required>
                <?php if (!empty($errors['email'])): ?>
                    <span class="field-error"><?= e($errors['email']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Minimal 8 karakter" required minlength="8">
                <?php if (!empty($errors['password'])): ?>
                    <span class="field-error"><?= e($errors['password']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="konfirmasi_password">Konfirmasi Password</label>
                <input type="password" id="konfirmasi_password" name="konfirmasi_password"
                       placeholder="Ulangi password" required>
                <?php if (!empty($errors['konfirmasi_password'])): ?>
                    <span class="field-error"><?= e($errors['konfirmasi_password']) ?></span>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Daftar</button>
        </form>

        <p class="auth-link">Sudah punya akun? <a href="login.php">Login di sini</a></p>
    </div>
</div>

<script>
// Validasi client-side: pastikan konfirmasi password cocok
document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('form');
    var pass = document.getElementById('password');
    var conf = document.getElementById('konfirmasi_password');
    if (!pass || !conf) return;
    form.addEventListener('submit', function (e) {
        if (pass.value !== conf.value) {
            e.preventDefault();
            conf.setCustomValidity('Konfirmasi password tidak cocok');
            conf.reportValidity();
        } else {
            conf.setCustomValidity('');
        }
    });
});
</script>
</body>
</html>