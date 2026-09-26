<?php
/**
 * TUGAS RUTIN 7 — Kumpulan fungsi helper.
 */

/* ========== 1. PENYIMPANAN JSON (Req #4) ========== */

/** Baca semua user dari data/users.json */
function get_users(): array
{
    if (!file_exists(USERS_FILE)) {
        return [];
    }
    $users = json_decode(file_get_contents(USERS_FILE), true);
    return is_array($users) ? $users : [];
}

/** Simpan array user ke data/users.json */
function save_users(array $users): bool
{
    $json = json_encode(
        $users,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    return file_put_contents(USERS_FILE, $json . PHP_EOL, LOCK_EX) !== false;
}

/** Cari user berdasarkan email — dipakai untuk cek duplikasi (Req #5) & login */
function find_user_by_email(string $email): ?array
{
    foreach (get_users() as $user) {
        if (strcasecmp($user['email'], $email) === 0) {
            return $user;
        }
    }
    return null;
}

/** Cari user berdasarkan ID (dari session) */
function find_user_by_id(string $id): ?array
{
    foreach (get_users() as $user) {
        if ($user['id'] === $id) {
            return $user;
        }
    }
    return null;
}

/** Cari user berdasarkan token Remember Me (yang sudah di-hash) */
function find_user_by_remember_token(string $hashedToken): ?array
{
    foreach (get_users() as $user) {
        if (!empty($user['remember_token'])
            && hash_equals($user['remember_token'], $hashedToken)) {
            return $user;
        }
    }
    return null;
}

/* ========== 2. AUTENTIKASI ========== */

/** Ambil user yang sedang login (null jika belum login) */
function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    return find_user_by_id($_SESSION['user_id']);
}

/** Req #6 — login dengan session: verifikasi password, lalu buat session */
function attempt_login(string $email, string $password): bool
{
    $user = find_user_by_email($email);

    // password_verify() membandingkan password mentah dengan hash bcrypt
    if ($user && password_verify($password, $user['password'])) {

        // Rehash otomatis bila algoritma default PHP berubah
        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            update_user($user['id'], [
                'password' => password_hash($password, PASSWORD_DEFAULT),
            ]);
        }

        $_SESSION['user_id'] = $user['id']; // simpan status login di session
        session_regenerate_id(true);        // cegah session fixation
        return true;
    }
    return false;
}

/** Update data sebagian user berdasarkan ID */
function update_user(string $id, array $data): bool
{
    $users = get_users();
    foreach ($users as $i => $user) {
        if ($user['id'] === $id) {
            $users[$i] = array_merge($user, $data);
            return save_users($users);
        }
    }
    return false;
}

/** Req #8 — logout: hapus cookie Remember Me, cookie session, lalu session_destroy() */
function logout_user(): void
{
    // 1. Hapus token Remember Me dari users.json (jika ada)
    if (!empty($_COOKIE['remember_token'])) {
        $hashed = hash('sha256', $_COOKIE['remember_token']);
        $users  = get_users();
        foreach ($users as $i => $user) {
            if (!empty($user['remember_token']) && $user['remember_token'] === $hashed) {
                unset($users[$i]['remember_token']);
            }
        }
        save_users($users);

        // 2. Hapus cookie remember_token di browser
        setcookie('remember_token', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    // 3. Bersihkan seluruh session lalu hancurkan
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/* ========== 3. FLASH MESSAGE (Req #10 — pesan error & sukses) ========== */

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/* ========== 4. CSRF (tambahan security) ========== */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_verify(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return $token !== ''
        && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/* ========== 5. SANITASI & HELPER ========== */

/**
 * Req #9 — Sanitasi dengan htmlspecialchars().
 * Dipakai setiap kali MENAMPILKAN data user (mencegah XSS).
 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Huruf pertama nama (untuk avatar) */
function inisial(string $name): string
{
    $first = function_exists('mb_substr')
        ? mb_substr(trim($name), 0, 1)
        : substr(trim($name), 0, 1);
    return strtoupper($first);
}

/** Format tanggal Indonesia: "15 September 2026 15:37 WIB" */
function tanggal_indo(?string $tanggal): string
{
    if (!$tanggal) {
        return '-';
    }
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret',     'April',   'Mei',
             'Juni',    'Juli',     'Agustus',   'September', 'Oktober',
             'November', 'Desember',
    ];
    $ts = strtotime($tanggal);
    if ($ts === false) {
        return $tanggal;
    }
    return date('j', $ts) . ' ' . $bulan[(int) date('n', $ts)]
         . ' ' . date('Y', $ts) . ' ' . date('H:i', $ts) . ' WIB';
}