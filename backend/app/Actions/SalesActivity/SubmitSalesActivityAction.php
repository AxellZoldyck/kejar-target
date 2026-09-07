<?php

namespace App\Actions\SalesActivity;

use App\Enums\SalesActivityStatus;
use App\Models\SalesActivity;
use App\Models\SalesActivityStatusHistory;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\InputWindowService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class SubmitSalesActivityAction
{
    public function __construct(private readonly InputWindowService $inputWindow) {}

    public function execute(User $sales, SalesActivity $activity): SalesActivity
    {
        $activity->loadMissing('company');
        $this->inputWindow->assertAllowed($activity->company, $activity->activity_date);

        $hasMembership = TeamMember::query()
            ->where('company_id', $sales->company_id)
            ->where('user_id', $sales->id)
            ->where('active_slot', 'active')
            ->whereHas('team', fn ($query) => $query->where('is_active', true))
            ->exists();

        if (! $hasMembership || ! $sales->is_active) {
            throw ValidationException::withMessages([
                'team' => ['Sales harus aktif dan menjadi anggota tim aktif untuk mengirim aktivitas.'],
            ]);
        }

        return DB::transaction(function () use ($sales, $activity): SalesActivity {
            $locked = SalesActivity::query()->lockForUpdate()->findOrFail($activity->id);
            $from = $locked->status instanceof SalesActivityStatus
                ? $locked->status
                : SalesActivityStatus::from($locked->status);

            if (! in_array($from, [SalesActivityStatus::DRAFT, SalesActivityStatus::REJECTED], true)) {
                throw new ConflictHttpException('Aktivitas tidak dapat dikirim dari status saat ini.');
            }

            $changed = SalesActivity::query()
                ->whereKey($locked->id)
                ->where('status', $from->value)
                ->update([
                    'status' => SalesActivityStatus::PENDING->value,
                    'submitted_at' => now(),
                    'rejection_reason' => null,
                    'updated_at' => now(),
                ]);

            if ($changed !== 1) {
                throw new ConflictHttpException('Aktivitas sudah diproses oleh permintaan lain.');
            }

            SalesActivityStatusHistory::query()->create([
                'company_id' => $sales->company_id,
                'sales_activity_id' => $locked->id,
                'from_status' => $from,
                'to_status' => SalesActivityStatus::PENDING,
                'actor_id' => $sales->id,
                'reason' => null,
            ]);

            return $locked->refresh();
        });
    }
}
