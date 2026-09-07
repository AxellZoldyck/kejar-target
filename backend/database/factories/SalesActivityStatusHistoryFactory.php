<?php

namespace Database\Factories;

use App\Enums\SalesActivityStatus;
use App\Models\SalesActivity;
use App\Models\SalesActivityStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<SalesActivityStatusHistory> */
class SalesActivityStatusHistoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => (string) Str::uuid(),
            'sales_activity_id' => SalesActivity::factory(),
            'from_status' => SalesActivityStatus::DRAFT,
            'to_status' => SalesActivityStatus::PENDING,
            'actor_id' => (string) Str::uuid(),
            'reason' => null,
            'created_at' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (SalesActivityStatusHistory $history): void {
            $activity = SalesActivity::query()->find($history->sales_activity_id);

            if ($activity) {
                $history->company_id = $activity->company_id;
                $actor = User::query()->find($history->actor_id) ?? $activity->sales;
                $history->actor_id = $actor->id;
            }
        });
    }
}
