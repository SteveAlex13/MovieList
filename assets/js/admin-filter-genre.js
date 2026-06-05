// Genre multi-select dropdown for admin sidebar
// Requires ADMIN_SEARCH and ADMIN_SORT to be defined before this script loads.

function toggleGenreDropdown(e) {
    e.stopPropagation();
    document.getElementById('genreMultiPanel').classList.toggle('open');
}

document.addEventListener('click', function(e) {
    const wrap = document.getElementById('genreMultiWrap');
    if (wrap && !wrap.contains(e.target)) {
        document.getElementById('genreMultiPanel').classList.remove('open');
    }
});

function applyGenreFilter() {
    const genres = [...document.querySelectorAll('.genre-cb:checked')].map(c => c.value);
    const params = new URLSearchParams();
    if (ADMIN_SEARCH) params.set('search', ADMIN_SEARCH);
    if (ADMIN_SORT)   params.set('sort', ADMIN_SORT);
    params.set('page', '1');
    genres.forEach(g => params.append('genres[]', g));
    window.location.href = 'admin.php?' + params.toString();
}

function removeGenreTag(val) {
    const cb = [...document.querySelectorAll('.genre-cb')].find(c => c.value === val);
    if (cb) { cb.checked = false; applyGenreFilter(); }
}
