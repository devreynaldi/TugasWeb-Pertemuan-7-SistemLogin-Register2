<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php'; // halaman ini juga terproteksi

 $user   = current_user();
 $errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify()) {
        $errors['general'] = 'Sesi kedaluwarsa, silakan coba lagi.';
    } else {
        // Req #9: sanitasi input dengan htmlspecialchars()
        $nama      = htmlspecialchars(trim($_POST['nama'] ?? ''), ENT_QUOTES, 'UTF-8');
        $email     = strtolower(trim($_POST['email'] ?? ''));
        $passLama  = $_POST['password_lama'] ?? '';
        $passBaru  = $_POST['password_baru'] ?? '';
        $passBaru2 = $_POST['konfirmasi_password_baru'] ?? '';

        // Validasi nama
        if ($nama === '' || mb_strlen($nama) < 3) {
            $errors['nama'] = 'Nama wajib diisi (minimal 3 karakter).';
        }

        // Req #2: validasi email dengan filter_var()
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format email tidak valid.';
        }

        // Req #5: cek duplikasi email (kecuali milik sendiri)
        if (empty($errors['email'])) {
            $lain = find_user_by_email($email);
            if ($lain && $lain['id'] !== $user['id']) {
                $errors['email'] = 'Email sudah dipakai akun lain.';
            }
        }

        // Ganti password (opsional — kosongkan jika tidak ingin ganti)
        $gantiPassword = ($passBaru !== '' || $passBaru2 !== '');
        if ($gantiPassword) {
            if ($passLama === '') {
                $errors['password_lama'] = 'Isi password lama untuk mengganti password.';
            } elseif (!password_verify($passLama, $user['password'])) {
                $errors['password_lama'] = 'Password lama salah.';
            }
            if (strlen($passBaru) < 8) {
                $errors['password_baru'] = 'Password baru minimal 8 karakter.';
            }
            if ($passBaru !== $passBaru2) {
                $errors['konfirmasi_password_baru'] = 'Konfirmasi password baru tidak cocok.';
            }
        }

        // Simpan ke JSON
        if (empty($errors)) {
            $data = ['nama' => $nama, 'email' => $email];
            if ($gantiPassword) {
                $data['password'] = password_hash($passBaru, PASSWORD_DEFAULT); // Req #3
            }
            update_user($user['id'], $data);

            set_flash('success', 'Profil berhasil diperbarui.');
            header('Location: profile.php');
            exit;
        }
    }
}

 $user = current_user(); // ambil data terbaru setelah update
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil — <?= APP_NAME ?></title>
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
        <a class="nav-link" href="dashboard.php">Dashboard</a>
        <form method="POST" action="logout.php" class="inline-form">
            <button type="submit" class="nav-link logout">Logout</button>
        </form>
    </div>
</nav>

<main class="container">
    <?php if ($flash = get_flash()): ?>
        <div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <section class="card">
        <div class="card-header">
            <div class="logo">👤</div>
            <h1>Edit Profil</h1>
            <p>Perbarui data akun Anda (fitur bonus)</p>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert error"><?= e($errors['general']) ?></div>
        <?php endif; ?>

        <form method="POST" action="profile.php">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="nama">Nama Lengkap</label>
                <input type="text" id="nama" name="nama" value="<?= e($user['nama']) ?>"
                       required minlength="3">
                <?php if (!empty($errors['nama'])): ?>
                    <span class="field-error"><?= e($errors['nama']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= e($user['email']) ?>" required>
                <?php if (!empty($errors['email'])): ?>
                    <span class="field-error"><?= e($errors['email']) ?></span>
                <?php endif; ?>
            </div>

            <hr class="divider">
            <p class="text-muted" style="font-size:.85rem;margin-bottom:14px;">
                Kosongkan bagian berikut jika tidak ingin mengganti password.
            </p>

            <div class="form-group">
                <label for="password_lama">Password Lama</label>
                <input type="password" id="password_lama" name="password_lama">
                <?php if (!empty($errors['password_lama'])): ?>
                    <span class="field-error"><?= e($errors['password_lama']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password_baru">Password Baru</label>
                <input type="password" id="password_baru" name="password_baru" minlength="8">
                <?php if (!empty($errors['password_baru'])): ?>
                    <span class="field-error"><?= e($errors['password_baru']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="konfirmasi_password_baru">Konfirmasi Password Baru</label>
                <input type="password" id="konfirmasi_password_baru" name="konfirmasi_password_baru">
                <?php if (!empty($errors['konfirmasi_password_baru'])): ?>
                    <span class="field-error"><?= e($errors['konfirmasi_password_baru']) ?></span>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Simpan Perubahan</button>
        </form>
    </section>
</main>

</body>
</html>