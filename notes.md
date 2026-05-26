# 🎬 CineList — Dokumentasi Website

> Dokumentasi lengkap untuk website manajemen daftar film berbasis PHP & MySQL.

---

## 📋 Daftar Isi

1. [Gambaran Umum](#gambaran-umum)
2. [Cara Instalasi](#cara-instalasi)
3. [Struktur File](#struktur-file)
4. [Struktur Database](#struktur-database)
5. [Sistem Autentikasi](#sistem-autentikasi)
6. [Halaman & Fitur](#halaman--fitur)
7. [Alur Pengguna](#alur-pengguna)
8. [CSS & Tampilan](#css--tampilan)

---

## Gambaran Umum

**CineList** adalah website manajemen daftar film dengan dua jenis pengguna:

| Jenis     | Akses                                                        |
| --------- | ------------------------------------------------------------ |
| **Admin** | Kelola film (tambah, edit, hapus), lihat dashboard statistik |
| **User**  | Daftar akun, login, simpan watchlist pribadi, ubah profil    |

**Teknologi yang digunakan:**

- Backend: PHP 8.x (PDO untuk koneksi database)
- Database: MySQL 8.x
- Frontend: HTML, CSS (external), JavaScript (vanilla)
- Server lokal: Laragon

---

## Konsep Autentikasi & Otorisasi

### Authentication — "Kamu orang sini kah?"

Proses memverifikasi **identitas** pengguna. Di CineList, ini dilakukan saat login:
username + password dicek ke database → kalau cocok, sesi dibuat → pengguna diakui.

### Authorization — "Kamu sebagai apa di sini?"

Setelah identitas dikenali, sistem mengecek **hak akses** berdasarkan role:

```
Masuk kantor (login)
    └── Karyawan biasa (role: user)  → boleh lihat film, simpan watchlist
    └── Manager (role: admin)        → boleh tambah, edit, hapus film
```

| Role    | Bisa apa                                          |
| ------- | ------------------------------------------------- |
| `user`  | Lihat film, simpan watchlist, ubah profil sendiri |
| `admin` | Semua yang user bisa + kelola seluruh data film   |

---

## Cara Instalasi

### Langkah 1 — Siapkan Database

1. Buka **phpMyAdmin** di browser (`http://localhost/phpmyadmin`)
2. Klik tab **Import**
3. Pilih file `data/movielist.sql`
4. Klik **Go** / **Kirim**

> File SQL otomatis membuat database `movielist`, semua tabel, dan akun default.

### Langkah 2 — Konfigurasi Koneksi Database

Buka file `config/db.php` dan sesuaikan jika perlu:

```php
$host     = 'localhost';
$dbname   = 'movielist';
$username = 'root';
$password = '';        // ganti jika MySQL kamu pakai password
```

### Langkah 3 — Akses Website

Buka browser dan kunjungi:

```
http://localhost/MovieList/
```

### Akun Default

| Role  | Username | Password   |
| ----- | -------- | ---------- |
| Admin | `admin`  | `admin123` |
| User  | `user1`  | `user123`  |

> **Penting:** Ganti password default setelah pertama kali login melalui halaman profil.

---

## Struktur File

```
MovieList/
│
├── assets/
│   └── style.css            # Semua CSS eksternal (satu file untuk semua halaman)
│
├── config/
│   └── db.php               # Konfigurasi koneksi database (PDO)
│
├── data/
│   └── movielist.sql        # File SQL untuk setup database lengkap
│
├── includes/
│   ├── auth.php             # Fungsi helper autentikasi (require_admin, is_logged_in, dll)
│   ├── navbar.php           # Komponen navbar (hanya untuk halaman publik)
│   └── footer.php           # Komponen footer
│
├── uploads/                 # Folder penyimpanan poster film yang diupload
│
├── index.php                # Halaman utama — daftar semua film (publik)
├── admin.php                # Dashboard admin — kelola film (khusus admin)
├── dashboard.php            # Dashboard user — profil & watchlist (khusus user login)
├── login.php                # Halaman login
├── register.php             # Halaman registrasi akun baru
├── logout.php               # Proses logout (hancurkan sesi)
│
├── add.php                  # Form tambah film baru (khusus admin)
├── edit.php                 # Form edit film (khusus admin)
├── delete.php               # Proses hapus satu film (khusus admin)
├── bulk_delete.php          # Proses hapus banyak film sekaligus (khusus admin)
├── watchlist.php            # API endpoint toggle/list watchlist (JSON, butuh login)
│
└── notes.md                 # File dokumentasi ini
```

---

## Struktur Database

Database bernama **`movielist`** berisi 3 tabel:

### Tabel `users`

Menyimpan data akun pengguna.

| Kolom        | Tipe                    | Keterangan                       |
| ------------ | ----------------------- | -------------------------------- |
| `id`         | INT, AUTO_INCREMENT, PK | ID unik pengguna                 |
| `username`   | VARCHAR(50), UNIQUE     | Nama pengguna (untuk login)      |
| `email`      | VARCHAR(100), UNIQUE    | Email (bisa dipakai untuk login) |
| `password`   | VARCHAR(255)            | Password ter-hash (bcrypt)       |
| `role`       | ENUM('admin','user')    | Peran pengguna                   |
| `created_at` | TIMESTAMP               | Waktu akun dibuat                |

### Tabel `movies`

Menyimpan data film.

| Kolom        | Tipe                    | Keterangan                  |
| ------------ | ----------------------- | --------------------------- |
| `id`         | INT, AUTO_INCREMENT, PK | ID unik film                |
| `title`      | VARCHAR(255)            | Judul film (wajib diisi)    |
| `director`   | VARCHAR(255)            | Nama sutradara              |
| `genre`      | VARCHAR(100)            | Genre film                  |
| `year`       | YEAR                    | Tahun rilis                 |
| `rating`     | DECIMAL(3,1)            | Rating 0.0–10.0             |
| `synopsis`   | TEXT                    | Sinopsis film               |
| `poster`     | TEXT                    | URL atau path gambar poster |
| `created_at` | TIMESTAMP               | Waktu film ditambahkan      |

### Tabel `watchlist`

Menghubungkan user dengan film yang disimpan di watchlist-nya.

| Kolom      | Tipe      | Keterangan                                |
| ---------- | --------- | ----------------------------------------- |
| `id`       | INT, PK   | ID unik                                   |
| `user_id`  | INT, FK   | Referensi ke `users.id` (CASCADE DELETE)  |
| `movie_id` | INT, FK   | Referensi ke `movies.id` (CASCADE DELETE) |
| `added_at` | TIMESTAMP | Waktu film ditambahkan ke watchlist       |

> Kombinasi `user_id` + `movie_id` bersifat **UNIQUE** — satu film hanya bisa disimpan satu kali per user.

---

## Sistem Autentikasi

### File Utama: `includes/auth.php`

Berisi 4 fungsi helper yang dipakai di seluruh website:

```php
require_admin()   // Redirect ke login.php jika bukan admin
require_login()   // Redirect ke login.php jika belum login
is_logged_in()    // Kembalikan true jika sudah login
is_admin()        // Kembalikan true jika login sebagai admin
```

### Session Variables

Setelah login berhasil, sesi menyimpan tiga data:

```php
$_SESSION['user_id']    // ID pengguna dari database
$_SESSION['username']   // Username pengguna
$_SESSION['user_role']  // 'admin' atau 'user'
```

### Proteksi Halaman

| File              | Perlindungan         | Siapa yang boleh akses |
| ----------------- | -------------------- | ---------------------- |
| `admin.php`       | `require_admin()`    | Admin saja             |
| `add.php`         | `require_admin()`    | Admin saja             |
| `edit.php`        | `require_admin()`    | Admin saja             |
| `delete.php`      | `require_admin()`    | Admin saja             |
| `bulk_delete.php` | `require_admin()`    | Admin saja             |
| `dashboard.php`   | `require_login()`    | Semua user yang login  |
| `watchlist.php`   | cek `is_logged_in()` | Semua user yang login  |
| `index.php`       | Tidak ada            | Publik (semua orang)   |
| `login.php`       | Tidak ada            | Publik (semua orang)   |
| `register.php`    | Tidak ada            | Publik (semua orang)   |

### Keamanan Password

Password **tidak pernah disimpan dalam bentuk asli**. Proses penyimpanan:

```
Password asli  →  password_hash()  →  hash bcrypt  →  disimpan di database
Login          →  password_verify() →  cocok/tidak
```

---

## Halaman & Fitur

### `index.php` — Halaman Utama (Publik)

Semua orang bisa mengakses halaman ini tanpa login.

**Fitur:**

- Tampilkan semua film dalam bentuk grid kartu
- Pencarian berdasarkan judul atau nama sutradara
- Filter berdasarkan genre
- Sorting berdasarkan rating, tahun, atau tanggal ditambahkan
- Sinopsis muncul saat hover di atas poster film
- Tombol watchlist (❤️/🤍) — klik redirect ke `login.php` jika belum login
- Tombol Edit & Hapus hanya muncul jika login sebagai **admin**

---

### `login.php` — Halaman Login

Halaman standalone tanpa navbar/footer.

**Fitur:**

- Login menggunakan username **atau** email
- Setelah login berhasil:
  - Admin → diarahkan ke `admin.php`
  - User → diarahkan ke `dashboard.php`
- Jika sudah login, otomatis redirect ke halaman yang sesuai

---

### `register.php` — Halaman Registrasi

Halaman standalone tanpa navbar/footer.

**Fitur:**

- Daftar akun baru (role selalu `user`, admin tidak bisa didaftar dari sini)
- Validasi: username minimal 3 karakter, hanya huruf/angka/underscore
- Validasi: email harus format yang valid dan belum dipakai
- Validasi: password minimal 6 karakter, konfirmasi harus cocok
- Setelah berhasil daftar → langsung login → diarahkan ke `dashboard.php`

---

### `admin.php` — Dashboard Admin

Hanya bisa diakses oleh admin. Tidak ada navbar — layout sidebar penuh.

**Fitur:**

- Statistik: total film, rata-rata rating, rating tertinggi, genre unik, tahun terbaru, jumlah film di watchlist
- Tabel film dengan kolom: poster, judul+sutradara, genre, tahun, rating, sinopsis, tanggal
- Pencarian & filter di atas tabel
- Sorting dengan klik header kolom (judul, tahun, rating)
- Paginasi — 10 film per halaman
- Hapus satu film dengan modal konfirmasi
- Bulk delete — centang banyak film lalu hapus sekaligus
- Sidebar berisi navigasi, filter genre, dan tombol logout

---

### `dashboard.php` — Dashboard User

Hanya bisa diakses oleh user yang sudah login. Tidak ada navbar — layout sidebar penuh.

**Fitur Sidebar:**

- Logo CineList (link ke halaman utama)
- Info user: avatar, username, email
- Navigasi: Halaman Utama, Pengaturan Profil
- Daftar watchlist — tampilkan poster mini + judul + tahun/rating
- Tombol ✕ untuk hapus film dari watchlist tanpa reload halaman
- Tombol Logout di bagian bawah

**Fitur Konten Utama:**

- Ubah Profil: ganti username dan email
- Ubah Password: isi password lama → password baru → konfirmasi

---

### `add.php` — Tambah Film (Admin)

Form untuk menambah film baru ke database.

**Field yang tersedia:**

- Judul (wajib diisi), Sutradara, Genre, Tahun Rilis, Rating (0–10)
- Sinopsis
- Poster: pilih dari URL eksternal atau upload file gambar (maks 5MB, format JPG/PNG/WebP/GIF)

---

### `edit.php` — Edit Film (Admin)

Form edit identik dengan `add.php`, tapi diisi otomatis dengan data film yang sudah ada. Bisa ganti poster.

---

### `delete.php` — Hapus Film (Admin)

Bukan halaman tampilan, tapi proses handler yang:

1. Validasi ID film
2. Hapus semua entri watchlist yang terkait film ini
3. Hapus file poster lokal (jika bukan URL eksternal)
4. Hapus data film dari database
5. Redirect kembali ke halaman sebelumnya

---

### `watchlist.php` — API Watchlist

Bukan halaman tampilan — hanya menerima request dan mengembalikan JSON. Butuh login.

**Actions:**

- `POST action=toggle&movie_id=X` → tambah atau hapus film dari watchlist
- `GET action=list` → kembalikan daftar ID film di watchlist milik user

**Contoh response:**

```json
{ "ok": true, "saved": true }               // film berhasil ditambahkan
{ "ok": true, "saved": false }              // film berhasil dihapus
{ "ok": false, "error": "not_logged_in" }   // belum login
```

---

## Alur Pengguna

### Alur Admin

```
Buka website
→ login.php → isi username + password admin
→ redirect ke admin.php
→ kelola film: tambah (add.php) / edit (edit.php) / hapus (delete.php)
→ logout → kembali ke login.php
```

### Alur User Baru

```
Buka website
→ register.php → isi form pendaftaran
→ otomatis login → redirect ke dashboard.php
→ buka index.php → tekan ❤️ di film untuk simpan ke watchlist
→ kembali ke dashboard.php → film muncul di sidebar watchlist
→ dashboard.php → ubah username / email / password
```

### Alur User Lama

```
Buka website
→ login.php → isi username + password
→ redirect ke dashboard.php
→ lihat & kelola watchlist di sidebar
→ ubah profil di konten utama
```

---

## CSS & Tampilan

Semua CSS tersimpan dalam satu file: **`assets/style.css`**

File ini dibagi menjadi beberapa seksi dengan komentar pemisah:

| Seksi komentar     | Keterangan                                      |
| ------------------ | ----------------------------------------------- |
| `:root`            | CSS variables — warna, spacing, shadow, radius  |
| `NAVBAR`           | Komponen navbar untuk halaman publik            |
| `MAIN LAYOUT`      | Layout utama halaman publik                     |
| `PAGE HEADER`      | Judul halaman dan subtitle                      |
| `SEARCH BAR`       | Komponen pencarian dan filter                   |
| `MOVIE GRID`       | Grid kartu film                                 |
| `EMPTY STATE`      | Tampilan saat tidak ada film ditemukan          |
| `FORM PAGE`        | Form tambah/edit film                           |
| `ALERT`            | Notifikasi sukses dan error                     |
| `MODAL`            | Dialog konfirmasi hapus                         |
| `FOOTER`           | Komponen footer                                 |
| `RESPONSIVE`       | Media query untuk layar kecil (mobile)          |
| `SYNOPSIS OVERLAY` | Overlay sinopsis saat hover kartu film          |
| `BOOKMARK BUTTON`  | Tombol watchlist di pojok kartu film            |
| `AUTH PAGES`       | Halaman login & register (standalone)           |
| `SHARED SIDEBAR`   | Sidebar yang dipakai admin & dashboard          |
| `ADMIN DASHBOARD`  | Layout & komponen khusus halaman admin          |
| `USER DASHBOARD`   | Layout & komponen khusus halaman dashboard user |

**Tema:** Dark mode sinematik dengan aksen warna emas (`#f5a623`).

**Font yang digunakan:**

- Judul: `Playfair Display` (serif — kesan sinematik dan elegan)
- Body: `DM Sans` (sans-serif — bersih dan mudah dibaca)
