<?php

namespace App\Services\Reporting;

use App\Enums\SalesActivityStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\SalesActivity;
use App\Models\Target;
use App\Models\Team;
use App\Models\User;
use App\Support\PeriodRange;
use Illuminate\Validation\ValidationException;

class LeaderboardService
{
    /** @return array{period: array<string, string>, items: array<int, array<string, mixed>>} */
    public function get(
        Company $company,
        User $viewer,
        string $type = 'monthly',
        ?string $period = null,
        string $scope = 'company',
        ?string $teamId = null,
    ): array {
        $range = PeriodRange::fromRequest($company, $type, $period);
        $team = null;

        if ($scope === 'team') {
            if (! $teamId) {
                throw ValidationException::withMessages(['team_id' => ['team_id wajib untuk scope team.']]);
            }
            $team = Team::query()->where('company_id', $company->id)->findOrFail($teamId);
            if ($viewer->isSpv() && $team->supervisor_id !== $viewer->id) {
                abort(404);
            }
            if ($viewer->isSales() && ! $viewer->teams()->where('teams.id', $team->id)->wherePivotNull('left_at')->exists()) {
                abort(404);
            }
        }

        $salesUsers = User::query()
            ->where('company_id', $company->id)
            ->where('role', UserRole::SALES->value)
            ->where('is_active', true)
            ->when($team, fn ($query) => $query->where(function ($visible) use ($team, $range): void {
                $visible->whereHas('teamMemberships', fn ($memberships) => $memberships
                    ->where('team_id', $team->id)
                    ->whereNull('left_at'))
                    ->orWhereHas('salesActivities', fn ($activities) => $activities
                        ->where('company_id', $team->company_id)
                        ->where('team_id', $team->id)
                        ->where('status', SalesActivityStatus::VALIDATED->value)
                        ->whereDate('activity_date', '>=', $range->startDate())
                        ->whereDate('activity_date', '<=', $range->endDate()));
            }))
            ->orderBy('name')
            ->get();

        $items = $salesUsers->map(function (User $sales) use ($company, $range, $team): array {
            $activityQuery = SalesActivity::query()
                ->where('company_id', $company->id)
                ->where('sales_id', $sales->id)
                ->where('status', SalesActivityStatus::VALIDATED->value)
                ->whereDate('activity_date', '>=', $range->startDate())
                ->whereDate('activity_date', '<=', $range->endDate())
                ->when($team, fn ($query) => $query->where('team_id', $team->id));
            $validatedCount = (clone $activityQuery)->count();
            $achievementAt = (clone $activityQuery)->max('validated_at');
            $target = Target::query()
                ->where('company_id', $company->id)
                ->where('sales_id', $sales->id)
                ->where('type', $range->type)
                ->whereDate('period_start', '<=', $range->startDate())
                ->whereDate('period_end', '>=', $range->endDate())
                ->first();
            $targetValue = $target && $target->target_value > 0 ? (int) $target->target_value : null;

            return [
                'rank' => 0,
                'sales_id' => $sales->id,
                'sales_name' => $sales->name,
                'validated_count' => $validatedCount,
                'sales_value' => 0,
                'achievement_at' => $achievementAt,
                'target' => $targetValue,
                'achievement_percent' => $targetValue
                    ? round(($validatedCount / $targetValue) * 100, 2)
                    : null,
            ];
        })->all();

        usort($items, function (array $left, array $right): int {
            $comparison = $right['validated_count'] <=> $left['validated_count'];
            if ($comparison !== 0) {
                return $comparison;
            }
            $comparison = $right['sales_value'] <=> $left['sales_value'];
            if ($comparison !== 0) {
                return $comparison;
            }
            $leftTime = $left['achievement_at'] ?? '9999-12-31T23:59:59Z';
            $rightTime = $right['achievement_at'] ?? '9999-12-31T23:59:59Z';
            $comparison = strcmp((string) $leftTime, (string) $rightTime);

            return $comparison !== 0
                ? $comparison
                : strcmp($left['sales_name'].'-'.$left['sales_id'], $right['sales_name'].'-'.$right['sales_id']);
        });

        foreach ($items as $index => &$item) {
            $item['rank'] = $index + 1;
        }
        unset($item);

        return [
            'period' => [
                'type' => $range->type,
                'start' => $range->startDate(),
                'end' => $range->endDate(),
                'timezone' => $company->timezone,
            ],
            'items' => $items,
        ];
    }
}
