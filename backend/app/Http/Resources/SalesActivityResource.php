<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesActivityResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'team_id' => $this->team_id,
            'sales_id' => $this->sales_id,
            'product_id' => $this->product_id,
            'team' => $this->whenLoaded('team', fn () => [
                'id' => $this->team->id,
                'name' => $this->team->name,
            ]),
            'sales' => $this->whenLoaded('sales', fn () => [
                'id' => $this->sales->id,
                'name' => $this->sales->name,
            ]),
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'code' => $this->product->code,
                'is_active' => (bool) $this->product->is_active,
            ]),
            'activity_date' => $this->activity_date?->format('Y-m-d'),
            'customer_reference' => $this->customer_reference,
            'notes' => $this->notes,
            'has_evidence' => filled($this->evidence_path),
            'evidence_url' => $this->evidence_path
                ? route('api.v1.sales-activities.evidence', ['activity' => $this->id])
                : null,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'validated_at' => $this->validated_at?->toISOString(),
            'validated_by' => $this->validated_by,
            'rejection_reason' => $this->rejection_reason,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
