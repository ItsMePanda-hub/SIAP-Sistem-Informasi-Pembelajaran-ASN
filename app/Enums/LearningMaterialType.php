<?php

namespace App\Enums;

enum LearningMaterialType: string
{
    case Native = 'native';
    case Pdf = 'pdf';
    case LinkOnly = 'link_only';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
