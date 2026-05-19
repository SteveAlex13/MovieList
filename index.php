<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';

// Search & filter
$search = trim($_GET['search'] ?? '');
$genre  = trim($_GET['genre']  ?? '');
$sort   = $_GET['sort'] ?? 'created_at_desc';

// Build query
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

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$orderMap = [
    'created_at_desc' => 'created_at DESC',
    'created_at_asc'  => 'created_at ASC',
    'rating_desc'     => 'rating DESC',
    'rating_asc'      => 'rating ASC',
    'title_asc'       => 'title ASC',
    'year_desc'       => 'year DESC',
];
$orderSQL = $orderMap[$sort] ?? 'created_at DESC';

$stmt = $pdo->prepare("SELECT * FROM movies $whereSQL ORDER BY $orderSQL");
$stmt->execute($params);
$movies = $stmt->fetchAll();

// All genres for filter dropdown
$genres = $pdo->query("SELECT DISTINCT genre FROM movies WHERE genre IS NOT NULL AND genre != '' ORDER BY genre")->fetchAll(PDO::FETCH_COLUMN);

$totalMovies = $pdo->query("SELECT COUNT(*) FROM movies")->fetchColumn();

// Flash messages
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
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h1 class="page-title">Daftar <span>Film</span></h1>
            <p class="page-subtitle">Koleksi film pilihan yang kamu kurasikan</p>
        </div>
        <span class="movie-count">🎞️ <?= $totalMovies ?> Film</span>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Search & Filter -->
    <form method="GET" action="index.php" class="search-bar">
        <div class="search-input-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.099zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
            <input
                type="text"
                name="search"
                class="search-input"
                placeholder="Cari judul atau sutradara..."
                value="<?= htmlspecialchars($search) ?>"
            >
        </div>
        <select name="genre" class="filter-select">
            <option value="">Semua Genre</option>
            <?php foreach ($genres as $g): ?>
                <option value="<?= htmlspecialchars($g) ?>" <?= $genre === $g ? 'selected' : '' ?>>
                    <?= htmlspecialchars($g) ?>
                </option>
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
            <a href="index.php" class="btn-reset">✕ Reset</a>
        <?php endif; ?>
    </form>

    <!-- Movie Grid -->
    <div class="movies-grid">
        <?php if (empty($movies)): ?>
            <div class="empty-state">
                <div class="empty-icon">🎭</div>
                <h3>Tidak ada film ditemukan</h3>
                <p>
                    <?= ($search || $genre) ? 'Coba ubah kata kunci pencarianmu.' : 'Belum ada film. Mulai tambahkan koleksimu!' ?>
                </p>
                <?php if (!$search && !$genre): ?>
                    <a href="add.php" class="btn-primary">➕ Tambah Film Pertama</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($movies as $movie): ?>
                <div class="movie-card">
                    <!-- Poster -->
                    <div class="card-poster">
                        <?php if (!empty($movie['poster'])): ?>
                            <img
                                src="<?= htmlspecialchars($movie['poster']) ?>"
                                alt="<?= htmlspecialchars($movie['title']) ?>"
                                loading="lazy"
                                onerror="this.parentElement.innerHTML='<div class=\'poster-placeholder\'>🎬<small>No Image</small></div>'"
                            >
                        <?php else: ?>
                            <div class="poster-placeholder">🎬<small>No Poster</small></div>
                        <?php endif; ?>
                        <?php if (!empty($movie['rating'])): ?>
                            <div class="card-rating">⭐ <?= number_format($movie['rating'], 1) ?></div>
                        <?php endif; ?>
                    </div>
                    <!-- Body -->
                    <div class="card-body">
                        <?php if (!empty($movie['genre'])): ?>
                            <div class="card-genre"><?= htmlspecialchars($movie['genre']) ?></div>
                        <?php endif; ?>
                        <div class="card-title"><?= htmlspecialchars($movie['title']) ?></div>
                        <div class="card-meta">
                            <?php if (!empty($movie['director'])): ?>
                                <span>🎬 <?= htmlspecialchars($movie['director']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($movie['year'])): ?>
                                <span>📅 <?= $movie['year'] ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="card-actions">
                            <a href="edit.php?id=<?= $movie['id'] ?>" class="btn-edit">
                                ✏️ Edit
                            </a>
                            <button
                                class="btn-delete"
                                onclick="confirmDelete(<?= $movie['id'] ?>, '<?= addslashes(htmlspecialchars($movie['title'])) ?>')"
                            >
                                🗑️ Hapus
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<!-- Delete Confirmation Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal">
        <div class="modal-icon">🗑️</div>
        <h2 class="modal-title">Hapus Film?</h2>
        <p class="modal-text" id="deleteModalText">Apakah kamu yakin ingin menghapus film ini? Tindakan ini tidak dapat dibatalkan.</p>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeModal()">Batal</button>
            <a href="#" id="deleteConfirmBtn" class="btn-danger">Ya, Hapus!</a>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, title) {
    document.getElementById('deleteModalText').textContent =
        `Apakah kamu yakin ingin menghapus "${title}"? Tindakan ini tidak dapat dibatalkan.`;
    document.getElementById('deleteConfirmBtn').href = `delete.php?id=${id}`;
    document.getElementById('deleteModal').classList.add('open');
}
function closeModal() {
    document.getElementById('deleteModal').classList.remove('open');
}
// Close modal on overlay click
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
// Auto-hide alerts
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
