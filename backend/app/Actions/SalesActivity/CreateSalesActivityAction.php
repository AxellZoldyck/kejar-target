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

class CreateSalesActivityAction
{
    public function __construct(private readonly InputWindowService $inputWindow) {}

    public function execute(User $sales, array $data): SalesActivity
    {
        $sales->loadMissing('company');
        $this->inputWindow->assertAllowed($sales->company, $data['activity_date']);

        $membership = TeamMember::query()
            ->where('company_id', $sales->company_id)
            ->where('user_id', $sales->id)
            ->where('active_slot', 'active')
            ->whereHas('team', fn ($query) => $query->where('is_active', true))
            ->first();

        if (! $membership) {
            throw ValidationException::withMessages([
                'team' => ['Sales harus menjadi anggota tim aktif untuk membuat aktivitas.'],
            ]);
        }

        return DB::transaction(function () use ($sales, $membership, $data): SalesActivity {
            $activity = SalesActivity::query()->create([
                ...$data,
                'company_id' => $sales->company_id,
                'team_id' => $membership->team_id,
                'sales_id' => $sales->id,
                'status' => SalesActivityStatus::DRAFT,
            ]);

            SalesActivityStatusHistory::query()->create([
                'company_id' => $sales->company_id,
                'sales_activity_id' => $activity->id,
                'from_status' => null,
                'to_status' => SalesActivityStatus::DRAFT,
                'actor_id' => $sales->id,
                'reason' => null,
            ]);

            return $activity;
        });
    }
}
