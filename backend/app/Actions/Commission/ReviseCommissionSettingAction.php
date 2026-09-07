<?php

namespace App\Actions\Commission;

use App\Enums\ProgressiveOverflowBehavior;
use App\Models\CommissionSetting;
use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\MoneyMath;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviseCommissionSettingAction
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function execute(
        User $actor,
        array $settings = [],
        ?array $fees = null,
        ?array $multipliers = null,
        ?array $progressives = null,
    ): CommissionSetting {
        return DB::transaction(function () use ($actor, $settings, $fees, $multipliers, $progressives): CommissionSetting {
            // The company row also serializes publication of the first version,
            // when there is no current setting row available to lock yet.
            $company = Company::query()->lockForUpdate()->findOrFail($actor->company_id);
            $current = CommissionSetting::query()
                ->where('company_id', $company->id)
                ->where('active_slot', 'active')
                ->with(['productFees', 'multiplierRules', 'progressiveRules'])
                ->lockForUpdate()
                ->first();

            $this->validateReplacements($company, $fees, $multipliers, $progressives);

            if ($current) {
                $current->update([
                    'is_active' => false,
                    'effective_until' => now(),
                ]);
            }

            $setting = CommissionSetting::query()->create([
                'company_id' => $company->id,
                'version' => ($current?->version ?? 0) + 1,
                'multiplier_enabled' => $settings['multiplier_enabled'] ?? $current?->multiplier_enabled ?? false,
                'progressive_enabled' => $settings['progressive_enabled'] ?? $current?->progressive_enabled ?? false,
                'progressive_overflow_behavior' => $settings['progressive_overflow_behavior']
                    ?? $current?->progressive_overflow_behavior
                    ?? ProgressiveOverflowBehavior::ZERO,
                'effective_from' => now(),
                'effective_until' => null,
                'is_active' => true,
            ]);

            $feeSource = collect($fees ?? ($current
                ? $current->productFees->map(fn ($row) => $row->only(['product_id', 'fee_amount']))->all()
                : []))->keyBy('product_id');
            $feeRows = Product::query()
                ->where('company_id', $company->id)
                ->get()
                ->map(fn (Product $product) => [
                    'product_id' => $product->id,
                    'fee_amount' => (int) ($feeSource->get($product->id)['fee_amount']
                        ?? $product->product_fee_amount),
                ])->all();
            $multiplierRows = $multipliers ?? ($current
                ? $current->multiplierRules->map(fn ($row) => $row->only(['min_sa', 'max_sa', 'multiplier_value']))->all()
                : []);
            $progressiveRows = $progressives ?? ($current
                ? $current->progressiveRules->map(fn ($row) => $row->only([
                    'product_id', 'sequence_number', 'incentive_amount',
                ]))->all()
                : []);

            foreach ($feeRows as $row) {
                $setting->productFees()->create([
                    ...$row,
                    'company_id' => $company->id,
                ]);
            }
            if ($fees !== null) {
                foreach ($fees as $row) {
                    Product::query()
                        ->where('company_id', $company->id)
                        ->whereKey($row['product_id'])
                        ->update(['product_fee_amount' => $row['fee_amount']]);
                }
            }
            foreach ($multiplierRows as $row) {
                $setting->multiplierRules()->create([
                    ...$row,
                    'multiplier_value' => MoneyMath::normalizeMultiplier((string) $row['multiplier_value']),
                    'company_id' => $company->id,
                ]);
            }
            foreach ($progressiveRows as $row) {
                $setting->progressiveRules()->create([
                    ...$row,
                    'company_id' => $company->id,
                ]);
            }

            $this->audit->record(
                event: 'commission.setting.published',
                actor: $actor,
                auditable: $setting,
                before: $current ? ['version' => $current->version] : [],
                after: ['version' => $setting->version],
            );

            return $setting->load(['productFees.product', 'multiplierRules', 'progressiveRules.product']);
        });
    }

    private function validateReplacements(
        Company $company,
        ?array $fees,
        ?array $multipliers,
        ?array $progressives,
    ): void {
        foreach ([$fees, $progressives] as $rows) {
            if ($rows === null) {
                continue;
            }
            $productIds = collect($rows)->pluck('product_id')->unique()->values();
            $ownedCount = Product::query()
                ->where('company_id', $company->id)
                ->whereIn('id', $productIds)
                ->count();
            if ($ownedCount !== $productIds->count()) {
                throw ValidationException::withMessages(['rules' => ['Semua produk harus berasal dari perusahaan aktif.']]);
            }
        }

        if ($fees !== null && collect($fees)->pluck('product_id')->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['fees' => ['Satu produk hanya boleh memiliki satu Product Fee.']]);
        }

        if ($progressives !== null) {
            $keys = collect($progressives)->map(fn ($row) => $row['product_id'].':'.$row['sequence_number']);
            if ($keys->duplicates()->isNotEmpty()) {
                throw ValidationException::withMessages(['rules' => ['Urutan progressive per produk harus unik.']]);
            }
        }

        if ($multipliers !== null) {
            $sorted = collect($multipliers)->sortBy('min_sa')->values();
            foreach ($sorted as $index => $row) {
                $max = $row['max_sa'] ?? PHP_INT_MAX;
                if ($max < $row['min_sa']) {
                    throw ValidationException::withMessages(['rules' => ['max_sa harus lebih besar atau sama dengan min_sa.']]);
                }
                if ($index > 0) {
                    $previous = $sorted[$index - 1];
                    $previousMax = $previous['max_sa'] ?? PHP_INT_MAX;
                    if ($row['min_sa'] <= $previousMax) {
                        throw ValidationException::withMessages(['rules' => ['Rentang multiplier tidak boleh tumpang tindih.']]);
                    }
                }
            }
        }
    }
}
