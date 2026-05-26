<!-- NAVBAR -->
<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="nav-brand">
            <span class="brand-icon">🎬</span>
            <span class="brand-text">CineList</span>
        </a>
        <div class="nav-links">
            <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                🎞️ Semua Film
            </a>
            <a href="admin.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'active' : '' ?>">
                📊 Admin
            </a>
            <a href="add.php" class="nav-link btn-add <?= basename($_SERVER['PHP_SELF']) == 'add.php' ? 'active' : '' ?>">
                ➕ Tambah Film
            </a>
        </div>
        <button class="hamburger" id="hamburger" onclick="toggleMenu()">
            <span></span><span></span><span></span>
        </button>
    </div>
    <div class="mobile-menu" id="mobileMenu">
        <a href="index.php"  class="mobile-link">🎞️ Semua Film</a>
        <a href="admin.php"  class="mobile-link">📊 Admin Dashboard</a>
        <a href="add.php"    class="mobile-link">➕ Tambah Film</a>
    </div>
</nav>
<script>
function toggleMenu() {
    document.getElementById('mobileMenu').classList.toggle('open');
}
</script>
