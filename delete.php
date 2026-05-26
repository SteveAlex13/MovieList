<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';

$id = intval($_GET['id'] ?? 0);

if (!$id) {
    $_SESSION['error'] = 'ID film tidak valid.';
    header('Location: index.php');
    exit;
}

try {
    // Fetch movie
    $stmt = $pdo->prepare("SELECT id, title, poster FROM movies WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $movie = $stmt->fetch();

    if (!$movie) {
        $_SESSION['error'] = 'Film tidak ditemukan atau sudah dihapus.';
        header('Location: index.php');
        exit;
    }

    // Remove watchlist entries first (in case FK not set)
    try {
        $pdo->prepare("DELETE FROM watchlist WHERE movie_id = :id")->execute([':id' => $id]);
    } catch (Exception $e) { /* watchlist table might not exist yet */ }

    // Delete the movie
    $del = $pdo->prepare("DELETE FROM movies WHERE id = :id");
    $del->execute([':id' => $id]);

    // Delete local poster file
    if (!empty($movie['poster'])
        && !str_starts_with($movie['poster'], 'http')
        && !str_starts_with($movie['poster'], '//')
        && file_exists($movie['poster'])
    ) {
        @unlink($movie['poster']);
    }

    $_SESSION['success'] = "Film \"" . htmlspecialchars($movie['title']) . "\" berhasil dihapus.";

} catch (PDOException $e) {
    $_SESSION['error'] = 'Gagal menghapus film: ' . $e->getMessage();
}

header('Location: index.php');
exit;
?>
