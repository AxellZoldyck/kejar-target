<?php

namespace App\Http\Resources\Commission;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommissionResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'team_id' => $this->team_id,
            'sales_id' => $this->sales_id,
            'sales' => $this->whenLoaded('sales', fn () => [
                'id' => $this->sales->id,
                'name' => $this->sales->name,
            ]),
            'team' => $this->whenLoaded('team', fn () => [
                'id' => $this->team->id,
                'name' => $this->team->name,
            ]),
            'period' => $this->period,
            'product_fee_amount' => (int) $this->product_fee_amount,
            'multiplier_value' => $this->multiplier_value,
            'progressive_incentive_amount' => (int) $this->progressive_incentive_amount,
            'total_amount' => (int) $this->total_amount,
            'formula_snapshot' => $this->formula_snapshot,
            'calculation_version' => (int) $this->calculation_version,
            'calculated_at' => $this->calculated_at?->toISOString(),
            'locked_at' => $this->locked_at?->toISOString(),
        ];
    }
}
