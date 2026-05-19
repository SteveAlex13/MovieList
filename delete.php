<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';

$id = intval($_GET['id'] ?? 0);

if (!$id) {
    $_SESSION['error'] = 'ID film tidak valid.';
    header('Location: index.php');
    exit;
}

// Fetch movie to get poster path & title
$stmt = $pdo->prepare("SELECT title, poster FROM movies WHERE id = :id");
$stmt->execute([':id' => $id]);
$movie = $stmt->fetch();

if (!$movie) {
    $_SESSION['error'] = 'Film tidak ditemukan.';
    header('Location: index.php');
    exit;
}

// Delete uploaded poster file if local
if (!empty($movie['poster']) && !str_starts_with($movie['poster'], 'http') && file_exists($movie['poster'])) {
    @unlink($movie['poster']);
}

// Delete from DB
$stmt = $pdo->prepare("DELETE FROM movies WHERE id = :id");
$stmt->execute([':id' => $id]);

$_SESSION['success'] = "Film \"" . $movie['title'] . "\" berhasil dihapus.";
header('Location: index.php');
exit;
?>
