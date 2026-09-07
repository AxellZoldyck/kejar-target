<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        string $event,
        ?User $actor = null,
        ?Model $auditable = null,
        array $before = [],
        array $after = [],
        array $metadata = [],
    ): AuditLog {
        $companyId = $actor?->company_id;

        if ($companyId === null && $auditable instanceof Company) {
            $companyId = $auditable->getKey();
        } elseif ($companyId === null) {
            $companyId = $auditable?->getAttribute('company_id');
        }

        return AuditLog::query()->create([
            'company_id' => $companyId,
            'actor_id' => $actor?->getKey(),
            'event' => $event,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'before' => $before === [] ? null : $before,
            'after' => $after === [] ? null : $after,
            'metadata' => $metadata === [] ? null : $metadata,
            'created_at' => now(),
        ]);
    }
}
