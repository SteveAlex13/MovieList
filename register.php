<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['user_role'] === 'admin' ? 'admin.php' : 'dashboard.php'));
    exit;
}

$error  = '';
$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['username'] = trim($_POST['username'] ?? '');
    $old['email']    = trim($_POST['email']    ?? '');
    $password        = $_POST['password']         ?? '';
    $password2       = $_POST['password_confirm'] ?? '';

    if ($old['username'] === '')   $errors[] = 'Username wajib diisi.';
    elseif (strlen($old['username']) < 3) $errors[] = 'Username minimal 3 karakter.';
    elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $old['username'])) $errors[] = 'Username hanya boleh huruf, angka, dan underscore.';

    if ($old['email'] === '')      $errors[] = 'Email wajib diisi.';
    elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';

    if ($password === '')          $errors[] = 'Password wajib diisi.';
    elseif (strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';
    elseif ($password !== $password2) $errors[] = 'Konfirmasi password tidak cocok.';

    if (empty($errors)) {
        // Check unique username
        $chk = $pdo->prepare("SELECT id FROM users WHERE username = :u");
        $chk->execute([':u' => $old['username']]);
        if ($chk->fetch()) $errors[] = 'Username sudah digunakan.';

        // Check unique email
        $chkE = $pdo->prepare("SELECT id FROM users WHERE email = :e");
        $chkE->execute([':e' => $old['email']]);
        if ($chkE->fetch()) $errors[] = 'Email sudah terdaftar.';
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ins  = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (:u, :e, :p, 'user')");
        $ins->execute([':u' => $old['username'], ':e' => $old['email'], ':p' => $hash]);

        $userId = $pdo->lastInsertId();
        session_regenerate_id(true);
        $_SESSION['user_id']   = $userId;
        $_SESSION['username']  = $old['username'];
        $_SESSION['user_role'] = 'user';

        $_SESSION['success'] = 'Akun berhasil dibuat! Selamat datang, ' . htmlspecialchars($old['username']) . '!';
        header('Location: dashboard.php');
        exit;
    }

    $error = implode('<br>', array_map('htmlspecialchars', $errors));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar — CineList</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎬</text></svg>">
</head>
<body>

<div class="auth-page">
    <a href="index.php" class="auth-logo">
        <span class="brand-icon">🎬</span>
        <span class="brand-text">CineList</span>
    </a>

    <div class="auth-card">
        <h1 class="auth-title">Buat <span>Akun</span></h1>
        <p class="auth-subtitle">Daftar untuk menyimpan watchlist kamu</p>

        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="form-group" style="margin-bottom:1.25rem;">
                <label class="form-label" for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    class="form-control"
                    placeholder="Minimal 3 karakter"
                    value="<?= htmlspecialchars($old['username'] ?? '') ?>"
                    required
                    autocomplete="username"
                >
            </div>
            <div class="form-group" style="margin-bottom:1.25rem;">
                <label class="form-label" for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    placeholder="contoh@email.com"
                    value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                    required
                    autocomplete="email"
                >
            </div>
            <div class="form-group" style="margin-bottom:1.25rem;">
                <label class="form-label" for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    placeholder="Minimal 6 karakter"
                    required
                    autocomplete="new-password"
                >
            </div>
            <div class="form-group" style="margin-bottom:1.75rem;">
                <label class="form-label" for="password_confirm">Konfirmasi Password</label>
                <input
                    type="password"
                    id="password_confirm"
                    name="password_confirm"
                    class="form-control"
                    placeholder="Ulangi password"
                    required
                    autocomplete="new-password"
                >
            </div>
            <button type="submit" class="btn-submit" style="width:100%;">📝 Daftar Sekarang</button>
        </form>

        <div class="auth-footer">
            Sudah punya akun? <a href="login.php">Login di sini</a>
        </div>
    </div>

    <div class="auth-back">
        <a href="index.php">← Kembali ke halaman utama</a>
    </div>
</div>

<script>
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(el => {
        el.style.transition = 'opacity 0.5s';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 500);
    });
}, 4000);
</script>
</body>
</html>
