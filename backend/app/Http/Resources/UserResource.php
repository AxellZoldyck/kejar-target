<?php

namespace App\Http\Resources;

use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $role = $this->resource->role;

        return [
            'id' => $this->resource->getKey(),
            'company_id' => $this->resource->company_id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'role' => $role instanceof BackedEnum ? $role->value : $role,
            'is_active' => (bool) $this->resource->is_active,
            'created_at' => $this->resource->created_at?->toIso8601String(),
        ];
    }
}
