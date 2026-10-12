<?php

namespace App\Enums;

enum LearningMaterialScope: string
{
    case Core = 'core';
    case Custom = 'custom';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
