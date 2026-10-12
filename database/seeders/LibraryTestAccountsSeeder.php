<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LibraryTestAccountsSeeder extends Seeder
{
    public function run(): void
    {
        // Cleanup legacy naming Phase 2 -> Phase 2.2 final naming.
        // Explicit allowlist: hanya akun test Library yang di-rename.
        User::whereIn('email', [
            'pemilik.library@siap.test',
            'atasan1.library@siap.test',
            'atasan2.library@siap.test',
            'staf1.library@siap.test',
            'staf2.library@siap.test',
            'staf3.library@siap.test',
            'staf4.library@siap.test',
            'staf5.library@siap.test',
            'staf6.library@siap.test',
        ])->delete();

        $password = Hash::make('password123');

        $accounts = [
            [
                'email' => 'admin.system@siap.test',
                'defaults' => [
                    'name' => 'Admin Sistem',
                    'password' => $password,
                    'nip' => '990000000000000001',
                    'jabatan' => 'Administrator Sistem',
                    'unit_kerja' => null,
                    'role' => 'admin',
                    'atasan_id' => null,
                    'status_kepegawaian' => 'aktif',
                ],
            ],
            [
                'email' => 'admin.library@siap.test',
                'defaults' => [
                    'name' => 'Admin Library',
                    'password' => $password,
                    'nip' => '990000000000000021',
                    'jabatan' => 'Administrator SIAP Library',
                    'unit_kerja' => null,
                    'role' => 'admin_library',
                    'atasan_id' => null,
                    'status_kepegawaian' => 'aktif',
                ],
            ],
            [
                'email' => 'pemilik@siap.test',
                'defaults' => [
                    'name' => 'Pemilik',
                    'password' => $password,
                    'nip' => '990000000000000002',
                    'jabatan' => 'Kepala Dinas',
                    'unit_kerja' => null,
                    'role' => 'pemilik',
                    'atasan_id' => null,
                    'status_kepegawaian' => 'aktif',
                ],
            ],
            [
                'email' => 'atasan1@siap.test',
                'defaults' => [
                    'name' => 'Atasan Satu',
                    'password' => $password,
                    'nip' => '990000000000000003',
                    'jabatan' => 'Kepala Bidang Informasi',
                    'unit_kerja' => 'Bidang Informasi dan Komunikasi Publik',
                    'role' => 'atasan',
                    'status_kepegawaian' => 'aktif',
                ],
            ],
            [
                'email' => 'atasan2@siap.test',
                'defaults' => [
                    'name' => 'Atasan Dua',
                    'password' => $password,
                    'nip' => '990000000000000004',
                    'jabatan' => 'Kepala Bidang Persandian',
                    'unit_kerja' => 'Bidang Persandian dan Statistik',
                    'role' => 'atasan',
                    'status_kepegawaian' => 'aktif',
                ],
            ],
            [
                'email' => 'staf1@siap.test',
                'defaults' => [
                    'name' => 'Staf Satu',
                    'password' => $password,
                    'nip' => '990000000000000011',
                    'jabatan' => 'Staf',
                    'unit_kerja' => 'Bidang Informasi dan Komunikasi Publik',
                    'role' => 'pegawai',
                    'status_kepegawaian' => 'aktif',
                ],
            ],
            [
                'email' => 'staf2@siap.test',
                'defaults' => [
                    'name' => 'Staf Dua',
                    'password' => $password,
                    'nip' => '990000000000000012',
                    'jabatan' => 'Staf',
                    'unit_kerja' => 'Bidang Informasi dan Komunikasi Publik',
                    'role' => 'pegawai',
                    'status_kepegawaian' => 'aktif',
                ],
            ],
            [
                'email' => 'staf3@siap.test',
                'defaults' => [
                    'name' => 'Staf Tiga',
                    'password' => $password,
                    'nip' => '990000000000000013',
                    'jabatan' => 'Staf',
                    'unit_kerja' => 'Bidang Persandian dan Statistik',
                    'role' => 'pegawai',
                    'status_kepegawaian' => 'aktif',
                ],
            ],
            [
                'email' => 'staf4@siap.test',
                'defaults' => [
                    'name' => 'Staf Empat',
                    'password' => $password,
                    'nip' => '990000000000000014',
                    'jabatan' => 'Staf',
                    'unit_kerja' => 'Bidang Persandian dan Statistik',
                    'role' => 'pegawai',
                    'status_kepegawaian' => 'aktif',
                ],
            ],
            [
                'email' => 'staf5@siap.test',
                'defaults' => [
                    'name' => 'Staf Lima',
                    'password' => $password,
                    'nip' => '990000000000000015',
                    'jabatan' => 'Staf',
                    'unit_kerja' => 'Sekretariat',
                    'role' => 'pegawai',
                    'status_kepegawaian' => 'aktif',
                ],
            ],
            [
                'email' => 'staf6@siap.test',
                'defaults' => [
                    'name' => 'Staf Enam',
                    'password' => $password,
                    'nip' => '990000000000000016',
                    'jabatan' => 'Staf',
                    'unit_kerja' => 'Sekretariat',
                    'role' => 'pegawai',
                    'status_kepegawaian' => 'aktif',
                ],
            ],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                $account['defaults']
            );
        }
    }
}
