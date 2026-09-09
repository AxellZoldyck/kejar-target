<?php

namespace App\Http\Resources\Commission;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommissionSettingResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'version' => (int) $this->version,
            'multiplier_enabled' => (bool) $this->multiplier_enabled,
            'progressive_enabled' => (bool) $this->progressive_enabled,
            'progressive_overflow_behavior' => $this->progressive_overflow_behavior instanceof \BackedEnum
                ? $this->progressive_overflow_behavior->value
                : $this->progressive_overflow_behavior,
            'effective_from' => $this->effective_from?->toISOString(),
            'effective_until' => $this->effective_until?->toISOString(),
            'is_active' => (bool) $this->is_active,
            'product_fees' => $this->whenLoaded('productFees', fn () => $this->productFees->map(fn ($fee) => [
                'id' => $fee->id,
                'product_id' => $fee->product_id,
                'product_name' => $fee->relationLoaded('product') ? $fee->product?->name : null,
                'fee_amount' => (int) $fee->fee_amount,
            ])->values()),
            'multiplier_rules' => $this->whenLoaded('multiplierRules', fn () => $this->multiplierRules->map(fn ($rule) => [
                'id' => $rule->id,
                'min_sa' => (int) $rule->min_sa,
                'max_sa' => $rule->max_sa === null ? null : (int) $rule->max_sa,
                'multiplier_value' => $rule->multiplier_value,
            ])->values()),
            'progressive_rules' => $this->whenLoaded('progressiveRules', fn () => $this->progressiveRules->map(fn ($rule) => [
                'id' => $rule->id,
                'min_sa' => (int) $rule->min_sa,
                'max_sa' => $rule->max_sa === null ? null : (int) $rule->max_sa,
                'incentive_amount' => (int) $rule->incentive_amount,
            ])->values()),
        ];
    }
}
