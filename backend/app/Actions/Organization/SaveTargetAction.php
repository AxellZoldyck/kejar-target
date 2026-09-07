<?php

namespace App\Actions\Organization;

use App\Models\Target;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveTargetAction
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function create(User $actor, array $data): Target
    {
        $this->assertCanonicalPeriod($data['type'], $data['period_start'], $data['period_end']);

        return DB::transaction(function () use ($actor, $data): Target {
            User::query()
                ->where('company_id', $actor->company_id)
                ->lockForUpdate()
                ->findOrFail($data['sales_id']);
            $duplicate = Target::query()
                ->where('company_id', $actor->company_id)
                ->where('sales_id', $data['sales_id'])
                ->where('type', $data['type'])
                ->where('period_start', $data['period_start'])
                ->lockForUpdate()
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'period_start' => ['Target untuk sales dan periode tersebut sudah tersedia.'],
                ]);
            }

            $target = Target::query()->create([
                ...$data,
                'company_id' => $actor->company_id,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->recordAudit($actor, $target, null, $target->getAttributes(), 'target.created');

            return $target;
        });
    }

    public function update(User $actor, Target $target, int $value): Target
    {
        return DB::transaction(function () use ($actor, $target, $value): Target {
            $locked = Target::query()
                ->where('company_id', $actor->company_id)
                ->lockForUpdate()
                ->findOrFail($target->id);
            $before = $locked->getAttributes();
            $locked->update(['target_value' => $value, 'updated_by' => $actor->id]);
            $this->recordAudit($actor, $locked, $before, $locked->fresh()->getAttributes(), 'target.updated');

            return $locked->refresh();
        });
    }

    private function assertCanonicalPeriod(string $type, string $start, string $end): void
    {
        $periodStart = CarbonImmutable::createFromFormat('Y-m-d', $start)->startOfDay();
        $periodEnd = CarbonImmutable::createFromFormat('Y-m-d', $end)->startOfDay();
        $valid = $type === 'weekly'
            ? $periodStart->isMonday() && $periodEnd->equalTo($periodStart->addDays(6))
            : $periodStart->day === 1 && $periodEnd->equalTo($periodStart->endOfMonth()->startOfDay());

        if (! $valid) {
            throw ValidationException::withMessages([
                'period_end' => [
                    $type === 'weekly'
                        ? 'Periode mingguan harus Senin sampai Minggu.'
                        : 'Periode bulanan harus mencakup satu bulan kalender penuh.',
                ],
            ]);
        }
    }

    private function recordAudit(User $actor, Target $target, ?array $before, array $after, string $event): void
    {
        $this->audit->record(
            event: $event,
            actor: $actor,
            auditable: $target,
            before: $before ?? [],
            after: $after,
        );
    }
}
