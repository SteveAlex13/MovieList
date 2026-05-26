<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';
require_once 'includes/auth.php';
require_admin();

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    $_SESSION['error'] = 'ID film tidak valid.';
    header('Location: index.php');
    exit;
}

// Fetch existing movie
$stmt = $pdo->prepare("SELECT * FROM movies WHERE id = :id");
$stmt->execute([':id' => $id]);
$movie = $stmt->fetch();

if (!$movie) {
    $_SESSION['error'] = 'Film tidak ditemukan.';
    header('Location: index.php');
    exit;
}

$errors = [];
$currentPoster = $movie['poster']; // always preserve unless explicitly changed

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title      = trim($_POST['title']      ?? '');
    $director   = trim($_POST['director']   ?? '');
    $genre      = trim($_POST['genre']      ?? '');
    $year       = trim($_POST['year']       ?? '');
    $rating     = trim($_POST['rating']     ?? '');
    $synopsis   = trim($_POST['synopsis']   ?? '');
    $posterType = $_POST['poster_type']     ?? 'keep'; // DEFAULT = keep!
    $posterUrl  = trim($_POST['poster_url'] ?? '');

    // Validation
    if ($title === '') $errors[] = 'Judul film wajib diisi.';
    if ($year !== '' && (!is_numeric($year) || $year < 1888 || $year > 2099))
        $errors[] = 'Tahun rilis tidak valid.';
    if ($rating !== '' && (!is_numeric($rating) || $rating < 0 || $rating > 10))
        $errors[] = 'Rating harus antara 0 dan 10.';

    // Handle poster — default: keep existing
    $poster = $currentPoster;

    if ($posterType === 'url') {
        // Only change if user actually typed a URL
        if ($posterUrl !== '') {
            $poster = $posterUrl;
        }
        // If empty → keep existing (poster stays unchanged)
    } elseif ($posterType === 'file') {
        if (isset($_FILES['poster_file']) && $_FILES['poster_file']['error'] === UPLOAD_ERR_OK) {
            $file    = $_FILES['poster_file'];
            $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
            $maxSize = 5 * 1024 * 1024;
            if (!in_array($file['type'], $allowed)) {
                $errors[] = 'Format gambar tidak didukung.';
            } elseif ($file['size'] > $maxSize) {
                $errors[] = 'Ukuran file terlalu besar. Maksimal 5MB.';
            } else {
                $uploadDir = 'uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid('poster_', true) . '.' . $ext;
                $dest     = $uploadDir . $filename;
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    if ($currentPoster && !str_starts_with($currentPoster, 'http') && file_exists($currentPoster)) {
                        @unlink($currentPoster);
                    }
                    $poster = $dest;
                } else {
                    $errors[] = 'Gagal mengupload file.';
                }
            }
        }
        // If no file selected → keep existing
    }
    // posterType === 'keep' → $poster stays as $currentPoster

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE movies SET title=:title, director=:director, genre=:genre, year=:year, rating=:rating, synopsis=:synopsis, poster=:poster WHERE id=:id");
        $stmt->execute([
            ':title'    => $title,
            ':director' => $director ?: null,
            ':genre'    => $genre    ?: null,
            ':year'     => $year     ?: null,
            ':rating'   => $rating !== '' ? $rating : null,
            ':synopsis' => $synopsis ?: null,
            ':poster'   => $poster,
            ':id'       => $id,
        ]);
        $_SESSION['success'] = "Film \"$title\" berhasil diperbarui! ✏️";
        header('Location: index.php');
        exit;
    }

    // Repopulate on error
    $movie = array_merge($movie, compact('title','director','genre','year','rating','synopsis'));
    $movie['poster'] = $poster;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Film — CineList</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎬</text></svg>">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main>
    <div class="form-page">
        <div class="page-header">
            <div>
                <h1 class="page-title">Edit <span>Film</span></h1>
                <p class="page-subtitle">Perbarui informasi "<?= htmlspecialchars($movie['title']) ?>"</p>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <div><?php foreach ($errors as $err): ?><div>• <?= htmlspecialchars($err) ?></div><?php endforeach; ?></div>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-grid">

                    <div class="form-group full-width">
                        <label class="form-label">Judul Film <span class="required">*</span></label>
                        <input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($movie['title']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Sutradara</label>
                        <input type="text" name="director" class="form-control" placeholder="Contoh: Christopher Nolan" value="<?= htmlspecialchars($movie['director'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Genre</label>
                        <input type="text" name="genre" class="form-control" placeholder="Contoh: Sci-Fi, Action" value="<?= htmlspecialchars($movie['genre'] ?? '') ?>" list="genre-list">
                        <datalist id="genre-list">
                            <option value="Action"><option value="Adventure"><option value="Animation">
                            <option value="Biography"><option value="Comedy"><option value="Crime">
                            <option value="Documentary"><option value="Drama"><option value="Fantasy">
                            <option value="Horror"><option value="Musical"><option value="Mystery">
                            <option value="Romance"><option value="Sci-Fi"><option value="Thriller"><option value="Western">
                        </datalist>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tahun Rilis</label>
                        <input type="number" name="year" class="form-control" placeholder="Contoh: 2024" min="1888" max="2099" value="<?= htmlspecialchars($movie['year'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Rating <small style="color:var(--text-secondary);text-transform:none">(0.0 – 10.0)</small></label>
                        <input type="number" name="rating" class="form-control" placeholder="Contoh: 8.5" min="0" max="10" step="0.1" value="<?= htmlspecialchars($movie['rating'] ?? '') ?>">
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Sinopsis</label>
                        <textarea name="synopsis" class="form-control" rows="3" placeholder="Ceritakan singkat tentang film ini..."><?= htmlspecialchars($movie['synopsis'] ?? '') ?></textarea>
                    </div>

                    <!-- ── POSTER SECTION ── -->
                    <div class="form-group poster-section">
                        <label class="form-label">Poster Film</label>

                        <?php if (!empty($currentPoster)): ?>
                        <div class="current-poster-box">
                            <img src="<?= htmlspecialchars($currentPoster) ?>" alt="Current Poster"
                                style="width:60px;height:90px;object-fit:cover;border-radius:6px;border:1px solid var(--border);"
                                onerror="this.style.display='none'">
                            <div>
                                <div style="font-size:0.78rem;font-weight:700;color:var(--accent);margin-bottom:0.25rem;text-transform:uppercase;letter-spacing:0.08em;">Poster Saat Ini</div>
                                <div style="font-size:0.78rem;color:var(--text-secondary);">Biarkan kosong di bawah untuk mempertahankan poster ini.</div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="poster-tabs" style="margin-top:0.75rem;">
                            <button type="button" class="poster-tab active" id="tabUrl"  onclick="switchPosterTab('url',  this)">🔗 Ganti URL</button>
                            <button type="button" class="poster-tab"        id="tabFile" onclick="switchPosterTab('file', this)">📁 Upload File</button>
                        </div>
                        <input type="hidden" name="poster_type" id="posterType" value="url">

                        <!-- URL Panel -->
                        <div class="poster-panel active" id="panelUrl">
                            <input type="url" name="poster_url" id="posterUrlInput" class="form-control"
                                placeholder="Masukkan URL baru — kosongkan untuk pakai poster lama"
                                oninput="previewUrl(this.value)">
                            <div class="poster-preview-wrap" id="urlPreviewWrap">
                                <img src="" alt="Preview" class="poster-preview-img" id="urlPreviewImg"
                                    onerror="document.getElementById('urlPreviewWrap').style.display='none'">
                            </div>
                        </div>

                        <!-- File Panel -->
                        <div class="poster-panel" id="panelFile">
                            <div class="file-drop" id="fileDrop">
                                <input type="file" name="poster_file" id="posterFile" accept="image/*" onchange="handleFileSelect(this)">
                                <div class="file-drop-icon">🖼️</div>
                                <div class="file-drop-text"><strong>Klik untuk upload</strong> atau drag & drop<br><small>JPG, PNG, WebP — maks. 5MB. Kosongkan untuk pakai poster lama.</small></div>
                            </div>
                            <div class="file-preview" id="filePreview">
                                <img src="" alt="" id="filePreviewImg">
                                <span class="file-preview-name" id="filePreviewName"></span>
                                <button type="button" class="file-preview-remove" onclick="clearFile()">✕</button>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="form-actions">
                    <a href="index.php" class="btn-cancel">← Batal</a>
                    <button type="submit" class="btn-submit">💾 Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script>
function switchPosterTab(type, btn) {
    document.querySelectorAll('.poster-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.poster-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('panelUrl').classList.toggle('active', type === 'url');
    document.getElementById('panelFile').classList.toggle('active', type === 'file');
    document.getElementById('posterType').value = type;
    if (type === 'url') {
        document.getElementById('posterFile').value = '';
        document.getElementById('filePreview').style.display = 'none';
    } else {
        document.getElementById('urlPreviewWrap').style.display = 'none';
    }
}
function previewUrl(url) {
    const wrap = document.getElementById('urlPreviewWrap');
    const img  = document.getElementById('urlPreviewImg');
    if (url) { img.src = url; wrap.style.display = 'block'; }
    else wrap.style.display = 'none';
}
function handleFileSelect(input) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('filePreviewImg').src = e.target.result;
        document.getElementById('filePreviewName').textContent = file.name;
        document.getElementById('filePreview').style.display = 'flex';
    };
    reader.readAsDataURL(file);
}
function clearFile() {
    document.getElementById('posterFile').value = '';
    document.getElementById('filePreview').style.display = 'none';
}
const dropzone = document.getElementById('fileDrop');
['dragenter','dragover'].forEach(e => dropzone.addEventListener(e, ev => { ev.preventDefault(); dropzone.classList.add('drag-over'); }));
['dragleave','drop'].forEach(e => dropzone.addEventListener(e, ev => { ev.preventDefault(); dropzone.classList.remove('drag-over'); }));
dropzone.addEventListener('drop', ev => {
    ev.preventDefault();
    const file = ev.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
        const dt = new DataTransfer(); dt.items.add(file);
        document.getElementById('posterFile').files = dt.files;
        handleFileSelect(document.getElementById('posterFile'));
    }
});
</script>
</body>
</html>
