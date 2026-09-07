<?php

namespace App\Http\Resources;

use App\Enums\SubscriptionStatus;
use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $status = $this->resource->status;
        $statusValue = $status instanceof BackedEnum ? $status->value : $status;
        $statusEnum = $status instanceof SubscriptionStatus
            ? $status
            : SubscriptionStatus::tryFrom((string) $statusValue);

        $canMutate = $statusEnum?->allowsMutation() === true;

        if ($statusEnum === SubscriptionStatus::TRIALING) {
            $canMutate = $this->resource->trial_ends_at?->isFuture() === true;
        } elseif ($statusEnum === SubscriptionStatus::ACTIVE && $this->resource->ends_at !== null) {
            $canMutate = $this->resource->ends_at->isFuture();
        }

        if ($this->resource->starts_at?->isFuture() === true) {
            $canMutate = false;
        }

        return [
            'id' => $this->resource->getKey(),
            'company_id' => $this->resource->company_id,
            'plan_code' => $this->resource->plan_code,
            'status' => $statusValue,
            'trial_ends_at' => $this->resource->trial_ends_at?->toIso8601String(),
            'starts_at' => $this->resource->starts_at?->toIso8601String(),
            'ends_at' => $this->resource->ends_at?->toIso8601String(),
            'provider' => $this->resource->provider,
            'can_mutate' => $canMutate,
        ];
    }
}
