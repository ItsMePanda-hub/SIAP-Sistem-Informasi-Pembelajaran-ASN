# SIAP — Paket Kode Final (Sesuai yang Kita Bangun Bersama)

Paket ini adalah versi bersih dari seluruh kode yang sudah kita bangun dan uji langkah demi langkah di percakapan sebelumnya — mencakup Fase 1 (Pengumuman, Surat), Fase 2 (Pelatihan, Ujian), dan penyempurnaan role (admin/pemilik/atasan/pegawai + target bidang).

**PENTING:** File `SIAP.zip` yang terakhir kamu unggah punya struktur berbeda (nama model/controller berbeda: `Document`, `Question`, `Option`, `ExamViolation`, `UserTrainingProgress`, dst) — kemungkinan besar hasil dari sumber lain yang tidak nyambung dengan histori kerja kita. **Paket ini menggantikan isi project itu**, bukan menggabungkannya. Ikuti langkah di bawah untuk menyamakan project Laragon kamu dengan versi final ini.

## Cara pakai — mulai dari project Laravel yang bersih

Supaya tidak ada sisa file lama yang bikin bingung (`Document.php`, `Question.php`, dst), paling aman mulai dari project baru:

1. Backup dulu folder `SIAP` yang lama (rename jadi `SIAP-lama` misalnya), jangan dihapus dulu.
2. Buat project baru di Laragon:
   ```
   composer create-project laravel/laravel SIAP
   cd SIAP
   ```
3. Buat ulang database `siap` (boleh pakai yang lama, tinggal di-drop-and-recreate lewat phpMyAdmin biar bersih), sesuaikan `.env`.
4. Pasang Breeze:
   ```
   composer require laravel/breeze --dev
   php artisan breeze:install blade
   npm install && npm run build
   ```
5. Tambahkan warna kustom ke `tailwind.config.js`, di dalam `theme.extend`:
   ```js
   colors: {
       primary: { DEFAULT: '#3E7C8C', dark: '#2C5F6D', light: '#E7F1F3' },
       accent: { DEFAULT: '#C9A15C', light: '#FAF3E6' },
   },
   ```
6. **Salin semua file dari paket ini** ke lokasi yang sama persis di project baru:

   | Dari paket ini | Ke project Laravel |
   |---|---|
   | `database/migrations/*.php` | `database/migrations/` |
   | `app/Models/*.php` | `app/Models/` (timpa `User.php` yang sudah ada) |
   | `app/Http/Controllers/*.php` | `app/Http/Controllers/` |
   | `resources/views/layouts/app.blade.php` | `resources/views/layouts/` (timpa) |
   | `resources/views/dashboard.blade.php` | `resources/views/` (timpa) |
   | `resources/views/announcements/` | `resources/views/announcements/` |
   | `resources/views/letters/` | `resources/views/letters/` |
   | `resources/views/trainings/` | `resources/views/trainings/` |
   | `resources/views/exams/` | `resources/views/exams/` |
   | `resources/views/users/` | `resources/views/users/` |

7. Untuk `routes/siap-web.php`: buka isinya, salin bagian route (bukan bagian `use`) ke dalam `routes/web.php`, gabungkan baris `use` di paling atas.
8. Migrate:
   ```
   php artisan migrate
   php artisan storage:link
   npm run build
   ```
9. Buat akun pertama lewat `/register`, lalu jadikan admin lewat Tinker:
   ```
   php artisan tinker
   ```
   ```php
   $user = App\Models\User::where('email', 'emailmu@contoh.com')->first();
   $user->role = 'admin';
   $user->save();
   ```

## Struktur final (ringkasan)

- **User**: `nip`, `jabatan`, `unit_kerja`, `role` (pegawai/atasan/pemilik/admin), `atasan_id`, `status_kepegawaian`
- **Announcement** + **AnnouncementRead**: pengumuman dengan tanda terima elektronik, target bidang
- **Letter**: surat digital (PDF), target bidang
- **Training** + **TrainingProgress**: pelatihan dengan sertifikat otomatis, target bidang
- **Exam** + **ExamQuestion** + **ExamOption** + **ExamAttempt** + **ExamAnswer**: ujian pilihan ganda/esai, anti-kecurangan bertingkat, skor otomatis, penilaian esai manual

Semua modul menerapkan pembatasan akses berbasis peran dan bidang (`target_unit_kerja`) yang sudah diuji: atasan hanya melihat/mengelola bidangnya sendiri, admin dan pemilik memiliki akses lintas-bidang.

## Pemetaan lengkap semua file dalam paket ini

### Migrations (`database/migrations/`)
| File | Isi |
|---|---|
| `2026_01_01_000001_add_kepegawaian_fields_to_users_table.php` | Menambah kolom `nip`, `jabatan`, `unit_kerja`, `atasan_id`, `status_kepegawaian` ke tabel `users` |
| `2026_01_02_000001_update_role_enum_add_pemilik_to_users_table.php` | Menambah nilai `pemilik` ke enum `role` |
| `2026_01_03_000001_create_announcements_table.php` | Tabel `announcements` |
| `2026_01_03_000002_create_announcement_reads_table.php` | Tabel `announcement_reads` (tanda terima elektronik) |
| `2026_01_04_000001_create_letters_table.php` | Tabel `letters` |
| `2026_01_05_000001_create_trainings_table.php` | Tabel `trainings` |
| `2026_01_05_000002_create_training_progresses_table.php` | Tabel `training_progresses` |
| `2026_01_06_000001_create_exams_table.php` | Tabel `exams` |
| `2026_01_06_000002_create_exam_questions_table.php` | Tabel `exam_questions` |
| `2026_01_06_000003_create_exam_options_table.php` | Tabel `exam_options` |
| `2026_01_06_000004_create_exam_attempts_table.php` | Tabel `exam_attempts` |
| `2026_01_06_000005_create_exam_answers_table.php` | Tabel `exam_answers` |

Jalankan sesuai urutan nama file (sudah diberi prefix tanggal supaya urut otomatis) — jangan diubah namanya karena Laravel menjalankan migration berurutan sesuai timestamp di nama file.

### Models (`app/Models/`)
| File | Menggantikan file lama di zip barumu | Catatan |
|---|---|---|
| `User.php` | `User.php` | Timpa — tambahan relasi `bawahan()`, `atasan()`, helper `isSystemWide()` |
| `Announcement.php` | `Announcement.php` | — |
| `AnnouncementRead.php` | (tidak ada) | Baru, untuk tanda terima elektronik |
| `Letter.php` | `Document.php` | **Ganti nama** — pakai `Letter.php`, hapus `Document.php` |
| `Training.php` | `Training.php` | — |
| `TrainingProgress.php` | `UserTrainingProgress.php` | **Ganti nama** — pakai `TrainingProgress.php`, hapus `UserTrainingProgress.php` |
| `Exam.php` | `Exam.php` | — |
| `ExamQuestion.php` | `Question.php` | **Ganti nama**, hapus `Question.php` |
| `ExamOption.php` | `Option.php` | **Ganti nama**, hapus `Option.php` |
| `ExamAttempt.php` | `ExamViolation.php` (fungsinya digabung) | **Ganti nama**, hapus `ExamViolation.php` — pelanggaran sudah ditangani sebagai kolom di `ExamAttempt`, bukan tabel terpisah |
| `ExamAnswer.php` | `Answer.php` | **Ganti nama**, hapus `Answer.php` |

### Controllers (`app/Http/Controllers/`)
| File | Menggantikan | Catatan |
|---|---|---|
| `DashboardController.php` | `DashboardController.php` | — |
| `AnnouncementController.php` | `AnnouncementController.php` | — |
| `LetterController.php` | `DocumentController.php` | **Ganti nama**, hapus `DocumentController.php` |
| `TrainingController.php` | `TrainingController.php` | — |
| `ExamController.php` | `ExamController.php` | Yang paling kompleks — logic mulai ujian, auto-save jawaban, deteksi pelanggaran bertingkat, auto-submit, skor otomatis, penilaian esai manual |
| `UserController.php` | (tidak ada / berbeda) | Baru — kelola pengguna oleh admin (assign role: pemilik/atasan/pegawai) |

### Routes
`routes/siap-web.php` — **bukan file yang dipanggil langsung oleh Laravel**, ini hanya kontainer sementara. Salin isi bagian route (di bawah bagian `use ...;`) ke dalam `routes/web.php` project barumu, lalu gabungkan semua baris `use` di paling atas file.

### Views (`resources/views/`)
| Folder/File | Isi |
|---|---|
| `layouts/app.blade.php` | Layout utama sidebar (teal + gold), menggantikan layout default Breeze |
| `dashboard.blade.php` | Dashboard ringkasan |
| `announcements/index.blade.php`, `show.blade.php`, `create.blade.php` | Modul Pengumuman lengkap dengan status pembacaan |
| `letters/index.blade.php` | Modul Surat & Dokumen (upload + daftar) |
| `trainings/index.blade.php`, `show.blade.php`, `create.blade.php` | Modul Pelatihan + sertifikat |
| `exams/index.blade.php`, `show.blade.php`, `create.blade.php`, `take.blade.php`, `results.blade.php` | Modul Ujian: daftar, detail, buat soal (dinamis dengan Alpine.js), pengerjaan ujian (dengan deteksi pindah tab), dan halaman penilaian hasil |
| `users/index.blade.php`, `edit.blade.php` | Kelola pengguna oleh admin (assign role & bidang) |

File-file view lama yang berhubungan dengan `Document`, `Question`, `Option` di folder `resources/views/` (kalau ada) juga sebaiknya dihapus supaya tidak ada route/view yang nyasar ke controller lama.

## Menyamakan desain (warna, font) dengan yang sudah kita bangun

File-file layout yang sudah ada (sidebar dashboard) sudah pakai skema warna ini, tapi **`tailwind.config.js` dan halaman login/register belum pernah disamakan** — makanya kalau masih terlihat "default Breeze" (biru-indigo, abu-abu gelap), itu sebabnya. Timpa file-file berikut:

| File | Fungsi |
|---|---|
| `tailwind.config.js` | **Timpa seluruh file** — mendaftarkan warna `primary` (teal `#3E7C8C`) dan `accent` (emas `#C9A15C`), plus font `Sora` (display) dan `Plus Jakarta Sans` (body) |
| `resources/views/layouts/guest.blade.php` | Layout untuk halaman login/register — background `#F7FAFB`, kartu putih rounded, logo SIAP, sebelumnya default Breeze |
| `resources/views/auth/login.blade.php` | Halaman login, diganti total ke skema teal/gold |
| `resources/views/auth/register.blade.php` | Halaman register, sama |
| `resources/views/components/primary-button.blade.php` | Tombol utama — warna dari `bg-gray-800` (default) jadi `bg-primary` |
| `resources/views/components/text-input.blade.php` | Input field — focus ring dari indigo jadi `primary` |
| `resources/views/components/checkbox.blade.php` | Checkbox "Ingat saya" — warna `primary` |
| `resources/views/components/input-label.blade.php` | Label form — warna teks disamakan |

**Setelah menyalin semua file di atas**, wajib jalankan ulang:
```
npm run build
```
Tailwind cuma generate class warna yang benar-benar dipakai/terdaftar di config — kalau lupa build ulang, warnanya nggak akan berubah walau file sudah ditimpa.

## Kalau tidak ingin mulai dari nol

Kalau kamu lebih memilih membersihkan project `SIAP` yang sekarang (bukan bikin baru), hapus dulu file-file yang tidak sesuai (`Document.php`, `Question.php`, `Option.php`, `Answer.php`, `ExamViolation.php`, `UserTrainingProgress.php`, `DocumentController.php`, dan controller/migration/view terkait), baru salin isi paket ini menimpa yang lama. Tapi risikonya lebih tinggi ada sisa referensi yang nyangkut — jalur "mulai dari project baru" di atas lebih aman.

## Setelah semua tersalin

Jalankan urutan tes cepat ini untuk memastikan semuanya nyambung dengan benar:

1. `siap.test/login` — login dengan akun admin yang sudah dibuat lewat Tinker.
2. Buka `/pengumuman`, `/surat`, `/pelatihan` — pastikan tidak ada error 404/500.
3. Buat 1 pengumuman baru, cek muncul di daftar dan status pembacaan bekerja.
4. Buat 1 ujian dengan 1 soal pilihan ganda + 1 esai, login sebagai pegawai, kerjakan, lalu cek halaman hasil sebagai admin.

Kalau ada error di salah satu langkah, kirim pesan errornya persis (termasuk baris file) biar bisa langsung dicek bareng.
