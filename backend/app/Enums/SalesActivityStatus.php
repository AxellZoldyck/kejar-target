<?php

namespace App\Enums;

enum SalesActivityStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case VALIDATED = 'validated';
    case REJECTED = 'rejected';

    public function isEditableBySales(): bool
    {
        return $this === self::DRAFT || $this === self::REJECTED;
    }

    public function isFinal(): bool
    {
        return $this === self::VALIDATED;
    }
}
