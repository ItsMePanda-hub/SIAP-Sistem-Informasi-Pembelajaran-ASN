<?php

namespace App\Enums;

enum LearningPublicationStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Unpublished = 'unpublished';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isPublished(): bool
    {
        return $this === self::Published;
    }
}
