<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';

// admin bisa login dan masuk ke dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['user_role'] === 'admin' ? 'admin.php' : 'dashboard.php'));
    exit;
}

$error   = '';
$success = '';

if (isset($_SESSION['success'])) { $success = $_SESSION['success']; unset($_SESSION['success']); }
if (isset($_SESSION['error']))   { $error   = $_SESSION['error'];   unset($_SESSION['error']); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = :u OR email = :u");
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['user_role'] = $user['role'];

            header('Location: ' . ($user['role'] === 'admin' ? 'admin.php' : 'dashboard.php'));
            exit;
        } else {
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — CineList</title>
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
        <h1 class="auth-title">Selamat <span>Datang</span></h1>
        <p class="auth-subtitle">Login untuk melanjutkan ke CineList</p>

        <?php if ($error):   ?><div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group" style="margin-bottom:1.25rem;">
                <label class="form-label" for="username">Username / Email</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    class="form-control"
                    placeholder="Masukkan username atau email"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    required
                    autocomplete="username"
                >
            </div>
            <div class="form-group" style="margin-bottom:1.75rem;">
                <label class="form-label" for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    placeholder="Masukkan password"
                    required
                    autocomplete="current-password"
                >
            </div>
            <button type="submit" class="btn-submit" style="width:100%;">🔐 Login</button>
        </form>

        <div class="auth-footer">
            Belum punya akun? <a href="register.php">Daftar sekarang</a>
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
