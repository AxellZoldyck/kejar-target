<?php

namespace App\Services\Reporting;

use App\Enums\SalesActivityStatus;
use App\Models\Commission;
use App\Models\Company;
use App\Models\SalesActivity;
use App\Models\Target;
use App\Models\User;
use App\Services\CommissionService;
use App\Support\PeriodRange;
use Illuminate\Validation\ValidationException;

class SalesDashboardService
{
    public function __construct(
        private readonly LeaderboardService $leaderboard,
        private readonly CommissionService $commissions,
    ) {}

    /** @return array<string, mixed> */
    public function get(Company $company, User $sales, ?string $period = null): array
    {
        $monthly = PeriodRange::monthly($company, $period);
        $anchor = $monthly->start->isCurrentMonth()
            ? now($company->timezone)->format('Y-m-d')
            : $monthly->startDate();
        $weekly = PeriodRange::fromRequest($company, 'weekly', $anchor);
        $counts = [];
        foreach (SalesActivityStatus::cases() as $status) {
            $counts[$status->value] = SalesActivity::query()
                ->where('company_id', $company->id)
                ->where('sales_id', $sales->id)
                ->where('status', $status->value)
                ->whereDate('activity_date', '>=', $monthly->startDate())
                ->whereDate('activity_date', '<=', $monthly->endDate())
                ->count();
        }
        $leaderboard = $this->leaderboard->get($company, $sales, 'monthly', $monthly->key());
        $position = collect($leaderboard['items'])->firstWhere('sales_id', $sales->id)['rank'] ?? null;
        $commission = Commission::query()
            ->where('company_id', $company->id)
            ->where('sales_id', $sales->id)
            ->where('period', $monthly->key())
            ->latest('calculation_version')
            ->first();
        $estimate = $commission?->total_amount;
        if ($estimate === null) {
            try {
                $estimate = $this->commissions->preview($company, $sales, $monthly->key())['total_amount'];
            } catch (ValidationException) {
                $estimate = 0;
            }
        }

        return [
            'period' => ['start' => $monthly->startDate(), 'end' => $monthly->endDate()],
            'weekly_target' => $this->targetProgress($company, $sales, $weekly),
            'monthly_target' => $this->targetProgress($company, $sales, $monthly),
            'activity_counts' => $counts,
            'leaderboard_rank' => $position,
            'commission_estimate' => (int) $estimate,
        ];
    }

    /** @return array<string, int|float|null>|null */
    private function targetProgress(Company $company, User $sales, PeriodRange $range): ?array
    {
        $target = Target::query()
            ->where('company_id', $company->id)
            ->where('sales_id', $sales->id)
            ->where('type', $range->type)
            ->whereDate('period_start', '<=', $range->startDate())
            ->whereDate('period_end', '>=', $range->endDate())
            ->first();

        if (! $target) {
            return null;
        }

        $realization = SalesActivity::query()
            ->where('company_id', $company->id)
            ->where('sales_id', $sales->id)
            ->where('status', SalesActivityStatus::VALIDATED->value)
            ->whereDate('activity_date', '>=', $range->startDate())
            ->whereDate('activity_date', '<=', $range->endDate())
            ->count();
        $targetValue = $target->target_value > 0 ? (int) $target->target_value : null;

        return [
            'target' => $targetValue,
            'realization' => $realization,
            'achievement_percent' => $targetValue ? round(($realization / $targetValue) * 100, 2) : null,
        ];
    }
}
