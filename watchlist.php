<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';
require_once 'includes/auth.php';
header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['ok' => false, 'error' => 'not_logged_in']);
    exit;
}

$action   = $_POST['action']   ?? $_GET['action']   ?? '';
$movie_id = intval($_POST['movie_id'] ?? $_GET['movie_id'] ?? 0);
$uid      = (int) $_SESSION['user_id'];

if ($action !== 'list' && !$movie_id) {
    echo json_encode(['ok' => false, 'error' => 'Invalid movie_id']);
    exit;
}

try {
    if ($action === 'toggle') {
        $chk = $pdo->prepare("SELECT id FROM watchlist WHERE movie_id = :m AND user_id = :u");
        $chk->execute([':m' => $movie_id, ':u' => $uid]);
        $exists = $chk->fetch();

        if ($exists) {
            $pdo->prepare("DELETE FROM watchlist WHERE movie_id = :m AND user_id = :u")->execute([':m' => $movie_id, ':u' => $uid]);
            echo json_encode(['ok' => true, 'saved' => false]);
        } else {
            $pdo->prepare("INSERT IGNORE INTO watchlist (user_id, movie_id) VALUES (:u, :m)")->execute([':u' => $uid, ':m' => $movie_id]);
            echo json_encode(['ok' => true, 'saved' => true]);
        }

    } elseif ($action === 'list') {
        $rows = $pdo->prepare("SELECT movie_id FROM watchlist WHERE user_id = :u");
        $rows->execute([':u' => $uid]);
        $ids = $rows->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode(['ok' => true, 'ids' => $ids]);

    } else {
        echo json_encode(['ok' => false, 'error' => 'Unknown action']);
    }
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
