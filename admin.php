<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';

// Pagination
$perPage = 10;
$page    = max(1, intval($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// Search/filter
$search = trim($_GET['search'] ?? '');
$genre  = trim($_GET['genre']  ?? '');
$sort   = $_GET['sort'] ?? 'created_at_desc';

$where  = [];
$params = [];
if ($search !== '') {
    $where[] = '(title LIKE :search OR director LIKE :search2)';
    $params[':search']  = "%$search%";
    $params[':search2'] = "%$search%";
}
if ($genre !== '') {
    $where[] = 'genre = :genre';
    $params[':genre'] = $genre;
}
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$orderMap = [
    'created_at_desc' => 'created_at DESC',
    'created_at_asc'  => 'created_at ASC',
    'rating_desc'     => 'rating DESC',
    'rating_asc'      => 'rating ASC',
    'title_asc'       => 'title ASC',
    'title_desc'      => 'title DESC',
    'year_desc'       => 'year DESC',
    'year_asc'        => 'year ASC',
];
$orderSQL = $orderMap[$sort] ?? 'created_at DESC';

// Total count for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM movies $whereSQL");
$countStmt->execute($params);
$totalRows  = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

// Fetch page
$stmt = $pdo->prepare("SELECT * FROM movies $whereSQL ORDER BY $orderSQL LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$movies = $stmt->fetchAll();

// Stats
$stats = $pdo->query("SELECT COUNT(*) as total, AVG(rating) as avg_rating, MAX(rating) as max_rating, MIN(year) as oldest_year, MAX(year) as newest_year FROM movies")->fetch();
$genreCount = $pdo->query("SELECT COUNT(DISTINCT genre) FROM movies WHERE genre IS NOT NULL AND genre != ''")->fetchColumn();
$genres     = $pdo->query("SELECT DISTINCT genre FROM movies WHERE genre IS NOT NULL AND genre != '' ORDER BY genre")->fetchAll(PDO::FETCH_COLUMN);

// Watchlist count
$wlCount = 0;
try {
    $wlCount = $pdo->query("SELECT COUNT(DISTINCT movie_id) FROM watchlist")->fetchColumn();
} catch (PDOException $e) {}

$success = '';
$error   = '';
if (isset($_SESSION['success'])) { $success = $_SESSION['success']; unset($_SESSION['success']); }
if (isset($_SESSION['error']))   { $error   = $_SESSION['error'];   unset($_SESSION['error']); }

// Build query string helper
function qs($extra = []) {
    $base = array_filter([
        'search' => $_GET['search'] ?? '',
        'genre'  => $_GET['genre']  ?? '',
        'sort'   => $_GET['sort']   ?? '',
    ]);
    $merged = array_merge($base, $extra);
    $merged = array_filter($merged, fn($v) => $v !== '' && $v !== null);
    return $merged ? '?' . http_build_query($merged) : '?';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — CineList</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎬</text></svg>">
    <style>
    /* ── Admin-specific styles ── */
    .admin-layout { display: flex; min-height: calc(100vh - 64px); }

    .admin-sidebar {
        width: 220px;
        flex-shrink: 0;
        background: var(--bg-card);
        border-right: 1px solid var(--border);
        padding: 1.5rem 0;
        position: sticky;
        top: 64px;
        height: calc(100vh - 64px);
        overflow-y: auto;
    }
    .sidebar-section { padding: 0 1rem; margin-bottom: 1.5rem; }
    .sidebar-label {
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        color: var(--text-secondary);
        text-transform: uppercase;
        padding: 0 0.5rem;
        margin-bottom: 0.5rem;
    }
    .sidebar-link {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.6rem 0.75rem;
        border-radius: var(--radius-sm);
        text-decoration: none;
        font-size: 0.88rem;
        color: var(--text-secondary);
        transition: var(--transition);
        font-weight: 500;
    }
    .sidebar-link:hover { background: var(--bg-card2); color: var(--text-primary); }
    .sidebar-link.active { background: var(--accent-dim); color: var(--accent); border: 1px solid var(--border-hover); }
    .sidebar-link .icon { font-size: 1rem; width: 20px; text-align: center; }

    .admin-content { flex: 1; padding: 2rem; overflow-x: auto; min-width: 0; }

    /* Stats cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    .stat-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.25rem 1.5rem;
        transition: var(--transition);
    }
    .stat-card:hover { border-color: var(--border-hover); transform: translateY(-2px); }
    .stat-icon { font-size: 1.6rem; margin-bottom: 0.5rem; }
    .stat-value {
        font-family: 'Playfair Display', serif;
        font-size: 2rem;
        font-weight: 900;
        color: var(--text-primary);
        line-height: 1;
        margin-bottom: 0.25rem;
    }
    .stat-label { font-size: 0.78rem; color: var(--text-secondary); font-weight: 500; }

    /* Admin toolbar */
    .admin-toolbar {
        display: flex;
        gap: 0.75rem;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 1.25rem;
    }
    .admin-toolbar .search-input-wrap { flex: 1; min-width: 180px; }

    /* Table */
    .table-wrap {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        overflow: hidden;
    }
    .data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }
    .data-table thead { background: var(--bg-card2); }
    .data-table th {
        padding: 0.85rem 1rem;
        text-align: left;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--text-secondary);
        border-bottom: 1px solid var(--border);
        white-space: nowrap;
    }
    .data-table th a {
        color: inherit;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }
    .data-table th a:hover { color: var(--accent); }
    .data-table td {
        padding: 0.85rem 1rem;
        border-bottom: 1px solid var(--border);
        color: var(--text-primary);
        vertical-align: middle;
    }
    .data-table tbody tr:last-child td { border-bottom: none; }
    .data-table tbody tr { transition: var(--transition); }
    .data-table tbody tr:hover { background: var(--bg-card2); }

    .td-poster img {
        width: 36px;
        height: 52px;
        object-fit: cover;
        border-radius: 4px;
        border: 1px solid var(--border);
    }
    .td-poster .no-img {
        width: 36px;
        height: 52px;
        background: var(--bg-input);
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        border: 1px solid var(--border);
    }
    .td-title { font-weight: 600; max-width: 220px; }
    .td-title small { display: block; color: var(--text-secondary); font-weight: 400; font-size: 0.78rem; margin-top: 2px; }
    .td-synopsis {
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        color: var(--text-secondary);
        font-size: 0.8rem;
    }
    .td-rating { font-weight: 700; color: var(--accent); }
    .genre-chip {
        display: inline-block;
        padding: 0.2rem 0.6rem;
        background: var(--accent-dim);
        color: var(--accent);
        border: 1px solid var(--border-hover);
        border-radius: 50px;
        font-size: 0.72rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .td-actions { display: flex; gap: 0.4rem; white-space: nowrap; }
    .tbl-btn {
        padding: 0.35rem 0.7rem;
        border-radius: 6px;
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
        border: 1px solid transparent;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        transition: var(--transition);
        font-family: inherit;
    }
    .tbl-btn-edit { background: var(--bg-card2); color: var(--text-secondary); border-color: var(--border); }
    .tbl-btn-edit:hover { background: var(--accent-dim); color: var(--accent); border-color: var(--border-hover); }
    .tbl-btn-del { background: var(--red-dim); color: var(--red); border-color: rgba(255,71,87,0.3); }
    .tbl-btn-del:hover { background: var(--red); color: #fff; }

    /* Pagination */
    .pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.25rem;
        border-top: 1px solid var(--border);
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    .pagination-info { font-size: 0.83rem; color: var(--text-secondary); }
    .pagination-links { display: flex; gap: 0.3rem; }
    .page-btn {
        padding: 0.4rem 0.75rem;
        border-radius: 6px;
        font-size: 0.82rem;
        font-weight: 600;
        text-decoration: none;
        color: var(--text-secondary);
        border: 1px solid var(--border);
        background: var(--bg-card2);
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
    }
    .page-btn:hover { color: var(--accent); border-color: var(--border-hover); }
    .page-btn.active { background: var(--accent); color: #000; border-color: var(--accent); }
    .page-btn.disabled { opacity: 0.35; pointer-events: none; }

    /* Bulk delete checkbox */
    .cb-movie { accent-color: var(--accent); width: 15px; height: 15px; cursor: pointer; }
    .bulk-bar {
        display: none;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem 1rem;
        background: var(--accent-dim);
        border: 1px solid var(--border-hover);
        border-radius: var(--radius-sm);
        margin-bottom: 1rem;
        font-size: 0.875rem;
    }
    .bulk-bar.visible { display: flex; }

    @media (max-width: 900px) {
        .admin-sidebar { display: none; }
        .admin-content { padding: 1rem; }
        .data-table th:nth-child(5),
        .data-table td:nth-child(5),
        .data-table th:nth-child(6),
        .data-table td:nth-child(6) { display: none; }
    }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-section">
            <div class="sidebar-label">Menu</div>
            <a href="admin.php" class="sidebar-link active">
                <span class="icon">📊</span> Dashboard
            </a>
            <a href="add.php" class="sidebar-link">
                <span class="icon">➕</span> Tambah Film
            </a>
            <a href="index.php" class="sidebar-link">
                <span class="icon">🎬</span> Lihat Publik
            </a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-label">Filter Genre</div>
            <a href="<?= qs(['genre'=>'','page'=>1]) ?>" class="sidebar-link <?= $genre==='' ? 'active':'' ?>">
                <span class="icon">🌐</span> Semua
            </a>
            <?php foreach ($genres as $g): ?>
            <a href="<?= qs(['genre'=>$g,'page'=>1]) ?>" class="sidebar-link <?= $genre===$g?'active':'' ?>">
                <span class="icon">🎭</span> <?= htmlspecialchars($g) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </aside>

    <!-- Main content -->
    <div class="admin-content">

        <!-- Page title -->
        <div class="page-header" style="margin-bottom:1.5rem;">
            <div>
                <h1 class="page-title">Admin <span>Dashboard</span></h1>
                <p class="page-subtitle">Kelola semua film dalam satu tempat</p>
            </div>
            <a href="add.php" class="btn-primary">➕ Tambah Film</a>
        </div>

        <?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🎬</div>
                <div class="stat-value"><?= $stats['total'] ?></div>
                <div class="stat-label">Total Film</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⭐</div>
                <div class="stat-value"><?= $stats['avg_rating'] ? number_format($stats['avg_rating'], 1) : '—' ?></div>
                <div class="stat-label">Rata-rata Rating</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🏆</div>
                <div class="stat-value"><?= $stats['max_rating'] ?? '—' ?></div>
                <div class="stat-label">Rating Tertinggi</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🎭</div>
                <div class="stat-value"><?= $genreCount ?></div>
                <div class="stat-label">Genre Unik</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-value"><?= $stats['newest_year'] ?? '—' ?></div>
                <div class="stat-label">Film Terbaru</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">❤️</div>
                <div class="stat-value"><?= $wlCount ?></div>
                <div class="stat-label">Di Watchlist</div>
            </div>
        </div>

        <!-- Toolbar -->
        <form method="GET" action="admin.php" class="admin-toolbar">
            <div class="search-input-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.099zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
                <input type="text" name="search" class="search-input" placeholder="Cari judul atau sutradara..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <select name="genre" class="filter-select">
                <option value="">Semua Genre</option>
                <?php foreach ($genres as $g): ?>
                    <option value="<?= htmlspecialchars($g) ?>" <?= $genre===$g?'selected':'' ?>><?= htmlspecialchars($g) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="sort" class="filter-select">
                <option value="created_at_desc" <?= $sort==='created_at_desc'?'selected':'' ?>>Terbaru</option>
                <option value="title_asc"       <?= $sort==='title_asc'      ?'selected':'' ?>>Judul A–Z</option>
                <option value="title_desc"      <?= $sort==='title_desc'     ?'selected':'' ?>>Judul Z–A</option>
                <option value="rating_desc"     <?= $sort==='rating_desc'    ?'selected':'' ?>>Rating ↓</option>
                <option value="year_desc"       <?= $sort==='year_desc'      ?'selected':'' ?>>Tahun ↓</option>
                <option value="year_asc"        <?= $sort==='year_asc'       ?'selected':'' ?>>Tahun ↑</option>
            </select>
            <button type="submit" class="btn-search">🔍 Cari</button>
            <?php if ($search || $genre || $sort !== 'created_at_desc'): ?>
                <a href="admin.php" class="btn-reset">✕ Reset</a>
            <?php endif; ?>
        </form>

        <!-- Bulk delete bar -->
        <div class="bulk-bar" id="bulkBar">
            <span id="bulkCount">0 film dipilih</span>
            <button type="button" class="tbl-btn tbl-btn-del" onclick="bulkDelete()">🗑️ Hapus Terpilih</button>
            <button type="button" class="btn-reset" onclick="clearSelection()">✕ Batal</button>
        </div>

        <!-- Table -->
        <div class="table-wrap">
            <table class="data-table" id="movieTable">
                <thead>
                    <tr>
                        <th style="width:36px;"><input type="checkbox" class="cb-movie" id="cbAll" onchange="toggleAll(this)" title="Pilih semua"></th>
                        <th style="width:52px;">Poster</th>
                        <th>
                            <a href="<?= qs(['sort'=> $sort==='title_asc'?'title_desc':'title_asc', 'page'=>1]) ?>">
                                Judul <?= $sort==='title_asc'?'↑':($sort==='title_desc'?'↓':'') ?>
                            </a>
                        </th>
                        <th>Genre</th>
                        <th>
                            <a href="<?= qs(['sort'=> $sort==='year_desc'?'year_asc':'year_desc', 'page'=>1]) ?>">
                                Tahun <?= $sort==='year_asc'?'↑':($sort==='year_desc'?'↓':'') ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?= qs(['sort'=> $sort==='rating_desc'?'rating_asc':'rating_desc', 'page'=>1]) ?>">
                                Rating <?= $sort==='rating_asc'?'↑':($sort==='rating_desc'?'↓':'') ?>
                            </a>
                        </th>
                        <th>Sinopsis</th>
                        <th>Ditambahkan</th>
                        <th style="text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($movies)): ?>
                        <tr><td colspan="9" style="text-align:center;padding:3rem;color:var(--text-secondary);">🎭 Tidak ada film ditemukan</td></tr>
                    <?php else: ?>
                    <?php foreach ($movies as $m): ?>
                        <tr>
                            <td><input type="checkbox" class="cb-movie cb-row" value="<?= $m['id'] ?>" onchange="updateBulk()"></td>
                            <td class="td-poster">
                                <?php if (!empty($m['poster'])): ?>
                                    <img src="<?= htmlspecialchars($m['poster']) ?>" alt="" loading="lazy" onerror="this.outerHTML='<div class=\'no-img\'>🎬</div>'">
                                <?php else: ?>
                                    <div class="no-img">🎬</div>
                                <?php endif; ?>
                            </td>
                            <td class="td-title">
                                <?= htmlspecialchars($m['title']) ?>
                                <?php if (!empty($m['director'])): ?>
                                    <small>🎬 <?= htmlspecialchars($m['director']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php if (!empty($m['genre'])): ?><span class="genre-chip"><?= htmlspecialchars($m['genre']) ?></span><?php else: ?>—<?php endif; ?></td>
                            <td><?= $m['year'] ?? '—' ?></td>
                            <td class="td-rating"><?= $m['rating'] ? '⭐ '.number_format($m['rating'],1) : '—' ?></td>
                            <td class="td-synopsis" title="<?= htmlspecialchars($m['synopsis'] ?? '') ?>">
                                <?= $m['synopsis'] ? htmlspecialchars($m['synopsis']) : '<span style="color:var(--text-secondary)">—</span>' ?>
                            </td>
                            <td style="color:var(--text-secondary);font-size:0.8rem;white-space:nowrap;">
                                <?= date('d M Y', strtotime($m['created_at'])) ?>
                            </td>
                            <td>
                                <div class="td-actions" style="justify-content:flex-end;">
                                    <a href="edit.php?id=<?= $m['id'] ?>" class="tbl-btn tbl-btn-edit">✏️ Edit</a>
                                    <button class="tbl-btn tbl-btn-del" onclick="confirmDelete(<?= $m['id'] ?>, '<?= addslashes(htmlspecialchars($m['title'])) ?>')">🗑️</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($totalPages > 1 || $totalRows > 0): ?>
            <div class="pagination">
                <div class="pagination-info">
                    Menampilkan <?= $offset + 1 ?>–<?= min($offset + $perPage, $totalRows) ?> dari <?= $totalRows ?> film
                </div>
                <div class="pagination-links">
                    <a href="<?= qs(['page'=>1]) ?>" class="page-btn <?= $page<=1?'disabled':'' ?>">«</a>
                    <a href="<?= qs(['page'=>$page-1]) ?>" class="page-btn <?= $page<=1?'disabled':'' ?>">‹</a>
                    <?php
                    $start = max(1, $page - 2);
                    $end   = min($totalPages, $page + 2);
                    if ($start > 1): ?><span class="page-btn disabled">…</span><?php endif;
                    for ($p = $start; $p <= $end; $p++): ?>
                        <a href="<?= qs(['page'=>$p]) ?>" class="page-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a>
                    <?php endfor;
                    if ($end < $totalPages): ?><span class="page-btn disabled">…</span><?php endif; ?>
                    <a href="<?= qs(['page'=>$page+1]) ?>" class="page-btn <?= $page>=$totalPages?'disabled':'' ?>">›</a>
                    <a href="<?= qs(['page'=>$totalPages]) ?>" class="page-btn <?= $page>=$totalPages?'disabled':'' ?>">»</a>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /admin-content -->
</div><!-- /admin-layout -->

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

<!-- Bulk Delete Modal -->
<div class="modal-overlay" id="bulkModal">
    <div class="modal">
        <div class="modal-icon">⚠️</div>
        <h2 class="modal-title">Hapus Film Terpilih?</h2>
        <p class="modal-text" id="bulkModalText">Tindakan ini tidak dapat dibatalkan.</p>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="document.getElementById('bulkModal').classList.remove('open')">Batal</button>
            <button class="btn-danger" onclick="submitBulkDelete()">Ya, Hapus Semua!</button>
        </div>
    </div>
</div>
<form id="bulkForm" method="POST" action="bulk_delete.php" style="display:none;">
    <input type="hidden" name="ids" id="bulkIds">
</form>

<script>
// Single delete
function confirmDelete(id, title) {
    document.getElementById('deleteModalText').textContent = `Hapus "${title}"? Tindakan ini tidak dapat dibatalkan.`;
    document.getElementById('deleteConfirmBtn').href = `delete.php?id=${id}`;
    document.getElementById('deleteModal').classList.add('open');
}
function closeModal() { document.getElementById('deleteModal').classList.remove('open'); }
document.getElementById('deleteModal').addEventListener('click', e => { if (e.target === document.getElementById('deleteModal')) closeModal(); });

// Checkbox / bulk
function toggleAll(cb) {
    document.querySelectorAll('.cb-row').forEach(c => c.checked = cb.checked);
    updateBulk();
}
function updateBulk() {
    const checked = document.querySelectorAll('.cb-row:checked');
    const bar = document.getElementById('bulkBar');
    document.getElementById('bulkCount').textContent = `${checked.length} film dipilih`;
    bar.classList.toggle('visible', checked.length > 0);
    document.getElementById('cbAll').indeterminate = checked.length > 0 && checked.length < document.querySelectorAll('.cb-row').length;
    document.getElementById('cbAll').checked = checked.length === document.querySelectorAll('.cb-row').length;
}
function clearSelection() {
    document.querySelectorAll('.cb-row, #cbAll').forEach(c => c.checked = false);
    updateBulk();
}
function bulkDelete() {
    const ids = [...document.querySelectorAll('.cb-row:checked')].map(c => c.value);
    if (!ids.length) return;
    document.getElementById('bulkModalText').textContent = `Kamu akan menghapus ${ids.length} film secara permanen.`;
    document.getElementById('bulkModal').classList.add('open');
}
function submitBulkDelete() {
    const ids = [...document.querySelectorAll('.cb-row:checked')].map(c => c.value).join(',');
    document.getElementById('bulkIds').value = ids;
    document.getElementById('bulkForm').submit();
}

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
