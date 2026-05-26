<?php

// function ini berfungsi untuk menentukan kamu itu admin atau bukan
function require_admin(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        $_SESSION['error'] = 'Akses ditolak. Login sebagai admin.';
        header('Location: login.php');
        exit;
    }
}

// semisal kita mau pake fitur watchlist
function require_login(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user_id'])) {
        $_SESSION['error'] = 'Silakan login terlebih dahulu.';
        header('Location: login.php');
        exit;
    }
}

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function is_admin(): bool {
    return !empty($_SESSION['user_id']) && $_SESSION['user_role'] === 'admin';
}
