<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TeamDemoSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'email'    => 'admin2@diskominfo.go.id',
                'defaults' => [
                    'name'       => 'Rudi Hartono',
                    'password'   => Hash::make('password123'),
                    'nip'        => '198501012006011001',
                    'jabatan'    => 'Kepala Bidang IT',
                    'unit_kerja' => 'Sekretariat',
                    'role'       => 'admin',
                ],
            ],
            [
                'email'    => 'pemilik2@diskominfo.go.id',
                'defaults' => [
                    'name'       => 'Yuli Astuti',
                    'password'   => Hash::make('password123'),
                    'nip'        => '197608082000032002',
                    'jabatan'    => 'Kepala Dinas',
                    'unit_kerja' => 'Sekretariat',
                    'role'       => 'pemilik',
                ],
            ],
            [
                'email'    => 'atasan.ikp@diskominfo.go.id',
                'defaults' => [
                    'name'       => 'Doni Saputra',
                    'password'   => Hash::make('password123'),
                    'nip'        => '198710152010011003',
                    'jabatan'    => 'Kepala Bidang',
                    'unit_kerja' => 'Bidang Informasi dan Komunikasi Publik',
                    'role'       => 'atasan',
                ],
            ],
            [
                'email'    => 'atasan.persandian@diskominfo.go.id',
                'defaults' => [
                    'name'       => 'Rina Marlina',
                    'password'   => Hash::make('password123'),
                    'nip'        => '198903222012012004',
                    'jabatan'    => 'Kepala Bidang',
                    'unit_kerja' => 'Bidang Persandian dan Statistik',
                    'role'       => 'atasan',
                ],
            ],
            [
                'email'    => 'fajar.nugroho@diskominfo.go.id',
                'defaults' => [
                    'name'       => 'Fajar Nugroho',
                    'password'   => Hash::make('password123'),
                    'nip'        => '199205102015011005',
                    'jabatan'    => 'Staf',
                    'unit_kerja' => 'Bidang Informasi dan Komunikasi Publik',
                    'role'       => 'pegawai',
                ],
            ],
            [
                'email'    => 'melati.putri@diskominfo.go.id',
                'defaults' => [
                    'name'       => 'Melati Putri',
                    'password'   => Hash::make('password123'),
                    'nip'        => '199407182016022006',
                    'jabatan'    => 'Staf',
                    'unit_kerja' => 'Bidang Informasi dan Komunikasi Publik',
                    'role'       => 'pegawai',
                ],
            ],
            [
                'email'    => 'agus.setiawan@diskominfo.go.id',
                'defaults' => [
                    'name'       => 'Agus Setiawan',
                    'password'   => Hash::make('password123'),
                    'nip'        => '199001252017011007',
                    'jabatan'    => 'Staf',
                    'unit_kerja' => 'Bidang Informasi dan Komunikasi Publik',
                    'role'       => 'pegawai',
                ],
            ],
            [
                'email'    => 'wahyu.ramadhan@diskominfo.go.id',
                'defaults' => [
                    'name'       => 'Wahyu Ramadhan',
                    'password'   => Hash::make('password123'),
                    'nip'        => '199311122016011008',
                    'jabatan'    => 'Staf',
                    'unit_kerja' => 'Bidang Persandian dan Statistik',
                    'role'       => 'pegawai',
                ],
            ],
            [
                'email'    => 'siti.nurhaliza@diskominfo.go.id',
                'defaults' => [
                    'name'       => 'Siti Nurhaliza',
                    'password'   => Hash::make('password123'),
                    'nip'        => '199508302018022009',
                    'jabatan'    => 'Staf',
                    'unit_kerja' => 'Bidang Persandian dan Statistik',
                    'role'       => 'pegawai',
                ],
            ],
            [
                'email'    => 'bayu.prasetyo@diskominfo.go.id',
                'defaults' => [
                    'name'       => 'Bayu Prasetyo',
                    'password'   => Hash::make('password123'),
                    'nip'        => '199702142019011010',
                    'jabatan'    => 'Staf',
                    'unit_kerja' => 'Bidang Persandian dan Statistik',
                    'role'       => 'pegawai',
                ],
            ],
        ];

        foreach ($accounts as $account) {
            User::firstOrCreate(
                ['email' => $account['email']],
                $account['defaults']
            );
        }

        $this->command->info('TeamDemoSeeder selesai — 10 akun demo tim berhasil dibuat/diverifikasi.');
        $this->command->info('Password semua akun: password123');
    }
}