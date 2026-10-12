<?php

namespace App\Enums;

enum LearningLicenseType: string
{
    case CcBy40 = 'cc-by-4.0';
    case CcBySa40 = 'cc-by-sa-4.0';
    case CcByNcSa = 'cc-by-nc-sa';
    case HakCipta = 'hak_cipta';
    case IzinTertulis = 'izin_tertulis';
    case Internal = 'internal';
    case TidakDiketahui = 'tidak_diketahui';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::CcBy40 => 'Creative Commons Attribution 4.0 (CC BY 4.0)',
            self::CcBySa40 => 'Creative Commons Attribution-ShareAlike 4.0 (CC BY-SA 4.0)',
            self::CcByNcSa => 'Creative Commons Non-Commercial (CC BY-NC-SA)',
            self::HakCipta => 'Hak Cipta Dilindungi (All Rights Reserved)',
            self::IzinTertulis => 'Izin Tertulis Pemegang Hak Cipta',
            self::Internal => 'Dokumen / SOP Internal Organisasi',
            self::TidakDiketahui => 'Tidak Diketahui / Belum Diperiksa',
        };
    }

    public function isRedistributable(): bool
    {
        return in_array($this, [self::CcBy40, self::CcBySa40, self::IzinTertulis], true);
    }

    public function requiresEvidence(): bool
    {
        return in_array($this, [self::CcBy40, self::CcBySa40, self::CcByNcSa, self::IzinTertulis], true);
    }
}
