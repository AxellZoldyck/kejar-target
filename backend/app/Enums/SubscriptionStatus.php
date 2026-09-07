<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case TRIALING = 'trialing';
    case ACTIVE = 'active';
    case PAST_DUE = 'past_due';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';

    public function allowsMutation(): bool
    {
        return $this === self::TRIALING || $this === self::ACTIVE;
    }

    public function isRunning(): bool
    {
        return ! $this->isTerminal();
    }

    public function isTerminal(): bool
    {
        return $this === self::EXPIRED || $this === self::CANCELLED;
    }
}
