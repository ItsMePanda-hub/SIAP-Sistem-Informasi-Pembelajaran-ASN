# Library Native — Paket Review Manusia

> 5 materi `custom/native draft/draft/unknown` — seeder `LibraryNativeBatchSeeder.php` sinkron DB 100% @2026-10-13 (literasi `545b005d` 17475, keamanan `bda702ba` 16665, komunikasi `6f49b004` 13714, pelayanan `d54c03c7` 16614, etika `dbbc2c20` 17776). `UU20 link_only draft/pending_review` tidak dibahas. Tidak ubah materi/DB/seeder/migration. Portal ≠ izin distribusi. Centang checklist ≠ disetujui. Persetujuan ikut gate Library (`verified+active+provenance+approved`, reviewer ≠ uploader) & `LibraryRightsReviewTest` 18 passed.

## Cara pakai
1. Reviewer konten cek klaim/bagian per materi → isi bukti di `Sumber` form.
2. Reviewer hak (berbeda dari pengunggah) periksa `source_name/publisher/license_type/license_url/source_url` nyata → beri `rights_status=approved`/`rejected` + `rights_review_notes`.
3. Self-approval ditolak model+controller; ubah `license_type/license_url/source_url/source_name/publisher/document_identifier` auto-invalidate ke `unreviewed` + log sistem.
4. Publikasi Core terkunci `canCoreBePublished` — panduan edukatif bukan aturan resmi.

> **Akses portal/dokumen tidak memberi izin menyalin & mendistribusikan.** Bukti lisensi (`license_url`) atau izin tertulis terpisah wajib; portal umum tidak cukup.

---

## 1) Literasi Digital untuk ASN — `literasi-digital-untuk-asn` (17475)

**A. Klaim/bagian perlu tinjau**
- 4 Pilar Komdigi sebagai kerangka edukatif bukan kewajiban hukum; `site:`/drive/versioning; 5 langkah berbagi + masa berlaku; alur end-to-end verifikasi→komunikasi→pengelolaan→evaluasi; klasifikasi `publik/internal/terbatas edukatif`.

**B. Bukti sudah tersedia (terverifikasi portal 2026-10-13)**
- `https://literasidigital.id/` — 4 Pilar (Kemampuan/Etika/Budaya/Keamanan Berdigital).

**C. Sumber/SOP masih dicari (UNVERIFIED/INTERNAL)**
- Judul+tahun dokumen BSSN `publikasi/edukasi` & `bsre.bssn.go.id` spesifik; `LAN` judul modul spesifik; daftar alat/teknisi diizinkan; SOP Diskominfo klasifikasi/retensi/timeout.

**D. Keputusan reviewer**
- Benarkan kerangka edukatif vs kewajiban; konfirmasi 4 Pilar masih kurikulum resmi; setujui bahwa `anyone-with-link` tidak aman untuk internal.

**E. Hak distribusi**
- Isi `source_name/publisher/license_type` + `license_url` bila `requiresEvidence`; verifikasi portal ≠ izin.

## 2) Keamanan Informasi dan Perlindungan Data — `keamanan-informasi-dan-perlindungan-data` (16665)

**A. Klaim/bagian**
- CIA trade-off KTP/KK & lelang; vault `konfirmasi IT→MFA→label`; typosquatting `dis-kominfo-sawahlunto.example` (fiktif label); hover/header tanpa klik; timeout 1–2 menit contoh; template insiden 6 kolom tanpa sandi/OTP; `mengikuti SOP/CSIRT`.

**B. Bukti**
- Portal `https://www.bssn.go.id/publikasi/`, `/edukasi-masyarakat/`, `https://bsre.bssn.go.id/` (portal); `ISO 27001:2022` metadata jujur (tidak klaim baca full, 403, berbayar).

**C. Masih dicari**
- Judul dokumen BSSN spesifik; SOP klasifikasi/retensi/CSIRT/helpdesk/daftar pengelola sandi/timeout aktual.

**D. Keputusan**
- Konfirmasi trade-off tidak jadi SOP universal; typosquatting fiktif jelas; timeout contoh bukan standar.

**E. Hak**
- Sama — `license_url` nyata, reviewer beda.

## 3) Komunikasi Efektif di Lingkungan Kerja — `komunikasi-efektif-di-lingkungan-kerja` (13714)

**A. Klaim/bagian**
- Alur 5 `dengar-catat-parafrase-konfirmasi-tindak lanjut` + format `Fakta-Keputusan-Tenggat-PJ` + dialog Bidang Y; email `[Permintaan] ... 16.00`; `single owner` + cek 10 menit; matriks `dampak-biaya-tenggat-risiko`; batasan `jangan jalankan keputusan melanggar hukum`.

**B. Bukti**
- Hanya `https://lan.go.id/` portal — paling lemah.

**C. Masih dicari**
- Judul Modul LAN komunikasi spesifik; SOP template catatan keputusan & matriks.

**D. Keputusan**
- Template disahkan; matriks sesuai SOP.

**E. Hak**
- Internal — `license_url` bila ada redistribusi.

## 4) Pelayanan Publik yang Berkualitas — `pelayanan-publik-yang-berkualitas` (16614)

**A. Klaim/bagian**
- `UU 25/2009 LN112 TLN5038 https://peraturan.bpk.go.id/Details/38748`; standar `persyaratan-prosedur-waktu-biaya-produk-pengaduan`; alur 5 permohonan→penyampaian hasil (contoh pembelajaran); pengaduan 6 langkah + template 6 field tanpa data sensitif; agregasi anonim `30%`.

**B. Bukti**
- UU25 portal JDIH BPK verified; `https://lan.go.id/` portal.

**C. Masih dicari**
- PP pelaksana UU25 judul/nomor/URL (sengaja tidak dikarang — fetch `pp-no-96` return salah doc `PERWALI Malang`); SOP standar waktu/biaya/kanal pengaduan resmi.

**D. Keputusan**
- PP pelaksana ada/tidak; standar waktu/biaya resmi; kanal pengaduan sebenarnya.

**E. Hak**
- Hak publikasi butuh provenance lengkap — `source_name/publisher` wajib.

## 5) Etika Profesional di Lingkungan Kerja — `etika-profesional-di-lingkungan-kerja` (17776)

**A. Klaim/bagian**
- 5 prinsip + nilai `BerAKHLAK`; konflik alur 4 `kenali-hindari sepihak-ungkap-minta arahan`; gratifikasi `jangan janji-catat-konsultasi UPG`; foto grup `sensor/crop/ringkasan`; AI `kebijakan-anonim-verifikasi-bias-tinjauan` contoh `Warga A`; dilema 8 `fakta-pihak-aturan-alternatif-risiko-konsultasi-keputusan-dokumentasi` + `jalur berwenang bukan tuduhan publik`.

**B. Bukti**
- `https://www.bkn.go.id/publikasi/panduan-perilaku-core-value-asn` + `https://www.bkn.go.id/` + `https://lan.go.id/` + `https://www.kpk.go.id/id/layanan/gratifikasi` (+ `https://gol.kpk.go.id/`) portal verified.

**C. Masih dicari**
- Kategori `wajib/tidak wajib lapor` gratifikasi detail; nominal/tenggat/formulir; UPG instansi ada; AI policy layanan diizinkan; `PP 94/2021` pasal.

**D. Keputusan**
- UPG/kanal, nominal/tenggat aktual, AI allowlist.

**E. Hak**
- Sama — tanpa mengarang batas.

---

## Checklist Metadata Sumber (isi per materi di `Sumber` form)

| Field form | Isi reviewer | Bukti |
|---|---|---|
| Nama dokumen sebenarnya (`source_name`) | | — |
| Penerbit (`publisher`) | | — |
| URL spesifik dokumen (`source_url`/`license_url`) | | verifikasi URL bukan portal umum |
| Identitas/versi dokumen (`document_identifier`/`version`/`source_issued_at`) | | — |
| Tanggal pemeriksaan (`last_checked_at`) | | — |
| Bukti lisensi/dasar hak (`license_type` + `license_url`) | `cc-by-4.0`/`cc-by-sa-4.0`/`izin_tertulis` perlu `license_url`; `hak_cipta`/`tidak_diketahui` tidak redistributable untuk Core Native/PDF | cek `requiresEvidence` |
| Catatan verifikasi (`verification_notes`/`rights_review_notes`) | | — |
| Nama reviewer & keputusan (`rights_status` + `rights_reviewed_by/at`) | `unreviewed`/`approved`/`rejected` — reviewer ≠ pengunggah | self-approval ditolak |
| Riwayat (`rightsLogs`/`lifecycleLogs`) | append-only | |

## Konfirmasi Instansi/Ahli Wajib

- **SOP Diskominfo:** klasifikasi informasi (`Publik/Internal/Terbatas` edukatif), retensi, CSIRT/helpdesk eskalasi, timeout sesi, kanal layanan & pengaduan resmi.
- **Hukum UU25/2009:** dasar pelaksanaan relevan — PP judul/nomor/URL; standar waktu/biaya/tenggat resmi.
- **Gratifikasi/UPG:** UPG ada + ketentuan `wajib/tidak wajib lapor`, nominal, tenggat, formulir (rujuk FAQ KPK — jangan karang).
- **Dokumen BSSN/LAN/BKN:** judul+tahun `Panduan Keamanan`, `Modul Komunikasi/Pelayanan`, `Buku Saku BerAKHLAK` isi — portal saja tidak cukup.

## Aturan Persetujuan

- Checklist selesai ≠ disetujui. `rights_status=approved` eksplisit via `library.source.update` oleh `admin`/`admin_library`/`pemilik` bukan pengunggah; `canCoreBePublished` + `isCurrent`/`scopeCurrent` gate publikasi. Ubah bukti auto-invalidate. Tanpa bukti memadai tetap `unreviewed`/`rejected` & tidak publish.

---
*Teams `TaskPolicy`/`tasks/index` milik pengguna dipertahankan. `docs/library-roadmap.md` tidak ditimpa.*
