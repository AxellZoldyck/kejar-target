<?php

namespace App\Actions\SalesActivity;

use App\Enums\SalesActivityStatus;
use App\Models\SalesActivity;
use App\Models\SalesActivityStatusHistory;
use App\Models\Team;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\CommissionService;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ReviewSalesActivityAction
{
    public function __construct(
        private readonly CommissionService $commissions,
        private readonly AuditLogger $audit,
    ) {}

    public function validate(User $reviewer, SalesActivity $activity): SalesActivity
    {
        return DB::transaction(function () use ($reviewer, $activity): SalesActivity {
            $validated = $this->transition($reviewer, $activity, SalesActivityStatus::VALIDATED, null);
            $validated->loadMissing(['company', 'sales']);
            $period = $validated->activity_date->format('Y-m');

            if ($this->commissions->hasSettingForPeriod($validated->company, $period)) {
                $this->commissions->calculate(
                    $validated->company,
                    $validated->sales,
                    $period,
                    $reviewer,
                );
            }

            return $validated;
        });
    }

    public function reject(User $reviewer, SalesActivity $activity, string $reason): SalesActivity
    {
        return $this->transition($reviewer, $activity, SalesActivityStatus::REJECTED, trim($reason));
    }

    private function transition(
        User $reviewer,
        SalesActivity $activity,
        SalesActivityStatus $to,
        ?string $reason,
    ): SalesActivity {
        return DB::transaction(function () use ($reviewer, $activity, $to, $reason): SalesActivity {
            $locked = SalesActivity::query()
                ->where('company_id', $reviewer->company_id)
                ->lockForUpdate()
                ->findOrFail($activity->id);
            Team::query()
                ->where('company_id', $reviewer->company_id)
                ->where('supervisor_id', $reviewer->id)
                ->lockForUpdate()
                ->findOrFail($locked->team_id);
            $current = $locked->status instanceof SalesActivityStatus
                ? $locked->status
                : SalesActivityStatus::from($locked->status);

            if ($current !== SalesActivityStatus::PENDING) {
                throw new ConflictHttpException('Aktivitas sudah diproses atau tidak lagi pending.');
            }

            $attributes = [
                'status' => $to->value,
                'updated_at' => now(),
                'validated_at' => $to === SalesActivityStatus::VALIDATED ? now() : null,
                'validated_by' => $to === SalesActivityStatus::VALIDATED ? $reviewer->id : null,
                'rejection_reason' => $to === SalesActivityStatus::REJECTED ? $reason : null,
            ];

            $changed = SalesActivity::query()
                ->whereKey($locked->id)
                ->where('status', SalesActivityStatus::PENDING->value)
                ->update($attributes);

            if ($changed !== 1) {
                throw new ConflictHttpException('Aktivitas sudah diproses oleh permintaan lain.');
            }

            SalesActivityStatusHistory::query()->create([
                'company_id' => $reviewer->company_id,
                'sales_activity_id' => $locked->id,
                'from_status' => SalesActivityStatus::PENDING,
                'to_status' => $to,
                'actor_id' => $reviewer->id,
                'reason' => $reason,
            ]);

            $this->audit->record(
                event: $to === SalesActivityStatus::VALIDATED
                    ? 'sales_activity.validated'
                    : 'sales_activity.rejected',
                actor: $reviewer,
                auditable: $locked,
                before: ['status' => SalesActivityStatus::PENDING->value],
                after: ['status' => $to->value],
                metadata: $reason === null ? [] : ['reason' => $reason],
            );

            return $locked->refresh();
        });
    }
}
