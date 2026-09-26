<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

if (current_user()) {
    header('Location: dashboard.php');
    exit;
}

 $email  = '';
 $errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify()) {
        $errors['general'] = 'Sesi kedaluwarsa, silakan coba lagi.';
    } else {
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        if ($email === '') {
            $errors['email'] = 'Email wajib diisi.';
        }
        if ($password === '') {
            $errors['password'] = 'Password wajib diisi.';
        }

        /* ---- Req #6: sistem login dengan session ---- */
        if (empty($errors)) {
            if (attempt_login($email, $password)) {

                /* ---- BONUS: "Remember Me" dengan cookies ---- */
                if ($remember) {
                    $rawToken = bin2hex(random_bytes(32)); // dikirim ke browser
                    $hashed   = hash('sha256', $rawToken); // disimpan di JSON

                    update_user($_SESSION['user_id'], ['remember_token' => $hashed]);

                    setcookie('remember_token', $rawToken, [
                        'expires'  => time() + REMEMBER_EXPIRY, // 30 hari
                        'path'     => '/',
                        'httponly' => true,
                        'samesite' => 'Lax',
                    ]);
                }

                set_flash('success', 'Login berhasil. Selamat datang, '
                    . current_user()['nama'] . '!');
                header('Location: dashboard.php');
                exit;
            }
            // Req #10 — pesan error yang jelas (generik, tidak membocorkan bagian mana yang salah)
            $errors['general'] = 'Email atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="card">
        <div class="card-header">
            <div class="logo">🔐</div>
            <h1>Selamat Datang</h1>
            <p>Masuk untuk mengakses dashboard</p>
        </div>

        <?php if ($flash = get_flash()): ?>
            <div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert error"><?= e($errors['general']) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                       value="<?= e($email) ?>" placeholder="email@domain.com" required>
                <?php if (!empty($errors['email'])): ?>
                    <span class="field-error"><?= e($errors['email']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Password Anda" required>
                <?php if (!empty($errors['password'])): ?>
                    <span class="field-error"><?= e($errors['password']) ?></span>
                <?php endif; ?>
            </div>

            <label class="checkbox-group">
                <input type="checkbox" name="remember" value="1">
                <span>Remember me (30 hari)</span>
            </label>

            <button type="submit" class="btn btn-primary btn-block">Masuk</button>
        </form>

        <p class="auth-link">Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
    </div>
</div>
</body>
</html>