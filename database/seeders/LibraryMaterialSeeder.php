<?php

namespace Database\Seeders;

use App\Enums\LearningMaterialScope;
use App\Enums\LearningMaterialType;
use App\Enums\LearningPublicationStatus;
use App\Enums\LearningValidityStatus;
use App\Enums\LearningVerificationStatus;
use App\Models\LearningCategory;
use App\Models\LearningLevel;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialSource;
use App\Models\User;
use Illuminate\Database\Seeder;

class LibraryMaterialSeeder extends Seeder
{
    public function run(): void
    {
        $level = fn (string $key) => LearningLevel::where('key', $key)->firstOrFail();
        $category = fn (string $slug) => LearningCategory::where('slug', $slug)->firstOrFail();
        $uploaderId = User::where('email', 'admin.system@siap.test')->value('id')
            ?? User::where('email', 'admin.library@siap.test')->value('id')
            ?? User::where('role', 'admin')->value('id');

        $reviewerId = User::where('email', 'admin.library@siap.test')->value('id')
            ?? User::where('email', 'pemilik@siap.test')->value('id')
            ?? User::where('role', 'admin_library')->value('id')
            ?? User::where('role', 'admin')->where('id', '!=', $uploaderId)->value('id')
            ?? $uploaderId;

        $materials = [
            [
                'slug' => 'uji-core-valid-dasar-administrasi',
                'title' => '[UJI] Tertib Administrasi Perkantoran',
                'description' => 'Dataset uji Core valid. Bukan dokumen resmi.',
                'material_type' => LearningMaterialType::Native->value,
                'scope' => LearningMaterialScope::Core->value,
                'level' => 'dasar',
                'category' => 'administrasi-pemerintahan',
                'verification_status' => LearningVerificationStatus::Verified->value,
                'validity_status' => LearningValidityStatus::Active->value,
                'publication_status' => LearningPublicationStatus::Published->value,
                'content' => $this->tertibAdministrasi(),
                'source' => [
                    'source_name' => 'SIAP Library Test Dataset',
                    'source_url' => null,
                    'source_type' => 'dataset_uji',
                    'publisher' => 'SIAP (Dataset Uji Internal)',
                    'document_identifier' => 'SIAP-TEST-CORE-001',
                    'version' => 'v1.0-test',
                    'source_issued_at' => null,
                    'license_type' => 'cc-by-4.0',
                    'license_url' => 'https://creativecommons.org/licenses/by/4.0/',
                    'last_checked_at' => now(),
                    'verification_notes' => 'Dataset uji Phase 2. Bukan dokumen resmi pemerintah.',
                    'rights_status' => 'approved',
                    'rights_reviewed_by' => $reviewerId,
                    'rights_reviewed_at' => now(),
                    'rights_review_notes' => 'Disetujui untuk dataset uji Core.',
                    'metadata' => ['dataset' => 'phase-2-test'],
                ],
            ],
            [
                'slug' => 'uji-core-draft-pemula-digital',
                'title' => '[UJI] Pengenalan Perangkat Kerja Digital',
                'description' => 'Dataset uji Core draft. Belum diverifikasi.',
                'material_type' => LearningMaterialType::Native->value,
                'scope' => LearningMaterialScope::Core->value,
                'level' => 'pemula',
                'category' => 'kompetensi-digital',
                'verification_status' => LearningVerificationStatus::Draft->value,
                'validity_status' => LearningValidityStatus::Unknown->value,
                'publication_status' => LearningPublicationStatus::Draft->value,
                'content' => $this->pengenalanPerangkatDigital(),
                'source' => null,
            ],
            [
                'slug' => 'uji-core-pending-menengah-manajemen',
                'title' => '[UJI] Dasar Perencanaan Kerja Tim',
                'description' => 'Dataset uji Core menunggu review.',
                'material_type' => LearningMaterialType::Pdf->value,
                'scope' => LearningMaterialScope::Core->value,
                'level' => 'menengah',
                'category' => 'manajemen',
                'verification_status' => LearningVerificationStatus::PendingReview->value,
                'validity_status' => LearningValidityStatus::Unknown->value,
                'publication_status' => LearningPublicationStatus::Draft->value,
                'source' => null,
            ],
            [
                'slug' => 'uji-core-rejected-lanjutan-pelayanan',
                'title' => '[UJI] Materi Ditolak Verifikasi',
                'description' => 'Dataset uji Core yang ditolak kurasi.',
                'material_type' => LearningMaterialType::Native->value,
                'scope' => LearningMaterialScope::Core->value,
                'level' => 'lanjutan',
                'category' => 'pelayanan-publik',
                'verification_status' => LearningVerificationStatus::Rejected->value,
                'validity_status' => LearningValidityStatus::Revoked->value,
                'publication_status' => LearningPublicationStatus::Unpublished->value,
                'content' => null,
                'source' => null,
            ],
            [
                'slug' => 'uji-core-needs-update-mahir-keamanan',
                'title' => '[UJI] Materi Perlu Pembaruan Keamanan',
                'description' => 'Dataset uji Core yang sumbernya perlu diperiksa ulang.',
                'material_type' => LearningMaterialType::Pdf->value,
                'scope' => LearningMaterialScope::Core->value,
                'level' => 'mahir',
                'category' => 'keamanan-informasi',
                'verification_status' => LearningVerificationStatus::NeedsUpdate->value,
                'validity_status' => LearningValidityStatus::Superseded->value,
                'publication_status' => LearningPublicationStatus::Unpublished->value,
                'source' => [
                    'source_name' => 'SIAP Library Test Dataset',
                    'source_url' => null,
                    'source_type' => 'dataset_uji',
                    'publisher' => 'SIAP (Dataset Uji Internal)',
                    'document_identifier' => 'SIAP-TEST-CORE-005',
                    'version' => 'v0.9-test',
                    'source_issued_at' => null,
                    'license_type' => 'internal',
                    'license_url' => null,
                    'last_checked_at' => now()->subMonths(6),
                    'verification_notes' => 'Sumber perlu diperiksa ulang sebelum dapat dipakai lagi.',
                    'rights_status' => 'unreviewed',
                    'rights_reviewed_by' => null,
                    'rights_reviewed_at' => null,
                    'rights_review_notes' => null,
                    'metadata' => ['dataset' => 'phase-2-test'],
                ],
            ],
            [
                'slug' => 'uji-custom-sop-internal',
                'title' => '[UJI] SOP Internal Pengajuan Surat Tugas',
                'description' => 'Dataset uji Custom: prosedur internal organisasi.',
                'material_type' => LearningMaterialType::Native->value,
                'scope' => LearningMaterialScope::Custom->value,
                'level' => 'dasar',
                'category' => 'administrasi-pemerintahan',
                'verification_status' => LearningVerificationStatus::Draft->value,
                'validity_status' => LearningValidityStatus::Unknown->value,
                'publication_status' => LearningPublicationStatus::Published->value,
                'content' => $this->sopInternal(),
                'source' => null,
            ],
            [
                'slug' => 'uji-custom-panduan-siap',
                'title' => '[UJI] Panduan Penggunaan Sistem SIAP',
                'description' => 'Dataset uji Custom: panduan aplikasi internal.',
                'material_type' => LearningMaterialType::Pdf->value,
                'scope' => LearningMaterialScope::Custom->value,
                'level' => 'pemula',
                'category' => 'kompetensi-digital',
                'verification_status' => LearningVerificationStatus::Draft->value,
                'validity_status' => LearningValidityStatus::Unknown->value,
                'publication_status' => LearningPublicationStatus::Published->value,
                'source' => null,
            ],
            [
                'slug' => 'uji-custom-orientasi-pegawai',
                'title' => '[UJI] Materi Orientasi Pegawai Baru',
                'description' => 'Dataset uji Custom: orientasi internal, masih draft.',
                'material_type' => LearningMaterialType::Native->value,
                'scope' => LearningMaterialScope::Custom->value,
                'level' => 'pemula',
                'category' => 'pengembangan-kompetensi',
                'verification_status' => LearningVerificationStatus::Draft->value,
                'validity_status' => LearningValidityStatus::Unknown->value,
                'publication_status' => LearningPublicationStatus::Draft->value,
                'content' => $this->orientasiPegawaiBaru(),
                'source' => null,
            ],
            [
                'slug' => 'seleksi-cpns',
                'title' => 'Seleksi CPNS',
                'description' => 'Pengantar seleksi Calon Pegawai Negeri Sipil: perbedaan ASN/PNS/CPNS/PPPK, tahapan seleksi, dan kanal pendaftaran resmi. Rujuk pengumuman resmi untuk ketentuan terbaru.',
                'material_type' => LearningMaterialType::Native->value,
                'scope' => LearningMaterialScope::Custom->value,
                'level' => 'dasar',
                'category' => 'pengembangan-kompetensi',
                'verification_status' => LearningVerificationStatus::Draft->value,
                'validity_status' => LearningValidityStatus::Unknown->value,
                'publication_status' => LearningPublicationStatus::Draft->value,
                'content' => $this->seleksiCpns(),
                'source' => null,
            ],
        ];

        foreach ($materials as $data) {
            // Two-phase write: material sebagai draft dulu agar rule Core publish
            // (provenance + verified + active + rights) dapat dievaluasi setelah source terisi.
            $isCorePublished = $data['scope'] === LearningMaterialScope::Core->value
                && $data['publication_status'] === LearningPublicationStatus::Published->value;

            $material = LearningMaterial::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'material_type' => $data['material_type'],
                    'scope' => $data['scope'],
                    'learning_level_id' => $level($data['level'])->id,
                    'learning_category_id' => $category($data['category'])->id,
                    'verification_status' => $data['verification_status'],
                    'validity_status' => $data['validity_status'],
                    'publication_status' => $isCorePublished
                        ? LearningPublicationStatus::Draft->value
                        : $data['publication_status'],
                    'published_at' => (! $isCorePublished && $data['publication_status'] === LearningPublicationStatus::Published->value) ? now() : null,
                    'uploaded_by' => $uploaderId,
                ]
            );

            if ($data['source'] !== null) {
                LearningMaterialSource::updateOrCreate(
                    ['learning_material_id' => $material->id],
                    $data['source']
                );
            }

            if (array_key_exists('content', $data)) {
                $material->content = $data['content'];
            }
            $material->save();

            if ($isCorePublished) {
                $material->publish();
            }
        }
    }

    private function tertibAdministrasi(): string
    {
        return <<<'TXT'
MODUL DATASET UJI INTERNAL SIAP — BUKAN DOKUMEN RESMI
Judul: Tertib Administrasi Perkantoran
Tanggal penyusunan: 2026-10-16 | Status: dataset uji, bukan rujukan hukum atau SOP resmi.

Tujuan Pembelajaran
Peserta dapat:
1) menjelaskan unsur surat dinas dan arsip yang tertib;
2) memberi nomor, mencatat, dan menyimpan surat sesuai pola unit kerja;
3) membedakan arsip aktif, inaktif, dan statis secara konseptual;
4) menyiapkan serah terima administrasi yang dapat ditelusuri.

Prasyarat
Bekerja dengan surat atau arsip di unit kerja. Tidak perlu latar hukum.

Daftar Isi
1. Pendahuluan
2. Bab 1 — Surat dinas yang tertib
3. Bab 2 — Pencatatan dan penyimpanan
4. Bab 3 — Arsip konseptual
5. Bab 4 — Serah terima administrasi
6. Ringkasan
7. Latihan
8. Glosarium
9. Referensi

Pendahuluan
Administrasi yang tertib membuat pekerjaan dapat dilanjutkan orang lain tanpa tanya ulang. Prinsipnya sederhana: setiap surat tercatat, tersimpan di satu tempat yang disepakati, dan mudah ditemukan kembali. Modul dataset ini bahan latihan membaca, bukan aturan kearsipan resmi.

BAB 1 — SURAT DINAS YANG TERTIB
Unsur yang perlu ada pada konsep surat dinas:
- identitas pengirim dan penerima yang jelas;
- tanggal dan nomor sesuai pola unit kerja;
- perihal singkat dan isi yang memuat maksud, tindakan yang diminta, dan tenggat;
- penandatangan sesuai kewenangan.
Contoh pola nomor edukatif: 090/ADM-DISKOMINFO/X/2026. Pola sebenarnya mengikuti ketentuan unit kerja masing-masing.

BAB 2 — PENCATATAN DAN PENYIMPANAN
Alur contoh pembelajaran:
1) catat surat masuk dan keluar pada buku atau sistem agenda;
2) simpan pindaian atau berkas di folder terpusat dengan penamaan NAMA_dokumen_TANGGAL_versi;
3) catat disposisi dan tindak lanjut sampai selesai;
4) tinjau berkas yang sudah tidak aktif sesuai jadwal unit kerja.
Jangan menyimpan satu-satunya salinan penting hanya di perangkat pribadi.

BAB 3 — ARSIP KONSEPTUAL
- Arsip aktif — masih sering dipakai untuk kerja harian.
- Arsip inaktif — jarang dipakai tetapi masih perlu disimpan sesuai jadwal.
- Arsip statis — bernilai sejarah dan disimpan permanen oleh lembaga kearsipan.
Jadwal retensi dan tata cara pemusnahan mengikuti ketentuan kearsipan resmi, bukan modul ini.

BAB 4 — SERAH TERIMA ADMINISTRASI
Dokumen serah terima memuat:
- daftar surat dan berkas yang dialihkan beserta lokasinya;
- status tiap pekerjaan dan hal yang tertunda;
- kontak pihak terkait.
Minta konfirmasi tertulis dari penerima agar tanggung jawab jelas.

RINGKASAN
- Surat memuat identitas, nomor, perihal, maksud, tindakan, tenggat, dan penandatangan berwenang.
- Catat, simpan terpusat, dan tindak lanjuti sampai selesai.
- Bedakan arsip aktif, inaktif, dan statis; retensi ikut aturan resmi.
- Serah terima tertulis dengan daftar, status, dan konfirmasi.

LATIHAN
1. Tulis ulang memo ambigu menjadi surat dengan nomor, maksud, tindakan, dan tenggat yang jelas.
2. Susun penamaan tiga berkas kerja minggu ini dengan pola NAMA_dokumen_TANGGAL_versi.
3. Buat checklist serah terima cuti 2 hari berisi daftar berkas, status, dan kontak.
4. Refleksi: berkas apa yang paling sulit ditemukan di unit Anda dan perbaikannya?

GLOSARIUM
Disposisi — arahan pimpinan atas surat masuk untuk ditindaklanjuti.
Agenda surat — catatan surat masuk dan keluar secara berurutan.
Arsip — rekaman kegiatan yang disimpan untuk pembuktian dan rujukan.

REFERENSI DAN BACAAN LANJUTAN
Bahan ajar internal ini disusun dari praktik administrasi umum. Untuk ketentuan kearsipan yang mengikat, rujuk peraturan kearsipan resmi dan arsiparis instansi. Modul tidak mengutip pasal atau angka resmi.

Tanggal pemeriksaan: 2026-10-16 (dataset uji, belum verifikasi manusia).
TXT;
    }

    private function pengenalanPerangkatDigital(): string
    {
        return <<<'TXT'
MODUL DATASET UJI INTERNAL SIAP — BUKAN DOKUMEN RESMI
Judul: Pengenalan Perangkat Kerja Digital
Tanggal penyusunan: 2026-10-16 | Status: dataset uji, bukan panduan teknis resmi.

Tujuan Pembelajaran
Peserta dapat:
1) menyebut perangkat kerja digital yang umum dipakai ASN;
2) menjelaskan fungsi email dinas, penyimpanan berkas, dan aplikasi perkantoran;
3) mempraktikkan kebiasaan aman tingkat pemula;
4) meminta bantuan IT dengan informasi yang lengkap.

Prasyarat
Tidak ada. Cocok untuk pegawai yang baru memakai perangkat kerja.

Daftar Isi
1. Pendahuluan
2. Bab 1 — Perangkat dan akun kerja
3. Bab 2 — Berkas dan aplikasi perkantoran
4. Bab 3 — Kebiasaan aman pemula
5. Bab 4 — Meminta bantuan IT
6. Ringkasan
7. Latihan
8. Glosarium
9. Referensi

Pendahuluan
Perangkat kerja digital meliputi komputer, laptop, ponsel dinas, printer, dan jaringan kantor. Tujuannya membantu ASN bekerja lebih cepat dan terdokumentasi. Modul ini pengantar umum; pengaturan spesifik mengikuti kebijakan unit TI instansi.

BAB 1 — PERANGKAT DAN AKUN KERJA
- Komputer atau laptop untuk mengetik, presentasi, dan sistem kantor.
- Ponsel dinas untuk koordinasi dan autentikasi bila diizinkan.
- Akun kerja (email dan sistem kantor) hanya untuk keperluan dinas.
Contoh penerapan: aktifkan kunci layar otomatis, keluar dari akun setelah memakai perangkat bersama, dan laporkan perangkat hilang ke atasan atau TI.

BAB 2 — BERKAS DAN APLIKASI PERKANTORAN
- Simpan berkas di folder kerja yang disepakati, bukan hanya di desktop.
- Beri nama NAMA_dokumen_TANGGAL_versi agar mudah dicari.
- Gunakan aplikasi perkantoran untuk dokumen, angka, dan presentasi.
- Cadangkan berkas penting ke lokasi yang ditetapkan unit kerja.
Contoh: SK_Tugas_2026-10-12_v03 disimpan di folder Surat Tugas dan dibagikan dengan izin lihat saja.

BAB 3 — KEBIASAAN AMAN PEMULA
- Gunakan kata sandi berbeda untuk akun penting dan jangan dibagikan.
- Jangan mengeklik tautan atau lampiran dari pengirim tidak dikenal.
- Kunci perangkat saat ditinggal dan perbarui aplikasi saat diminta TI.
- Jangan menyimpan data warga di layanan pribadi.
Langkah bila ragu: berhenti, tanya atasan atau helpdesk, dan jangan teruskan pesan mencurigakan.

BAB 4 — MEMINTA BANTUAN IT
Sertakan informasi berikut agar cepat ditangani:
- perangkat dan sistem yang bermasalah;
- waktu kejadian dan langkah yang sudah dicoba;
- tangkapan layar bila aman dan tidak memuat data sensitif;
- kontak yang bisa dihubungi.
Contoh pesan: "Laptop A tidak bisa membuka sistem persuratan sejak jam 09.00, sudah coba buka ulang peramban. Mohon bantuan. Kontak: staf1@siap.test."
Jangan menulis kata sandi atau kode OTP dalam laporan.

RINGKASAN
- Kenali perangkat, akun, dan fungsinya untuk kerja harian.
- Simpan berkas terpusat dengan penamaan yang konsisten.
- Terapkan kunci layar, kata sandi unik, dan kehati-hatian tautan.
- Lapor ke TI dengan fakta lengkap tanpa kata sandi.

LATIHAN
1. Sebutkan tiga perangkat di meja kerja Anda dan fungsinya masing-masing.
2. Perbaiki tiga nama berkas acak menjadi pola NAMA_dokumen_TANGGAL_versi.
3. Tulis pesan permintaan bantuan IT untuk printer yang tidak terhubung.
4. Refleksi: kebiasaan aman apa yang akan Anda mulai minggu ini?

GLOSARIUM
Kata sandi — rangkaian rahasia untuk membuktikan identitas akun.
Cadangan — salinan berkas untuk pemulihan bila perangkat rusak.
Helpdesk — layanan bantuan teknis TI instansi.

REFERENSI DAN BACAAN LANJUTAN
Bahan ajar internal dari praktik kerja umum. Untuk pengaturan perangkat dan aplikasi yang berlaku, ikuti panduan TI Diskominfo Sawahlunto. Modul tidak menetapkan merek atau angka teknis sebagai standar.

Tanggal pemeriksaan: 2026-10-16 (dataset uji, belum verifikasi manusia).
TXT;
    }

    private function sopInternal(): string
    {
        return <<<'TXT'
MATERI DATASET UJI INTERNAL SIAP — BUKAN SOP RESMI
Judul: SOP Internal Pengajuan Surat Tugas
Tanggal penyusunan: 2026-10-16 | Status: dataset uji yang belum diverifikasi.

Tujuan Pembelajaran
Peserta dapat:
1) menjelaskan unsur pengajuan surat tugas secara umum;
2) menyiapkan kelengkapan administrasi sebelum mengajukan;
3) membedakan contoh format pembelajaran dari SOP resmi instansi;
4) menulis draf pengajuan yang rapi untuk diperiksa atasan.

Prasyarat
Memahami alur persuratan dasar di unit kerja masing-masing.

Daftar Isi
1. Pendahuluan dan batasan penting
2. Bab 1 — Unsur pengajuan surat tugas
3. Bab 2 — Kelengkapan yang umumnya disiapkan
4. Bab 3 — Contoh format pembelajaran
5. Bab 4 — Hal yang wajib dikonfirmasi ke SOP resmi
6. Ringkasan
7. Latihan
8. Glosarium
9. Referensi

Pendahuluan
Surat tugas adalah surat penugasan dari pejabat berwenang kepada pegawai untuk melaksanakan tugas tertentu. Prosedur resminya berbeda pada setiap instansi dan ditetapkan dalam SOP internal masing-masing.
Materi ini HANYA contoh pembelajaran umum. Materi ini BUKAN SOP resmi Diskominfo Sawahlunto. Jangan memakai contoh di sini sebagai dasar penerbitan surat tugas.

BAB 1 — UNSUR PENGAJUAN SURAT TUGAS
Secara umum, pengajuan surat tugas memuat:
- identitas pegawai yang ditugasi;
- maksud dan tujuan penugasan;
- waktu dan tempat pelaksanaan;
- dasar penugasan bila ada;
- persetujuan pejabat berwenang.
Unsur final, formulir, dan pejabat penandatangan mengikuti SOP resmi instansi.

BAB 2 — KELENGKAPAN YANG UMUMNYA DISIAPKAN
Contoh kelengkapan pembelajaran:
- draf surat tugas yang sudah diisi lengkap dan rapi;
- undangan atau surat pendukung bila penugasan berasal dari pihak luar;
- rencana jadwal dan rincian kebutuhan sesuai ketentuan perjalanan dinas;
- persetujuan atasan langsung sebelum diajukan ke pejabat berwenang.
Daftar ini contoh umum. Persyaratan final mengikuti SOP dan ketentuan keuangan yang berlaku.

BAB 3 — CONTOH FORMAT PEMBELAJARAN
Contoh kerangka draf untuk latihan menulis, bukan formulir resmi:
1) kop dan judul surat tugas;
2) nomor, tanggal, dan perihal yang jelas;
3) menimbang dan dasar bila diminta unit kerja;
4) diktum penugasan berisi nama, maksud, waktu, tempat;
5) tanda tangan pejabat berwenang dan tembusan bila perlu.
Contoh ini tidak menetapkan nomor, kode, atau tanda tangan yang sah.

BAB 4 — HAL YANG WAJIB DIKONFIRMASI KE SOP RESMI
Sebelum mengajukan surat tugas sungguhan, konfirmasi ke SOP resmi instansi:
- formulir dan sistem pengajuan yang berlaku saat ini;
- pejabat yang berwenang menandatangani untuk tiap jenis penugasan;
- batas waktu pengajuan dan alur persetujuan;
- ketentuan biaya, bukti, dan pertanggungjawaban perjalanan dinas;
- cara pencatatan nomor dan pengarsipan surat tugas.
Jika SOP resmi belum tersedia atau belum jelas, tanyakan ke atasan langsung atau unit persuratan. Jangan mengarang nomor, tenggat, atau biaya sebagai aturan resmi.

RINGKASAN
- Surat tugas diterbitkan pejabat berwenang; prosedurnya mengikuti SOP instansi.
- Siapkan identitas, maksud, waktu, tempat, dasar, dan persetujuan atasan.
- Contoh di materi ini untuk latihan menulis, bukan SOP resmi.
- Konfirmasi formulir, penandatangan, tenggat, dan biaya ke SOP resmi.

LATIHAN
1. Tulis draf pengajuan surat tugas untuk penugasan fiktif dengan unsur lengkap.
2. Tandai bagian draf yang wajib dikonfirmasi ke SOP resmi dan jelaskan alasannya.
3. Susun checklist kelengkapan sebelum mengajukan ke atasan langsung.
4. Refleksi: pertanyaan apa yang akan Anda ajukan ke unit persuratan sebelum pengajuan nyata?

GLOSARIUM
Surat tugas — surat penugasan resmi dari pejabat berwenang.
Disposisi — arahan pimpinan untuk menindaklanjuti surat.
Tembusan — salinan surat untuk pihak terkait yang perlu mengetahui.

REFERENSI DAN BACAAN LANJUTAN
Materi edukatif umum tanpa rujukan SOP resmi. Untuk pengajuan nyata, rujuk SOP persuratan Diskominfo Sawahlunto, ketentuan perjalanan dinas yang berlaku, dan arahan atasan. Modul ini tidak mencantumkan nomor SOP, tenggat, atau nominal.

Tanggal pemeriksaan: 2026-10-16 (dataset uji, belum verifikasi manusia, belum disetujui sebagai SOP).
TXT;
    }

    private function orientasiPegawaiBaru(): string
    {
        return <<<'TXT'
MODUL DATASET UJI INTERNAL SIAP — BUKAN DOKUMEN RESMI
Judul: Materi Orientasi Pegawai Baru
Tanggal penyusunan: 2026-10-16 | Status: dataset uji, bukan pedoman kepegawaian resmi.

Tujuan Pembelajaran
Peserta dapat:
1) menjelaskan tugas pokok unit kerja dan perannya;
2) menyebut tata tertib, jam kerja, dan etika dasar;
3) menggunakan perangkat, akun, dan folder kerja bersama;
4) menyusun rencana kerja 30 hari pertama.

Prasyarat
Telah diterima sebagai pegawai dan memiliki akun kerja atau surat pengantar unit.

Daftar Isi
1. Pendahuluan
2. Bab 1 — Mengenal unit kerja
3. Bab 2 — Tata tertib dan etika dasar
4. Bab 3 — Perangkat, akun, dan berkas kerja
5. Bab 4 — Rencana 30 hari pertama
6. Ringkasan
7. Latihan
8. Glosarium
9. Referensi

Pendahuluan
Minggu pertama menentukan kecepatan adaptasi. Fokus orientasi: kenal orang, kenal tugas, kenal aturan, dan kenal berkas. Modul ini panduan umum; ketentuan kepegawaian final mengikuti aturan resmi dan arahan pimpinan.

BAB 1 — MENGENAL UNIT KERJA
Cari tahu dan catat:
- tugas pokok dan fungsi bidang atau seksi tempat bertugas;
- atasan langsung dan rekan satu tim;
- layanan utama yang ditangani unit kerja;
- dokumen penting seperti rencana kerja dan arsip berjalan.
Contoh penerapan: buat peta satu halaman berisi nama, peran, dan kontak tim untuk rujukan harian.

BAB 2 — TATA TERTIB DAN ETIKA DASAR
- Hadir tepat waktu dan catat kehadiran sesuai sistem yang berlaku.
- Berpakaian rapi sesuai ketentuan unit kerja.
- Jaga kerahasiaan data warga dan dokumen internal.
- Sampaikan izin atau kendala lebih awal kepada atasan.
- Minta bimbingan bila ragu; catat arahan yang diterima.
Jam kerja, cuti, dan disiplin mengikuti peraturan kepegawaian resmi, bukan modul ini.

BAB 3 — PERANGKAT, AKUN, DAN BERKAS KERJA
- Minta akun kerja, akses folder bersama, dan daftar aplikasi yang dipakai tim.
- Simpan berkas di folder tim dengan penamaan NAMA_dokumen_TANGGAL_versi.
- Pelajari arsip berjalan sebelum membuat dokumen baru.
- Jangan menyimpan data sensitif di perangkat atau layanan pribadi.
Contoh: salin template surat yang dipakai tim, lalu buat draf pertama dengan bimbingan rekan.

BAB 4 — RENCANA 30 HARI PERTAMA
- Hari 1 sampai 7: kenalan, pelajari tugas, baca arsip, dan catat pertanyaan.
- Hari 8 sampai 14: kerjakan tugas kecil dengan pendampingan dan minta umpan balik.
- Hari 15 sampai 30: kerjakan tugas mandiri, susun ringkasan capaian, dan diskusikan target berikutnya dengan atasan.
Tulis target mingguan yang spesifik dan cara mengukurnya.

RINGKASAN
- Kenali tugas, orang, layanan, dan dokumen unit kerja.
- Patuhi tata tertib dan jaga kerahasiaan sejak hari pertama.
- Gunakan folder tim dan penamaan berkas yang konsisten.
- Susun rencana 30 hari dengan target yang terukur.

LATIHAN
1. Buat peta tim satu halaman berisi nama, peran, dan kontak.
2. Tulis tiga pertanyaan orientasi untuk atasan langsung minggu ini.
3. Susun rencana kerja 30 hari dengan target mingguan yang terukur.
4. Refleksi: hal apa yang paling membingungkan di minggu pertama dan cara mengatasinya?

GLOSARIUM
Atasan langsung — pimpinan yang memberi tugas dan penilaian harian.
Arsip berjalan — berkas aktif yang masih dipakai unit kerja.
Umpan balik — masukan untuk memperbaiki cara kerja.

REFERENSI DAN BACAAN LANJUTAN
Bahan ajar internal dari praktik orientasi umum. Untuk hak, kewajiban, dan disiplin ASN, rujuk peraturan kepegawaian resmi dan arahan SDM instansi. Modul tidak mengutip pasal atau angka resmi.

Tanggal pemeriksaan: 2026-10-16 (dataset uji, belum verifikasi manusia).
TXT;
    }

    private function seleksiCpns(): string
    {
        return <<<'TXT'
MODUL PEMBELAJARAN INTERNAL SIAP — DISKOMINFO SAWAHLUNTO
Judul: Seleksi CPNS
Tanggal penyusunan: 2026-10-16 | Status: draft, belum diterbitkan, menunggu verifikasi dan review hak.

Tujuan Pembelajaran
Peserta dapat:
1) menjelaskan perbedaan ASN, PNS, CPNS, dan PPPK;
2) menyebut tahapan seleksi CPNS secara umum;
3) menyebut kanal pendaftaran dan informasi resmi;
4) menjelaskan bahwa jadwal, formasi, dan nilai mengikuti pengumuman resmi periode berjalan.

Prasyarat
Tidak ada. Modul ini pengantar umum, bukan pengumuman formasi.

Daftar Isi
1. Pendahuluan
2. Bab 1 — Perbedaan ASN, PNS, CPNS, dan PPPK
3. Bab 2 — Tahapan seleksi CPNS secara umum
4. Bab 3 — Seleksi PPPK secara umum
5. Bab 4 — Kanal pendaftaran dan informasi resmi
6. Ringkasan
7. Latihan
8. Glosarium
9. Referensi

Pendahuluan
ASN adalah kategori aparatur negara, sedangkan CPNS adalah tahap sebelum seseorang diangkat menjadi PNS. Jalur seleksi dapat berubah pada setiap periode penerimaan. Modul ini memberi gambaran umum agar karyawan memahami istilah dan alur. Ketentuan yang mengikat selalu mengikuti pengumuman resmi periode berjalan, bukan modul ini.

BAB 1 — PERBEDAAN ASN, PNS, CPNS, DAN PPPK
- ASN adalah Aparatur Sipil Negara yang mencakup PNS dan PPPK.
- CPNS adalah calon PNS yang telah lulus seleksi dan menjalani masa percobaan sebelum diangkat menjadi PNS.
- PNS adalah pegawai ASN yang diangkat sebagai pegawai tetap oleh pejabat pembina kepegawaian.
- PPPK adalah pegawai ASN yang diangkat berdasarkan perjanjian kerja untuk jangka waktu tertentu sesuai ketentuan.
CPNS bukan jenis ASN yang terpisah dari PNS, melainkan tahap sebelum seseorang resmi menjadi PNS.

BAB 2 — TAHAPAN SELEKSI CPNS SECARA UMUM
Tahapan berikut gambaran umum dan dapat berbeda pada setiap periode:
1) seleksi administrasi — pemeriksaan ijazah, identitas, usia, persyaratan jabatan, dan dokumen lain sesuai pengumuman formasi;
2) Seleksi Kompetensi Dasar (SKD) — umumnya memakai CAT BKN dengan materi Tes Wawasan Kebangsaan, Tes Intelegensia Umum, dan Tes Karakteristik Pribadi;
3) Seleksi Kompetensi Bidang (SKB) — menguji kemampuan sesuai jabatan yang dilamar;
4) integrasi nilai dan kelulusan akhir — nilai diperhitungkan sesuai ketentuan resmi dan kelulusan berdasar peringkat serta formasi;
5) pemberkasan dan pengangkatan — penetapan NIP dan pengangkatan sebagai CPNS sesuai prosedur, lalu pengangkatan menjadi PNS setelah masa percobaan dan persyaratan terpenuhi.
Bentuk, bobot, dan nilai ambang batas mengikuti pengumuman resmi periode berjalan.

BAB 3 — SELEKSI PPPK SECARA UMUM
PPPK juga ASN, tetapi jalur seleksinya berbeda dari CPNS. Secara umum meliputi seleksi administrasi dan seleksi kompetensi dengan materi serta mekanisme sesuai jenis jabatan dan pengumuman instansi. Jangan menyamakan seleksi CPNS dengan seleksi PPPK begitu saja.

BAB 4 — KANAL PENDAFTARAN DAN INFORMASI RESMI
- Portal resmi CASN: SSCASN BKN di https://sscasn.bkn.go.id/
- Informasi kebijakan ASN: Kementerian PANRB di https://www.menpan.go.id/
- Informasi kepegawaian: Badan Kepegawaian Negara di https://www.bkn.go.id/
Jadwal, formasi, batas usia, kualifikasi pendidikan, nilai ambang batas, dan bobot penilaian mengikuti pengumuman resmi untuk periode penerimaan yang bersangkutan. Modul ini tidak mencantumkan jadwal atau angka sebagai ketentuan tetap.

RINGKASAN
- ASN mencakup PNS dan PPPK; CPNS adalah tahap sebelum menjadi PNS.
- Tahapan umum CPNS: administrasi, SKD, SKB, integrasi nilai, pemberkasan, dan pengangkatan.
- PPPK jalurnya berbeda dan mengikuti pengumuman jabatan masing-masing.
- Pendaftaran dan ketentuan resmi melalui SSCASN, PANRB, dan BKN periode berjalan.

LATIHAN
1. Jelaskan perbedaan ASN, PNS, CPNS, dan PPPK dengan kalimat sendiri.
2. Sebutkan lima tahapan seleksi CPNS dan dokumen yang diperiksa pada seleksi administrasi.
3. Sebutkan tiga kanal resmi seleksi CASN beserta fungsinya.
4. Refleksi: informasi apa yang wajib dicek pada pengumuman resmi sebelum mendaftar?

GLOSARIUM
ASN — Aparatur Sipil Negara yang mencakup PNS dan PPPK.
CPNS — calon PNS yang menjalani masa percobaan sebelum diangkat menjadi PNS.
SKD — Seleksi Kompetensi Dasar.
SKB — Seleksi Kompetensi Bidang.
SSCASN — portal resmi pendaftaran seleksi CASN oleh BKN.

REFERENSI DAN BACAAN LANJUTAN
Terverifikasi portal (perlu cek edisi terbaru sebelum dipakai sebagai ketentuan):
- SSCASN BKN — https://sscasn.bkn.go.id/
- Kementerian PANRB — https://www.menpan.go.id/
- Badan Kepegawaian Negara — https://www.bkn.go.id/
Catatan keterbatasan: modul pengantar umum. Nomor peraturan, jadwal, formasi, dan angka penilaian mengikuti pengumuman resmi periode berjalan; modul ini sengaja tidak menetapkan angka tetap.

Tanggal pemeriksaan: 2026-10-16 (draft, perlu verifikasi substansi oleh reviewer konten).
TXT;
    }
}
