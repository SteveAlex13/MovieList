<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';
require_once 'includes/auth.php';
require_admin();

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim($_POST['title']    ?? '');
    $director = trim($_POST['director'] ?? '');
    $genre    = trim($_POST['genre']    ?? '');
    $year     = trim($_POST['year']     ?? '');
    $rating   = trim($_POST['rating']   ?? '');
    $synopsis = trim($_POST['synopsis'] ?? '');
    $posterType = $_POST['poster_type'] ?? 'url';
    $posterUrl  = trim($_POST['poster_url'] ?? '');

    // Validation
    if ($title === '') $errors[] = 'Judul film wajib diisi.';
    if ($year !== '' && (!is_numeric($year) || $year < 1888 || $year > 2099))
        $errors[] = 'Tahun rilis tidak valid.';
    if ($rating !== '' && (!is_numeric($rating) || $rating < 0 || $rating > 10))
        $errors[] = 'Rating harus antara 0 dan 10.';

    $poster = '';
    if ($posterType === 'file' && isset($_FILES['poster_file']) && $_FILES['poster_file']['error'] === UPLOAD_ERR_OK) {
        $file     = $_FILES['poster_file'];
        $allowed  = ['image/jpeg','image/png','image/webp','image/gif'];
        $maxSize  = 5 * 1024 * 1024;
        if (!in_array($file['type'], $allowed)) {
            $errors[] = 'Format gambar tidak didukung. Gunakan JPG, PNG, WebP, atau GIF.';
        } elseif ($file['size'] > $maxSize) {
            $errors[] = 'Ukuran file terlalu besar. Maksimal 5MB.';
        } else {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('poster_', true) . '.' . $ext;
            $dest     = $uploadDir . $filename;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $poster = $dest;
            } else {
                $errors[] = 'Gagal mengupload file.';
            }
        }
    } elseif ($posterType === 'url' && $posterUrl !== '') {
        $poster = $posterUrl;
    }

    $old = compact('title','director','genre','year','rating','posterUrl','posterType','synopsis');

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO movies (title, director, genre, year, rating, synopsis, poster) VALUES (:title, :director, :genre, :year, :rating, :synopsis, :poster)");
        $stmt->execute([
            ':title'    => $title,
            ':director' => $director ?: null,
            ':genre'    => $genre    ?: null,
            ':year'     => $year     ?: null,
            ':rating'   => $rating   !== '' ? $rating : null,
            ':synopsis' => $synopsis ?: null,
            ':poster'   => $poster   ?: null,
        ]);
        $_SESSION['success'] = "Film \"$title\" berhasil ditambahkan! 🎉";
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Film — CineList</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎬</text></svg>">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main>
    <div class="form-page">
        <div class="page-header">
            <div>
                <h1 class="page-title">Tambah <span>Film</span></h1>
                <p class="page-subtitle">Masukkan detail film baru ke koleksimu</p>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <div>
                    <?php foreach ($errors as $err): ?>
                        <div>• <?= htmlspecialchars($err) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-grid">

                    <!-- Title -->
                    <div class="form-group full-width">
                        <label class="form-label">Judul Film <span class="required">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="Contoh: Inception" required
                            value="<?= htmlspecialchars($old['title'] ?? '') ?>">
                    </div>

                    <!-- Director -->
                    <div class="form-group">
                        <label class="form-label">Sutradara</label>
                        <input type="text" name="director" class="form-control" placeholder="Contoh: Christopher Nolan"
                            value="<?= htmlspecialchars($old['director'] ?? '') ?>">
                    </div>

                    <!-- Genre -->
                    <div class="form-group">
                        <label class="form-label">Genre</label>
                        <input type="text" name="genre" class="form-control" placeholder="Contoh: Sci-Fi, Action, Drama"
                            value="<?= htmlspecialchars($old['genre'] ?? '') ?>" list="genre-list">
                        <datalist id="genre-list">
                            <option value="Action"><option value="Adventure"><option value="Animation">
                            <option value="Biography"><option value="Comedy"><option value="Crime">
                            <option value="Documentary"><option value="Drama"><option value="Fantasy">
                            <option value="Horror"><option value="Musical"><option value="Mystery">
                            <option value="Romance"><option value="Sci-Fi"><option value="Thriller">
                            <option value="Western">
                        </datalist>
                    </div>

                    <!-- Year -->
                    <div class="form-group">
                        <label class="form-label">Tahun Rilis</label>
                        <input type="number" name="year" class="form-control" placeholder="Contoh: 2024"
                            min="1888" max="2099"
                            value="<?= htmlspecialchars($old['year'] ?? '') ?>">
                    </div>

                    <!-- Rating -->
                    <div class="form-group">
                        <label class="form-label">Rating <small style="color:var(--text-secondary);text-transform:none">(0.0 – 10.0)</small></label>
                        <input type="number" name="rating" class="form-control" placeholder="Contoh: 8.5"
                            min="0" max="10" step="0.1"
                            value="<?= htmlspecialchars($old['rating'] ?? '') ?>">
                    </div>

                    <!-- Synopsis -->
                    <div class="form-group full-width">
                        <label class="form-label">Sinopsis</label>
                        <textarea name="synopsis" class="form-control" rows="3" placeholder="Ceritakan singkat tentang film ini..."><?= htmlspecialchars($old['synopsis'] ?? '') ?></textarea>
                    </div>

                    <!-- Poster -->
                    <div class="form-group poster-section">
                        <label class="form-label">Poster Film</label>
                        <div class="poster-tabs">
                            <button type="button" class="poster-tab active" onclick="switchPosterTab('url', this)">🔗 URL</button>
                            <button type="button" class="poster-tab" onclick="switchPosterTab('file', this)">📁 Upload File</button>
                        </div>
                        <input type="hidden" name="poster_type" id="posterType" value="<?= htmlspecialchars($old['posterType'] ?? 'url') ?>">

                        <!-- URL Panel -->
                        <div class="poster-panel active" id="panelUrl">
                            <input type="url" name="poster_url" id="posterUrlInput" class="form-control"
                                placeholder="https://example.com/poster.jpg"
                                value="<?= htmlspecialchars($old['posterUrl'] ?? '') ?>"
                                oninput="previewUrl(this.value)">
                            <div class="poster-preview-wrap" id="urlPreviewWrap">
                                <img src="" alt="Preview" class="poster-preview-img" id="urlPreviewImg"
                                    onerror="document.getElementById('urlPreviewWrap').style.display='none'">
                            </div>
                        </div>

                        <!-- File Panel -->
                        <div class="poster-panel" id="panelFile">
                            <div class="file-drop" id="fileDrop">
                                <input type="file" name="poster_file" id="posterFile" accept="image/*"
                                    onchange="handleFileSelect(this)">
                                <div class="file-drop-icon">🖼️</div>
                                <div class="file-drop-text">
                                    <strong>Klik untuk upload</strong> atau drag & drop<br>
                                    <small>JPG, PNG, WebP, GIF — maks. 5MB</small>
                                </div>
                            </div>
                            <div class="file-preview" id="filePreview">
                                <img src="" alt="" id="filePreviewImg">
                                <span class="file-preview-name" id="filePreviewName"></span>
                                <button type="button" class="file-preview-remove" onclick="clearFile()">✕</button>
                            </div>
                        </div>
                    </div>

                </div><!-- /form-grid -->

                <div class="form-actions">
                    <a href="index.php" class="btn-cancel">← Batal</a>
                    <button type="submit" class="btn-submit">➕ Tambah Film</button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script>
// Poster tab switching
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

// URL preview
function previewUrl(url) {
    const wrap = document.getElementById('urlPreviewWrap');
    const img  = document.getElementById('urlPreviewImg');
    if (url) {
        img.src = url;
        wrap.style.display = 'block';
    } else {
        wrap.style.display = 'none';
    }
}

// File select
function handleFileSelect(input) {
    const file = input.files[0];
    if (!file) return;
    const preview = document.getElementById('filePreview');
    const img     = document.getElementById('filePreviewImg');
    const name    = document.getElementById('filePreviewName');
    const reader  = new FileReader();
    reader.onload = e => {
        img.src  = e.target.result;
        name.textContent = file.name;
        preview.style.display = 'flex';
    };
    reader.readAsDataURL(file);
}
function clearFile() {
    document.getElementById('posterFile').value = '';
    document.getElementById('filePreview').style.display = 'none';
}

// Drag & drop
const dropzone = document.getElementById('fileDrop');
['dragenter','dragover'].forEach(e => dropzone.addEventListener(e, ev => {
    ev.preventDefault(); dropzone.classList.add('drag-over');
}));
['dragleave','drop'].forEach(e => dropzone.addEventListener(e, ev => {
    ev.preventDefault(); dropzone.classList.remove('drag-over');
}));
dropzone.addEventListener('drop', ev => {
    ev.preventDefault();
    const file = ev.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
        const dt = new DataTransfer();
        dt.items.add(file);
        document.getElementById('posterFile').files = dt.files;
        handleFileSelect(document.getElementById('posterFile'));
    }
});

// Init URL preview if value exists
const initUrl = document.getElementById('posterUrlInput').value;
if (initUrl) previewUrl(initUrl);

// Init tab if returning from form
const initType = document.getElementById('posterType').value;
if (initType === 'file') {
    const tabs = document.querySelectorAll('.poster-tab');
    switchPosterTab('file', tabs[1]);
}
</script>
</body>
</html>
