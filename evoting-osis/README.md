# E-Voting Ketua OSIS

Aplikasi voting ringan berbasis PHP 8 Native, MySQL, Bootstrap 5, Chart.js, dan dukungan PhpSpreadsheet.

UI menggunakan identitas SMKN 20 Jakarta: logo sekolah, logo OSIS, logo MPK, palet biru resmi, aksen gold, dan layout responsif untuk desktop/tablet/mobile.

## Cara Menjalankan di Laragon/XAMPP

1. Salin folder `evoting-osis` ke `htdocs` atau `www`.
2. Pastikan Apache dan MySQL aktif.
3. Buka `http://localhost/evoting-osis/setup.php`.
4. Klik `Jalankan Setup`.
5. Login admin di `http://localhost/evoting-osis/admin/login.php`.

Admin default:

- Username: `admin`
- Password: `admin123`

Ganti password admin langsung dari database sebelum digunakan serius.

## Preview UI

Untuk melihat tampilan landing page tanpa server PHP, buka file:

```text
preview.html
```

Preview ini hanya untuk tampilan. Aplikasi voting penuh tetap dijalankan melalui Apache/PHP di Laragon atau XAMPP.

## Aset Identitas

Logo resmi tersimpan di:

- `assets/img/logo-smkn20.png`
- `assets/img/logo-osis-smkn20.png`
- `assets/img/logo-mpk-smkn20.png`

Hero landing memakai `assets/img/school-building-hero.png`. Jika memiliki foto gedung SMKN 20 Jakarta asli, ganti file tersebut dengan nama yang sama.

## Import Pemilih

Format file:

```text
Jenis | NIS/NIP | Nama | Kelas
```

Jenis hanya boleh `Siswa` atau `Guru`. Untuk guru, kelas boleh kosong.

Import memakai PhpSpreadsheet jika `composer install` sudah dijalankan. Jika belum, aplikasi tetap mendukung CSV dan XLSX sederhana.

## Composer

Untuk dukungan Excel penuh:

```bash
composer install
```

## Alur Pemilih

Landing Page -> Login NIS/NIP -> Halaman Kandidat -> Konfirmasi -> Terima Kasih -> kembali ke Login.

Pemilih yang sudah memilih tidak dapat login kembali.

## Catatan Keamanan

- Semua query utama memakai prepared statement PDO.
- Admin memakai session.
- Form penting memakai CSRF token.
- Tabel `voting.pemilih_id` dibuat unique agar satu pemilih hanya punya satu suara.
- Pesan error database tidak ditampilkan langsung ke pengguna.
