<?php

namespace App\Enums;

enum LearningVerificationStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case NeedsUpdate = 'needs_update';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isVerified(): bool
    {
        return $this === self::Verified;
    }

    public function canBePublishedForCore(): bool
    {
        return $this === self::Verified;
    }
}
