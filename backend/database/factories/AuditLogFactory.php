<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AuditLog> */
class AuditLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'actor_id' => (string) Str::uuid(),
            'event' => 'resource.updated',
            'auditable_type' => null,
            'auditable_id' => null,
            'before' => null,
            'after' => null,
            'metadata' => [],
            'created_at' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (AuditLog $log): void {
            if ($log->company_id && $log->actor_id) {
                $actor = User::query()->find($log->actor_id)
                    ?? User::factory()->create(['company_id' => $log->company_id]);
                $actor->forceFill(['company_id' => $log->company_id])->save();
                $log->actor_id = $actor->id;
            }
        });
    }

    public function system(): static
    {
        return $this->state(fn (): array => [
            'company_id' => null,
            'actor_id' => null,
        ]);
    }
}
