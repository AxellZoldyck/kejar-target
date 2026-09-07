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
use Carbon\CarbonImmutable;

class SpvDashboardService
{
    /** @return array<string, mixed> */
    public function get(Company $company, User $spv, ?string $period = null): array
    {
        $range = PeriodRange::monthly($company, $period);
        $teamIds = Team::query()
            ->where('company_id', $company->id)
            ->where('supervisor_id', $spv->id)
            ->pluck('id');
        $salesIds = User::query()
            ->where('company_id', $company->id)
            ->where('role', UserRole::SALES->value)
            ->where('is_active', true)
            ->whereHas('teamMemberships', fn ($query) => $query
                ->whereIn('team_id', $teamIds)
                ->whereNull('left_at'))
            ->pluck('id');
        $validated = SalesActivity::query()
            ->where('company_id', $company->id)
            ->whereIn('team_id', $teamIds)
            ->where('status', SalesActivityStatus::VALIDATED->value)
            ->whereDate('activity_date', '>=', $range->startDate())
            ->whereDate('activity_date', '<=', $range->endDate())
            ->count();
        $target = Target::query()
            ->where('company_id', $company->id)
            ->whereIn('sales_id', $salesIds)
            ->where('type', 'monthly')
            ->whereDate('period_start', '<=', $range->startDate())
            ->whereDate('period_end', '>=', $range->endDate())
            ->sum('target_value');
        $targetValue = $target > 0 ? (int) $target : null;
        $today = CarbonImmutable::now($company->timezone)->startOfDay();
        $activity = [];
        for ($daysAgo = 6; $daysAgo >= 0; $daysAgo--) {
            $date = $today->subDays($daysAgo)->format('Y-m-d');
            $activity[] = [
                'date' => $date,
                'count' => SalesActivity::query()
                    ->where('company_id', $company->id)
                    ->whereIn('team_id', $teamIds)
                    ->where('status', SalesActivityStatus::VALIDATED->value)
                    ->whereDate('activity_date', $date)
                    ->count(),
            ];
        }
        $topSalesIds = SalesActivity::query()
            ->where('company_id', $company->id)
            ->whereIn('team_id', $teamIds)
            ->where('status', SalesActivityStatus::VALIDATED->value)
            ->whereDate('activity_date', '>=', $range->startDate())
            ->whereDate('activity_date', '<=', $range->endDate())
            ->pluck('sales_id')
            ->merge($salesIds)
            ->unique()
            ->values();
        $top = User::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->whereIn('id', $topSalesIds)
            ->get(['id', 'name'])
            ->map(function (User $sales) use ($company, $teamIds, $range): array {
                $activityQuery = SalesActivity::query()
                    ->where('company_id', $company->id)
                    ->whereIn('team_id', $teamIds)
                    ->where('sales_id', $sales->id)
                    ->where('status', SalesActivityStatus::VALIDATED->value)
                    ->whereDate('activity_date', '>=', $range->startDate())
                    ->whereDate('activity_date', '<=', $range->endDate());

                return [
                    'sales_id' => $sales->id,
                    'sales_name' => $sales->name,
                    'validated_count' => (clone $activityQuery)->count(),
                    'achievement_at' => (clone $activityQuery)->max('validated_at'),
                ];
            })
            ->sort(function (array $left, array $right): int {
                $comparison = $right['validated_count'] <=> $left['validated_count'];
                if ($comparison !== 0) {
                    return $comparison;
                }

                $comparison = strcmp(
                    (string) ($left['achievement_at'] ?? '9999-12-31T23:59:59Z'),
                    (string) ($right['achievement_at'] ?? '9999-12-31T23:59:59Z'),
                );

                return $comparison !== 0
                    ? $comparison
                    : strcmp($left['sales_name'].'-'.$left['sales_id'], $right['sales_name'].'-'.$right['sales_id']);
            })
            ->take(5)
            ->values();

        return [
            'period' => ['start' => $range->startDate(), 'end' => $range->endDate()],
            'total_sales' => $validated,
            'target' => $targetValue,
            'achievement_percent' => $targetValue ? round(($validated / $targetValue) * 100, 2) : null,
            'active_sales' => $salesIds->count(),
            'pending_validation' => SalesActivity::query()
                ->where('company_id', $company->id)
                ->whereIn('team_id', $teamIds)
                ->where('status', SalesActivityStatus::PENDING->value)
                ->count(),
            'activity_last_7_days' => $activity,
            'top_performers' => $top->map(fn ($item) => [
                'sales_id' => $item['sales_id'],
                'sales_name' => $item['sales_name'],
                'validated_count' => $item['validated_count'],
            ])->all(),
        ];
    }
}
