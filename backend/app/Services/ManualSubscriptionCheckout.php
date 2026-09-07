<?php

namespace App\Services;

use App\Contracts\SubscriptionCheckout;
use App\Models\Company;
use App\Models\User;

class ManualSubscriptionCheckout implements SubscriptionCheckout
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function request(Company $company, User $actor, string $planCode): array
    {
        $this->auditLogger->record(
            event: 'subscription.checkout_requested',
            actor: $actor,
            auditable: $company,
            metadata: [
                'mode' => 'manual',
                'plan_code' => $planCode,
            ],
        );

        return [
            'mode' => 'manual',
            'status' => 'action_required',
            'plan_code' => $planCode,
            'provider' => null,
            'checkout_url' => null,
            'message' => 'Payment gateway belum dikonfigurasi. Hubungi administrator untuk aktivasi manual.',
        ];
    }
}
