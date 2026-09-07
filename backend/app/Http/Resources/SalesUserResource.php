<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesUserResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $membership = $this->relationLoaded('activeTeamMembership')
            ? $this->activeTeamMembership
            : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role instanceof \BackedEnum ? $this->role->value : $this->role,
            'is_active' => (bool) $this->is_active,
            'team' => $membership?->team ? [
                'id' => $membership->team->id,
                'name' => $membership->team->name,
            ] : null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
