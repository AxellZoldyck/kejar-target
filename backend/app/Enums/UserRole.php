<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case SPV = 'spv';
    case SALES = 'sales';

    public function isBusinessRole(): bool
    {
        return $this !== self::SUPER_ADMIN;
    }
}
