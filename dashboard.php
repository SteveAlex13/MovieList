<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';
require_once 'includes/auth.php';
require_login();

$uid      = (int) $_SESSION['user_id'];
$success  = '';
$error    = '';

if (isset($_SESSION['success'])) { $success = $_SESSION['success']; unset($_SESSION['success']); }
if (isset($_SESSION['error']))   { $error   = $_SESSION['error'];   unset($_SESSION['error']); }

// Fetch current user data
$user = $pdo->prepare("SELECT id, username, email FROM users WHERE id = :id");
$user->execute([':id' => $uid]);
$user = $user->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $newUsername = trim($_POST['username'] ?? '');
        $newEmail    = trim($_POST['email']    ?? '');
        $errors      = [];

        if ($newUsername === '') $errors[] = 'Username wajib diisi.';
        elseif (strlen($newUsername) < 3) $errors[] = 'Username minimal 3 karakter.';
        elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $newUsername)) $errors[] = 'Username hanya boleh huruf, angka, dan underscore.';

        if ($newEmail === '') $errors[] = 'Email wajib diisi.';
        elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';

        if (empty($errors)) {
            // Check unique username (excluding self)
            $chk = $pdo->prepare("SELECT id FROM users WHERE username = :u AND id != :id");
            $chk->execute([':u' => $newUsername, ':id' => $uid]);
            if ($chk->fetch()) $errors[] = 'Username sudah digunakan akun lain.';

            $chkE = $pdo->prepare("SELECT id FROM users WHERE email = :e AND id != :id");
            $chkE->execute([':e' => $newEmail, ':id' => $uid]);
            if ($chkE->fetch()) $errors[] = 'Email sudah digunakan akun lain.';
        }

        if (empty($errors)) {
            $pdo->prepare("UPDATE users SET username = :u, email = :e WHERE id = :id")
                ->execute([':u' => $newUsername, ':e' => $newEmail, ':id' => $uid]);
            $_SESSION['username'] = $newUsername;
            $_SESSION['success']  = 'Profil berhasil diperbarui.';
            header('Location: dashboard.php');
            exit;
        } else {
            $error = implode('<br>', array_map('htmlspecialchars', $errors));
            // Keep new values in form
            $user['username'] = $newUsername;
            $user['email']    = $newEmail;
        }

    } elseif ($action === 'change_password') {
        $oldPw  = $_POST['old_password']      ?? '';
        $newPw  = $_POST['new_password']      ?? '';
        $newPw2 = $_POST['confirm_password']  ?? '';
        $errors = [];

        if ($oldPw === '')  $errors[] = 'Password lama wajib diisi.';
        if ($newPw === '')  $errors[] = 'Password baru wajib diisi.';
        elseif (strlen($newPw) < 6) $errors[] = 'Password baru minimal 6 karakter.';
        elseif ($newPw !== $newPw2) $errors[] = 'Konfirmasi password baru tidak cocok.';

        if (empty($errors)) {
            $chk = $pdo->prepare("SELECT password FROM users WHERE id = :id");
            $chk->execute([':id' => $uid]);
            $row = $chk->fetch();
            if (!password_verify($oldPw, $row['password'])) {
                $errors[] = 'Password lama salah.';
            }
        }

        if (empty($errors)) {
            $hash = password_hash($newPw, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = :p WHERE id = :id")
                ->execute([':p' => $hash, ':id' => $uid]);
            $_SESSION['success'] = 'Password berhasil diubah.';
            header('Location: dashboard.php');
            exit;
        } else {
            $error = implode('<br>', array_map('htmlspecialchars', $errors));
        }
    }
}

// Fetch watchlist with movie details
$wlStmt = $pdo->prepare("
    SELECT m.id, m.title, m.genre, m.year, m.rating, m.poster
    FROM watchlist w
    JOIN movies m ON m.id = w.movie_id
    WHERE w.user_id = :uid
    ORDER BY w.added_at DESC
");
$wlStmt->execute([':uid' => $uid]);
$watchlistMovies = $wlStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — CineList</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎬</text></svg>">
</head>
<body class="app-body">

<div class="dash-layout">
    <!-- Sidebar -->
    <aside class="dash-sidebar">
        <a href="index.php" class="sidebar-brand">
            <span class="brand-icon">🎬</span>
            <span class="brand-text">CineList</span>
        </a>

        <div class="sidebar-user-box">
            <div class="sidebar-avatar">👤</div>
            <div class="sidebar-username"><?= htmlspecialchars($user['username']) ?></div>
            <div class="sidebar-email"><?= htmlspecialchars($user['email']) ?></div>
        </div>

        <nav class="sidebar-nav">
            <a href="index.php" class="sidebar-link">
                <span class="icon">🏠</span> Halaman Utama
            </a>
            <a href="dashboard.php" class="sidebar-link active">
                <span class="icon">⚙️</span> Pengaturan Profil
            </a>
        </nav>

        <!-- Watchlist in sidebar -->
        <div class="sidebar-watchlist">
            <div class="sidebar-wl-label">
                Watchlist Saya
                <span class="wl-count"><?= count($watchlistMovies) ?></span>
            </div>
            <?php if (empty($watchlistMovies)): ?>
                <div class="wl-empty">
                    🤍 Belum ada film di watchlist.<br>
                    <a href="index.php" style="color:var(--accent);font-size:0.75rem;">Tambah sekarang</a>
                </div>
            <?php else: ?>
                <?php foreach ($watchlistMovies as $m): ?>
                <div class="wl-item">
                    <div class="wl-poster">
                        <?php if (!empty($m['poster'])): ?>
                            <img src="<?= htmlspecialchars($m['poster']) ?>" alt="" loading="lazy"
                                onerror="this.style.display='none';this.parentElement.textContent='🎬'">
                        <?php else: ?>
                            🎬
                        <?php endif; ?>
                    </div>
                    <div class="wl-info">
                        <div class="wl-title" title="<?= htmlspecialchars($m['title']) ?>"><?= htmlspecialchars($m['title']) ?></div>
                        <div class="wl-meta">
                            <?= $m['year'] ?? '' ?>
                            <?php if ($m['rating']): ?> · ⭐ <?= number_format($m['rating'], 1) ?><?php endif; ?>
                        </div>
                    </div>
                    <button class="wl-remove" onclick="removeFromWatchlist(<?= $m['id'] ?>, this)" title="Hapus dari watchlist">✕</button>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="sidebar-bottom">
            <a href="logout.php" class="sidebar-link" style="color:var(--red,#ff4757);">
                <span class="icon">🚪</span> Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="dash-content">
        <div class="dash-header">
            <h1>Dashboard <span>Saya</span></h1>
            <p>Kelola profil dan pengaturan akun kamu</p>
        </div>

        <?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-error">❌ <?= $error ?></div><?php endif; ?>

        <!-- Profile Info -->
        <div class="dash-section">
            <h2>👤 Informasi Profil</h2>
            <form method="POST" action="dashboard.php">
                <input type="hidden" name="action" value="update_profile">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="username">Username</label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="form-control"
                            value="<?= htmlspecialchars($user['username']) ?>"
                            required
                            autocomplete="username"
                        >
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            value="<?= htmlspecialchars($user['email']) ?>"
                            required
                            autocomplete="email"
                        >
                    </div>
                </div>
                <button type="submit" class="btn-submit">💾 Simpan Perubahan</button>
            </form>
        </div>

        <!-- Change Password -->
        <div class="dash-section">
            <h2>🔐 Ubah Password</h2>
            <form method="POST" action="dashboard.php">
                <input type="hidden" name="action" value="change_password">
                <div class="form-row single">
                    <div class="form-group">
                        <label class="form-label" for="old_password">Password Lama</label>
                        <input
                            type="password"
                            id="old_password"
                            name="old_password"
                            class="form-control"
                            placeholder="Masukkan password lama"
                            required
                            autocomplete="current-password"
                        >
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="new_password">Password Baru</label>
                        <input
                            type="password"
                            id="new_password"
                            name="new_password"
                            class="form-control"
                            placeholder="Minimal 6 karakter"
                            required
                            autocomplete="new-password"
                        >
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Konfirmasi Password Baru</label>
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            class="form-control"
                            placeholder="Ulangi password baru"
                            required
                            autocomplete="new-password"
                        >
                    </div>
                </div>
                <button type="submit" class="btn-submit">🔑 Ubah Password</button>
            </form>
        </div>

    </main>
</div>

<script>
function removeFromWatchlist(movieId, btn) {
    fetch('watchlist.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=toggle&movie_id=${movieId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok && !data.saved) {
            const item = btn.closest('.wl-item');
            item.style.transition = 'opacity 0.3s';
            item.style.opacity = '0';
            setTimeout(() => {
                item.remove();
                // Update count badge
                const remaining = document.querySelectorAll('.wl-item').length;
                const badge = document.querySelector('.wl-count');
                if (badge) badge.textContent = remaining;
                if (remaining === 0) {
                    document.querySelector('.sidebar-watchlist').innerHTML = `
                        <div class="sidebar-wl-label">Watchlist Saya <span class="wl-count">0</span></div>
                        <div class="wl-empty">🤍 Belum ada film di watchlist.<br>
                        <a href="index.php" style="color:var(--accent);font-size:0.75rem;">Tambah sekarang</a></div>
                    `;
                }
            }, 300);
        }
    })
    .catch(console.error);
}

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
