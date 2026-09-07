<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_active' => (bool) $this->is_active,
            'supervisor' => $this->whenLoaded('supervisor', fn () => [
                'id' => $this->supervisor->id,
                'name' => $this->supervisor->name,
                'email' => $this->supervisor->email,
            ]),
            'active_members_count' => $this->whenCounted('activeMembers'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
