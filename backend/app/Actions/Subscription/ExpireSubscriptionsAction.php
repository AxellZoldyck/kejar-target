<?php

namespace App\Actions\Subscription;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ExpireSubscriptionsAction
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function execute(?CarbonImmutable $at = null): int
    {
        $at ??= CarbonImmutable::now('UTC');
        $candidateIds = Subscription::query()
            ->where('running_slot', 'running')
            ->where(function ($query) use ($at): void {
                $query->where(function ($trial) use ($at): void {
                    $trial->where('status', SubscriptionStatus::TRIALING->value)
                        ->whereNotNull('trial_ends_at')
                        ->where('trial_ends_at', '<=', $at);
                })->orWhere(function ($active) use ($at): void {
                    $active->where('status', SubscriptionStatus::ACTIVE->value)
                        ->whereNotNull('ends_at')
                        ->where('ends_at', '<=', $at);
                });
            })
            ->pluck('id');

        $expired = 0;
        foreach ($candidateIds as $subscriptionId) {
            $changed = DB::transaction(function () use ($subscriptionId, $at): bool {
                $subscription = Subscription::query()->lockForUpdate()->find($subscriptionId);
                if (! $subscription || ! $this->hasEnded($subscription, $at)) {
                    return false;
                }

                $before = [
                    'status' => $subscription->status->value,
                    'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
                    'ends_at' => $subscription->ends_at?->toIso8601String(),
                ];
                $subscription->update(['status' => SubscriptionStatus::EXPIRED]);
                $this->audit->record(
                    event: 'subscription.expired',
                    auditable: $subscription,
                    before: $before,
                    after: ['status' => SubscriptionStatus::EXPIRED->value],
                    metadata: ['source' => 'scheduler'],
                );

                return true;
            });
            $expired += $changed ? 1 : 0;
        }

        return $expired;
    }

    private function hasEnded(Subscription $subscription, CarbonImmutable $at): bool
    {
        return match ($subscription->status) {
            SubscriptionStatus::TRIALING => $subscription->trial_ends_at?->lessThanOrEqualTo($at) === true,
            SubscriptionStatus::ACTIVE => $subscription->ends_at?->lessThanOrEqualTo($at) === true,
            default => false,
        };
    }
}
