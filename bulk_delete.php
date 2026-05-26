<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';

$idsRaw = $_POST['ids'] ?? '';
$ids = array_filter(array_map('intval', explode(',', $idsRaw)), fn($v) => $v > 0);

if (empty($ids)) {
    $_SESSION['error'] = 'Tidak ada film yang dipilih.';
    header('Location: admin.php');
    exit;
}

try {
    // Fetch posters before delete
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, poster FROM movies WHERE id IN ($placeholders)");
    $stmt->execute(array_values($ids));
    $rows = $stmt->fetchAll();

    // Delete watchlist entries
    try {
        $pdo->prepare("DELETE FROM watchlist WHERE movie_id IN ($placeholders)")->execute(array_values($ids));
    } catch (PDOException $e) {}

    // Delete movies
    $pdo->prepare("DELETE FROM movies WHERE id IN ($placeholders)")->execute(array_values($ids));

    // Remove local poster files
    foreach ($rows as $r) {
        if (!empty($r['poster']) && !str_starts_with($r['poster'], 'http') && file_exists($r['poster'])) {
            @unlink($r['poster']);
        }
    }

    $_SESSION['success'] = count($ids) . " film berhasil dihapus.";
} catch (PDOException $e) {
    $_SESSION['error'] = 'Gagal menghapus: ' . $e->getMessage();
}

header('Location: admin.php');
exit;
