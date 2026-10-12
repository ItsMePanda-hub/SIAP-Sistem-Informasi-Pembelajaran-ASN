<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Exam;
use App\Models\Letter;
use App\Models\Training;
use App\Models\TrainingProgress;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::whereIn('email', [
            'admin@diskominfo.go.id',
            'pemilik@diskominfo.go.id',
            'budi@diskominfo.go.id',
            'siti@diskominfo.go.id',
            'andi@diskominfo.go.id',
            'rani@diskominfo.go.id',
        ])->delete();

        $this->call([
            LibraryLevelSeeder::class,
            LibraryCategorySeeder::class,
            LibraryTestAccountsSeeder::class,
            LibraryMaterialSeeder::class,
        ]);

        $adminId = User::where('email', 'admin.system@siap.test')->value('id')
            ?? User::where('email', 'admin.library@siap.test')->value('id')
            ?? User::where('role', 'admin')->value('id');

        $atasanId = User::where('email', 'atasan1@siap.test')->value('id') ?? $adminId;

        Storage::disk('public')->makeDirectory('letters');
        Storage::disk('public')->put('letters/panduan-siap.txt', 'Ini adalah file contoh untuk modul Surat & Dokumen SIAP.');

        Announcement::updateOrCreate(
            ['title' => 'Selamat Datang di SIAP'],
            [
                'body' => 'Selamat datang di Sistem Informasi & Pembelajaran ASN Diskominfo Sawahlunto. Silakan cek menu Pengumuman, Surat & Dokumen, Workspace, dan Ujian secara berkala.',
                'category' => 'rutin',
                'target_unit_kerja' => null,
                'created_by' => $adminId,
            ]
        );

        Announcement::updateOrCreate(
            ['title' => 'Update Sistem Keamanan Bidang IT'],
            [
                'body' => 'Mohon seluruh staf Bidang Layanan e-Government mengganti password akun masing-masing paling lambat akhir minggu ini.',
                'category' => 'mendesak',
                'target_unit_kerja' => 'Bidang Informasi dan Komunikasi Publik',
                'created_by' => $atasanId,
            ]
        );

        Letter::updateOrCreate(
            ['title' => 'Panduan Penggunaan SIAP'],
            [
                'nomor_surat' => '001/SIAP/2026',
                'category' => 'lainnya',
                'target_unit_kerja' => null,
                'file_path' => 'letters/panduan-siap.txt',
                'uploaded_by' => $adminId,
            ]
        );

        $training = Training::updateOrCreate(
            ['title' => 'Pelatihan Dasar Keamanan Siber'],
            [
                'description' => 'Pelatihan dasar tentang keamanan siber untuk seluruh pegawai.',
                'material_path' => null,
                'target_unit_kerja' => null,
                'created_by' => $adminId,
            ]
        );

        $staf1Id = User::where('email', 'staf1@siap.test')->value('id');
        $staf2Id = User::where('email', 'staf2@siap.test')->value('id');

        if ($staf1Id) {
            TrainingProgress::firstOrCreate(
                ['training_id' => $training->id, 'user_id' => $staf1Id],
                [
                    'status' => 'selesai',
                    'completed_at' => now(),
                    'certificate_code' => 'CERT-' . strtoupper(uniqid()),
                ]
            );
        }

        if ($staf2Id) {
            TrainingProgress::firstOrCreate(
                ['training_id' => $training->id, 'user_id' => $staf2Id],
                [
                    'status' => 'sedang_berjalan',
                ]
            );
        }

        $exam = Exam::updateOrCreate(
            ['title' => 'Quiz Keamanan Siber Dasar'],
            [
                'training_id' => $training->id,
                'description' => 'Quiz sederhana tentang dasar keamanan siber.',
                'target_unit_kerja' => null,
                'duration_minutes' => 15,
                'max_violations' => 3,
                'created_by' => $adminId,
            ]
        );

        $mcq = $exam->questions()->updateOrCreate(
            ['exam_id' => $exam->id, 'order' => 1],
            [
                'type' => 'pilihan_ganda',
                'question' => 'Apa singkatan dari ISP dalam konteks internet?',
            ]
        );

        $mcq->options()->firstOrCreate(['option_text' => 'Internet Service Provider', 'exam_question_id' => $mcq->id], ['is_correct' => true]);
        $mcq->options()->firstOrCreate(['option_text' => 'Internal Security Protocol', 'exam_question_id' => $mcq->id], ['is_correct' => false]);
        $mcq->options()->firstOrCreate(['option_text' => 'Integrated Systems Programming', 'exam_question_id' => $mcq->id], ['is_correct' => false]);
        $mcq->options()->firstOrCreate(['option_text' => 'International Software Partnership', 'exam_question_id' => $mcq->id], ['is_correct' => false]);

        $exam->questions()->updateOrCreate(
            ['exam_id' => $exam->id, 'order' => 2],
            [
                'type' => 'esai',
                'question' => 'Jelaskan mengapa penting untuk memperbarui perangkat lunak antivirus secara teratur.',
            ]
        );

        $this->command->info('Database seeded successfully!');
        $this->command->info('Akun final (password semua: password123):');
        $this->command->info('- admin.system@siap.test (admin)');
        $this->command->info('- admin.library@siap.test (admin_library)');
        $this->command->info('- pemilik@siap.test (pemilik)');
        $this->command->info('- atasan1@siap.test / atasan2@siap.test (atasan)');
        $this->command->info('- staf1@siap.test - staf6@siap.test (pegawai)');
        $this->command->info('Library Phase 2.2 seeded: 5 level, kategori, 8 material, 11 akun (admin.system/admin.library/pemilik/atasan/staf final naming)');
    }
}
