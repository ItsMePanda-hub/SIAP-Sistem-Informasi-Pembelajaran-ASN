# SIAP Library — Roadmap

> **Status dokumen:** otoritatif untuk modul Library. Menggantikan asumsi fase sebelumnya.
> **Aturan penutupan fase:** fase dinyatakan selesai hanya jika `php artisan test` (filter relevan + full suite), `view:clear`/`view:cache`, `migrate:status`, dan `git diff --check` lulus dengan bukti output aktual. Laporan historis tidak menggantikan verifikasi.

## 1. Tujuan dan Batasan Modul

**Tujuan:** Portal bacaan internal ASN Diskominfo Sawahlunto — bukan LMS sekolah. Menyediakan pengelolaan materi Core (kurasi resmi, terikat provenance + hak redistribusi) dan Custom (internal), dengan siklus publikasi, konsumsi (baca/PDF/tautan eksternal), progres, bookmark, dan rekomendasi.

**Batasan eksplisit:**
- Tidak mengelola data kepegawaian (SIMPEG) — hanya `users.nip/jabatan/unit_kerja/role` lokal.
- Tidak menggantikan Training/Exam/Workspace/Surat/Pengumuman/Chatbot/Notifications/Web Push/Statistics. Integrasi hanya bila dependensi terverifikasi.
- Tidak melakukan crawling, AI, job terjadwal, atau penambahan dependency Composer tanpa keputusan desain.
- LinkOnly = tautan eksternal resmi; tidak menyalin file ke storage internal.

## 2. Ringkasan Fase Selesai (Terverifikasi dari Kode)

| Fase | Judul | Bukti | Status |
|------|-------|-------|--------|
| 1 | Foundation: Level, Kategori, Material, Scope | `database/migrations/2026_10_03_000001..000004`, `app/Models/LearningLevel.php:1`, `LearningCategory.php:1`, `LearningMaterial.php:1`, `database/seeders/Library{Level,Category,Material}Seeder.php` | `verified` |
| 2 | Content: Native/PDF, Reader, PDF delivery | `database/migrations/2026_10_08_000001`, `LearningMaterial.php:124-132,284-337`, `LibraryController.php:338-356,476-555`, `resources/views/library/{form,read}.blade.php`, `tests/Feature/LibraryContentTest.php`, `LibraryContentHardeningTest.php` | `verified` |
| 2.1 | Consumption: Progress, Bookmark, Discovery, Rekomendasi | `database/migrations/2026_10_08_000002,000003`, `ReadingProgress.php`, `LibraryBookmark.php`, `LibraryRecommendationService.php`, `LibraryController.php:24-132,254-336`, `resources/views/library/{index,bookmarks,read}.blade.php`, `tests/Feature/Library{Discovery,Bookmark,ReadingProgress,Recommendation,Reader,Menu}Test.php` | `verified` |
| 2.2 | Lifecycle: Verification/Validity/Publication, Provenance | `LearningMaterial.php:185-253,255-282`, `LearningMaterialSource.php:1`, `LibraryController.php:372-452`, `resources/views/library/source-form.blade.php` | `verified` |
| 4.3 | Rights-Review Audit Trail | `database/migrations/2026_10_08_000005_create_learning_material_rights_logs_table.php:1` (`lm_rights_logs_source_id_fk`, `lm_rights_logs_reviewer_id_fk`, `reviewer_user_id nullable nullOnDelete`), `app/Models/LearningMaterialRightsLog.php:1`, `LearningMaterialSource.php:51-99` (`rightsLogs()`, `recordRightsLog()`, `saving`/`saved` auto-invalidate + log sistem), `LearningMaterial.php:144-183,201-253` (`hasApprovedRights()` + `scopeCurrent()` cabang Native/PDF vs LinkOnly), `LibraryController.php:414-449` (self-approval guard, `recordRightsLog`), `app/Policies/LearningMaterialPolicy.php:29-35`, `app/Enums/LearningLicenseType.php:33-41`, `tests/Feature/LibraryRightsReviewTest.php:1` (15 tests) | `verified` |

**Catatan historis (tidak dianggap bukti berjalan):** laporan Phase 4.3 menyebut `LibraryRightsReviewTest` 15 passed / 93 assertions, Library 258 passed / 945 assertions, full suite 376 passed / 1338 assertions, `view:clear`/`view:cache`/`migrate:status`/`diff --check` sukses. Harus diverifikasi ulang sebelum klaim.

## 3. Keadaan Implementasi Saat Ini (Terverifikasi 2026-10-08)

**Migrasi:** `php artisan migrate:status` — semua `Ran`, termasuk `2026_10_08_000005_create_learning_material_rights_logs_table` batch 6. Tidak ada migrasi pending.

**Model & Enum:**
- `LearningMaterial` — `scopeCurrent()`/`isCurrent()`/`hasApprovedRights()`/`canCoreBePublished()` konsisten setelah patch 4.3; cabang LinkOnly vs Native/PDF sudah dibedakan (cek `license_url`, `source_url`, `tidak_diketahui`).
- `LearningMaterialSource` — `wasAutoInvalidated` (public, non-persisted) + hook `saving` (invalidate 6 field bukti bila `rights_reviewed_at` tidak dirty dan `getOriginal('rights_status') !== 'unreviewed'`) + `saved` (log `unreviewed` sistem). Audit `rightsLogs()` `latest()`.
- `LearningMaterialRightsLog` — `license_type` cast enum, FK `reviewer_user_id nullable nullOnDelete`.
- `LearningLevel`/`LearningCategory` — `is_active`, `sort_order`, slug unik.
- `ReadingProgress` (`reading_progress`) unique `[user_id, learning_material_id]`; `LibraryBookmark` (`library_bookmarks`) unique sama.
- **Duplikat terverifikasi:** `app/Models/LearningBookmark.php` dan `app/Models/LibraryBookmark.php` identik (beda nama, tabel sama `library_bookmarks`). Hanya `LibraryBookmark` dipakai di controller/service/test. `LearningBookmark` dead code — risiko divergensi.

**Controller & Route:**
- `LibraryController` — `index` (filter `q/scope/level/category/type/publication/verification/validity/progress_status/sort` + pagination 12 + `progressMap`/`bookmarkSet` + `LibraryRecommendationService`), `read`/`pdf`/`bookmarks`/`storeBookmark`/`destroyBookmark`/`updateProgress`/`create`/`store`/`edit`/`update`/`destroy`/`editSource`/`updateSource`/`publish`/`unpublish`.
- `routes/web.php:96-112` — 13 route `library.*`, semua `auth` group, `pdf`/`read`/`progress` pakai `can view` policy, `editSource`/`updateSource`/`publish` pakai `can update`.
- `LibraryRecommendationService` — menghormati `scopeCurrent()` untuk non-manager, prioritas unfinished > kategori > next level.

**Policy:**
- `LearningMaterialPolicy::view()` → `isCurrent()` untuk semua, fallback `isLibraryManager()` (`admin, admin_library, pemilik`). `create/update/delete` hanya manager. `before()` admin bypass.

**View:**
- `index.blade.php` — filter GET 8 kolom, `activeFilters`, `recommendations`, tabel provenance/license, CTA Baca/bookmark, aksi manager.
- `read.blade.php` — provenance block, progress bar (Native) / iframe PDF / card LinkOnly (`target=_blank rel=noopener`, tulis "Tidak ada berkas file yang disalin").
- `source-form.blade.php` — form provenance + lisensi + `rights_status` + `rights_review_notes` + audit trail `rightsLogs` (badge approved/rejected/unreviewed, reviewer name/`Sistem (Otomatis)` bila null).
- `bookmarks.blade.php` — `@can view` → `Tersedia`/`Tidak tersedia`, tidak render link Baca bila tidak authorized.

**Factory/Seeder:**
- `LearningMaterialFactory` — `core()`/`custom()`/`native()`/`pdf()`/`withUploader()`; `LearningMaterialSourceFactory` — `unreviewed()`/`rejected()`/`hakCipta()`/`tidakDiketahui()`; `LibraryMaterialSeeder` — 8 material (5 Core: valid/draft/pending/rejected/needs_update, 3 Custom) idempotent via `updateOrCreate`.

**Test:** 14 file `tests/Feature/Library*Test.php` + foundation. Konvensi `RefreshDatabase`, `actingAs`, `route()` helper. Phase 4.3 menetapkan `LibraryRightsReviewTest` 15 tests sebagai regression lock.

## 4. Keterbatasan & Risiko Belum Selesai

**Terverifikasi (beri file:baris):**
1. **Duplikat model bookmark** — `app/Models/LearningBookmark.php:1` vs `LibraryBookmark.php:1` — keduanya `table library_bookmarks`. Risiko inkonsistensi import.
2. **Enforcement hak hanya di controller** — `LibraryController.php:408-411` validasi `source_url` untuk LinkOnly dan `392-405` lisensi untuk Core hanya di `updateSource()`. Update langsung via `LearningMaterialSource::update()` / Tinker / seeder bisa bypass, hanya `saving` hook yang invalidate tetapi tidak validasi required. `learning_material_sources` DB `source_name` not-null, lainnya nullable — tidak ada CHECK constraint untuk Core.
3. **Search wildcard tidak di-escape** — `LibraryController.php:37` `"%{$search}%"` tanpa escape `%`/`_`. Bukan SQLi (binding via `where like`), tapi bisa wildcard abuse & scan penuh.
4. **Index hilang untuk path rights** — `learning_materials` punya index `scope,publication_status/verification/validity` tetapi `learning_material_sources` tidak ada index `rights_status/license_type/source_url` yang dipakai berat di `scopeCurrent()` `whereHas`.
5. **Tidak ada audit siklus hidup selain rights** — `publish()`/`unpublish()`/`verification_status`/`validity_status` tidak tercatat. Sulit forensik kapan Core menjadi `expired/revoked/superseded`.
6. **Progress JS rapuh pada dokumen pendek** — `read.blade.php:162-201` `calc()` `scrollable <=0 => 100%` menandai selesai tanpa scroll; `updateProgress` monotonic (`max(existing,incoming)`) jadi tidak bisa koreksi turun.

**Butuh investigasi lanjut (label `perlu investigasi`):**
- Apakah `file_path` perlu validasi prefix `library-pdfs/` di model (saat ini hanya `Storage::disk('public')->exists()` di `pdf()`)?
- Apakah `metadata` JSON perlu schema validation?
- Beban `scopeCurrent()` dengan 2 `whereHas` + `TRIM()` `whereRaw` pada katalog besar — perlu `EXPLAIN` di dataset produksi.

**Bukan cacat (sengaja):**
- LinkOnly Core dengan `hak_cipta` + `source_url` valid dianggap `isCurrent()` — benar, LinkOnly tidak redistribusi file.
- `Custom` selalu `isCurrent()` bila `published` — benar, tidak terikat Core gate.

## 5. Roadmap Usulan (Prioritas: keamanan > integritas > reliabilitas > UX)

### Phase 4.4 — Hardening Inti & Kunci Regresi (`proposed`, prioritas 1)

**Masalah:** celah #1-4 di atas + parity `scopeCurrent()` vs `isCurrent()` belum terkunci otomatis.

**Tujuan:** tutup bypass validasi, hilangkan duplikat, percepat query katalog, kunci regresi Phase 4.3.

**Ruang lingkup:**
- Hapus/konsolidasi `LearningBookmark` → pakai satu `LibraryBookmark` (atau alias teruji). Update import/test yang masih pakai `LearningBookmark` bila ada.
- Pindahkan required-rule Core/LinkOnly dari controller ke model/service yang tidak bisa di-bypass: `LearningMaterialSource::saving` validasi (throw `ValidationException`) bila `material->isCore()` tanpa `source_name/publisher/license_type` atau `license requiresEvidence` tanpa `license_url`, dan bila `material->isLinkOnly()` tanpa `source_url`. Controller tetap validasi untuk UX, model jadi penjaga terakhir.
- Escape `%`/`_` di `LibraryController@index` search (`str_replace(['%','_'], ['\%','\_'], $search)` + `where like` dengan `escape`).
- Tambah index: `learning_material_sources(rights_status, license_type)`, `learning_material_sources(source_url)` (atau composite sesuai `EXPLAIN`).
- Tambah test paritas: `scopeCurrent` vs `isCurrent` untuk tiap kombinasi (Core Native/PDF redistributable vs non-redistributable, tanpa license_url, LinkOnly tanpa source_url/`tidak_diketahui`, self-approval).

**Non-tujuan:** audit lifecycle, UX polish, manajemen level/kategori UI, penambahan dependency.

**File/komponen:** `app/Models/LearningBookmark.php`, `LibraryBookmark.php`, `LearningMaterialSource.php`, `LearningMaterial.php`, `LibraryController.php`, `database/migrations/*_add_library_source_indexes.php` (baru, additive), `tests/Feature/LibraryRightsReviewTest.php` + test paritas baru.

**Kriteria penerimaan (objektif):**
1. `grep -r LearningBookmark app/ tests/ --include="*.php"` hanya satu model (alias bila dipertahankan harus diuji).
2. `LearningMaterialSource::factory()->create(['license_type'=>'cc-by-4.0','license_url'=>null])` untuk Core via model langsung gagal validasi (exception) — tidak hanya via controller.
3. `LinkOnly` tanpa `source_url` via model langsung gagal.
4. `GET /perpustakaan?q=%25` tidak mengembalikan semua baris via wildcard.
5. `EXPLAIN` katalog `current()` memakai index baru (atau bukti `migrate:status` + `SHOW INDEX`).
6. Test paritas `scopeCurrent` vs `isCurrent` lulus untuk 8+ kombinasi (termasuk self-approval).

**Test wajib:** `LibraryRightsReviewTest` tetap 15 passed, test paritas baru ≥8 assertions, `LibraryDiscoveryTest` search escape, regression `Library` 258 passed tidak turun.

**Migrasi:** 1 additive (`add_library_source_indexes`). Dilarang `migrate:fresh`/`db:wipe`.

**Risiko:** index salah nama → `migrate:status` gagal; validasi model terlalu ketat → seeder/factory gagal — mitigasi: factory `definition` sudah set `cc-by-4.0` + `license_url`, seeder Core valid sudah pakai `license_url`.

**Dependensi:** Phase 4.3 selesai (`verified`).

**Definition of Done:** semua kriteria di atas + `php artisan test --filter=Library` + `php artisan test` + `view:clear`/`view:cache` + `migrate:status` + `git diff --check` lulus dengan output tercatat.

### Phase 4.5 — Audit Siklus Hidup & Observabilitas (`proposed`)

**Masalah:** celah #5 — perubahan `verification/validity/publication` tidak teraudit, sulit insiden.

**Tujuan:** jejak audit minimal untuk lifecycle tanpa membocorkan ke konsumen.

**Ruang lingkup:** tabel `learning_material_lifecycle_logs` (additive) atau reuse `activity_logs` bila sudah ada (`2026_09_23_012614_create_activity_logs_table`). Catat `material_id, actor_id, field, old, new, reason`. Tampilkan hanya di view manager (mirip `rightsLogs`).

**Non-tujuan:** notifikasi, retention policy.

**Kriteria:** publish/unpublish/verification/validity tercatat, tidak bocor di `index`/`read` konsumen (uji string secret tidak muncul).

### Phase 4.6 — Polish Konsumsi & Aksesibilitas (`proposed`)

**Masalah:** celah #6 + UX: empty state, error validation, LinkOnly card sudah baik tapi progress scroll pendek perlu UX yang tidak auto-complete.

**Tujuan:** konsumen paham status `Tidak tersedia` tanpa bocor konten, empty state konsisten, progress tidak false-positive.

**Kriteria:** Native pendek tidak auto-100% tanpa interaksi; bookmark `Tidak tersedia` tetap tanpa link Baca (sudah ada, kunci regresi); filter reset & pagination `withQueryString` teruji.

### Phase 4.7 — Manajemen Level/Kategori & Operasional (`proposed`, opsional)

**Masalah:** level/kategori hanya via seeder (`LibraryLevelSeeder`, `LibraryCategorySeeder`). Tidak ada UI admin.

**Tujuan:** CRUD terbatas untuk `admin_library` (tanpa hapus bila masih dipakai `restrictOnDelete`).

**Non-tujuan:** bulk import, SIMPEG sync (Bulan 3 roadmap 4-bulan).

**Kriteria:** `admin_library` dapat tambah/nonaktifkan level/kategori, `restrictOnDelete` tetap cegah hapus yang terpakai.

## 6. Kriteria Penerimaan & Strategi Verifikasi (Semua Fase)

- Tiap fase wajib tulis test terfokus dulu, lalu `php artisan test --filter=Library`, lalu `php artisan test`, lalu `view:clear`/`view:cache`/`migrate:status`/`git diff --check`/`git status --short`/`git diff --stat`. Klaim lulus hanya bila output menegaskan sukses.
- `scopeCurrent()` vs `isCurrent()` harus diuji paritas — bukan hanya salah satu.
- Jalur baca Core invalid harus diuji 5 path: katalog/search, rekomendasi, reader langsung, bookmark, PDF — seperti `LibraryRightsReviewTest::test_invalidated_core_material_blocked_from_all_consumer_access_paths`.
- LinkOnly harus diuji: tanpa file lokal, wajib `source_url`, tolak PDF, tidak iframe.
- Custom harus tetap kompatibel — regresi `Library{Content,Lifecycle}*` tidak boleh turun.

## 7. Batas Modul & Pengaman Regresi

- Jangan ubah Training/Exam/Workspace/Surat/Pengumuman/Chatbot/Notifications/Web Push/Statistics/global role kecuali dependensi terverifikasi dan tertulis di fase.
- Jangan tambah dependency Composer/crawler/job/AI.
- Jangan `migrate:fresh`/`db:wipe`/`truncate`/hapus tabel `learning_material_rights_logs`.
- Pertahankan notifikasi model Gemini apa adanya.
- Tiap perubahan Library harus sertakan test regresi Training/Exam/Workspace tidak pecah (full suite).

## 8. Progres

| Fase | Status | Bukti |
|------|--------|-------|
| 1 — Foundation | `verified` | migrasi 2026_10_03_000001..000004, seeder, model |
| 2 — Content | `verified` | migrasi 000001, controller pdf, view read, test Content/Hardening |
| 2.1 — Consumption | `verified` | migrasi 000002/000003, progress/bookmark/discovery/recommendation, test Discovery/Bookmark/Progress/Recommendation/Reader/Menu |
| 2.2 — Lifecycle | `verified` | `isCurrent`/`publish`, source-form, test Lifecycle/Phase2Dataset |
| 4.3 — Rights-Review Audit Trail | `verified` | migrasi 000005, model `RightsLog`, `recordRightsLog`, auto-invalidate + log sistem, `scopeCurrent` cabang, 15 tests |
| 4.4 — Hardening Inti | `proposed` | dokumen ini — menunggu persetujuan, belum implementasi |
| 4.5 — Audit Lifecycle | `proposed` | — |
| 4.6 — Polish Konsumsi | `proposed` | — |
| 4.7 — Manajemen Level/Kategori | `proposed` | — |

## 9. Keputusan yang Butuh Persetujuan Manusia

1. Hapus `LearningBookmark` atau pertahankan sebagai alias `class_alias`? Pilihan aman: hapus file, biarkan `LibraryBookmark` satu-satunya; bila ada kode eksternal pakai `LearningBookmark`, buat alias + test.
2. Validasi model `saving` vs FormRequest/Service — setuju model jadi penjaga terakhir?
3. Index komposit mana untuk `learning_material_sources` — `rights_status` saja atau `(rights_status, license_type)`? Tunggu `EXPLAIN` di data staging.
4. Apakah 4.5 reuse `activity_logs` atau tabel baru `lifecycle_logs`? Perlu cek `app/Traits/LogsActivity.php` dan `ActivityLog` yang sudah ada.

## 10. Sumber Bukti Audit

`git status --short`/`diff --stat`/`diff --check`/`log -10 --oneline`/`migrate:status`, `CLAUDE.md`, `IMPLEMENTATION_SUMMARY.md`, `docs/siap-roadmap-4-bulan.svg`, `database/migrations/2026_10_03_000003..2026_10_08_000005`, `app/Models/Learning{Material,MaterialSource,MaterialRightsLog,Level,Category,ReadingProgress,LibraryBookmark,LearningBookmark,User}`, `app/Enums/Learning*`, `app/Http/Controllers/LibraryController.php`, `app/Policies/LearningMaterialPolicy.php`, `app/Services/LibraryRecommendationService.php`, `routes/web.php:96-112`, `resources/views/library/{index,form,source-form,read,bookmarks}.blade.php`, `database/factories/Learning{Material,MaterialSource}Factory.php`, `database/seeders/DatabaseSeeder.php`+`Library*Seeder.php`, `tests/Feature/Library*.php` (14 file).

---
*Dibuat 2026-10-08 — tanpa implementasi fitur, hanya dokumentasi. Tunggu persetujuan sebelum eksekusi Phase 4.4.*
