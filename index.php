<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';
require_once 'includes/auth.php';

$search = trim($_GET['search'] ?? '');
$genre  = trim($_GET['genre']  ?? '');
$sort   = $_GET['sort'] ?? 'created_at_desc';
$watchlistOnly = isset($_GET['watchlist']);

$where  = [];
$params = [];

if ($search !== '') {
    $where[]  = '(title LIKE :search OR director LIKE :search2)';
    $params[':search']  = "%$search%";
    $params[':search2'] = "%$search%";
}
if ($genre !== '') {
    $where[]  = 'genre = :genre';
    $params[':genre'] = $genre;
}

$orderMap = [
    'created_at_desc' => 'created_at DESC',
    'created_at_asc'  => 'created_at ASC',
    'rating_desc'     => 'rating DESC',
    'rating_asc'      => 'rating ASC',
    'title_asc'       => 'title ASC',
    'year_desc'       => 'year DESC',
];
$orderSQL = $orderMap[$sort] ?? 'created_at DESC';

// Watchlist filter — requires login
$watchlistIds = [];
if (is_logged_in()) {
    try {
        $wRows = $pdo->prepare("SELECT movie_id FROM watchlist WHERE user_id = :u");
        $wRows->execute([':u' => (int) $_SESSION['user_id']]);
        $watchlistIds = $wRows->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {}
} elseif ($watchlistOnly) {
    // Not logged in but trying to view watchlist → redirect to login
    $_SESSION['error'] = 'Login dulu untuk melihat watchlist kamu.';
    header('Location: login.php');
    exit;
}

if ($watchlistOnly && !empty($watchlistIds)) {
    $placeholders = implode(',', array_fill(0, count($watchlistIds), '?'));
    $where[] = "id IN ($placeholders)";
    // Will be merged in params below
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$stmt = $pdo->prepare("SELECT * FROM movies $whereSQL ORDER BY $orderSQL");

// Merge watchlist IDs into params if filtering
if ($watchlistOnly && !empty($watchlistIds)) {
    $allParams = array_values($params);
    foreach ($watchlistIds as $wid) $allParams[] = $wid;
    $stmt->execute($allParams);
} elseif ($watchlistOnly && empty($watchlistIds)) {
    $movies = [];
    goto skip_query;
} else {
    $stmt->execute($params);
}
$movies = $stmt->fetchAll();
skip_query:

$genres      = $pdo->query("SELECT DISTINCT genre FROM movies WHERE genre IS NOT NULL AND genre != '' ORDER BY genre")->fetchAll(PDO::FETCH_COLUMN);
$totalMovies = $pdo->query("SELECT COUNT(*) FROM movies")->fetchColumn();

$success = '';
$error   = '';
if (isset($_SESSION['success'])) { $success = $_SESSION['success']; unset($_SESSION['success']); }
if (isset($_SESSION['error']))   { $error   = $_SESSION['error'];   unset($_SESSION['error']); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineList — Daftar Film</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎬</text></svg>">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main>
    <div class="page-header">
        <div>
            <h1 class="page-title">Daftar <span>Film</span></h1>
            <p class="page-subtitle">Koleksi film pilihan yang kamu kurasikan</p>
        </div>
        <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
            <span class="movie-count">🎞️ <?= $totalMovies ?> Film</span>
            <a href="index.php<?= $watchlistOnly ? '' : '?watchlist' ?>" class="watchlist-toggle-btn <?= $watchlistOnly ? 'active' : '' ?>">
                <?= $watchlistOnly ? '❤️ Watchlist' : '🤍 Watchlist' ?>
                <?php if (!empty($watchlistIds)): ?>
                    <span class="wl-badge"><?= count($watchlistIds) ?></span>
                <?php endif; ?>
            </a>
        </div>
    </div>

    <?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- Search & Filter -->
    <form method="GET" action="index.php" class="search-bar">
        <?php if ($watchlistOnly): ?><input type="hidden" name="watchlist" value="1"><?php endif; ?>
        <div class="search-input-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.099zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
            <input type="text" name="search" class="search-input" placeholder="Cari judul atau sutradara..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <select name="genre" class="filter-select">
            <option value="">Semua Genre</option>
            <?php foreach ($genres as $g): ?>
                <option value="<?= htmlspecialchars($g) ?>" <?= $genre === $g ? 'selected' : '' ?>><?= htmlspecialchars($g) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="sort" class="filter-select">
            <option value="created_at_desc" <?= $sort==='created_at_desc'?'selected':'' ?>>Terbaru</option>
            <option value="rating_desc"     <?= $sort==='rating_desc'    ?'selected':'' ?>>Rating Tertinggi</option>
            <option value="rating_asc"      <?= $sort==='rating_asc'     ?'selected':'' ?>>Rating Terendah</option>
            <option value="title_asc"       <?= $sort==='title_asc'      ?'selected':'' ?>>Judul A-Z</option>
            <option value="year_desc"       <?= $sort==='year_desc'       ?'selected':'' ?>>Tahun Terbaru</option>
        </select>
        <button type="submit" class="btn-search">🔍 Cari</button>
        <?php if ($search || $genre || $sort !== 'created_at_desc'): ?>
            <a href="index.php<?= $watchlistOnly ? '?watchlist' : '' ?>" class="btn-reset">✕ Reset</a>
        <?php endif; ?>
    </form>

    <!-- Movie Grid -->
    <div class="movies-grid">
        <?php if (empty($movies)): ?>
            <div class="empty-state">
                <div class="empty-icon"><?= $watchlistOnly ? '🤍' : '🎭' ?></div>
                <h3><?= $watchlistOnly ? 'Watchlist kosong' : 'Tidak ada film ditemukan' ?></h3>
                <p><?= $watchlistOnly ? 'Kamu belum menambahkan film ke watchlist.' : (($search || $genre) ? 'Coba ubah kata kunci pencarianmu.' : 'Belum ada film. Mulai tambahkan koleksimu!') ?></p>
                <?php if (!$search && !$genre && !$watchlistOnly): ?>
                    <a href="add.php" class="btn-primary">➕ Tambah Film Pertama</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($movies as $movie): ?>
                <?php $inWatchlist = in_array($movie['id'], $watchlistIds); ?>
                <div class="movie-card" data-id="<?= $movie['id'] ?>">
                    <div class="card-poster">
                        <?php if (!empty($movie['poster'])): ?>
                            <img src="<?= htmlspecialchars($movie['poster']) ?>" alt="<?= htmlspecialchars($movie['title']) ?>" loading="lazy"
                                onerror="this.parentElement.querySelector('.poster-placeholder') && (this.style.display='none'); this.parentElement.insertAdjacentHTML('afterbegin','<div class=\'poster-placeholder\'>🎬<small>No Image</small></div>')">
                        <?php else: ?>
                            <div class="poster-placeholder">🎬<small>No Poster</small></div>
                        <?php endif; ?>

                        <?php if (!empty($movie['rating'])): ?>
                            <div class="card-rating">⭐ <?= number_format($movie['rating'], 1) ?></div>
                        <?php endif; ?>

                        <!-- Bookmark button -->
                        <?php if (is_logged_in()): ?>
                        <button class="bookmark-btn <?= $inWatchlist ? 'saved' : '' ?>"
                            onclick="toggleWatchlist(<?= $movie['id'] ?>, this)"
                            title="<?= $inWatchlist ? 'Hapus dari watchlist' : 'Tambah ke watchlist' ?>">
                            <?= $inWatchlist ? '❤️' : '🤍' ?>
                        </button>
                        <?php else: ?>
                        <a href="login.php" class="bookmark-btn" title="Login untuk menambah ke watchlist">🤍</a>
                        <?php endif; ?>

                        <!-- Synopsis hover overlay -->
                        <?php if (!empty($movie['synopsis'])): ?>
                        <div class="synopsis-overlay">
                            <div class="synopsis-content">
                                <div class="synopsis-title"><?= htmlspecialchars($movie['title']) ?></div>
                                <p class="synopsis-text"><?= htmlspecialchars($movie['synopsis']) ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="card-body">
                        <?php if (!empty($movie['genre'])): ?>
                            <div class="card-genre"><?= htmlspecialchars($movie['genre']) ?></div>
                        <?php endif; ?>
                        <div class="card-title"><?= htmlspecialchars($movie['title']) ?></div>
                        <div class="card-meta">
                            <?php if (!empty($movie['director'])): ?><span>🎬 <?= htmlspecialchars($movie['director']) ?></span><?php endif; ?>
                            <?php if (!empty($movie['year'])): ?><span>📅 <?= $movie['year'] ?></span><?php endif; ?>
                        </div>
                        <?php if (is_admin()): ?>
                        <div class="card-actions">
                            <a href="edit.php?id=<?= $movie['id'] ?>" class="btn-edit">✏️ Edit</a>
                            <button class="btn-delete" onclick="confirmDelete(<?= $movie['id'] ?>, '<?= addslashes(htmlspecialchars($movie['title'])) ?>')">🗑️ Hapus</button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<!-- Delete Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal">
        <div class="modal-icon">🗑️</div>
        <h2 class="modal-title">Hapus Film?</h2>
        <p class="modal-text" id="deleteModalText">Tindakan ini tidak dapat dibatalkan.</p>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeModal()">Batal</button>
            <a href="#" id="deleteConfirmBtn" class="btn-danger">Ya, Hapus!</a>
        </div>
    </div>
</div>

<script>
// ── Delete modal ──────────────────────────
function confirmDelete(id, title) {
    document.getElementById('deleteModalText').textContent = `Hapus "${title}"? Tindakan ini tidak dapat dibatalkan.`;
    document.getElementById('deleteConfirmBtn').href = `delete.php?id=${id}`;
    document.getElementById('deleteModal').classList.add('open');
}
function closeModal() { document.getElementById('deleteModal').classList.remove('open'); }
document.getElementById('deleteModal').addEventListener('click', e => { if (e.target === document.getElementById('deleteModal')) closeModal(); });

// ── Watchlist toggle ──────────────────────
function toggleWatchlist(movieId, btn) {
    fetch('watchlist.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=toggle&movie_id=${movieId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.error === 'not_logged_in') {
            window.location.href = 'login.php';
            return;
        }
        if (data.ok) {
            btn.textContent = data.saved ? '❤️' : '🤍';
            btn.classList.toggle('saved', data.saved);
            btn.title = data.saved ? 'Hapus dari watchlist' : 'Tambah ke watchlist';
            updateWatchlistBadge();
        }
    })
    .catch(console.error);
}

function updateWatchlistBadge() {
    fetch('watchlist.php?action=list')
        .then(r => r.json())
        .then(data => {
            const badge = document.querySelector('.wl-badge');
            const btn   = document.querySelector('.watchlist-toggle-btn');
            if (data.ok) {
                const count = data.ids.length;
                if (badge) badge.textContent = count;
                else if (count > 0 && btn) {
                    const span = document.createElement('span');
                    span.className = 'wl-badge';
                    span.textContent = count;
                    btn.appendChild(span);
                }
            }
        });
}

// ── Auto-hide alerts ─────────────────────
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
