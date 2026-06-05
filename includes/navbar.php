<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth.php';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!-- NAVBAR -->
<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="nav-brand">
            <span class="brand-icon">🎬</span>
            <span class="brand-text">CineList</span>
        </a>
        <div class="nav-links">
            <a href="index.php" class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
                🎞️ Semua Film
            </a>

            <?php if (is_admin()): ?>
                <a href="admin.php" class="nav-link <?= $currentPage === 'admin.php' ? 'active' : '' ?>">
                    📊 Dashboard Admin
                </a>
                <a href="add.php" class="nav-link btn-add <?= $currentPage === 'add.php' ? 'active' : '' ?>">
                    ➕ Tambah Film
                </a>
            <?php endif; ?>

            <?php if (is_logged_in() && !is_admin()): ?>
                <a href="dashboard.php" class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                    👤 <?= htmlspecialchars($_SESSION['username']) ?>
                </a>
                <a href="logout.php" class="nav-link" style="color:var(--red,#ff4757);">
                    🚪 Logout
                </a>
            <?php elseif (is_logged_in() && is_admin()): ?>
                <a href="admin.php" class="nav-link">
                    👤 <?= htmlspecialchars($_SESSION['username']) ?>
                </a>
                <a href="logout.php" class="nav-link" style="color:var(--red,#ff4757);">
                    🚪 Logout
                </a>
            <?php else: ?>
                <a href="login.php" class="nav-link <?= $currentPage === 'login.php' ? 'active' : '' ?>">
                    🔐 Login
                </a>
                <a href="register.php" class="nav-link btn-add <?= $currentPage === 'register.php' ? 'active' : '' ?>">
                    📝 Daftar
                </a>
            <?php endif; ?>
        </div>
        <button class="hamburger" id="hamburger" onclick="toggleMenu()">
            <span></span><span></span><span></span>
        </button>
    </div>
    <div class="mobile-menu" id="mobileMenu">
        <a href="index.php" class="mobile-link">🎞️ Semua Film</a>
        <?php if (is_admin()): ?>
            <a href="admin.php" class="mobile-link">📊 Dashboard Admin</a>
            <a href="add.php"   class="mobile-link">➕ Tambah Film</a>
        <?php endif; ?>
        <?php if (is_logged_in() && !is_admin()): ?>
            <a href="dashboard.php" class="mobile-link">👤 <?= htmlspecialchars($_SESSION['username']) ?></a>
            <a href="logout.php" class="mobile-link" style="color:var(--red,#ff4757);">🚪 Logout</a>
        <?php elseif (is_logged_in() && is_admin()): ?>
            <a href="admin.php" class="mobile-link">👤 <?= htmlspecialchars($_SESSION['username']) ?></a>
            <a href="logout.php" class="mobile-link" style="color:var(--red,#ff4757);">🚪 Logout</a>
        <?php else: ?>
            <a href="login.php"    class="mobile-link">🔐 Login</a>
            <a href="register.php" class="mobile-link">📝 Daftar</a>
        <?php endif; ?>
    </div>
</nav>
<script>
function toggleMenu() {
    document.getElementById('mobileMenu').classList.toggle('open');
}
</script>
