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
$user = $pdo->prepare("SELECT id, username, email, avatar FROM users WHERE id = :id");
$user->execute([':id' => $uid]);
$user = $user->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_avatar') {
        $file = $_FILES['avatar'] ?? null;
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $maxSize = 2 * 1024 * 1024;
            if (!in_array($file['type'], $allowed)) {
                $_SESSION['error'] = 'Format tidak didukung. Gunakan JPG, PNG, WebP, atau GIF.';
            } elseif ($file['size'] > $maxSize) {
                $_SESSION['error'] = 'Ukuran file terlalu besar. Maksimal 2MB.';
            } else {
                $uploadDir = 'assets/uploads/avatars/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $filename = 'avatar_' . $uid . '_' . uniqid() . '.' . $ext;
                $dest     = $uploadDir . $filename;
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $old = $user['avatar'];
                    if ($old && !str_starts_with($old, 'http') && file_exists($old)) @unlink($old);
                    $pdo->prepare("UPDATE users SET avatar = :a WHERE id = :id")
                        ->execute([':a' => $dest, ':id' => $uid]);
                    $_SESSION['success'] = 'Foto profil berhasil diperbarui.';
                } else {
                    $_SESSION['error'] = 'Gagal mengupload foto.';
                }
            }
        } elseif ($file && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            $_SESSION['error'] = 'Terjadi kesalahan saat upload.';
        }
        header('Location: dashboard.php');
        exit;

    } elseif ($action === 'update_profile') {
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

// Watchlist count for sidebar badge
$wlStmt = $pdo->prepare("SELECT COUNT(*) FROM watchlist WHERE user_id = :uid");
$wlStmt->execute([':uid' => $uid]);
$wlCount = (int) $wlStmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — CineList</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
            <div class="sidebar-avatar">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                <?php else: ?>
                    👤
                <?php endif; ?>
            </div>
            <div class="sidebar-username"><?= htmlspecialchars($user['username']) ?></div>
            <div class="sidebar-email"><?= htmlspecialchars($user['email']) ?></div>
        </div>

        <nav class="sidebar-nav">
            <a href="index.php" class="sidebar-link">
                <span class="icon">🏠</span> Halaman Utama
            </a>
            <a href="my_watchlist.php" class="sidebar-link">
                <span class="icon">❤️</span> Watchlist
                <?php if ($wlCount > 0): ?>
                <span class="wl-badge" style="margin-left:auto;"><?= $wlCount ?></span>
                <?php endif; ?>
            </a>
            <a href="dashboard.php" class="sidebar-link active">
                <span class="icon">⚙️</span> Pengaturan Profil
            </a>
        </nav>

        <div class="sidebar-bottom" style="margin-top:auto;">
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

        <!-- Avatar -->
        <div class="dash-section">
            <h2>🖼️ Foto Profil</h2>
            <form method="POST" action="dashboard.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_avatar">
                <div class="avatar-upload-row">
                    <div class="avatar-lg" id="avatarPreview">
                        <?php if (!empty($user['avatar'])): ?>
                            <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar" id="avatarImg">
                        <?php else: ?>
                            <span id="avatarPlaceholder">👤</span>
                        <?php endif; ?>
                    </div>
                    <div class="avatar-upload-info">
                        <label class="form-label" style="margin-bottom:0.5rem;display:block;">Pilih foto baru</label>
                        <input type="file" name="avatar" id="avatarInput" accept="image/jpeg,image/png,image/webp,image/gif" onchange="previewAvatar(this)" class="form-control" style="padding:0.5rem;cursor:pointer;">
                        <p style="font-size:0.78rem;color:var(--text-secondary);margin-top:0.4rem;">JPG, PNG, WebP, GIF · Maks 2MB</p>
                        <button type="submit" class="btn-submit" style="margin-top:0.75rem;">💾 Simpan Foto</button>
                    </div>
                </div>
            </form>
        </div>

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
function previewAvatar(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('avatarPreview').innerHTML =
            `<img src="${e.target.result}" alt="Preview" id="avatarImg">`;
    };
    reader.readAsDataURL(input.files[0]);
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
