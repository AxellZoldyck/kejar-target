<?php

namespace App\Services;

use App\Enums\ProgressiveOverflowBehavior;
use App\Enums\SalesActivityStatus;
use App\Models\Commission;
use App\Models\CommissionMultiplierRule;
use App\Models\CommissionSetting;
use App\Models\Company;
use App\Models\SalesActivity;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\MoneyMath;
use App\Support\PeriodRange;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommissionService
{
    public const FORMULA_VERSION = '1.0';

    public function __construct(private readonly AuditLogger $audit) {}

    public function calculate(Company $company, User $sales, string $period, ?User $actor = null): Commission
    {
        if ($sales->company_id !== $company->id || ! $sales->isSales()) {
            throw ValidationException::withMessages(['sales_id' => ['Sales tidak ditemukan pada perusahaan ini.']]);
        }

        return DB::transaction(function () use ($company, $sales, $period, $actor): Commission {
            // A stable user row serializes every calculation for the same sales.
            // This also protects the first insert, for which no commission row
            // exists yet and therefore cannot itself be locked.
            $lockedSales = User::query()
                ->where('company_id', $company->id)
                ->whereKey($sales->id)
                ->lockForUpdate()
                ->firstOrFail();
            $calculation = $this->preview($company, $lockedSales, $period);
            /** @var CommissionSetting $setting */
            $setting = $calculation['setting'];
            $attributes = [
                'company_id' => $company->id,
                'sales_id' => $lockedSales->id,
                'period' => $period,
                'calculation_version' => $setting->version,
            ];
            $values = [
                'team_id' => $calculation['team_id'],
                'product_fee_amount' => $calculation['product_fee_amount'],
                'multiplier_value' => $calculation['multiplier_value'],
                'progressive_incentive_amount' => $calculation['progressive_incentive_amount'],
                'total_amount' => $calculation['total_amount'],
                'formula_snapshot' => $calculation['formula_snapshot'],
                'calculated_at' => now(),
            ];

            $commission = Commission::query()
                ->where($attributes)
                ->lockForUpdate()
                ->first();

            if ($commission) {
                $commission->update($values);
            } else {
                $commission = Commission::query()->create([...$attributes, ...$values]);
            }

            $this->audit->record(
                event: 'commission.calculated',
                actor: $actor,
                auditable: $commission,
                after: [
                    'period' => $period,
                    'calculation_version' => $setting->version,
                    'total_amount' => $commission->total_amount,
                ],
            );

            return $commission->refresh()->load(['sales:id,name', 'team:id,name']);
        });
    }

    /** @return array<string, mixed> */
    public function preview(Company $company, User $sales, string $period): array
    {
        if ($sales->company_id !== $company->id || ! $sales->isSales()) {
            throw ValidationException::withMessages(['sales_id' => ['Sales tidak ditemukan pada perusahaan ini.']]);
        }

        $range = PeriodRange::monthly($company, $period);
        $setting = $this->settingForPeriod($company, $range);

        if (! $setting) {
            throw ValidationException::withMessages([
                'commission_setting' => ['Aturan komisi untuk periode ini belum diatur.'],
            ]);
        }

        $setting->loadMissing(['productFees.product', 'multiplierRules', 'progressiveRules.product']);

        /** @var Collection<int, SalesActivity> $activities */
        $activities = SalesActivity::query()
            ->where('company_id', $company->id)
            ->where('sales_id', $sales->id)
            ->where('status', SalesActivityStatus::VALIDATED->value)
            ->whereDate('activity_date', '>=', $range->startDate())
            ->whereDate('activity_date', '<=', $range->endDate())
            ->with('product:id,name,code,is_active,product_fee_amount')
            ->orderBy('product_id')
            ->orderBy('activity_date')
            ->orderBy('validated_at')
            ->orderBy('id')
            ->get();

        $fees = $setting->productFees->keyBy('product_id');
        $missingFeeProduct = $activities
            ->first(fn (SalesActivity $activity) => ! $fees->has($activity->product_id));
        if ($missingFeeProduct) {
            throw ValidationException::withMessages([
                'commission_setting' => [
                    'Product Fee untuk '.$missingFeeProduct->product?->name.' belum dikonfigurasi pada versi aturan ini.',
                ],
            ]);
        }

        $productFeeTotal = 0;
        $feeBreakdown = [];
        foreach ($activities->groupBy('product_id') as $productId => $productActivities) {
            $fee = (int) $fees->get($productId)->fee_amount;
            $subtotal = MoneyMath::multiplyUnits($fee, $productActivities->count());
            $productFeeTotal = MoneyMath::add($productFeeTotal, $subtotal);
            $feeBreakdown[] = [
                'product_id' => $productId,
                'product_name' => $productActivities->first()?->product?->name,
                'validated_count' => $productActivities->count(),
                'fee_amount' => $fee,
                'subtotal' => $subtotal,
            ];
        }

        $multiplierRule = $setting->multiplier_enabled
            ? $setting->multiplierRules->first(fn (CommissionMultiplierRule $rule) => $rule->matches($activities->count()))
            : null;
        $multiplier = $multiplierRule?->multiplier_value ?? '1.0000';
        $feeAfterMultiplier = MoneyMath::multiply($productFeeTotal, $multiplier);

        $progressiveTotal = 0;
        $progressiveBreakdown = [];
        if ($setting->progressive_enabled) {
            $rulesByProduct = $setting->progressiveRules->groupBy('product_id');
            foreach ($activities->groupBy('product_id') as $productId => $productActivities) {
                $rules = $rulesByProduct->get($productId, collect())->keyBy('sequence_number');
                $lastRule = $rules->sortKeys()->last();
                $productTotal = 0;
                foreach ($productActivities->values() as $index => $activity) {
                    $sequence = $index + 1;
                    $rule = $rules->get($sequence);
                    if (! $rule
                        && $setting->progressive_overflow_behavior === ProgressiveOverflowBehavior::REPEAT_LAST) {
                        $rule = $lastRule;
                    }
                    $incentive = (int) ($rule?->incentive_amount ?? 0);
                    $productTotal = MoneyMath::add($productTotal, $incentive);
                    $progressiveBreakdown[] = [
                        'activity_id' => $activity->id,
                        'product_id' => $productId,
                        'sequence_number' => $sequence,
                        'incentive_amount' => $incentive,
                    ];
                }
                $progressiveTotal = MoneyMath::add($progressiveTotal, $productTotal);
            }
        }

        // Historical activity owns the attribution. Current membership is only
        // a fallback for an empty period.
        $teamId = $activities->sortByDesc('validated_at')->first()?->team_id
            ?? TeamMember::query()
                ->where('company_id', $company->id)
                ->where('user_id', $sales->id)
                ->where('active_slot', 'active')
                ->value('team_id');

        if (! $teamId) {
            throw ValidationException::withMessages(['sales_id' => ['Sales belum memiliki tim untuk snapshot komisi.']]);
        }

        $total = MoneyMath::add($feeAfterMultiplier, $progressiveTotal);
        $snapshot = [
            'formula' => '(product_fee_total × multiplier) + progressive_incentive',
            'formula_version' => self::FORMULA_VERSION,
            'commission_setting_id' => $setting->id,
            'calculation_version' => $setting->version,
            'period' => $period,
            'timezone' => $company->timezone,
            'validated_activity_ids' => $activities->pluck('id')->values()->all(),
            'product_fee' => ['total' => $productFeeTotal, 'by_product' => $feeBreakdown],
            'multiplier' => [
                'enabled' => $setting->multiplier_enabled,
                'rule_id' => $multiplierRule?->id,
                'validated_count' => $activities->count(),
                'value' => MoneyMath::normalizeMultiplier((string) $multiplier),
                'subtotal' => $feeAfterMultiplier,
            ],
            'progressive' => [
                'enabled' => $setting->progressive_enabled,
                'overflow_behavior' => $setting->progressive_overflow_behavior->value,
                'total' => $progressiveTotal,
                'entries' => $progressiveBreakdown,
            ],
            'total' => $total,
        ];

        return [
            'setting' => $setting,
            'team_id' => $teamId,
            'product_fee_amount' => $productFeeTotal,
            'multiplier_value' => MoneyMath::normalizeMultiplier((string) $multiplier),
            'progressive_incentive_amount' => $progressiveTotal,
            'total_amount' => $total,
            'formula_snapshot' => $snapshot,
        ];
    }

    public function hasSettingForPeriod(Company $company, string $period): bool
    {
        return $this->settingForPeriod($company, PeriodRange::monthly($company, $period)) !== null;
    }

    private function settingForPeriod(Company $company, PeriodRange $range): ?CommissionSetting
    {
        return CommissionSetting::query()
            ->where('company_id', $company->id)
            // A historical calculation uses the newest version that had
            // become effective by the end of that calendar period.
            ->where('effective_from', '<=', $range->end->utc())
            ->with(['productFees.product', 'multiplierRules', 'progressiveRules.product'])
            ->latest('effective_from')
            ->latest('version')
            ->first();
    }
}
