<?php

namespace App\Enums;

enum LearningValidityStatus: string
{
    case Unknown = 'unknown';
    case Active = 'active';
    case Superseded = 'superseded';
    case Revoked = 'revoked';
    case Expired = 'expired';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
