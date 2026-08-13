<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Exam;
use App\Models\Letter;
use App\Models\Training;
use App\Models\TrainingProgress;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Admin — akses penuh ke seluruh sistem, di luar struktur organisasi
        $admin = User::create([
            'name' => 'Admin SIAP',
            'email' => 'admin@diskominfo.go.id',
            'password' => Hash::make('password123'),
            'nip' => '198001012005011001',
            'jabatan' => 'Administrator Sistem',
            'unit_kerja' => null,
            'role' => 'admin',
        ]);

        // 2. Pemilik — pimpinan tertinggi, lintas-bidang
        $pemilik = User::create([
            'name' => 'Kepala Dinas Diskominfo',
            'email' => 'pemilik@diskominfo.go.id',
            'password' => Hash::make('password123'),
            'nip' => '197505051999031005',
            'jabatan' => 'Kepala Dinas',
            'unit_kerja' => null,
            'role' => 'pemilik',
        ]);

        // 3. Atasan — per bidang
        $atasanIT = User::create([
            'name' => 'Budi (Atasan Bidang IT)',
            'email' => 'budi@diskominfo.go.id',
            'password' => Hash::make('password123'),
            'nip' => '198203102010011002',
            'jabatan' => 'Kepala Bidang Layanan e-Government',
            'unit_kerja' => 'Bidang Layanan e-Government',
            'role' => 'atasan',
        ]);

        $atasanPersandian = User::create([
            'name' => 'Siti (Atasan Bidang Persandian)',
            'email' => 'siti@diskominfo.go.id',
            'password' => Hash::make('password123'),
            'nip' => '198407152011022003',
            'jabatan' => 'Kepala Bidang Persandian',
            'unit_kerja' => 'Bidang Persandian',
            'role' => 'atasan',
        ]);

        // 4. Pegawai — di bawah masing-masing atasan
        $pegawai1 = User::create([
            'name' => 'Andi (Staf Bidang IT)',
            'email' => 'andi@diskominfo.go.id',
            'password' => Hash::make('password123'),
            'nip' => '199001012015011001',
            'jabatan' => 'Staf Layanan e-Government',
            'unit_kerja' => 'Bidang Layanan e-Government',
            'role' => 'pegawai',
            'atasan_id' => $atasanIT->id,
        ]);

        $pegawai2 = User::create([
            'name' => 'Rani (Staf Bidang Persandian)',
            'email' => 'rani@diskominfo.go.id',
            'password' => Hash::make('password123'),
            'nip' => '199203152016022002',
            'jabatan' => 'Staf Persandian',
            'unit_kerja' => 'Bidang Persandian',
            'role' => 'pegawai',
            'atasan_id' => $atasanPersandian->id,
        ]);

        // Pengumuman contoh
        Announcement::create([
            'title' => 'Selamat Datang di SIAP',
            'body' => 'Selamat datang di Sistem Informasi & Pembelajaran ASN Diskominfo Sawahlunto. Silakan cek menu Pengumuman, Surat & Dokumen, dan Pelatihan secara berkala.',
            'category' => 'rutin',
            'target_unit_kerja' => null, // broadcast semua bidang
            'created_by' => $admin->id,
        ]);

        Announcement::create([
            'title' => 'Update Sistem Keamanan Bidang IT',
            'body' => 'Mohon seluruh staf Bidang Layanan e-Government mengganti password akun masing-masing paling lambat akhir minggu ini.',
            'category' => 'mendesak',
            'target_unit_kerja' => 'Bidang Layanan e-Government',
            'created_by' => $atasanIT->id,
        ]);

        // Surat & dokumen contoh
        Storage::disk('public')->makeDirectory('letters');
        Storage::disk('public')->put('letters/panduan-siap.txt', 'Ini adalah file contoh untuk modul Surat & Dokumen SIAP.');

        Letter::create([
            'title' => 'Panduan Penggunaan SIAP',
            'nomor_surat' => '001/SIAP/2026',
            'category' => 'lainnya',
            'target_unit_kerja' => null,
            'file_path' => 'letters/panduan-siap.txt',
            'uploaded_by' => $admin->id,
        ]);

        // Pelatihan contoh
        $training = Training::create([
            'title' => 'Pelatihan Dasar Keamanan Siber',
            'description' => 'Pelatihan dasar tentang keamanan siber untuk seluruh pegawai.',
            'material_path' => null,
            'target_unit_kerja' => null,
            'created_by' => $admin->id,
        ]);

        TrainingProgress::create([
            'training_id' => $training->id,
            'user_id' => $pegawai1->id,
            'status' => 'selesai',
            'completed_at' => now(),
            'certificate_code' => 'CERT-' . strtoupper(uniqid()),
        ]);

        TrainingProgress::create([
            'training_id' => $training->id,
            'user_id' => $pegawai2->id,
            'status' => 'sedang_berjalan',
        ]);

        // Ujian contoh, terhubung ke pelatihan di atas
        $exam = Exam::create([
            'training_id' => $training->id,
            'title' => 'Quiz Keamanan Siber Dasar',
            'description' => 'Quiz sederhana tentang dasar keamanan siber.',
            'target_unit_kerja' => null,
            'duration_minutes' => 15,
            'max_violations' => 3,
            'created_by' => $admin->id,
        ]);

        $mcq = $exam->questions()->create([
            'type' => 'pilihan_ganda',
            'question' => 'Apa singkatan dari ISP dalam konteks internet?',
            'order' => 1,
        ]);

        $mcq->options()->create(['option_text' => 'Internet Service Provider', 'is_correct' => true]);
        $mcq->options()->create(['option_text' => 'Internal Security Protocol', 'is_correct' => false]);
        $mcq->options()->create(['option_text' => 'Integrated Systems Programming', 'is_correct' => false]);
        $mcq->options()->create(['option_text' => 'International Software Partnership', 'is_correct' => false]);

        $exam->questions()->create([
            'type' => 'esai',
            'question' => 'Jelaskan mengapa penting untuk memperbarui perangkat lunak antivirus secara teratur.',
            'order' => 2,
        ]);

        $this->command->info('Database seeded successfully!');
        $this->command->info('Akun contoh (password semua: password123):');
        $this->command->info('- admin@diskominfo.go.id (admin)');
        $this->command->info('- pemilik@diskominfo.go.id (pemilik)');
        $this->command->info('- budi@diskominfo.go.id / siti@diskominfo.go.id (atasan)');
        $this->command->info('- andi@diskominfo.go.id / rani@diskominfo.go.id (pegawai)');
    }
}
