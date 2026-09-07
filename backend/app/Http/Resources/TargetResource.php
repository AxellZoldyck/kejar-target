<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TargetResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $target = (int) $this->target_value;
        $actualValue = $this->resource->getAttribute('validated_count');
        $actual = $actualValue === null ? null : (int) $actualValue;

        return [
            'id' => $this->id,
            'sales_id' => $this->sales_id,
            'sales' => $this->whenLoaded('sales', fn () => [
                'id' => $this->sales->id,
                'name' => $this->sales->name,
            ]),
            'type' => $this->type instanceof \BackedEnum ? $this->type->value : $this->type,
            'period_start' => $this->period_start?->format('Y-m-d'),
            'period_end' => $this->period_end?->format('Y-m-d'),
            'target_value' => $target,
            'validated_count' => $actual,
            'achievement_percent' => $actual !== null && $target > 0
                ? round(($actual / $target) * 100, 2)
                : null,
            'configured' => $target > 0,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
