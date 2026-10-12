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
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LibraryNativeBatchSeeder extends Seeder
{
    public function run(): void
    {
        $uploaderId = User::where('email', 'admin.system@siap.test')->value('id')
            ?? User::where('role', 'admin')->value('id');
        if (! $uploaderId) {
            $this->command->warn('LibraryNativeBatchSeeder: uploader admin tidak ditemukan, skip.');
            return;
        }

        $levels = [
            'dasar' => LearningLevel::where('key', 'dasar')->firstOrFail()->id,
            'menengah' => LearningLevel::where('key', 'menengah')->firstOrFail()->id,
        ];
        $cats = [
            'kompetensi-digital' => LearningCategory::where('slug', 'kompetensi-digital')->firstOrFail()->id,
            'keamanan-informasi' => LearningCategory::where('slug', 'keamanan-informasi')->firstOrFail()->id,
            'manajemen' => LearningCategory::where('slug', 'manajemen')->firstOrFail()->id,
            'pelayanan-publik' => LearningCategory::where('slug', 'pelayanan-publik')->firstOrFail()->id,
            'etika-profesionalisme' => LearningCategory::where('slug', 'etika-profesionalisme')->firstOrFail()->id,
        ];

        $modules = [
            [
                'slug' => 'literasi-digital-untuk-asn',
                'title' => 'Literasi Digital untuk ASN',
                'description' => 'Modul pembelajaran internal SIAP: konsep literasi digital, kecakapan, etika, budaya, keamanan, dan penerapan kerja harian ASN. Disusun untuk Diskominfo Sawahlunto, bersifat edukatif, bukan sertifikasi resmi instansi.',
                'level' => $levels['dasar'],
                'category' => $cats['kompetensi-digital'],
                'content' => $this->literasiDigital(),
            ],
            [
                'slug' => 'keamanan-informasi-dan-perlindungan-data',
                'title' => 'Keamanan Informasi dan Perlindungan Data',
                'description' => 'Modul pembelajaran internal SIAP: prinsip kerahasiaan-integritas-ketersediaan, kata sandi, phishing, penanganan dokumen, praktik perangkat/jaringan, dan respons insiden. Bersifat edukatif, bukan kewajiban hukum spesifik.',
                'level' => $levels['dasar'],
                'category' => $cats['keamanan-informasi'],
                'content' => $this->keamananInformasi(),
            ],
            [
                'slug' => 'komunikasi-efektif-di-lingkungan-kerja',
                'title' => 'Komunikasi Efektif di Lingkungan Kerja',
                'description' => 'Modul pembelajaran internal SIAP: komunikasi lisan-tulisan, mendengarkan aktif, email profesional, koordinasi, dan pengelolaan perbedaan pendapat. Dilengkapi contoh kasus dan latihan.',
                'level' => $levels['dasar'],
                'category' => $cats['manajemen'],
                'content' => $this->komunikasiEfektif(),
            ],
            [
                'slug' => 'pelayanan-publik-yang-berkualitas',
                'title' => 'Pelayanan Publik yang Berkualitas',
                'description' => 'Modul pembelajaran internal SIAP: konsep pelayanan publik, kebutuhan pengguna, komunikasi masyarakat, aksesibilitas, penanganan keluhan, dan indikator evaluasi. Catatan: pembahasan bersifat edukatif; rujukan hukum resmi tetap pada peraturan terkait.',
                'level' => $levels['dasar'],
                'category' => $cats['pelayanan-publik'],
                'content' => $this->pelayananPublik(),
            ],
            [
                'slug' => 'etika-profesional-di-lingkungan-kerja',
                'title' => 'Etika Profesional di Lingkungan Kerja',
                'description' => 'Modul pembelajaran internal SIAP: integritas, konflik kepentingan konseptual, kerahasiaan, kolaborasi, dan penggunaan teknologi/AI bertanggung jawab. Pisahkan prinsip umum dari kewajiban hukum spesifik yang perlu rujukan resmi.',
                'level' => $levels['dasar'],
                'category' => $cats['etika-profesionalisme'],
                'content' => $this->etikaProfesional(),
            ],
        ];

        foreach ($modules as $m) {
            if (LearningMaterial::where('slug', $m['slug'])->exists() || LearningMaterial::where('title', $m['title'])->exists()) {
                $this->command->info("LibraryNativeBatchSeeder: skip duplikat {$m['slug']}");
                continue;
            }
            DB::transaction(function () use ($m, $uploaderId) {
                LearningMaterial::create([
                    'title' => $m['title'],
                    'slug' => $m['slug'],
                    'description' => $m['description'],
                    'content' => $m['content'],
                    'material_type' => LearningMaterialType::Native->value,
                    'scope' => LearningMaterialScope::Custom->value,
                    'learning_level_id' => $m['level'],
                    'learning_category_id' => $m['category'],
                    'publication_status' => LearningPublicationStatus::Draft->value,
                    'verification_status' => LearningVerificationStatus::Draft->value,
                    'validity_status' => LearningValidityStatus::Unknown->value,
                    'published_at' => null,
                    'uploaded_by' => $uploaderId,
                    'file_path' => null,
                    'file_original_name' => null,
                    'file_mime' => null,
                    'file_size' => null,
                ]);
            });
            $this->command->info("LibraryNativeBatchSeeder: dibuat {$m['slug']}");
        }
    }

    private function literasiDigital(): string
    {
        return <<<'TXT'
MODUL PEMBELAJARAN INTERNAL SIAP — DISKOMINFO SAWAHLUNTO
Judul: Literasi Digital untuk ASN
Tanggal penyusunan: 2026-10-13 | Status: draft, belum diterbitkan, menunggu verifikasi dan review hak.

Tujuan Pembelajaran
Setelah mempelajari modul ini, peserta dapat:
1) menjelaskan landasan literasi digital di konteks kerja pemerintahan;
2) memeriksa informasi dan sumber digital sebelum meneruskan;
3) berkomunikasi dan berkolaborasi digital secara etis dan tercatat;
4) mengelola identitas dan jejak digital sebagai ASN;
5) berbagi dokumen dan tautan dengan aman termasuk masa berlaku akses;
6) mempraktikkan alur literasi digital end-to-end dalam layanan ASN.
Kriteria keberhasilan: peserta mampu menyebut 4 pilar literasi digital, menulis langkah verifikasi 4 langkah untuk informasi meragukan, memberi contoh pesan profesional dengan tujuan-tindakan-tenggat-PJ, dan menjelaskan cara meninjau atau mencabut akses tautan dokumen sesuai kebijakan instansi.

Prasyarat
Peserta mampu mengoperasikan perangkat komputer/ponsel untuk tugas kantor dan pernah menggunakan email serta aplikasi perkantoran.

Daftar Isi
1. Pendahuluan — mengapa literasi digital penting bagi ASN
2. Bab 1 — Landasan literasi digital
3. Bab 2 — Memeriksa informasi dan sumber digital
4. Bab 3 — Berkomunikasi dan berkolaborasi digital
5. Bab 4 — Mengelola identitas dan jejak digital
6. Bab 5 — Berbagi dokumen dan tautan dengan aman
7. Bab 6 — Praktik literasi digital dalam layanan ASN
8. Ringkasan
9. Latihan refleksi dan pedoman evaluasi
10. Glosarium
11. Referensi dan bacaan lanjutan

Pendahuluan
Literasi digital bukan sekadar bisa memakai aplikasi. Bagi ASN, ini tentang kemampuan mencari, menilai, membuat, dan membagikan informasi secara bertanggung jawab untuk mendukung pelayanan publik. Diskominfo menghadapi ekspektasi masyarakat yang semakin digital, sementara risiko misinformasi dan kebocoran data meningkat. Modul ini membantu ASN membedakan alat dan tujuan: alat boleh berganti, prinsip kehati-hatian dan akuntabilitas tetap. Modul tidak memberi sertifikasi; fokus pada kebiasaan kerja yang dapat diamati atasan dan rekan.

BAB 1 — LANDASAN LITERASI DIGITAL
Mengapa penting untuk ASN: (a) melayani masyarakat yang menuntut informasi cepat dan akurat; (b) mengelola arsip, data, dan laporan agar tetap tertib dan dapat diaudit; (c) berkomunikasi lintas bidang/tingkat tanpa menimbulkan salah tafsir; (d) menggunakan sistem digital (e-office, drive, layanan publik) secara bertanggung jawab agar kepercayaan publik terjaga.
Kerangka konseptual vs kewajiban: empat pilar Gerakan Nasional Literasi Digital yang dikelola oleh Komdigi (dulu Kominfo) dan dapat diverifikasi di https://literasidigital.id/ merupakan kerangka edukatif untuk memahami kemampuan, bukan daftar kewajiban hukum. Kewajiban hukum atau kebijakan formal tetap mengikuti peraturan dan SOP yang berlaku di instansi. Bedakan: kerangka membantu belajar, aturan mengikat pelaksanaan.
Empat pilar tersebut:
- Kemampuan Digital — mengetahui, memahami, dan menggunakan perangkat keras/lunak dan sistem operasi digital.
- Etika Berdigital — menyadari dan menerapkan tata kelola etika digital (netiquette).
- Budaya Berdigital — membaca dan membangun wawasan kebangsaan, nilai Pancasila dan Bhinneka Tunggal Ika.
- Keamanan Berdigital — mengenali dan meningkatkan kesadaran keamanan digital.
Keterampilan teknis saja tidak cukup tanpa kemampuan menilai sumber: apakah sumber resmi, apakah tanggal relevan, apakah ada konteks yang hilang. Contoh sederhana: menerima PDF berjudul surat edaran tanpa kop dan tanda tangan — ASN yang literat akan verifikasi ke sumber resmi (JDIH instansi, website resmi, atau konfirmasi atasan) sebelum meneruskan.
Kesalahan umum: menganggap viral sama dengan benar, atau menganggap semua yang ada di grup kerja sudah terverifikasi. Jika rujukan kebijakan spesifik belum dapat diverifikasi, gunakan referensi umum yang tersedia dan catat untuk verifikasi reviewer; jangan mengarang nomor regulasi atau tautan.

BAB 2 — MEMERIKSA INFORMASI DAN SUMBER DIGITAL
Prosedur praktis sebelum meneruskan informasi:
1) identifikasi penerbit asli — periksa kop, tanda tangan, domain pengirim, dan apakah akun yang membagikan adalah akun resmi;
2) periksa tanggal dan konteks — apakah informasi masih relevan, apakah ada pembaruan, apakah kutipan diambil utuh atau terpotong;
3) cari sumber pembanding independen — cari di kanal resmi lain (website instansi, JDIH, akun resmi) tanpa bergantung pada satu grup chat;
4) bedakan fakta dari opini/klaim — fakta dapat diverifikasi (tanggal kejadian, angka, keputusan), opini adalah penilaian.
Contoh fiktif realistis ASN: Anda menerima pesan berantai di grup kerja berisi "Surat edaran cuti bersama baru, berlaku besok, unduh di tautan pendek berikut". Penerbit tidak jelas, tanpa nomor surat, tanpa kop. Tindakan: jangan teruskan dulu; cek nomor dan tanggal surat di JDIH/website resmi atau konfirmasi ke atasan/operator persuratan; cari pengumuman pembanding di kanal resmi instansi.
Jika informasi meragukan:
- jangan langsung meneruskan atau memberi instruksi berdasar informasi tersebut;
- verifikasi melalui kanal resmi yang diketahui independen (bukan tautan dari pesan yang sama);
- jika sudah terlanjur tersebar keliru, koreksi secara proporsional: sampaikan ralat singkat dengan sumber yang benar dan instruksi yang diperbarui.
Catatan: jangan menilai keaslian hanya dari logo atau tampilan visual yang meyakinkan; periksa penerbit dan sumber pembanding.
Kecakapan pendukung yang tetap relevan:
- pengelolaan Berkas (penamaan NAMA_dokumen_TANGGAL_versi, contoh SK_Tugas_2026-10-12_v03, folder terstruktur, versioning);
- pencarian (kata kunci tepat, filter tanggal, operator site: untuk membatasi domain);
- alur persetujuan digital: siapa berwenang menyetujui, jejak audit, dan pemulihan versi sebelumnya bila salah kirim.

BAB 3 — BERKOMUNIKASI DAN BERKOLABORASI DIGITAL
Pemilihan kanal: gunakan kanal publik (website/pengumuman resmi) untuk informasi yang sudah boleh disebar; grup kerja untuk koordinasi cepat yang tidak sensitif; komunikasi akses terbatas (surat dinas, sistem berizin) untuk data internal atau personal. Pemilihan kanal mengikuti kebijakan instansi, bukan kenyamanan semata.
Etika pesan: hormati privasi (izin sebelum membagikan foto/data warga), hindari ujaran merendahkan dan bias, beri atribusi saat mengutip, dan cantumkan konteks agar tidak salah tafsir. Contoh kasus: staf membagikan tangkapan layar percakapan berisi data warga ke grup besar untuk minta bantuan. Alternatif: samarkan identitas (inisial, sensor NIK) atau konsultasi via jalur resmi akses terbatas dengan catatan tujuan.
Konteks dan pencatatan: setiap pesan kerja harus memuat tujuan, tindakan yang diminta, tenggat, dan penanggung jawab. Contoh pesan profesional: "Yth. Tim Data, mohon siapkan rekap pelayanan triwulan III (tabel) untuk rapat 13 Okt jam 09.00 Ruang A. Penanggung jawab: Bidang Pelayanan. Simpan di folder Drive X dan konfirmasi jam 15.00. Terima kasih."
Perlindungan informasi dalam kolaborasi: bagikan data minimal yang diperlukan, gunakan izin akses terbatas sesuai kebijakan, catat keputusan dan pembaruan status di dokumen bersama, bukan hanya di chat yang mudah tenggelam. Bantu rekan yang kurang mahir dengan panduan langkah, bukan mengambil alih tanpa penjelasan.

BAB 4 — MENGELOLA IDENTITAS DAN JEJAK DIGITAL
Jejak digital ASN mencakup unggahan, komentar, foto kegiatan, dan dokumen yang dibagikan ulang. Contoh jejak yang dapat memengaruhi kepercayaan publik: foto layanan yang menampilkan data warga tanpa sensor, pernyataan pribadi yang mengatasnamakan instansi, atau meneruskan informasi belum terverifikasi sehingga menimbulkan kebingungan. Jejak tersebut dapat tersimpan lama dan disalin ulang di luar kendali pengunggah.
Pemeriksaan sebelum mengunggah atau meneruskan konten:
- kewenangan — apakah Anda berwenang membagikan informasi tersebut sesuai tugas dan kebijakan instansi;
- akurasi — apakah informasi telah diverifikasi melalui sumber resmi dan konteksnya utuh;
- data pribadi — apakah ada NIK, alamat, foto wajah, atau data sensitif yang perlu disamarkan atau tidak dibagikan;
- audiens — apakah kanal dan penerima sudah tepat (publik, grup kerja, akses terbatas);
- konsekuensi — apakah unggahan dapat menimbulkan salah tafsir, merugikan warga, atau melanggar kebijakan.
Bab ini berfokus pada perilaku dan tanggung jawab digital. Aspek teknis keamanan seperti kata sandi, perangkat, dan tautan mencurigakan dibahas lebih rinci pada modul Keamanan Informasi untuk ASN; rujuk modul tersebut untuk langkah teknis dan SOP instansi.

BAB 5 — BERBAGI DOKUMEN DAN TAUTAN DENGAN AMAN
Langkah berurutan sebelum membagikan dokumen:
1) periksa klasifikasi sesuai kebijakan instansi — tentukan apakah dokumen bersifat publik, internal, atau contoh terbatas edukatif, dan ikuti kebijakan yang berlaku;
2) pilih penerima yang berhak — hanya yang memerlukan untuk tugas, bukan seluruh grup;
3) tetapkan izin minimum — lihat saja bila cukup, batasi waktu akses bila tersedia, hindari tautan yang dapat dibuka siapa saja untuk dokumen internal;
4) verifikasi tujuan tautan — pastikan tautan mengarah ke dokumen yang benar, bukan versi lama atau folder yang salah;
5) uji akses dengan akun yang sesuai bila diizinkan — cek apakah penerima dapat membuka tanpa membuka untuk umum.
Cara memeriksa masa berlaku dan mengelola akses:
- lihat pengaturan berbagi pada platform untuk mengetahui apakah akses masih aktif, berbatas waktu, atau terbuka umum;
- cabut atau perbarui akses ketika tugas selesai, penerima berubah, daftar penerima salah, atau tautan tersebar melebihi kebutuhan;
- ganti tautan lama dengan tautan baru berizin terbatas bila terjadi kesalahan berbagi, dan informasikan penerima yang sah.
Catatan: nama menu dan lokasi pengaturan berbeda antar platform; ikuti panduan resmi platform yang dipakai instansi, jangan mengandalkan petunjuk UI produk tertentu yang tidak diverifikasi di sini.
Jangan menganggap tautan yang dapat dibuka siapa saja sebagai aman: untuk dokumen internal, tautan semacam itu berarti siapa yang memiliki tautan dapat melihat, termasuk bila tautan diteruskan tanpa sengaja. Pilih izin terbatas dan batas waktu.
Skenario contoh (klasifikasi sebagai contoh edukatif, bukan standar universal): notulen rapat berisi daftar hadir dan keputusan — bagikan dengan izin lihat saja kepada peserta rapat dan atasan terkait, batas waktu 7 hari; setelah tindak lanjut selesai, tinjau daftar akses dan cabut yang tidak lagi memerlukan.

BAB 6 — PRAKTIK LITERASI DIGITAL DALAM LAYANAN ASN
Skenario end-to-end tanpa merek aplikasi tertentu:
1) Verifikasi informasi — menerima pesan "perubahan jam layanan besok" tanpa sumber jelas. Langkah: cek tanggal dan penerbit, cari pengumuman pembanding di website/kanal resmi, konfirmasi ke atasan/petugas layanan; bila belum terverifikasi, jangan umumkan dulu.
2) Komunikasi — setelah terverifikasi, susun pengumuman singkat: tujuan perubahan jam, siapa terdampak, tindakan yang diminta warga, tenggat, dan kontak. Pilih kanal sesuai kebijakan (website/papan pengumuman untuk publik, grup kerja untuk koordinasi internal).
3) Pengelolaan dokumen — simpan pengumuman final di folder terpusat dengan penamaan NAMA_dokumen_TANGGAL_versi dan izin terbatas; bagikan tautan sesuai langkah BAB 5; catat keputusan dan pembaruan di dokumen bersama.
4) Evaluasi dampak — pantau pertanyaan warga, antrean, dan keluhan 1–2 hari setelah perubahan; catat apakah informasi tersampaikan jelas, apakah ada kebingungan, dan perbaiki template untuk kejadian berikutnya.
Prosedur sederhana yang dapat diterapkan:
- sebelum menyebar informasi, verifikasi 4 langkah (penerbit-tanggal-pembanding-fakta/opini) dan catat sumbernya;
- sebelum berbagi dokumen, terapkan 5 langkah berbagi aman dan tentukan kapan akses perlu ditinjau atau dicabut;
- setelah layanan, evaluasi 3 pertanyaan: apakah warga memahami, apakah dokumen mudah ditemukan, apakah ada koreksi yang diperlukan.
Jika menyebut SOP atau retensi, ikuti kebijakan instansi yang berlaku; modul ini tidak menetapkan jangka retensi universal.

RINGKASAN
- Bab 1 — Landasan: literasi digital untuk melayani, mengelola informasi, berkomunikasi, dan memakai sistem; bedakan kerangka edukatif 4 pilar dari kewajiban hukum/SOP.
- Bab 1 — Pilar: Kemampuan, Etika, Budaya, Keamanan Berdigital sebagai kerangka belajar, bukan daftar kewajiban.
- Bab 2 — Memeriksa informasi: identifikasi penerbit, cek tanggal/konteks, cari pembanding independen, bedakan fakta/opini; tunda meneruskan bila ragu dan ralat proporsional bila keliru.
- Bab 3 — Komunikasi/kolaborasi: pilih kanal publik/grup/terbatas sesuai kebijakan; tulis pesan tujuan-tindakan-tenggat-PJ; catat keputusan di dokumen bersama dan lindungi data minimal.
- Bab 4 — Identitas/jejak: jejak ASN memengaruhi kepercayaan; periksa kewenangan-akurasi-data pribadi-audiens-konsekuensi sebelum unggah/teruskan.
- Bab 5 — Berbagi dokumen/tautan: 5 langkah klasifikasi-penerima-izin minimum-verifikasi tautan-uji akses; periksa masa berlaku, cabut saat selesai/berubah/salah, jangan pakai tautan terbuka untuk internal; nama menu berbeda antar platform.
- Bab 6 — Praktik layanan: alur verifikasi-komunikasi-pengelolaan dokumen-evaluasi dampak; terapkan prosedur sederhana tanpa merek dan ikuti SOP instansi.
- Alur singkat penghubung: verifikasi dulu sumbernya, komunikasikan dengan tujuan dan tanggung jawab yang jelas, lalu bagikan dokumen hanya kepada yang berhak dengan izin dan masa berlaku terbatas, kemudian evaluasi apakah layanan tersampaikan kepada masyarakat.

LATIHAN REFLEKSI — beserta pedoman evaluasi dan rubrik singkat
1. Informasi belum terverifikasi: Anda menerima pesan berantai "jam layanan besok berubah" tanpa kop/nomor. Tulis langkah verifikasi 4 langkah dan keputusan meneruskan atau menunda. Pedoman: identifikasi penerbit-tanggal-pembanding-fakta/opini, verifikasi kanal resmi, jangan teruskan sebelum terverifikasi. Rubrik: 4 langkah lengkap + keputusan menunda = baik; 2-3 langkah = cukup; hanya 1 = kurang.
2. Tautan perlu ditinjau: Anda salah membagikan tautan notulen dengan akses terlalu luas dan masa berlaku terbuka. Jelaskan cara memeriksa masa berlaku, mencabut akses, dan mengganti dengan tautan terbatas serta pemberitahuan ke penerima. Pedoman: cek pengaturan berbagi, cabut/perbarui akses, ganti tautan terbatas, informasikan penerima sah. Rubrik: 4 tindakan lengkap = baik; 2-3 = cukup; hanya cabut tanpa ganti = kurang. Kriteria SOP: jawaban menyebut kepatuhan terhadap kebijakan instansi.
3. Berikan contoh jejak digital yang dapat memengaruhi kepercayaan publik dan pemeriksaan 5 poin sebelum mengunggah. Pedoman: contoh foto/data warga tanpa sensor atau pernyataan mengatasnamakan instansi, plus cek kewenangan-akurasi-data pribadi-audiens-konsekuensi.
4. Tulis pesan profesional untuk meminta data dengan tujuan-tindakan-tenggat-PJ dan kanal yang tepat. Pedoman: tujuan jelas, tindakan spesifik, tenggat waktu, penanggung jawab, kanal sesuai kebijakan, dan tautan folder (bukan tautan terbuka untuk umum).
5. Simulasikan alur end-to-end perubahan jam layanan dari verifikasi hingga evaluasi dampak. Pedoman: urut verifikasi-komunikasi-pengelolaan dokumen-evaluasi, tanpa merek aplikasi, dan tandai langkah yang mengikuti SOP instansi.
6. Refleksi: satu kebiasaan berbagi dokumen atau memeriksa sumber yang akan Anda ubah minggu ini dan cara mengukurnya. Pedoman: target spesifik dan terukur (mis. tinjau 100% tautan aktif mingguan dan cabut yang sudah selesai).

GLOSARIUM
Literasi digital — kemampuan mencari, menilai, membuat, dan membagikan informasi melalui sistem digital secara bertanggung jawab dalam konteks kerja pemerintahan.
Memeriksa sumber — langkah mengidentifikasi penerbit asli, tanggal, konteks, dan pembanding independen sebelum mempercayai atau meneruskan informasi.
Kanal komunikasi — jalur penyampaian pesan yang dipilih berdasarkan sifat informasi dan kebijakan instansi (publik, grup kerja, akses terbatas).
Jejak digital — rekam aktivitas digital (unggahan, komentar, dokumen) yang dapat tersimpan lama dan memengaruhi kepercayaan publik terhadap ASN.
Berbagi aman — praktik membagikan dokumen dengan penerima terbatas, izin minimum, verifikasi tautan, dan pengelolaan masa berlaku akses sesuai kebijakan instansi.
Identitas digital — representasi ASN di ruang digital melalui akun, unggahan, dan interaksi yang membawa nama instansi.

REFERENSI DAN BACAAN LANJUTAN
Terverifikasi (diakses 2026-10-12, perlu cek edisi terbaru):
- Gerakan Nasional Literasi Digital, Komdigi — 4 Pilar Literasi Digital (Kemampuan Digital, Etika Berdigital, Budaya Berdigital, Keamanan Berdigital) — https://literasidigital.id/
- Badan Siber dan Sandi Negara — Publikasi — https://www.bssn.go.id/publikasi/
- Badan Siber dan Sandi Negara — Edukasi Masyarakat — https://www.bssn.go.id/edukasi-masyarakat/
- Lembaga Administrasi Negara — https://lan.go.id/
Arahan umum (perlu verifikasi mandiri edisi terbaru, belum diverifikasi URL spesifik):
- Materi pengembangan kompetensi ASN LAN (cek situs resmi LAN).
Catatan keterbatasan: modul disusun dari pengetahuan umum dan praktik kerja pemerintahan, tidak mengutip teks berhak cipta panjang. Daftar di atas tidak menggantikan SOP instansi; pembaca perlu memastikan edisi terbaru di situs resmi.

Tanggal pemeriksaan: 2026-10-13 (draft, perlu verifikasi substansi oleh reviewer konten).
TXT;
    }

    private function keamananInformasi(): string
    {
        return <<<'TXT'
MODUL PEMBELAJARAN INTERNAL SIAP — DISKOMINFO SAWAHLUNTO
Judul: Keamanan Informasi dan Perlindungan Data
Tanggal penyusunan: 2026-10-13 | Status: draft | Sifat: edukatif, bukan SOP resmi. Rujuk SOP instansi untuk kewajiban spesifik.

Tujuan Pembelajaran
Peserta dapat:
1) menjelaskan CIA triad dengan contoh kerja;
2) menerapkan praktik kata sandi dan autentikasi aman;
3) mengenali phishing/rekayasa sosial dan langkah verifikasi;
4) menangani dokumen/data kerja sesuai contoh klasifikasi edukatif;
5) mempraktikkan keamanan perangkat/jaringan;
6) melakukan respons awal insiden dengan preservasi bukti dan eskalasi.
Kriteria keberhasilan: peserta mampu membedakan kerahasiaan vs integritas dengan contoh, menyebut 3 ciri phishing termasuk typosquatting, dan menulis urutan respons insiden tanpa menghilangkan jejak serta mengisi template laporan 6 kolom tanpa menulis kata sandi/OTP.

Prasyarat
Pemahaman dasar penggunaan komputer, email, dan penyimpanan berkas. Tidak diperlukan keahlian teknis keamanan siber.

Daftar Isi
1. Pendahuluan
2. Bab 1 — Kerahasiaan, integritas, ketersediaan
3. Bab 2 — Kata sandi dan autentikasi
4. Bab 3 — Phishing dan rekayasa sosial
5. Bab 4 — Penanganan dokumen dan data kerja
6. Bab 5 — Praktik perangkat dan jaringan
7. Bab 6 — Respons awal insiden
8. Ringkasan
9. Latihan
10. Glosarium
11. Referensi

Pendahuluan
Kebocoran data sering berawal dari kelalaian kecil: kata sandi dipakai ulang, tautan diklik tanpa verifikasi, atau dokumen dibagikan via kanal tidak resmi. Dampaknya besar bagi warga dan reputasi instansi. Modul ini menekankan pencegahan yang dapat dilakukan setiap ASN tanpa menunggu tim IT, serta kapan harus eskalasi. Modul tidak menyajikan perintah teknis spesifik (mis. konfigurasi firewall) sebagai kewajiban — fokus pada perilaku aman yang teruji. Untuk instruksi insiden yang mengikat, SOP instansi dan arahan BSSN yang berlaku.

BAB 1 — KERAHASIAAN, INTEGRITAS, KETERSEDIAAN (CIA)
Kerahasiaan: hanya pihak berwenang yang dapat mengakses. Contoh: data kependudukan, surat masih konsep. Integritas: data tidak diubah tanpa jejak dan otorisasi. Contoh: angka anggaran yang diubah tanpa persetujuan dan tanpa log. Ketersediaan: data dapat diakses saat dibutuhkan. Contoh: sistem down saat pelayanan loket. Ketiga aspek harus seimbang; terlalu ketat hingga menghambat kerja mengganggu ketersediaan, terlalu longgar mengorbankan kerahasiaan.
Tanyakan sebelum berbagi: data ini masuk sensitivitas apa, siapa yang boleh akses, dan bagaimana memastikan perubahan tercatat (log/versi). Prinsip izin minimal: beri akses view-only dan batas waktu, bukan full control permanen.
Trade-off yang perlu dipahami dengan contoh layanan ASN realistis:
- Pelayanan kependudukan (mis. pencetakan KTP/KK): jika arsip kependudukan dibuka tanpa batas (ketersediaan tinggi, kerahasiaan rendah), risiko kebocoran NIK besar; jika dikunci total tanpa jalur layanan yang jelas (kerahasiaan tinggi, ketersediaan rendah), warga tidak dapat dilayani dan antrean menumpuk. Jalan tengah: akses hanya bagi petugas loket berwenang dengan login, log perubahan, dan ketersediaan sistem dijaga via cadangan.
- Publikasi pengumuman lelang pengadaan: jika diunggah tanpa verifikasi integritas (mudah diubah tanpa jejak), warga ragu keabsahan; jika harus 5 persetujuan berlapis tanpa jejak versi, ketersediaan terlambat. Jalan tengah: satu pintu publikasi dengan versi dan tanda persetujuan tercatat.
Prinsip: sesuaikan tingkat pembatasan dengan sensitivitas dan kebutuhan layanan, selalu ikuti kebijakan klasifikasi instansi.

BAB 2 — KATA SANDI DAN AUTENTIKASI
Gunakan frasa sandi panjang yang mudah diingat tapi sulit ditebak, berbeda untuk tiap layanan penting. Hindari tanggal lahir, NIP, atau pola keyboard (qwerty). Aktifkan verifikasi dua langkah (OTP/aplikasi autentikasi) untuk email dan sistem sensitif. Jangan simpan kata sandi di sticky note atau file teks tanpa proteksi; gunakan pengelola kata sandi yang disetujui instansi. Saat mengganti perangkat atau curiga kompromi, cabut sesi lama dan ganti kata sandi dari perangkat aman.
Praktik baik:
- ganti kata sandi bila ada indikasi kebocoran, bukan rotasi periodik tanpa alasan;
- jangan berbagi kata sandi via chat atau menulis di papan tulis;
- gunakan frasa 4-5 kata acak (contoh pola edukatif, bukan kata sandi sungguhan).
Langkah praktis menggunakan pengelola kata sandi yang diizinkan instansi (tidak merekomendasikan merek tertentu sebagai resmi):
- pastikan aplikasi disetujui — tanyakan IT/helpdesk daftar alat yang diizinkan instansi, jangan instal pengelola pribadi tanpa persetujuan;
- aktifkan perlindungan akun pengelola sesuai kebijakan — kunci utama yang kuat, MFA untuk vault bila tersedia dan diizinkan, serta kunci perangkat;
- simpan kredensial secara aman — simpan di vault terenkripsi yang disetujui, beri label layanan dan tanggal, jangan salin ke chat atau file teks;
- aktifkan MFA pada layanan penting — email dinas dan sistem sensitif, ikuti panduan aktivasi resmi instansi.
Alur awal penggunaan (contoh edukatif, ikuti SOP instansi):
1) konfirmasi persetujuan IT untuk pengelola yang akan dipakai;
2) aktifkan perlindungan akun (kunci utama + MFA vault bila tersedia);
3) simpan kredensial layanan penting ke vault dengan label jelas;
4) aktifkan MFA pada email/sistem sensitif sesuai panduan instansi.
Tindakan jika akun atau kata sandi dicurigai bocor:
- segera ganti kata sandi dari perangkat aman yang diketahui bersih, jangan dari perangkat yang dicurigai terkompromi;
- cabut sesi aktif di perangkat lain sesuai panduan layanan;
- laporkan ke helpdesk/IT sesuai SOP dan catat waktu serta akun terdampak tanpa menulis kata sandi/OTP di laporan;
- periksa aktivitas login dan ganti kredensial terkait yang memakai pola sama; ikuti arahan pemindaian perangkat dari IT.
Catatan: prosedur dan daftar alat yang diizinkan harus mengikuti kebijakan instansi; modul ini tidak menetapkan aplikasi tertentu sebagai resmi.

BAB 3 — PHISHING DAN REKAYASA SOSIAL
Phishing memanfaatkan rasa urgensi: "akun akan diblokir", "hadiah", "tagihan", "file .apk undangan". Ciri:
- pengirim mirip tapi tidak sama (typosquatting);
- ejaan/grammar aneh, tekanan waktu berlebihan;
- tautan dipendekkan atau domain tidak resmi;
- permintaan data sensitif atau instalasi aplikasi.
Contoh fiktif untuk edukasi (bukan alamat atau tautan nyata, jangan diklik, jangan disalin sebagai tautan aktif):
- Pengirim fiktif: notifikasi@dis-kominfo-sawahlunto.example — perhatikan tanda hubung berlebih dan akhiran .example yang bukan domain pemerintah .go.id.
- Tautan fiktif: https://portal-diskominfo-sawahlunto.example/login — tambahan tanda hubung, ejaan mirip, dan akhiran .example bukan alamat resmi.
Ciri typosquatting yang dapat dipelajari: tambahan tanda hubung, huruf yang mirip (l dan I), akhiran domain bukan .go.id, serta subdomain panjang yang mengaburkan domain utama.
Cara memeriksa alamat pengirim dan domain sebenarnya tanpa mengeklik:
- arahkan kursor ke tautan (hover) untuk melihat alamat tujuan di bilah status, atau tekan lama di ponsel untuk pratinjau, jangan mengeklik;
- periksa domain akhir setelah @ untuk email dan sebelum / pertama untuk tautan, bandingkan dengan daftar domain resmi yang diketahui secara independen;
- periksa header email bila tersedia di klien (lihat From dan Return-Path asli) tanpa membuka lampiran.
Cara memverifikasi permintaan melalui kanal resmi yang diketahui independen:
- hubungi nomor telepon atau alamat email resmi yang ada di buku alamat internal/situs resmi yang diketik manual, bukan nomor/tautan yang diberikan dalam pesan mencurigakan;
- kunjungi situs resmi dengan mengetik alamat manual atau via bookmark resmi, bukan via tautan pesan;
- konfirmasi ke atasan/helpdesk melalui kanal resmi terpisah.
Rekayasa sosial via telepon: penelepon mengaku atasan meminta transfer data. Verifikasi via kanal independen: hubungi nomor resmi instansi, bukan nomor yang diberikan penelepon. Jangan meneruskan pesan mencurigakan ke rekan tanpa laporan resmi.
Tindakan jika sudah mengeklik tautan atau memasukkan kredensial:
- segera putuskan jaringan bila diizinkan SOP, jangan hapus jejak log;
- segera ganti kata sandi dari perangkat aman dan aktifkan/cabut MFA sesuai arahan;
- laporkan ke helpdesk/CSIRT dengan detail tautan fiktif dan waktu kejadian, tanpa menulis kata sandi/OTP di laporan;
- ikuti arahan pemindaian perangkat dan pemulihan akun dari IT; jangan mencoba memperbaiki dengan alat tidak resmi.

BAB 4 — PENANGANAN DOKUMEN DAN DATA KERJA
Contoh klasifikasi edukatif (bukan klasifikasi resmi instansi — rujuk SOP/kebijakan internal untuk yang mengikat, klasifikasi sebenarnya mengikuti kebijakan instansi):
- Publik — boleh disebar luas (pengumuman resmi yang sudah tayang).
- Internal — untuk keperluan kerja internal (notulen rapat, draf yang sudah izin edar terbatas).
- Terbatas — sensitif, akses sangat terbatas (data kependudukan, surat masih konsep, data keuangan rinci).
Dokumen bertanda Terbatas tidak boleh disimpan di layanan pribadi atau dibagikan dengan tautan publik/anyone-with-link. Beri label jelas di nama file atau halaman sampul sesuai pedoman instansi. Saat berbagi, gunakan izin minimal (view-only, batas waktu, non-forward). Hapus atau arsipkan data yang tidak perlu sesuai jadwal retensi instansi. Saat mencetak, ambil segera dari printer; saat membuang, hancurkan kertas sensitif. Catat siapa menerima data dan untuk keperluan apa (log sederhana).

BAB 5 — PRAKTIK PERANGKAT DAN JARINGAN
- Kunci layar otomatis (timeout singkat, contoh 1–2 menit sesuai SOP instansi, bukan standar universal), aktifkan enkripsi perangkat bila disediakan instansi.
- Perbarui sistem operasi dan aplikasi segera setelah tersedia.
- Hindari Wi-Fi publik untuk akses sistem sensitif; gunakan jaringan resmi atau VPN yang disediakan.
- Matikan Bluetooth/AirDrop bila tidak perlu.
- Cadangkan data ke lokasi yang disepakati instansi, bukan hanya di satu perangkat.
- Saat bepergian, bawa perangkat seperlunya dan waspada shoulder surfing (orang mengintip layar).
- Untuk tanda tangan elektronik, gunakan layanan resmi seperti BSrE BSSN, bukan aplikasi tidak resmi.

BAB 6 — RESPONS AWAL TERHADAP INSIDEN
Tujuan: membatasi dampak dan melestarikan bukti, bukan memperbaiki sendiri secara universal. Selalu ikuti SOP instansi; langkah di bawah bersifat edukatif dan dapat berbeda dengan SOP Anda. Prosedur isolasi, pemulihan, pelaporan, dan pelestarian bukti mengikuti SOP instansi serta arahan tim IT/CSIRT. Jangan memberi instruksi universal yang dapat memperburuk insiden atau menghapus bukti.
Jika mencurigai insiden (mis. akun mengirim email tanpa Anda kirim, file terenkripsi mendadak, muncul pesan tebusan):
1) isolasi — putuskan jaringan (cabut kabel/nonaktifkan Wi-Fi) bila diizinkan SOP, jangan matikan paksa yang menghilangkan jejak log/RAM tanpa arahan;
2) preservasi — jangan instal alat tidak resmi, jangan hapus log, catat waktu, gejala, dan langkah yang sudah dilakukan;
3) eskalasi — hubungi helpdesk/atasan/CSIRT instansi dengan informasi terstruktur (waktu kejadian, perangkat, akun terdampak, tautan/file mencurigakan);
4) pemulihan — ganti kata sandi dari perangkat aman dan cabut sesi, hanya setelah arahan helpdesk;
5) tindak lanjut — simpan bukti (screenshot, header email) sesuai arahan dan ikuti instruksi pemulihan data dari cadangan resmi.
Jangan membayar tebusan atau mencoba dekripsi tanpa arahan pimpinan/CSIRT. Jika ragu, prioritaskan lapor cepat daripada eksperimen perbaikan.
Template laporan insiden singkat (salin ke formulir resmi instansi, jangan menulis kata sandi atau OTP):
- Waktu kejadian / pertama diketahui:
- Perangkat / sistem terdampak:
- Akun yang mungkin terdampak (tanpa kata sandi/OTP):
- Gejala dan tindakan yang sudah dilakukan:
- Bukti yang tersedia dan lokasi penyimpanan sesuai kebijakan (mis. screenshot, header email, file log):
- Kontak pelapor dan kanal eskalasi resmi (helpdesk/CSIRT/atasan, nomor/email resmi):

RINGKASAN
- Bab 1 — Keseimbangan CIA: terapkan izin minimal view-only + batas waktu; seimbangkan dengan contoh layanan kependudukan vs publikasi pengumuman agar kerahasiaan tidak mengorbankan ketersediaan dan sebaliknya.
- Bab 2 — Kata sandi dan autentikasi: gunakan frasa unik, 2FA/MFA, pengelola yang disetujui instansi dengan alur persetujuan-aktivasi-simpan, dan segera ganti serta lapor bila dicurigai bocor.
- Bab 3 — Phishing: kenali typosquatting pada contoh fiktif dis-kominfo-sawahlunto.example, periksa domain tanpa klik via hover/header, verifikasi via kanal resmi independen, dan segera ganti kata sandi serta lapor bila sudah klik/isi kredensial.
- Bab 4 — Dokumen: terapkan contoh klasifikasi edukatif Publik/Internal/Terbatas sesuai SOP instansi, label jelas, izin minimal, dan retensi.
- Bab 5 — Perangkat/jaringan: kunci layar dengan timeout contoh sesuai SOP (bukan standar universal), enkripsi, pembaruan, VPN resmi, dan BSrE untuk tanda tangan.
- Bab 6 — Insiden: isolasi-preservasi-eskalasi-pemulihan-tindak lanjut sesuai SOP/CSIRT, gunakan template 6 kolom tanpa kata sandi/OTP, jangan bayar tebusan tanpa arahan.

LATIHAN REFLEKSI
1. Jelaskan perbedaan kerahasiaan dan integritas dengan contoh kerja Anda. Pedoman: contoh konkret dan dampak jika dilanggar. Rubrik: contoh tepat + dampak = baik. Contoh ideal: kerahasiaan — data NIK warga hanya dapat diakses petugas loket berwenang (jika bocor, risiko penyalahgunaan identitas); integritas — angka pagu anggaran tidak boleh diubah tanpa persetujuan dan log (jika diubah diam-diam, laporan salah dan akuntabilitas rusak).
2. Audit kata sandi Anda: apakah ada yang dipakai ulang? Rencana perbaikan? Pedoman: langkah spesifik dan jadwal (mis. migrasi ke pengelola kata sandi yang disetujui minggu ini). Rubrik: menyebut layanan yang dipakai ulang + jadwal migrasi + aktivasi MFA = baik. Contoh ideal: "Email dinas dan e-office masih sama; minggu ini migrasi email ke vault yang disetujui IT dan aktifkan MFA email."
3. Tuliskan tiga ciri email phishing yang pernah Anda lihat. Pedoman: ciri spesifik dan tindakan yang diambil (lapor/hapus). Rubrik: 3 ciri (mis. typosquatting dis-kominfo, urgensi palsu, tautan .example) + tindakan lapor = baik.
4. Bagaimana Anda menentukan sensitivitas dokumen sebelum membagikannya? Pedoman: sebut contoh klasifikasi edukatif dan alasan, serta rujukan SOP. Rubrik: menyebut Publik/Internal/Terbatas + alasan + rujuk SOP = baik. Contoh ideal: "Draf surat edaran belum final = Terbatas edukatif, tidak share via tautan publik, izin view-only 2 hari sesuai SOP."
5. Simulasikan respons awal bila laptop menampilkan pesan tebusan (ransomware). Pedoman: urutan isolasi, preservasi, catat, lapor, jangan bayar tanpa arahan, serta isi template 6 kolom. Rubrik: urutan 5 langkah benar + template terisi tanpa kata sandi + lapor CSIRT = baik.
6. Refleksi: kebijakan keamanan apa di unit Anda yang paling sulit dipatuhi dan mengapa? Pedoman: identifikasi hambatan dan usulan perbaikan proses yang tidak mengurangi keamanan. Rubrik: hambatan spesifik + usulan yang tetap jaga kerahasiaan/integritas = baik.

GLOSARIUM
Kerahasiaan — pembatasan akses hanya untuk pihak berwenang.
Integritas — jaminan data tidak diubah tanpa otorisasi dan jejak audit.
Ketersediaan — data/layanan dapat diakses saat dibutuhkan oleh pihak berwenang.
Phishing — upaya menipu untuk mendapatkan data sensitif via komunikasi palsu yang memanfaatkan urgensi dan typosquatting (contoh fiktif dis-kominfo-sawahlunto.example).
Rekayasa sosial — manipulasi psikologis untuk membuat korban melakukan tindakan yang merugikan.

REFERENSI DAN BACAAN LANJUTAN
Terverifikasi (diakses 2026-10-12, perlu cek edisi terbaru):
- Badan Siber dan Sandi Negara — Publikasi — https://www.bssn.go.id/publikasi/
- Badan Siber dan Sandi Negara — Edukasi Masyarakat — https://www.bssn.go.id/edukasi-masyarakat/
- BSSN — Layanan Sertifikat Elektronik BSrE — https://bsre.bssn.go.id/ (portal layanan resmi)
Arahan umum / metadata publik (belum diverifikasi isi lengkap):
- ISO/IEC 27001:2022 — Information security, cybersecurity and privacy protection — Information security management systems — Requirements (metadata publik ISO; teks lengkap berbayar dan tidak diperiksa) — https://www.iso.org/standard/27001
- Kebijakan internal Diskominfo / SOP pengelolaan data dan respons insiden — rujuk dokumen resmi instansi (tidak publik).
Catatan keterbatasan: modul disusun dari pengetahuan umum; tidak menyalin teks berhak cipta. Untuk kewajiban spesifik, verifikasi di situs resmi BSSN dan SOP instansi. Tidak ada referensi baru yang dikarang pada revisi ini.

Tanggal pemeriksaan: 2026-10-13.
TXT;
    }

    private function komunikasiEfektif(): string
    {
        return <<<'TXT'
MODUL PEMBELAJARAN INTERNAL SIAP — DISKOMINFO SAWAHLUNTO
Judul: Komunikasi Efektif di Lingkungan Kerja
Tanggal penyusunan: 2026-10-13 | Status: draft

Tujuan Pembelajaran
Peserta mampu:
1) membedakan komunikasi lisan dan tulisan yang efektif;
2) mempraktikkan mendengarkan aktif;
3) menulis pesan/email dinas profesional;
4) melakukan koordinasi dan serah terima pekerjaan;
5) mengelola perbedaan pendapat secara konstruktif.
Kriteria keberhasilan: mampu menulis email dinas dengan subjek-tujuan-tenggat-tautan yang lengkap, mempraktikkan alur mendengarkan aktif 5 langkah dengan parafase dan konfirmasi, dan membuat checklist serah terima yang mencakup status-risiko-kontak.

Prasyarat
Pengalaman bekerja dalam tim dan pernah mengikuti rapat/serah terima tugas. Tidak ada prasyarat teknis.

Daftar Isi
1. Pendahuluan
2. Bab 1 — Komunikasi lisan dan tulisan
3. Bab 2 — Mendengarkan aktif
4. Bab 3 — Menulis pesan dan email profesional
5. Bab 4 — Koordinasi dan serah terima pekerjaan
6. Bab 5 — Mengelola perbedaan pendapat
7. Bab 6 — Contoh kasus dan latihan
8. Ringkasan
9. Latihan
10. Glosarium
11. Referensi

Pendahuluan
Banyak pekerjaan tertunda bukan karena kurang kemampuan teknis, tetapi karena komunikasi tidak jelas: instruksi ambigu, tanggung jawab tidak tertulis, atau keputusan rapat tidak ditindaklanjuti. Modul ini membantu ASN menyusun pesan yang mudah dipahami, mendengarkan dengan tujuan memahami, dan menutup lingkaran komunikasi dengan konfirmasi tertulis.

BAB 1 — KOMUNIKASI LISAN DAN TULISAN
Komunikasi lisan efektif: singkat, terstruktur (konteks — tujuan — permintaan), dan memberi ruang tanya. Contoh ASN: saat menerima disposisi surat "Mohon ditindaklanjuti", tanyakan balik struktur 3 poin — "Konteks: surat undangan Bappeda 12 Okt; Tujuan: siapkan data; Permintaan: apakah Bapak berkenan saya siapkan tabel rekap untuk rapat 13 Okt jam 09.00?". Komunikasi tulisan efektif: subjek jelas, satu pesan satu tujuan, poin penting di awal. Hindari paragraf panjang tanpa poin aksi. Gunakan bahasa yang menghormati dan inklusif.
Kesalahan umum: pesan "mohon ditindaklanjuti" tanpa menyebutkan siapa, apa, dan kapan — solusinya pakai checklist 5W1H sebelum kirim.
Template cek cepat sebelum kirim:
- Apakah subjek menyebut tujuan dan tenggat?
- Apakah penerima dan CC sudah benar?
- Apakah tautan/lampiran dan izin akses sudah benar?

BAB 2 — MENDENGARKAN AKTIF
Mengapa penting: banyak miskomunikasi bukan karena instruksi salah, tetapi karena penerima tidak menangkap maksud pengirim secara utuh. Mendengarkan aktif memastikan instruksi, tenggat, dan tanggung jawab dipahami sama sebelum bertindak, sehingga mengurangi pekerjaan ulang dan keterlambatan.
Apa itu: menunda penilaian dan tanggapan, menangkap isi faktual dan nada kebutuhan pembicara, lalu memparafrase dengan bahasa sendiri untuk konfirmasi sebelum mencatat tindak lanjut.
Alur praktik yang dapat langsung dipakai:
1) dengar tanpa menyela — beri waktu bicara penuh, tahan menyimpulkan dini;
2) catat fakta/keputusan/tenggat — tulis poin faktual (apa, kapan, siapa), bukan interpretasi;
3) parafrasekan — "Apakah pemahaman saya tepat: ...?" dengan kalimat sendiri;
4) konfirmasi — tunggu pembicara mengoreksi atau menyetujui;
5) catat tindak lanjut — siapa mengerjakan apa, kapan, di mana dokumen disimpan.
Kesalahan umum:
- menyiapkan jawaban saat orang lain bicara sehingga melewatkan detail tenggat;
- memotong untuk memberi solusi cepat sebelum fakta lengkap;
- mencatat semua ucapan tanpa membedakan keputusan vs diskusi;
- mengangguk tanpa konfirmasi verbal sehingga kesepakatan semu.
Teknik pendukung:
- kontak mata wajar, anggukan, dan jeda singkat sebelum menjawab;
- catat poin dengan format Fakta — Keputusan — Tenggat — Penanggung jawab;
- di rapat daring, matikan notifikasi, catat keputusan (bukan semua ucapan), dan konfirmasi di chat: "Ringkasan: A mengerjakan X sampai Jumat, B menyiapkan data Y. Mohon koreksi bila keliru."
Contoh dialog ASN:
- Rekan: "Saya khawatir data pelayanan triwulan belum lengkap karena Bidang Y belum kirim, dan tenggat besok jam 10."
- Respons mendengarkan aktif: "Apakah pemahaman saya tepat: kendala di kelengkapan data karena menunggu Bidang Y, dan tenggat besok jam 10? Apakah boleh saya bantu cek ke Bidang Y hari ini dan kabari jam 15.00?"
- Rekan: "Benar, tolong cek jam 15.00."
Template catatan keputusan (salin ke dokumen bersama):
- Tanggal/Rapat:
- Fakta yang disampaikan:
- Keputusan:
- Tenggat:
- Penanggung jawab:
- Tindak lanjut/konfirmasi:

BAB 3 — MENULIS PESAN DAN EMAIL PROFESIONAL
Struktur email dinas yang dapat langsung dipakai:
- subjek spesifik: "[Permintaan] Data Pelayanan Triwulan III — tenggat 13 Okt 16.00";
- salam dan konteks satu kalimat;
- tujuan/permintaan eksplisit;
- detail pendukung (tautan dokumen dengan izin view, bukan "lihat file di drive");
- tenggat dan penanggung jawab;
- penutup dan kontak.
Untuk chat kerja: gunakan kalimat lengkap bila topik sensitif, hindari singkatan ambigu (mis. "ACC" tanpa konteks), dan pisahkan topik berbeda ke pesan terpisah agar mudah dirujuk. Selalu cantumkan tautan dokumen, bukan hanya nama file. Periksa sebelum kirim: penerima, lampiran, dan izin akses. Simpan email penting di folder terstruktur, bukan hanya di inbox.

BAB 4 — KOORDINASI DAN SERAH TERIMA PEKERJAAN
Koordinasi yang baik menjawab 5W1H:
- apa yang dikerjakan;
- mengapa (tujuan/kaitan dengan target);
- siapa penanggung jawab;
- kapan tenggat;
- di mana dokumen disimpan;
- bagaimana melaporkan kemajuan (harian/mingguan via kanal yang disepakati).
Serah terima untuk cuti 3 hari harus tertulis dan disimpan di lokasi yang disepakati (bukan hanya chat personal):
- ringkasan status tiap tugas;
- hal yang sudah selesai dan bukti (tautan dokumen);
- hal yang tertunda, risiko, dan mitigasi;
- kontak pihak terkait (atasan, rekan lintas bidang).
Gunakan checklist dan minta konfirmasi tertulis "Siap menerima serah terima". Untuk lintas bidang, tetapkan satu pemilik tugas (single owner) dan jadwal cek harian 10 menit.

BAB 5 — MENGELOLA PERBEDAAN PENDAPAT
Perbedaan pendapat wajar bila fokus pada isu, bukan pribadi. Bedakan empat unsur sebelum memutuskan:
- Fakta — yang dapat diverifikasi (tanggal, angka, dokumen, kejadian);
- Asumsi — yang dianggap benar tanpa bukti;
- Pendapat — penilaian pribadi tentang fakta;
- Kepentingan — kebutuhan atau target tiap pihak (mis. tenggat, sumber daya).
Langkah kerja:
1) pisahkan fakta, asumsi, pendapat, dan kepentingan — tulis di papan/catatan bersama;
2) cari tujuan bersama (mis. pelayanan tepat waktu, data akurat);
3) tawarkan 2-3 opsi dengan pro-kontra singkat dan dampak pada tujuan bersama;
4) sepakati kriteria keputusan (dampak, biaya, tenggat, risiko) sebelum memilih opsi;
5) dokumentasikan alasan keputusan dan tindak lanjut, termasuk yang belum sepakat.
Cara menyampaikan keberatan tanpa menyerang pribadi:
- Kalimat menyerang: "Anda selalu terlambat mengirim data, tidak peduli tim lain."
- Ubah menjadi profesional: "Saya khawatir tenggat Jumat terlewat karena data triwulan belum masuk per Kamis sore. Fakta: data Bidang Y belum diterima. Opsi: saya bantu cek jam 15.00 atau sesuaikan tenggat dengan persetujuan pimpinan. Bagaimana menurut Bapak/Ibu?"
Teknik membandingkan pilihan: buat matriks sederhana Opsi — Pro — Kontra — Dampak pada tujuan — Risiko, lalu minta pimpinan memutuskan berdasar kriteria, bukan preferensi pribadi.
Mendokumentasikan alasan: tulis "Keputusan: pakai Opsi B karena biaya paling rendah dan tenggat terpenuhi. Alasan: ... . Yang belum sepakat: Bidang Z menilai risiko X, ditindaklanjuti dengan uji coba 1 minggu dan evaluasi." Ini menjaga transparansi meski belum sepakat.
Bila belum sepakat: sepakati hal yang disetujui, hal yang belum, langkah berikut (cari data tambahan, konsultasi pimpinan, uji coba terbatas), dan jadwal tinjau ulang. Tidak semua konflik harus selesai hari itu; yang penting ada catatan dan jadwal tindak lanjut.
Batasan penting: jangan menyetujui atau menjalankan keputusan yang melanggar hukum, prosedur, atau kewenangan. Jika diminta melakukan yang bertentangan dengan aturan, sampaikan keberatan tertulis berbasis fakta/aturan, tawarkan alternatif yang sesuai prosedur, dan eskalasi ke atasan/pimpinan sesuai jalur resmi. Dokumentasikan keberatan tersebut.
Contoh ASN: dua bidang berbeda prioritas anggaran — Bidang A butuh cetak formulir, Bidang B butuh perbaikan jaringan. Buat matriks 2 opsi, nilai berdasar kriteria dampak layanan — biaya — tenggat, lalu ajukan ke pimpinan untuk keputusan tertulis.

BAB 6 — CONTOH KASUS DAN LATIHAN
Kasus A: Instruksi atasan via chat: "Tolong siapkan data untuk rapat besok." Tanpa rincian, staf menyiapkan data yang salah. Perbaikan: staf mengonfirmasi dengan template — "Apakah data yang dimaksud adalah data pelayanan triwulan terakhir dalam format tabel, untuk rapat jam 09.00 di ruang A? Saya simpan di [tautan] dan konfirmasi jam 15.00."
Kasus B: Dua bidang saling menunggu karena masing-masing mengira pihak lain yang memulai. Perbaikan: tetapkan pemilik tugas tunggal, tulis di dokumen bersama, dan jadwal cek harian 10 menit dengan update tertulis.
Kasus C: Notulen rapat tidak ditindaklanjuti. Perbaikan: notulen 1 halaman berisi keputusan, penanggung jawab, tenggat, dan tautan dokumen; kirim maksimal 2 jam setelah rapat dan minta konfirmasi.

RINGKASAN
- Bab 1 — Komunikasi lisan/tulisan efektif: gunakan struktur konteks-tujuan-permintaan, satu pesan satu tujuan, checklist 5W1H, dan cek penerima/tautan sebelum kirim.
- Bab 2 — Mendengarkan aktif: terapkan alur dengar tanpa sela — catat fakta/keputusan/tenggat — parafrase "Apakah pemahaman tepat" — konfirmasi — catat tindak lanjut; hindari menyiapkan jawaban sambil mendengar dan gunakan template catatan keputusan.
- Bab 3 — Email dinas: subjek spesifik dengan tenggat, tujuan eksplisit, tautan dengan izin view, pisah topik chat, dan simpan terstruktur.
- Bab 4 — Koordinasi dan serah terima: jawab 5W1H, serah terima tertulis dengan status-bukti-risiko-mitigasi-kontak, single owner, dan cek harian 10 menit.
- Bab 5 — Perbedaan pendapat: bedakan fakta/asumsi/pendapat/kepentingan, pakai bahasa "saya", bandingkan opsi via matriks kriteria, dokumentasikan alasan termasuk yang belum sepakat, dan jangan jalankan keputusan melanggar aturan.
- Bab 6 — Kasus: konfirmasi instruksi ambigu, tetapkan pemilik tunggal, dan kirim notulen 1 halaman dengan penanggung jawab-tenggat-tautan maksimal 2 jam.

LATIHAN
1. Tulis ulang pesan ambigu "Mohon ditindaklanjuti" menjadi jelas dengan 5W1H dan templatenya. Pedoman: menyebut penerima, tugas, tenggat, lokasi berkas. Rubrik: lengkap 5W1H = baik; 3-4 unsur = cukup; kurang dari 3 = perlu perbaikan. Contoh ideal: "Yth. Tim Data, mohon siapkan rekap pelayanan triwulan III (tabel) untuk rapat 13 Okt jam 09.00 Ruang A, simpan di [tautan Drive X] dan konfirmasi jam 15.00."
2. Praktikkan mendengarkan aktif: dengarkan rekan 2 menit lalu ringkas tanpa menilai. Pedoman: ringkasan akurat, pakai parafase "Apakah pemahaman saya tepat: ...?", dan ada konfirmasi. Rubrik: (a) fakta/keputusan/tenggat tercatat benar = 2 poin; (b) parafase dengan bahasa sendiri = 1 poin; (c) konfirmasi dan tindak lanjut tertulis = 1 poin; total 4 = baik, 3 = cukup. Contoh ringkasan ideal: "Apakah pemahaman saya tepat: kendala di data Bidang Y dan tenggat besok jam 10? Saya bantu cek jam 15.00. Mohon koreksi bila keliru."
3. Susun template email permintaan data yang profesional (subjek, tujuan, tenggat, tautan). Pedoman: subjek jelas, tujuan, tenggat, tautan dengan izin.
4. Buat checklist serah terima untuk cuti 3 hari. Pedoman: mencakup status, risiko, mitigasi, kontak. Rubrik: 4 unsur lengkap = baik.
5. Jelaskan langkah mengelola perbedaan pendapat tanpa menyerang pribadi (pakai bahasa "saya" dan pisahkan fakta-opini-asumsi-kepentingan). Pedoman: langkah 1-5 berurutan, bedakan empat unsur, dan contoh kalimat profesional "Saya khawatir ... Fakta: ... Opsi: ...". Rubrik: (a) urutan langkah benar = 1 poin; (b) empat unsur dibedakan = 1 poin; (c) contoh kalimat tanpa serang pribadi = 2 poin; total 4 = baik. Jika belum sepakat, tulis hal yang disetujui/belum dan jadwal tinjau ulang. Jangan setujui keputusan melanggar prosedur; tulis keberatan berbasis aturan bila diminta.
6. Refleksi: kapan terakhir Anda salah paham karena komunikasi? Apa perbaikan satu kebiasaan minggu ini? Pedoman: spesifik dan terukur (mis. selalu konfirmasi disposisi dengan 3 poin). Rubrik: kebiasaan spesifik + cara ukur = baik.

GLOSARIUM
Mendengarkan aktif — proses memberi perhatian penuh, memahami isi dan emosi, serta mengonfirmasi pemahaman dengan parafase dan pencatatan tindak lanjut.
Koordinasi — pengaturan bersama agar tugas saling mendukung, menjawab 5W1H dan disepakati tertulis.
Serah terima — pengalihan tanggung jawab dengan dokumentasi status, hal tertunda, risiko, dan kontak pihak terkait.
Konflik konstruktif — perbedaan pendapat yang dikelola dengan fokus isu dan kriteria untuk menghasilkan keputusan lebih baik, termasuk dokumentasi yang belum sepakat.

REFERENSI
Terverifikasi (diakses 2026-10-12):
- Lembaga Administrasi Negara — https://lan.go.id/ (portal resmi pengembangan kompetensi ASN).
Arahan umum (perlu verifikasi mandiri, belum diverifikasi dokumen spesifik):
- Panduan komunikasi organisasi LAN dan modul pengembangan kompetensi ASN (cek situs resmi LAN).
- SOP komunikasi internal instansi (rujuk dokumen resmi instansi).
Catatan keterbatasan: modul disusun dari pengetahuan umum komunikasi organisasi, tidak mengutip teks berhak cipta panjang. Tidak mencantumkan URL spesifik dokumen internal untuk mencegah rujukan keliru.

Tanggal pemeriksaan: 2026-10-13.
TXT;
    }

    private function pelayananPublik(): string
    {
        return <<<'TXT'
MODUL PEMBELAJARAN INTERNAL SIAP — DISKOMINFO SAWAHLUNTO
Judul: Pelayanan Publik yang Berkualitas
Tanggal penyusunan: 2026-10-13 | Status: draft
Catatan: Pembahasan bersifat edukatif. Rujukan hukum resmi tetap pada peraturan perundang-undangan terkait, bukan menggantikan teks peraturan. Untuk pasal dan kewajiban spesifik, rujuk teks resmi di JDIH.

Tujuan Pembelajaran
Peserta dapat:
1) menjelaskan konsep dan tujuan pelayanan publik;
2) mengidentifikasi kebutuhan pengguna layanan;
3) mempraktikkan komunikasi yang ramah dan jelas;
4) menerapkan aksesibilitas dan inklusivitas;
5) menangani keluhan sesuai alur;
6) menggunakan indikator evaluasi untuk perbaikan berkelanjutan.
Kriteria keberhasilan: mampu memetakan 3 titik gesekan layanan dengan bukti, dan memilih 2 indikator yang terukur dengan sumber data.

Prasyarat
Pernah berinteraksi dengan pengguna layanan atau mendukung proses pelayanan. Tidak memerlukan latar belakang hukum.

Daftar Isi
1. Pendahuluan dan catatan hukum
2. Bab 1 — Dasar pelayanan publik
3. Bab 2 — Standar dan alur pelayanan
4. Bab 3 — Komunikasi dengan masyarakat
5. Bab 4 — Aksesibilitas dan keadilan layanan
6. Bab 5 — Pengaduan dan perbaikan layanan
7. Bab 6 — Evaluasi dan peningkatan mutu
8. Ringkasan
9. Latihan
10. Glosarium
11. Referensi

Pendahuluan
Pelayanan publik adalah titik temu antara negara dan warga. Kualitasnya dinilai bukan dari niat baik, tetapi dari pengalaman warga: apakah mudah, cepat, jelas, dan adil. Modul ini menekankan empati, kejelasan informasi, dan perbaikan berkelanjutan melalui data keluhan dan evaluasi. Untuk kewajiban hukum spesifik (mis. standar pelayanan, waktu penyelesaian, sanksi), rujuk peraturan resmi dan keputusan pimpinan, bukan modul ini. Modul tidak mengutip pasal; kutipan pasal hanya sah dari teks resmi. Contoh alur dan template dalam modul adalah ilustrasi pembelajaran, bukan prosedur resmi instansi.

BAB 1 — DASAR PELAYANAN PUBLIK
Tujuan pelayanan publik adalah memenuhi kebutuhan warga secara adil, transparan, dan akuntabel. Kebutuhan masyarakat (mis. dokumen kependudukan, izin usaha, informasi layanan) dijawab oleh kewajiban penyelenggara: menyediakan standar pelayanan yang jelas, melayani sesuai prosedur, dan mempertanggungjawabkan hasilnya. Standar pelayanan memuat sekurang-kurangnya persyaratan, prosedur, waktu penyelesaian, biaya atau tarif bila ada, produk layanan, serta sarana pengaduan — rinciannya mengikuti peraturan dan keputusan pimpinan yang berlaku.
Prinsip praktis: sederhanakan prosedur, jelaskan persyaratan dan biaya secara terbuka, beri kepastian waktu, dan catat setiap penyimpangan dengan alasan. Pelayanan yang baik mengurangi beban warga (bolak-balik, biaya tambahan) dan meningkatkan kepercayaan. Kesalahan umum: menambah persyaratan tidak tertulis atau mempersulit tanpa dasar tertulis.
Untuk rujukan hukum resmi yang terverifikasi: Undang-Undang Nomor 25 Tahun 2009 tentang Pelayanan Publik (LN 2009 No. 112, TLN 5038) — status berlaku, dapat diverifikasi di https://peraturan.bpk.go.id/Details/38748/uu-no-25-tahun-2009
Catatan keterbatasan: peraturan pelaksana (mis. ketentuan tentang standar pelayanan dan sistem pelayanan terpadu) memerlukan verifikasi judul, nomor, dan URL dari sumber resmi JDIH sebelum dicantumkan sebagai rujukan spesifik. Modul ini tidak mengarang nomor PP; rujuk teks resmi untuk ketentuan pelaksana yang mengikat dan tandai untuk reviewer bila diperlukan.

BAB 2 — STANDAR DAN ALUR PELAYANAN
Standar pelayanan memuat sekurang-kurangnya persyaratan, prosedur, waktu penyelesaian, biaya atau tarif bila ada, produk layanan, serta sarana pengaduan — rinciannya mengikuti peraturan dan keputusan pimpinan yang berlaku. Jelaskan setiap unsur secara transparan: persyaratan apa yang wajib, prosedur bagaimana, waktu berapa lama sesuai standar resmi, biaya berapa bila ada, produk apa yang diterima warga, dan kanal mana untuk bertanya atau mengadu. Hindari mengarang angka waktu atau biaya sebagai standar universal; rujuk standar resmi yang ditetapkan penyelenggara.
Alur layanan contoh pembelajaran (bukan prosedur resmi instansi):
1) permohonan — warga mengajukan sesuai persyaratan melalui kanal yang ditetapkan;
2) pemeriksaan kelengkapan — petugas memeriksa berkas, memberi checklist tertulis bila ada yang kurang;
3) pemberian informasi — jelaskan langkah selanjutnya, waktu sesuai standar, dan cara memantau status;
4) tindak lanjut — proses oleh unit berwenang sesuai prosedur;
5) penyampaian hasil — serahkan produk layanan dan jelaskan penggunaan atau langkah lanjutan.
Bedakan contoh alur ini dari prosedur resmi: nama langkah, formulir, dan tenggat mengikuti SOP instansi. Contoh ilustrasi: warga mengajukan permohonan dokumen kependudukan, petugas memberi tanda terima berisi nomor registrasi dan daftar berkas kurang, warga melengkapi, petugas memproses dan menyampaikan hasil sesuai standar waktu yang tercantum pada pengumuman layanan.

BAB 3 — KOMUNIKASI DENGAN MASYARAKAT
Gunakan bahasa sederhana, hindari jargon, beri tahu langkah selanjutnya dan estimasi waktu sesuai standar resmi yang berlaku ("Berkas Anda akan kami periksa kelengkapannya hari ini; jika lengkap, proses selanjutnya sesuai standar waktu yang tercantum pada pengumuman layanan, cek di ..."). Sampaikan informasi melalui kanal yang mudah diakses (papan pengumuman, media sosial resmi, website) dan pastikan konsistensi lintas kanal. Sediakan template pengumuman layanan satu halaman dengan poin: syarat, langkah, waktu sesuai standar resmi, biaya bila ada, dan kontak.
Menangani permohonan belum lengkap tanpa mempermalukan: terima dengan ramah, jelaskan bagian yang kurang secara spesifik, dan beri daftar tertulis.
- Contoh sopan: "Terima kasih sudah melengkapi sebagian berkas. Masih kurang fotokopi KTP dan surat pengantar RT. Berikut checklist-nya; Bapak/Ibu dapat melengkapi dan kami bantu cek lagi sebelum diserahkan."
- Hindari menyalahkan: jangan katakan "Salah isi lagi" atau "Tidak dibaca ya aturannya"; ganti dengan "Mari kita cek bersama bagian yang perlu dilengkapi agar tidak bolak-balik."
Menyampaikan keterlambatan atau hambatan secara transparan sesuai kewenangan:
- "Mohon maaf, verifikasi berkas memerlukan waktu tambahan karena antrean sistem. Perkiraan selesai sesuai standar yang tercantum, atau kami kabari melalui kanal yang Bapak/Ibu pilih. Jika melewati standar, silakan hubungi kontak pengaduan yang tertera."
- Jangan menjanjikan tenggat yang bukan kewenangan Anda; rujuk pada standar resmi dan sampaikan opsi pemantauan status.
Empati berarti mengakui kesulitan warga ("Kami memahami perjalanan ke loket ini butuh waktu") tanpa berjanji yang tidak dapat dipenuhi.

BAB 4 — AKSESIBILITAS DAN KEADILAN LAYANAN
Pastikan layanan dapat diakses warga dengan literasi digital, kemampuan, dan kebutuhan akses yang berbeda: lansia dan warga dengan keterbatasan baca (gunakan kalimat pendek dan contoh isian), warga dengan keterbatasan pendengaran atau penglihatan (sediakan pendamping dan informasi tertulis/lisan yang setara), warga dengan keterbatasan mobilitas (jalur antrian prioritas dan pilihan layanan yang tidak mewajibkan kehadiran berulang bila prosedur memungkinkan), serta warga di daerah dengan koneksi terbatas (sediakan alternatif luring sesuai ketentuan yang berlaku).
Praktik:
- kontras warna memadai, huruf cukup besar, petunjuk langkah bergambar;
- pendamping bagi yang kesulitan, jalur antrian prioritas;
- jam layanan yang mempertimbangkan pekerja dan pilihan kanal alternatif yang tersedia.
Kanal digital melengkapi, bukan otomatis menggantikan kanal alternatif. Jika warga tidak dapat menggunakan layanan digital, arahkan ke kanal alternatif yang tersedia atau diwajibkan sesuai prosedur (mis. loket, telepon, surat) tanpa mengurangi kualitas. Hindari pernyataan bahwa semua layanan harus digital; ikuti ketentuan penyelenggara tentang kanal yang sah.
Inklusivitas berarti perlakuan setara tanpa diskriminasi (suku, agama, gender, disabilitas, status ekonomi). Evaluasi aksesibilitas dengan mencoba sendiri alur layanan dari sudut pandang warga (walkthrough) dan catat hambatan fisik, informasi, dan sikap petugas. Modul ini tidak membuat klaim hukum tentang kewajiban akomodasi tertentu; rujuk peraturan dan standar resmi untuk ketentuan yang mengikat.

BAB 5 — PENGADUAN DAN PERBAIKAN LAYANAN
Keluhan adalah data perbaikan, bukan serangan pribadi. Alur praktis contoh pembelajaran (bukan kanal atau tenggat resmi instansi):
1) menerima — dengarkan penuh, catat waktu, kanal, isi, dan harapan tanpa meminta data pribadi yang tidak diperlukan;
2) mencatat — gunakan formulir baku dengan informasi minimum: inti masalah, waktu dan lokasi bila relevan, dampak yang dialami, bukti opsional yang aman bila ada, dan kanal balasan yang diinginkan;
3) memberi tanda terima atau informasi status — berikan nomor atau tanda terima bila tersedia, atau jelaskan cara memantau status sesuai prosedur yang berlaku, tanpa menjanjikan hasil atau tenggat di luar kewenangan;
4) meneruskan — teruskan ke unit berwenang dengan klasifikasi (informasi, permintaan, komplain) dan konteks yang lengkap;
5) memantau tindak lanjut — pantau status dan pastikan tidak terlewat;
6) menyampaikan hasil — sampaikan hasil atau penjelasan dengan bahasa yang jelas dan tawarkan alternatif bila permohonan tidak dapat dipenuhi.
Bedakan contoh alur ini dari kanal atau tenggat resmi: nama kanal, tenggat, dan formulir mengikuti SOP instansi. Jangan menjanjikan penyelesaian di luar kewenangan petugas.

Template pengaduan (contoh pembelajaran, sesuaikan dengan formulir resmi):
- Inti masalah:
- Waktu dan lokasi kejadian bila relevan:
- Dampak yang dialami:
- Bukti opsional yang aman (mis. foto dokumen yang tidak memuat data sensitif, bukan data pribadi):
- Kanal balasan yang diinginkan (mis. telepon, email, datang kembali):
- Harapan penyelesaian:

BAB 6 — EVALUASI DAN PENINGKATAN MUTU
Contoh indikator yang dapat dievaluasi (bukan indikator wajib regulasi):
- kelengkapan informasi yang disampaikan kepada pemohon;
- kejelasan proses dan konsistensi informasi lintas kanal;
- ketepatan penyelesaian terhadap standar resmi yang ditetapkan penyelenggara;
- pengulangan masalah yang sama (mis. berkas yang sering kurang, pertanyaan berulang);
- umpan balik masyarakat tentang kemudahan dan keramahan layanan.
Bedakan indikator contoh dari indikator wajib: indikator wajib mengikuti regulasi atau keputusan pimpinan; contoh di atas adalah ilustrasi pembelajaran untuk latihan evaluasi. Gunakan temuan pengaduan untuk perbaikan proses tanpa membocorkan identitas pelapor: agregasikan data (mis. "30% pengaduan tentang formulir membingungkan"), hilangkan nama dan data pribadi, dan bahas perbaikan pada rapat evaluasi mingguan sebagai "pola masalah", bukan "laporan individu".
Kasus ilustrasi: Kasus A: Antrian panjang karena warga harus fotokopi di luar. Perbaikan: sediakan contoh isian, cek kelengkapan di awal, dan pertimbangkan layanan fotokopi di lokasi bila memungkinkan sesuai kebijakan. Kasus B: Informasi di website berbeda dengan di loket. Perbaikan: tunjuk penanggung jawab pembaruan informasi dan jadwal cek mingguan, serta log perubahan. Tambahan untuk evaluasi: catat waktu tunggu, tingkat penyelesaian terhadap standar resmi, dan persentase pengulangan masalah mingguan sebagai bahan perbaikan.

RINGKASAN
- Bab 1 — Dasar pelayanan publik: kebutuhan masyarakat dijawab kewajiban penyelenggara melalui standar pelayanan persyaratan-prosedur-waktu-biaya-produk-pengaduan, rujuk UU 25/2009, PP pelaksana masih perlu verifikasi spesifik.
- Bab 2 — Standar dan alur: jelaskan persyaratan-waktu-biaya-produk-kanal secara transparan tanpa mengarang angka, alur 5 langkah permohonan sampai penyampaian hasil sebagai contoh pembelajaran.
- Bab 3 — Komunikasi: bahasa sederhana, contoh sopan tanpa menyalahkan, jelaskan kekurangan dengan checklist, sampaikan keterlambatan transparan sesuai kewenangan dengan opsi pemantauan.
- Bab 4 — Aksesibilitas dan keadilan: layani literasi, kemampuan, dan koneksi berbeda, sediakan kanal alternatif yang tersedia, walkthrough untuk evaluasi hambatan.
- Bab 5 — Pengaduan: alur 6 langkah dari menerima sampai menyampaikan hasil, template inti masalah-waktu-dampak-bukti aman-kanal balasan, bedakan dari kanal/tenggat resmi.
- Bab 6 — Evaluasi: indikator contoh kelengkapan-kejelasan-ketepatan-pengulangan-umpan balik, bedakan dari indikator wajib, gunakan agregasi pengaduan tanpa identitas untuk perbaikan.

LATIHAN
1. Kasus persyaratan belum lengkap: warga mengajukan permohonan dokumen kependudukan tetapi berkas kurang. Tulis respons sopan yang menjelaskan kekurangan, memberi checklist, dan mengarahkan pelengkapan tanpa mempermalukan. Pedoman: bahasa menghormati, daftar kurang spesifik, ajak cek bersama. Rubrik: (a) nada sopan tanpa menyalahkan = 1 poin; (b) daftar kurang jelas = 1 poin; (c) ajakan cek bersama/lengkapi = 1 poin; total 3 = baik. Contoh ideal: "Terima kasih sudah melengkapi sebagian berkas. Masih kurang fotokopi KTP dan surat pengantar RT. Berikut checklist-nya; dapat dilengkapi dan kami bantu cek lagi."
2. Kasus pengaduan eskalasi: warga mengadu keterlambatan yang memerlukan tindak lanjut unit lain. Tulis alur penerimaan sampai penyampaian hasil dan isian template pengaduan. Pedoman: urutan 6 langkah, informasi minimum, tidak meminta data sensitif, tidak janji tenggat di luar kewenangan, eskalasi ke unit berwenang. Rubrik: (a) alur 6 langkah benar = 2 poin; (b) template lengkap tanpa data berlebih = 1 poin; (c) penanda eskalasi dan pemantauan = 1 poin; total 4 = baik.
3. Petakan satu layanan di unit Anda dan identifikasi tiga titik gesekan bagi warga dengan bukti (keluhan/observasi). Pedoman: titik spesifik dan bukti (keluhan/observasi). Rubrik: 3 titik + bukti = baik.
4. Pilih dua indikator evaluasi contoh (mis. kejelasan proses dan ketepatan terhadap standar resmi) dan jelaskan cara mengukurnya mingguan beserta sumber data, serta bagaimana agregasi pengaduan dipakai tanpa identitas pelapor. Pedoman: indikator terukur, sumber data jelas, agregasi tanpa nama. Rubrik: (a) dua indikator + cara ukur = 1 poin; (b) sumber data = 1 poin; (c) agregasi anonim = 1 poin; total 3 = baik.
5. Susun checklist aksesibilitas untuk loket Anda yang mencakup fisik, informasi, dan sikap petugas (minimal 2 poin tiap aspek) dan jelaskan kanal alternatif untuk warga dengan koneksi terbatas. Pedoman: 2 poin tiap aspek + kanal alternatif. Rubrik: lengkap = baik.
6. Refleksi: apa satu perbaikan kecil yang dapat dilakukan minggu ini tanpa anggaran besar yang menghubungkan standar, komunikasi, dan evaluasi? Pedoman: spesifik, terukur, dan tidak menambah beban warga. Rubrik: spesifik + terukur + hubungkan 2 aspek = baik.

GLOSARIUM
Pelayanan publik — rangkaian kegiatan untuk memenuhi kebutuhan warga sesuai peraturan dan standar pelayanan yang ditetapkan penyelenggara, dinilai dari pengalaman warga tentang kemudahan, kejelasan, dan keadilan.
Standar pelayanan — uraian persyaratan, prosedur, waktu penyelesaian, biaya bila ada, produk layanan, dan sarana pengaduan yang ditetapkan penyelenggara sebagai acuan transparan.
Aksesibilitas — kemudahan bagi semua orang termasuk kelompok rentan untuk menjangkau dan menggunakan layanan tanpa hambatan fisik, informasi, atau kanal.
Keadilan layanan — perlakuan setara tanpa diskriminasi dengan mempertimbangkan kebutuhan akses yang berbeda.
Pengaduan — umpan balik warga tentang ketidaksesuaian antara harapan dan pengalaman layanan yang dicatat, diteruskan, dan ditindaklanjuti sesuai prosedur.
Evaluasi mutu — peninjauan berkala terhadap kelengkapan informasi, kejelasan proses, ketepatan terhadap standar resmi, dan umpan balik untuk perbaikan berkelanjutan.

REFERENSI
Terverifikasi (diakses 2026-10-12):
- Undang-Undang Nomor 25 Tahun 2009 tentang Pelayanan Publik (LN 2009 No. 112, TLN 5038, status Berlaku) — https://peraturan.bpk.go.id/Details/38748/uu-no-25-tahun-2009
- Lembaga Administrasi Negara — https://lan.go.id/ (portal resmi kebijakan pelayanan publik dan pengembangan kompetensi — verifikasi dokumen spesifik perlu cek mandiri).
Arahan umum (perlu verifikasi mandiri, belum diverifikasi URL spesifik):
- Pedoman pelayanan instansi dan SOP layanan Diskominfo (rujuk dokumen resmi internal).
Catatan keterbatasan: modul edukatif, bukan teks hukum. Tidak mencantumkan kutipan pasal, tanggal, atau angka statistik sebagai standar universal; rujuk teks resmi di JDIH untuk ketentuan yang mengikat. Peraturan pelaksana masih perlu verifikasi judul, nomor, dan URL sebelum dicantumkan sebagai rujukan spesifik.

Tanggal pemeriksaan: 2026-10-13.
TXT;
    }

    private function etikaProfesional(): string
    {
        return <<<'TXT'
MODUL PEMBELAJARAN INTERNAL SIAP — DISKOMINFO SAWAHLUNTO
Judul: Etika Profesional di Lingkungan Kerja
Tanggal penyusunan: 2026-10-13 | Status: draft
Catatan: Pisahkan prinsip pembelajaran di modul ini dari kewajiban hukum formal yang mengikuti peraturan, putusan, dan kode etik instansi yang berlaku.

Tujuan Pembelajaran
Peserta dapat:
1) menjelaskan prinsip etika dan profesionalisme dengan contoh kerja ASN;
2) mengenali potensi konflik kepentingan dan memilih langkah yang tepat;
3) menjelaskan alur aman saat menghadapi gratifikasi dan pelaporan sesuai prosedur;
4) menjaga kerahasiaan, privasi, dan jejak profesional sesuai kewenangan;
5) menggunakan AI dan teknologi secara bertanggung jawab dengan tinjauan manusia;
6) menangani dilema etika dengan kerangka keputusan dan jalur berwenang.
Kriteria keberhasilan: mampu memberi contoh konflik kepentingan, menuliskan alur aman gratifikasi tanpa menjanjikan perlakuan khusus, memberi contoh anonim penggunaan AI yang mematuhi kebijakan, dan menguraikan kerangka keputusan dilema fakta-pihak-aturan-alternatif-risiko-konsultasi-dokumentasi.

Prasyarat
Pengalaman kerja di organisasi dan pemahaman dasar tata kerja. Tidak memerlukan latar belakang hukum.

Daftar Isi
1. Pendahuluan — mengapa etika menentukan kepercayaan
2. Bab 1 — Prinsip etika dan profesionalisme
3. Bab 2 — Konflik kepentingan dan pengambilan keputusan
4. Bab 3 — Gratifikasi dan pelaporan
5. Bab 4 — Kerahasiaan, privasi, dan jejak profesional
6. Bab 5 — Penggunaan AI dan teknologi secara bertanggung jawab
7. Bab 6 — Dilema etika dan tindak lanjut
8. Ringkasan
9. Latihan
10. Glosarium
11. Referensi

Pendahuluan
Etika profesional adalah kompas saat aturan tidak merinci setiap situasi. ASN memegang amanah publik; keputusan kecil yang tidak etis dapat merusak kepercayaan yang dibangun lama. Modul ini membahas prinsip pembelajaran, bukan daftar larangan hukum — untuk kewajiban formal, rujuk peraturan ASN (termasuk UU ASN 20/2023 sebagai materi Core terpisah di Library), peraturan disiplin, dan kode etik instansi. Prinsip membantu menimbang tindakan; aturan menetapkan kewajiban, prosedur, dan sanksi.

BAB 1 — PRINSIP ETIKA DAN PROFESIONALISME
Integritas: bertindak selaras antara nilai, ucapan, dan tindakan, bahkan tanpa pengawasan. Contoh ASN: tidak mengubah angka laporan agar terlihat baik, mengakui kekeliruan data dan menyampaikan ralat dengan catatan perbaikan.
Tanggung jawab: memahami batas wewenang, meminta persetujuan bila ragu, dan mencatat keputusan beserta dasarnya. Contoh: menolak menandatangani berita acara di luar kewenangan dan mengarahkan ke pejabat yang berwenang.
Penghormatan: menghormati martabat warga dan rekan kerja, mendengarkan, dan menghindari ujaran merendahkan. Contoh: menyapa pemohon dengan ramah dan menjelaskan prosedur tanpa mempermalukan.
Objektivitas: menilai berdasarkan fakta dan kriteria yang ditetapkan, bukan kedekatan pribadi. Contoh: menilai berkas pengadaan berdasarkan checklist yang sama untuk semua penyedia.
Akuntabilitas: bersedia menjelaskan dan mempertanggungjawabkan tindakan serta hasilnya. Contoh: mencatat alasan penundaan layanan dan menyampaikan secara transparan kepada pemohon dan atasan.
Contoh terintegrasi: saat diminta mempercepat layanan di luar antrean karena "kenalan", ASN menjelaskan standar antrean yang berlaku, tidak menjanjikan perlakuan khusus, dan menawarkan bantuan sesuai prosedur (mis. memeriksa kelengkapan berkas).
Kaitkan dengan nilai BerAKHLAK (Berorientasi Pelayanan, Akuntabel, Kompeten, Harmonis, Loyal, Adaptif, Kolaboratif) yang dipandu BKN — lihat Panduan Perilaku Core Value ASN di https://www.bkn.go.id/publikasi/panduan-perilaku-core-value-asn/
Bedakan prinsip pembelajaran di bab ini dengan kewajiban hukum formal: prinsip membantu memutuskan, kewajiban ditetapkan oleh peraturan dan putusan yang berlaku. Modul ini tidak mengarang nomor peraturan atau kutipan.
Cara evaluasi: tanyakan "Apakah tindakan ini dapat dijelaskan secara terbuka kepada publik dan atasan tanpa mengubah cerita, dan apakah sesuai dengan fakta, kewenangan, serta catatan yang ada?"

BAB 2 — KONFLIK KEPENTINGAN DAN PENGAMBILAN KEPUTUSAN
Konflik kepentingan terjadi ketika kepentingan pribadi, keluarga, atau kelompok berpotensi memengaruhi penilaian kedinasan. Tidak selalu berarti pelanggaran, tetapi berisiko mengaburkan objektivitas sehingga perlu dikelola secara terbuka. Contoh merupakan bahan belajar, bukan penetapan hukum terhadap kasus nyata.
Skenario ilustrasi: ASN diminta menilai proposal bantuan yang diajukan organisasi tempat kerabatnya menjadi pengurus; atau diminta mempercepat layanan untuk tetangga tanpa antrean. Potensi konflik muncul karena kedekatan pribadi dapat memengaruhi penilaian.
Alur praktis:
1) kenali potensi konflik — tanyakan apakah hubungan pribadi dapat memengaruhi penilaian orang lain tentang objektivitas Anda;
2) hindari keputusan sepihak — jangan memutuskan sendiri saat ada potensi konflik, tunda keputusan yang dapat ditunda;
3) ungkapkan sesuai mekanisme instansi — sampaikan fakta potensi konflik kepada atasan atau jalur yang ditetapkan;
4) minta arahan pihak berwenang — ikuti arahan berupa penugasan ulang, reviu sejawat, atau keputusan oleh pejabat lain dan dokumentasikan.
Prinsip: ungkap di awal, bukan setelah keputusan dibuat, dan catat pengungkapan serta arahan yang diterima. Untuk definisi dan kewajiban hukum spesifik (termasuk sanksi), rujuk peraturan terkait dan konsultasi SDM/BKN/KemenPANRB, bukan modul ini.

BAB 3 — GRATIFIKASI DAN PELAPORAN
Situasi berisiko etika belum tentu sama dengan penetapan resmi menurut aturan. Penilaian akhir mengikuti ketentuan yang berlaku; modul ini membantu mengenali risiko dan memilih langkah aman. Contoh berisiko: warga yang sedang mengurus izin menitipkan bingkisan; vendor yang ikut pengadaan menawarkan jamuan atau hadiah setelah presentasi; pihak yang menunggu keputusan kedinasan mengirimkan voucher. Dalam situasi tersebut, hindari memberi kesan imbalan memengaruhi keputusan.
Alur aman:
- jangan menjanjikan perlakuan khusus, prioritas, atau percepatan sebagai imbalan;
- catat fakta yang diperlukan secara proporsional (siapa, kapan, apa yang ditawarkan, konteks layanan/keputusan terkait) tanpa menambah penilaian;
- konsultasikan melalui kanal resmi yang ditetapkan instansi (mis. atasan langsung atau Unit Pengendali Gratifikasi/UPG bila ada) dan ikuti prosedur pelaporan yang berlaku;
- ikuti arahan tentang penyerahan, penolakan, atau pencatatan sesuai ketentuan.
Jangan mengarang batas nominal, tenggat pelaporan, formulir, atau kanal internal. Jika menyebut KPK, rujuk sumber resmi yang telah diperiksa: Komisi Pemberantasan Korupsi — Layanan Gratifikasi — https://www.kpk.go.id/id/layanan/gratifikasi — yang menjelaskan tahapan pengendalian gratifikasi (komitmen pimpinan, penyusunan aturan, pembentukan UPG, monitoring dan evaluasi) serta kanal pelaporan resmi (https://gol.kpk.go.id/, aplikasi GOL, pelaporan.gratifikasi@kpk.go.id, atau melalui UPG instansi). Rincian nominal, tenggat, dan formulir mengikuti ketentuan dan FAQ KPK yang berlaku; modul ini tidak menetapkan angka.

BAB 4 — KERAHASIAAN, PRIVASI, DAN JEJAK PROFESIONAL
Menjaga martabat masyarakat dan rekan kerja berarti memperlakukan informasi dan citra orang lain dengan hormat: tidak menyebarkan data pribadi, foto, atau dokumen yang tidak perlu, serta tidak mempermalukan pemohon di ruang publik atau grup besar.
Prinsip akses dan penggunaan data:
- akses sesuai kewenangan — hanya buka atau bagikan data yang menjadi tugas Anda;
- penggunaan data minimum — ambil dan bagikan sesedikit mungkin yang diperlukan untuk tugas, bukan seluruh arsip;
- jangan menganggap semua informasi memiliki klasifikasi yang sama di setiap instansi; ikuti kebijakan klasifikasi dan retensi instansi.
Skenario: foto kegiatan layanan yang menampilkan dokumen warga atau wajah warga hendak dibagikan ke grup kerja untuk laporan.
- Langkah pemeriksaan: apakah foto diperlukan untuk laporan; apakah ada data pribadi yang terlihat; apakah ada izin menampilkan; apakah grup penerima memang berwenang.
- Pembatasan penerima: kirim hanya ke penerima yang berwenang melalui kanal resmi dengan izin akses terbatas, bukan ke grup besar campuran;
- Alternatif lebih aman: sensor atau tutup data pribadi, potong (crop) bagian sensitif, atau buat ringkasan tertulis tanpa foto; hapus salinan yang tidak perlu sesuai retensi.

BAB 5 — PENGGUNAAN AI DAN TEKNOLOGI SECARA BERTANGGUNG JAWAB
AI dapat membantu merangkum dokumen panjang, menyusun draf surat, atau membantu analisis awal, tetapi keputusan resmi tetap berada pada manusia yang berwenang. Jangan menyerahkan keputusan yang mengikat kepada AI.
Risiko memasukkan data ke layanan yang belum disetujui: data pribadi warga, dokumen internal, kredensial, atau informasi rahasia dapat tersimpan di luar kendali instansi, sulit ditarik kembali, dan berisiko disalahgunakan.
Langkah aman:
- periksa kebijakan instansi tentang layanan AI dan teknologi yang diizinkan;
- gunakan data contoh atau anonim bila sesuai — ganti nama, NIK, dan detail pengenal dengan contoh fiktif sebelum diolah;
- verifikasi keluaran terhadap sumber primer (dokumen asli, data sistem, aturan);
- periksa bias atau kesalahan — tanggal kedaluwarsa, angka yang tidak masuk akal, kutipan tanpa sumber;
- tetap lakukan tinjauan manusia dan simpan catatan penggunaan bila diminta kebijakan.
Contoh anonim realistis: untuk merangkum laporan layanan triwulan, buat salinan anonim dengan mengganti "Nama Warga A, NIK 123... Desa X" menjadi "Warga A (contoh anonim), wilayah contoh", lalu minta AI merangkum tren keterlambatan tanpa memasukkan data asli. Verifikasi ringkasan dengan laporan asli sebelum dipakai. Jangan mengklaim semua layanan AI aman atau tidak aman secara universal; penilaian mengikuti kebijakan dan izin instansi.
Untuk publikasi eksternal, pastikan ada reviu atasan dan atribusi sumber.

BAB 6 — DILEMA ETIKA DAN TINDAK LANJUT
Skenario gabungan: atasan meminta Anda mempercepat layanan untuk rekan dekat tanpa antrean di tengah antrean warga, sambil meminta Anda membagikan daftar nama pemohon ke grup umum "agar transparan", dan menyarankan memakai layanan AI publik untuk mengolah data pemohon agar cepat selesai. Tekanan, kepentingan publik, kerahasiaan, dan akuntabilitas bertemu dalam satu situasi.
Kerangka pengambilan keputusan:
1) fakta — apa yang terjadi, siapa yang terlibat, apa buktinya;
2) pihak terdampak — warga dalam antrean, pemohon yang datanya akan dibuka, rekan kerja, instansi;
3) aturan/kebijakan relevan — standar antrean, ketentuan privasi/kerahasiaan, kebijakan penggunaan AI, tanpa mengarang pasal;
4) alternatif — tawarkan percepatan sesuai prosedur (cek kelengkapan, antrean prioritas yang sah bila ada), ringkasan anonim tanpa membuka daftar nama, pengolahan data via layanan yang disetujui atau manual;
5) risiko — hilangnya kepercayaan publik, kebocoran data, pelanggaran prosedur, beban rekan;
6) konsultasi — sampaikan fakta dan alternatif kepada atasan dan, bila perlu, jalur berwenang sesuai mekanisme instansi;
7) keputusan — pilih alternatif yang menjaga kepentingan publik dan kepatuhan;
8) dokumentasi — catat fakta, alternatif yang ditawarkan, arahan yang diterima, dan keputusan akhir.
Jika muncul dugaan pelanggaran, sampaikan melalui jalur berwenang yang ditetapkan instansi (mis. atasan, inspektorat, atau kanal pengaduan resmi), bukan dengan menyebarkan tuduhan di kanal publik atau media sosial. Hindari menyebarkan tuduhan tanpa bukti; fokus pada fakta yang dapat diverifikasi. Modul ini tidak menjanjikan perlindungan atau hasil pelaporan tertentu; perlindungan dan tindak lanjut mengikuti ketentuan yang berlaku.

RINGKASAN
- Bab 1 — Prinsip etika: integritas, tanggung jawab, penghormatan, objektivitas, akuntabilitas dengan contoh ASN; bedakan prinsip pembelajaran dari kewajiban hukum formal.
- Bab 1 — Nilai BerAKHLAK sebagai rujukan perilaku ASN; cara evaluasi keterbukaan cerita.
- Bab 2 — Konflik kepentingan: kenali potensi, hindari keputusan sepihak, ungkapkan via mekanisme, minta arahan, dokumentasikan; contoh bahan belajar bukan penetapan hukum.
- Bab 3 — Gratifikasi: bedakan risiko etika vs penetapan resmi; jangan janjikan perlakuan khusus; catat fakta proporsional; konsultasi UPG/kanal resmi; rujuk KPK https://www.kpk.go.id/id/layanan/gratifikasi dan https://gol.kpk.go.id/ tanpa mengarang nominal/tenggat.
- Bab 4 — Kerahasiaan/privasi/jejak: akses sesuai kewenangan, data minimum, periksa sebelum berbagi foto/dokumen, batasi penerima, alternatif sensor/ringkasan.
- Bab 5 — AI: bantu ringkas/draf/analisis tanpa serahkan keputusan; risiko data pribadi/internal; langkah cek kebijakan, anonimkan, verifikasi sumber primer, cek bias, tinjauan manusia.
- Bab 6 — Dilema: kerangka fakta-pihak-aturan-alternatif-risiko-konsultasi-keputusan-dokumentasi; dugaan pelanggaran via jalur berwenang, bukan tuduhan publik; tanpa janji perlindungan.

LATIHAN
1. Kasus hadiah dari pemohon: warga yang sedang mengurus izin menitipkan bingkisan agar diprioritaskan. Jelaskan langkah Anda agar tidak menjanjikan perlakuan khusus, mencatat fakta, berkonsultasi via kanal resmi, dan mengikuti prosedur. Pedoman: jangan janjikan prioritas; catat siapa-kapan-apa-konteks layanan; konsultasi atasan/UPG; ikuti arahan pelaporan. Rubrik: (a) tidak menjanjikan perlakuan khusus = 1 poin; (b) catat fakta proporsional = 1 poin; (c) konsultasi kanal resmi = 1 poin; (d) ikuti prosedur tanpa mengarang tenggat/nominal = 1 poin; total 4 = baik. Contoh ideal: "Terima kasih, kami layani sesuai antrean. Saya catat penawaran bingkisan hari ini terkait permohonan izin X dan akan konsultasikan ke atasan/UPG sesuai prosedur."
2. Kasus hadiah dari vendor pengadaan: penyedia yang ikut seleksi menawarkan jamuan setelah presentasi. Tentukan apakah situasi berisiko, bagaimana menolak secara profesional, dan bagaimana mencatat untuk konsultasi. Pedoman: kenali risiko kedekatan dengan keputusan, tolak dengan bahasa "saya", catat fakta. Rubrik: kenali risiko + tolak profesional + catat = baik.
3. Penggunaan AI untuk meringkas dokumen layanan: Anda diminta meringkas data pemohon dengan AI publik. Tulis langkah aman: periksa kebijakan, buat data anonim, verifikasi keluaran, dan tinjauan manusia. Pedoman: jangan masukkan data asli tanpa izin, verifikasi sumber primer. Rubrik: (a) cek kebijakan = 1 poin; (b) anonimkan contoh = 1 poin; (c) verifikasi + tinjauan manusia = 1 poin; total 3 = baik.
4. Tekanan untuk membagikan informasi terbatas: atasan meminta daftar nama pemohon dibagikan ke grup umum agar transparan. Jelaskan pemeriksaan, pembatasan penerima, dan alternatif ringkasan anonim. Pedoman: periksa kewenangan dan risiko, batasi penerima berwenang, tawarkan ringkasan tanpa identitas. Rubrik: pemeriksaan + batasi + alternatif = baik.
5. Dilema gabungan tekanan-kerahasiaan-akuntabilitas: gunakan kerangka 8 langkah untuk memutuskan kasus percepatan layanan + buka data + pakai AI publik. Pedoman: fakta-pihak-aturan-alternatif-risiko-konsultasi-keputusan-dokumentasi lengkap. Rubrik: 6-8 langkah terisi dengan fakta dan alternatif = baik; tanpa konsultasi/dokumentasi = kurang.
6. Refleksi: kebiasaan etis apa yang ingin Anda perkuat bulan ini dan bagaimana rekan dapat mengingatkan tanpa menghakimi, dengan menjaga bukti/data yang diperlukan? Pedoman: target terukur + mekanisme saling ingatkan (mis. pengingat sejawat, checklist) + jaga catatan. Rubrik: spesifik + mekanisme + jaga bukti = baik.

GLOSARIUM
Integritas — keselarasan nilai, ucapan, dan tindakan yang dapat dipertanggungjawabkan, bahkan tanpa pengawasan.
Konflik kepentingan — situasi ketika kepentingan pribadi, keluarga, atau kelompok berpotensi memengaruhi penilaian kedinasan dan perlu diungkap serta dikelola.
Gratifikasi — pemberian dalam arti luas yang terkait dengan jabatan atau layanan dan berisiko memengaruhi objektivitas; penetapan resminya mengikuti ketentuan yang berlaku.
Kerahasiaan — kewajiban melindungi informasi sensitif dari akses tidak berwenang sesuai prinsip kewenangan dan data minimum.
Privasi — hak warga dan rekan kerja agar data pribadinya tidak disebarkan tanpa dasar yang sah.
Jejak profesional — rekam tindakan digital dan non-digital ASN yang membawa nama instansi dan memengaruhi kepercayaan publik.
AI bertanggung jawab — penggunaan AI untuk membantu tugas dengan pengecekan kebijakan, anonimisasi, verifikasi sumber primer, dan tinjauan manusia.

REFERENSI
Terverifikasi (diakses 2026-10-13, perlu cek edisi terbaru):
- BKN — Panduan Perilaku Core Value ASN BerAKHLAK — https://www.bkn.go.id/publikasi/panduan-perilaku-core-value-asn/ (buku saku panduan dapat diunduh di https://www.bkn.go.id/e-library/buku-saku-panduan-perilaku-core-values-bkn/)
- BKN — Portal resmi — https://www.bkn.go.id/
- Lembaga Administrasi Negara — https://lan.go.id/
- Komisi Pemberantasan Korupsi — Layanan Gratifikasi — https://www.kpk.go.id/id/layanan/gratifikasi (tahapan pengendalian gratifikasi dan kanal pelaporan https://gol.kpk.go.id/)
Arahan umum (perlu verifikasi mandiri, belum diverifikasi dokumen spesifik):
- Peraturan terkait ASN dan disiplin (termasuk UU ASN 20/2023 sebagai materi Core terpisah di SIAP Library — rujuk teks resmi di JDIH untuk pasal mengikat).
- Kebijakan penggunaan teknologi/AI instansi — rujuk SOP internal Diskominfo Sawahlunto.
Catatan keterbatasan: modul bersifat edukatif umum, bukan rujukan hukum. Tidak mencantumkan kutipan pasal, URL spesifik internal, atau angka statistik agar tidak salah rujukan. Daftar bacaan perlu verifikasi edisi terbaru secara mandiri. Tidak ada referensi baru yang dikarang pada revisi ini selain yang telah diverifikasi di atas.

Tanggal pemeriksaan: 2026-10-13.
TXT;
    }
}
