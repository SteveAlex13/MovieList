<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';
require_once 'includes/auth.php';
require_login();

$uid = (int) $_SESSION['user_id'];

$user = $pdo->prepare("SELECT id, username, email, avatar FROM users WHERE id = :id");
$user->execute([':id' => $uid]);
$user = $user->fetch();

$wlStmt = $pdo->prepare("
    SELECT m.id, m.title, m.director, m.genre, m.year, m.rating, m.poster, m.synopsis
    FROM watchlist w
    JOIN movies m ON m.id = w.movie_id
    WHERE w.user_id = :uid
    ORDER BY w.added_at DESC
");
$wlStmt->execute([':uid' => $uid]);
$watchlistMovies = $wlStmt->fetchAll();
$wlCount = count($watchlistMovies);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watchlist Saya — CineList</title>
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
            <a href="my_watchlist.php" class="sidebar-link active">
                <span class="icon">❤️</span> Watchlist
                <?php if ($wlCount > 0): ?>
                <span class="wl-badge" style="margin-left:auto;"><?= $wlCount ?></span>
                <?php endif; ?>
            </a>
            <a href="dashboard.php" class="sidebar-link">
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
            <h1>Watchlist <span>Saya</span></h1>
            <p><?= $wlCount ?> film tersimpan di watchlist kamu</p>
        </div>

        <?php if (empty($watchlistMovies)): ?>
        <div class="empty-state" style="grid-column:unset;padding:5rem 2rem;">
            <div class="empty-icon">🤍</div>
            <h3>Watchlist masih kosong</h3>
            <p>Tambahkan film favorit kamu dari halaman utama.</p>
            <a href="index.php" class="btn-primary">🎬 Jelajahi Film</a>
        </div>
        <?php else: ?>
        <div class="movies-grid" id="watchlistGrid">
            <?php foreach ($watchlistMovies as $m): ?>
            <div class="movie-card" id="card-<?= $m['id'] ?>">
                <div class="card-poster">
                    <?php if (!empty($m['poster'])): ?>
                        <img src="<?= htmlspecialchars($m['poster']) ?>" alt="<?= htmlspecialchars($m['title']) ?>" loading="lazy"
                             onerror="this.parentElement.innerHTML='<div class=\'poster-placeholder\'><span>🎬</span></div>'">
                    <?php else: ?>
                        <div class="poster-placeholder"><span>🎬</span></div>
                    <?php endif; ?>
                    <?php if ($m['rating']): ?>
                    <div class="card-rating">⭐ <?= number_format($m['rating'], 1) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($m['synopsis'])): ?>
                    <div class="synopsis-overlay">
                        <div class="synopsis-content">
                            <div class="synopsis-title"><?= htmlspecialchars($m['title']) ?></div>
                            <div class="synopsis-text"><?= htmlspecialchars($m['synopsis']) ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <button class="bookmark-btn saved" onclick="removeFromWatchlist(<?= $m['id'] ?>)" title="Hapus dari watchlist">❤️</button>
                </div>
                <div class="card-body">
                    <?php if (!empty($m['genre'])): ?>
                    <div class="card-genre"><?= htmlspecialchars($m['genre']) ?></div>
                    <?php endif; ?>
                    <div class="card-title"><?= htmlspecialchars($m['title']) ?></div>
                    <div class="card-meta">
                        <?php if (!empty($m['director'])): ?><span>🎬 <?= htmlspecialchars($m['director']) ?></span><?php endif; ?>
                        <?php if (!empty($m['year'])): ?><span>📅 <?= $m['year'] ?></span><?php endif; ?>
                    </div>
                    <div class="card-actions">
                        <button class="btn-delete" onclick="removeFromWatchlist(<?= $m['id'] ?>)">🗑️ Hapus dari Watchlist</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </main>
</div>

<script>
function removeFromWatchlist(movieId) {
    fetch('watchlist.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=toggle&movie_id=${movieId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok && !data.saved) {
            const card = document.getElementById('card-' + movieId);
            if (!card) return;
            card.style.transition = 'opacity 0.3s, transform 0.3s';
            card.style.opacity = '0';
            card.style.transform = 'scale(0.95)';
            setTimeout(() => {
                card.remove();
                const remaining = document.querySelectorAll('.movie-card').length;
                document.querySelector('.dash-header p').textContent = `${remaining} film tersimpan di watchlist kamu`;
                const badge = document.querySelector('.wl-badge');
                if (badge) { badge.textContent = remaining; if (remaining === 0) badge.remove(); }
                if (remaining === 0) {
                    document.getElementById('watchlistGrid').outerHTML = `
                        <div class="empty-state" style="padding:5rem 2rem;">
                            <div class="empty-icon">🤍</div>
                            <h3>Watchlist masih kosong</h3>
                            <p>Tambahkan film favorit kamu dari halaman utama.</p>
                            <a href="index.php" class="btn-primary">🎬 Jelajahi Film</a>
                        </div>`;
                }
            }, 300);
        }
    })
    .catch(console.error);
}
</script>
</body>
</html>
