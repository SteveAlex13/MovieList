<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';
header('Content-Type: application/json');

$action   = $_POST['action']   ?? $_GET['action']   ?? '';
$movie_id = intval($_POST['movie_id'] ?? $_GET['movie_id'] ?? 0);
$sid      = session_id();

if (!$movie_id) {
    echo json_encode(['ok' => false, 'error' => 'Invalid movie_id']);
    exit;
}

// Ensure watchlist table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS watchlist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        movie_id INT NOT NULL,
        session_id VARCHAR(128) NOT NULL,
        added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_watchlist (movie_id, session_id)
    )");
} catch (PDOException $e) {}

try {
    if ($action === 'toggle') {
        // Check if exists
        $chk = $pdo->prepare("SELECT id FROM watchlist WHERE movie_id=:m AND session_id=:s");
        $chk->execute([':m' => $movie_id, ':s' => $sid]);
        $exists = $chk->fetch();

        if ($exists) {
            $pdo->prepare("DELETE FROM watchlist WHERE movie_id=:m AND session_id=:s")->execute([':m'=>$movie_id,':s'=>$sid]);
            echo json_encode(['ok' => true, 'saved' => false]);
        } else {
            $pdo->prepare("INSERT IGNORE INTO watchlist (movie_id, session_id) VALUES (:m, :s)")->execute([':m'=>$movie_id,':s'=>$sid]);
            echo json_encode(['ok' => true, 'saved' => true]);
        }

    } elseif ($action === 'list') {
        $rows = $pdo->prepare("SELECT movie_id FROM watchlist WHERE session_id=:s");
        $rows->execute([':s' => $sid]);
        $ids = $rows->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode(['ok' => true, 'ids' => $ids]);

    } else {
        echo json_encode(['ok' => false, 'error' => 'Unknown action']);
    }
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
