# SIAP — Sistem Informasi & Pembelajaran ASN

Portal internal untuk pegawai ASN di Diskominfo Sawahlunto. Bukan LMS sekolah — ini portal kerja pemerintahan: pengumuman, distribusi surat digital, pelatihan ASN dengan sertifikat, dan ujian.

## Tech Stack
- Laravel 10 + Breeze (Blade, bukan Inertia/API)
- MySQL
- Dev environment: Laragon di Windows (project di C:\laragon\www\SIAP)

## Role Hierarchy
1. **Admin** — akses penuh sistem, dikelola tim IT Diskominfo
2. **Pemilik** — pimpinan puncak, lintas bidang (cross-bidang)
3. **Atasan** — kepala bidang/departemen, scoped hanya ke unit_kerja miliknya sendiri
4. **Pengguna/Pegawai** — staff biasa

Setiap query yang scoped per-role harus filter berdasarkan `unit_kerja` untuk role Atasan.

## Struktur Data Users
Field tambahan di tabel `users` (selain default Breeze):
- `nip` — nomor induk pegawai
- `jabatan` — jabatan/posisi
- `unit_kerja` — bidang/departemen
- `role` — enum: admin, pemilik, atasan, pengguna

## Modul yang Dibangun

### 1. Announcements
- CRUD pengumuman oleh Admin/Pemilik/Atasan
- Read-receipt tracking — sistem tau siapa aja yang udah baca

### 2. Distribusi Dokumen/Surat
- Upload dokumen (PDF/Word) oleh admin/atasan
- Download oleh pegawai terkait
- Tracking siapa yang udah download

### 3. Training/Pelatihan
- Daftar training yang bisa diikuti pegawai
- Completion tracking (progress per pegawai)
- Generate sertifikat otomatis setelah training selesai

### 4. Exam Module
- Tipe soal: Pilihan Ganda (PG) + Essay
- Auto-grading untuk PG
- Essay dinilai manual oleh atasan/admin
- Anti-cheat: deteksi tab-switch selama ujian, dengan sistem peringatan bertingkat (graduated warnings) — bukan langsung diskualifikasi di percobaan pertama

## Design Direction
- Palette: teal-blue sebagai warna utama, gold/kuning muda sebagai aksen
- Layout: sidebar navigation
- Hindari warna gelap/dark mode sebagai default

## Catatan Status
- Project di-rebuild dari nol (Agustus 2026) — file/database sebelumnya hilang, tidak ada backup
- Client minta lihat flowchart, business process (probis), mockup gambar, dan struktur sistem dulu sebelum development modul baru dilanjutkan — jangan buru-buru nambah fitur baru tanpa sign-off desain dulu