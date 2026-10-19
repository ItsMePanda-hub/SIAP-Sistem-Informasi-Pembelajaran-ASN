# SIAP

SIAP (Sistem Informasi dan Pembelajaran ASN) adalah portal internal untuk membantu pegawai mengakses informasi, surat, tugas, pelatihan, dan ujian dalam satu aplikasi.

Aplikasi ini dibuat dengan Laravel 10, Blade, Tailwind CSS, dan MySQL.

## Fitur

- Dashboard ringkasan aktivitas pengguna.
- Pengumuman berdasarkan unit kerja.
- Surat dan dokumen digital yang dapat diunduh.
- Workspace untuk membuat tugas, mengirim hasil pekerjaan, dan melakukan review.
- Pelatihan dengan materi, status progres, dan sertifikat setelah selesai.
- Perpustakaan pembelajaran dengan fitur baca, progres, bookmark, dan publikasi materi.
- Ujian pilihan ganda dan esai.
- Penilaian pilihan ganda otomatis dan penilaian esai oleh admin atau atasan.
- Deteksi perpindahan tab saat ujian dengan sistem peringatan bertingkat.
- Notifikasi aplikasi dan web push.
- Manajemen pengguna dan pembagian unit kerja.
- Tim saya untuk atasan.
- Log aktivitas.
- Chatbot SIAP.

## Hak Akses

| Role | Akses |
|---|---|
| Admin | Mengelola seluruh data dan pengguna. |
| Pemilik | Melihat dan mengelola data lintas unit kerja. |
| Atasan | Mengelola data dalam unit kerja sendiri, termasuk tugas, pelatihan, dan penilaian. |
| Pengguna | Mengakses pengumuman, surat, tugas, pelatihan, perpustakaan, dan ujian yang ditujukan kepadanya. |

## Alur Penggunaan

### 1. Membuka aplikasi

Buka alamat aplikasi melalui browser. Pengguna yang belum masuk akan diarahkan ke halaman login.

### 2. Login

Masukkan email dan kata sandi, lalu tekan **Masuk**. Jika belum memiliki akun, pilih **Daftar**. Jika lupa kata sandi, gunakan menu **Lupa kata sandi**.

Setelah login berhasil, pengguna masuk ke halaman **Dashboard**. Menu yang tampil menyesuaikan role dan hak akses pengguna.

### 3. Mengatur profil

Buka menu profil untuk memperbarui nama, NIP, jabatan, unit kerja, email, atau kata sandi.

### 4. Membaca pengumuman

Buka menu **Pengumuman**. Pilih judul pengumuman untuk membaca isi lengkapnya. Sistem mencatat pengumuman yang sudah dibaca.

Admin, pemilik, dan atasan dapat membuat pengumuman. Atasan hanya dapat mengelola pengumuman untuk unit kerja yang menjadi tanggung jawabnya.

### 5. Mengakses surat dan dokumen

Buka menu **Surat** untuk melihat dokumen yang tersedia. Pilih **Unduh** pada dokumen yang diperlukan. Dokumen ditampilkan sesuai target unit kerja pengguna.

Admin dan atasan dapat mengunggah surat atau dokumen baru.

### 6. Mengerjakan tugas

Buka menu **Workspace** untuk melihat tugas yang diberikan. Pilih tugas untuk membaca instruksi dan mengunduh lampiran. Setelah selesai, unggah hasil pekerjaan melalui halaman tugas.

Atasan dapat memeriksa hasil pekerjaan dan memberikan review. Pengguna dapat melihat status pengumpulan dan hasil review.

### 7. Mengikuti pelatihan

Buka menu **Pelatihan**, lalu pilih pelatihan yang tersedia. Baca atau unduh materi, tekan **Mulai**, dan pelajari materi sampai selesai. Setelah semua tahapan selesai, tekan **Selesaikan pelatihan**.

Sertifikat dapat diakses setelah pelatihan dinyatakan selesai.

### 8. Membaca materi perpustakaan

Buka menu **Perpustakaan** untuk mencari materi pembelajaran. Pilih materi, lalu tekan **Baca**. Posisi bacaan dan progres tersimpan selama pengguna mempelajari materi.

Materi penting dapat disimpan melalui fitur **Bookmark**. Admin atau pengguna yang memiliki hak akses dapat membuat, mengubah, menerbitkan, atau menyembunyikan materi.

### 9. Mengikuti ujian

Buka menu **Ujian**, pilih ujian yang tersedia, lalu baca detailnya. Tekan **Mulai ujian** untuk memulai pengerjaan.

Selama ujian:

1. Jawaban tersimpan melalui sistem.
2. Perpindahan tab atau halaman dapat dicatat sebagai pelanggaran.
3. Sistem memberikan peringatan secara bertahap.
4. Jawaban dikirim melalui tombol **Kumpulkan ujian**.

Nilai pilihan ganda dihitung otomatis. Jawaban esai menunggu penilaian dari admin atau atasan. Jika terdapat pelanggaran, pengguna dapat mengajukan banding melalui halaman hasil ujian.

### 10. Melihat notifikasi

Buka ikon notifikasi untuk melihat informasi baru, seperti pengumuman, tugas, pelatihan, atau ujian. Notifikasi dapat ditandai sudah dibaca satu per satu atau sekaligus.

### 11. Keluar dari aplikasi

Buka menu profil, lalu pilih **Keluar** setelah selesai menggunakan aplikasi.

## Instalasi Lokal

### Persyaratan

- PHP 8.1 atau lebih baru
- Composer
- Node.js dan NPM
- MySQL
- Laragon atau web server lain

### Langkah instalasi

1. Clone atau salin project ke folder web server.
2. Masuk ke folder project:

```bash
cd SIAP
```

3. Pasang dependency PHP:

```bash
composer install
```

4. Pasang dependency frontend:

```bash
npm install
```

5. Salin file environment:

```bash
copy .env.example .env
```

Pada Linux atau macOS, gunakan:

```bash
cp .env.example .env
```

6. Buat database MySQL, lalu sesuaikan bagian berikut di `.env`:

```env
DB_DATABASE=siap
DB_USERNAME=root
DB_PASSWORD=
```

7. Buat application key:

```bash
php artisan key:generate
```

8. Jalankan migration:

```bash
php artisan migrate
```

9. Buat symbolic link untuk file upload:

```bash
php artisan storage:link
```

10. Build aset frontend:

```bash
npm run build
```

11. Jalankan aplikasi saat menggunakan server bawaan Laravel:

```bash
php artisan serve
```

Untuk pengembangan frontend, gunakan terminal lain:

```bash
npm run dev
```

## Membuat Akun Admin Pertama

Daftar akun melalui halaman `/register`, lalu ubah role akun tersebut melalui Tinker:

```bash
php artisan tinker
```

```php
$user = App\Models\User::where('email', 'emailmu@contoh.com')->first();
$user->role = 'admin';
$user->save();
```

Setelah itu, login menggunakan akun tersebut dan kelola pengguna lain dari menu **Pengguna**.

## Struktur Utama

```text
app/
├── Http/Controllers/     Controller untuk setiap fitur
├── Models/               Model database

database/
├── migrations/           Struktur tabel database
├── seeders/              Data awal

resources/
├── views/                Halaman Blade
├── css/                  Style aplikasi
├── js/                   JavaScript aplikasi

routes/
├── web.php               Route aplikasi
├── auth.php              Route autentikasi
```

## Perintah Pengembangan

```bash
php artisan migrate       # Menjalankan migration
php artisan route:list    # Melihat daftar route
php artisan test          # Menjalankan pengujian
npm run build             # Build aset frontend
npm run dev               # Menjalankan Vite dalam mode pengembangan
```

## Catatan

Akses data dibatasi berdasarkan role dan unit kerja. Jangan membagikan file `.env`, kredensial database, atau kunci aplikasi ke repositori publik.
