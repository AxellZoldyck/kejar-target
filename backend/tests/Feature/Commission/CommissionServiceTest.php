<?php

namespace Tests\Feature\Commission;

use App\Actions\Commission\ReviseCommissionSettingAction;
use App\Enums\SalesActivityStatus;
use App\Models\Company;
use App\Models\Product;
use App\Models\SalesActivity;
use App\Models\Subscription;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\CommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CommissionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_formula_handles_multiple_products_multiplier_progressive_and_ignores_nonvalidated(): void
    {
        [$company, $spv, $sales, $team, $productA, $productB] = $this->scenario();
        $this->setting($spv, [
            'multiplier_enabled' => true,
            'progressive_enabled' => true,
            'progressive_overflow_behavior' => 'zero',
        ], [
            ['product_id' => $productA->id, 'fee_amount' => 50_000],
            ['product_id' => $productB->id, 'fee_amount' => 100_000],
        ], [
            ['min_sa' => 1, 'max_sa' => 2, 'multiplier_value' => '1.0000'],
            ['min_sa' => 3, 'max_sa' => 5, 'multiplier_value' => '1.5000'],
        ], [
            ['min_sa' => 1, 'max_sa' => 2, 'incentive_amount' => 50_000],
            ['min_sa' => 3, 'max_sa' => 4, 'incentive_amount' => 100_000],
        ]);

        $this->activities($company, $spv, $sales, $team, $productA, 3);
        $this->activities($company, $spv, $sales, $team, $productB, 1);
        $this->activity($company, $spv, $sales, $team, $productA, SalesActivityStatus::PENDING);

        $commission = app(CommissionService::class)->calculate($company, $sales, now()->format('Y-m'), $spv);

        $this->assertSame(250_000, $commission->product_fee_amount);
        $this->assertSame('1.5000', $commission->multiplier_value);
        $this->assertSame(300_000, $commission->progressive_incentive_amount);
        $this->assertSame(675_000, $commission->total_amount);
        $this->assertCount(4, $commission->formula_snapshot['validated_activity_ids']);
        $this->assertSame('2.0', $commission->formula_snapshot['formula_version']);
        $this->assertSame(
            [1, 2, 3, 4],
            array_column($commission->formula_snapshot['progressive']['entries'], 'sequence_number'),
        );
    }

    public function test_disabled_rules_default_multiplier_to_one_and_no_progressive(): void
    {
        [$company, $spv, $sales, $team, $productA] = $this->scenario();
        $this->setting($spv, [
            'multiplier_enabled' => false,
            'progressive_enabled' => false,
            'progressive_overflow_behavior' => 'zero',
        ], [['product_id' => $productA->id, 'fee_amount' => 50_000]], [
            ['min_sa' => 1, 'max_sa' => null, 'multiplier_value' => '2.0000'],
        ], [['min_sa' => 1, 'max_sa' => 1, 'incentive_amount' => 99_000]]);
        $this->activities($company, $spv, $sales, $team, $productA, 1);

        $commission = app(CommissionService::class)->calculate($company, $sales, now()->format('Y-m'));

        $this->assertSame(50_000, $commission->total_amount);
        $this->assertSame('1.0000', $commission->multiplier_value);
        $this->assertSame(0, $commission->progressive_incentive_amount);
    }

    public function test_no_activity_returns_zero_and_recalculation_is_idempotent(): void
    {
        [$company, $spv, $sales, , $productA] = $this->scenario();
        $this->setting($spv, [
            'multiplier_enabled' => false,
            'progressive_enabled' => false,
            'progressive_overflow_behavior' => 'zero',
        ], [['product_id' => $productA->id, 'fee_amount' => 50_000]], [], []);
        $period = now()->format('Y-m');

        $first = app(CommissionService::class)->calculate($company, $sales, $period, $spv);
        $second = app(CommissionService::class)->calculate($company, $sales, $period, $spv);

        $this->assertSame(0, $first->total_amount);
        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('commissions', 1);
    }

    public function test_progressive_tier_overflow_zero_and_repeat_last_are_deterministic(): void
    {
        [$company, $spv, $sales, $team, $productA] = $this->scenario();
        $action = app(ReviseCommissionSettingAction::class);
        $action->execute($spv, [
            'multiplier_enabled' => false,
            'progressive_enabled' => true,
            'progressive_overflow_behavior' => 'zero',
        ], [['product_id' => $productA->id, 'fee_amount' => 0]], [], [
            ['min_sa' => 1, 'max_sa' => 1, 'incentive_amount' => 10_000],
            ['min_sa' => 2, 'max_sa' => 2, 'incentive_amount' => 20_000],
        ]);
        $this->activities($company, $spv, $sales, $team, $productA, 4);
        $period = now()->format('Y-m');

        $zero = app(CommissionService::class)->calculate($company, $sales, $period);
        $this->assertSame(30_000, $zero->total_amount);

        $action->execute($spv, ['progressive_overflow_behavior' => 'repeat_last']);
        $repeat = app(CommissionService::class)->calculate($company, $sales, $period);

        $this->assertSame(70_000, $repeat->total_amount);
        $this->assertNotSame($zero->calculation_version, $repeat->calculation_version);
    }

    public function test_progressive_ranges_must_not_overlap(): void
    {
        [, $spv] = $this->scenario();

        $this->expectException(ValidationException::class);
        app(ReviseCommissionSettingAction::class)->execute($spv, progressives: [
            ['min_sa' => 1, 'max_sa' => 3, 'incentive_amount' => 10_000],
            ['min_sa' => 3, 'max_sa' => 5, 'incentive_amount' => 20_000],
        ]);
    }

    public function test_multiplier_boundaries_no_match_and_inactive_historical_product(): void
    {
        [$company, $spv, $sales, $team, $productA] = $this->scenario();
        $this->setting($spv, [
            'multiplier_enabled' => true,
            'progressive_enabled' => false,
            'progressive_overflow_behavior' => 'zero',
        ], [['product_id' => $productA->id, 'fee_amount' => 100]], [
            ['min_sa' => 2, 'max_sa' => 3, 'multiplier_value' => '1.2500'],
        ], []);
        $period = now()->format('Y-m');

        $this->activities($company, $spv, $sales, $team, $productA, 1);
        $this->assertSame(100, app(CommissionService::class)->calculate($company, $sales, $period)->total_amount);

        $this->activities($company, $spv, $sales, $team, $productA, 1);
        $this->assertSame(250, app(CommissionService::class)->calculate($company, $sales, $period)->total_amount);

        $this->activities($company, $spv, $sales, $team, $productA, 1);
        $productA->update(['is_active' => false]);
        $this->assertSame(375, app(CommissionService::class)->calculate($company, $sales, $period)->total_amount);

        $this->activities($company, $spv, $sales, $team, $productA, 1);
        $commission = app(CommissionService::class)->calculate($company, $sales, $period);
        $this->assertSame(400, $commission->total_amount);
        $this->assertSame('1.0000', $commission->multiplier_value);
        $this->assertDatabaseCount('commissions', 1);
    }

    public function test_commission_rejects_sales_from_another_company(): void
    {
        [$company, $spv, , , $productA] = $this->scenario();
        $this->setting($spv, [
            'multiplier_enabled' => false,
            'progressive_enabled' => false,
            'progressive_overflow_behavior' => 'zero',
        ], [['product_id' => $productA->id, 'fee_amount' => 1]], [], []);
        $outsider = User::factory()->sales()->create();

        $this->expectException(ValidationException::class);
        app(CommissionService::class)->calculate($company, $outsider, now()->format('Y-m'));
    }

    private function setting(User $spv, array $settings, array $fees, array $multipliers, array $progressives): void
    {
        app(ReviseCommissionSettingAction::class)->execute($spv, $settings, $fees, $multipliers, $progressives);
    }

    private function activities(
        Company $company,
        User $spv,
        User $sales,
        Team $team,
        Product $product,
        int $count,
    ): void {
        for ($index = 0; $index < $count; $index++) {
            $this->activity($company, $spv, $sales, $team, $product, SalesActivityStatus::VALIDATED, $index);
        }
    }

    private function activity(
        Company $company,
        User $spv,
        User $sales,
        Team $team,
        Product $product,
        SalesActivityStatus $status,
        int $offset = 0,
    ): SalesActivity {
        return SalesActivity::factory()->create([
            'company_id' => $company->id,
            'team_id' => $team->id,
            'sales_id' => $sales->id,
            'product_id' => $product->id,
            'activity_date' => now()->startOfMonth()->addDays($offset),
            'status' => $status,
            'submitted_at' => now()->subMinute(),
            'validated_at' => $status === SalesActivityStatus::VALIDATED ? now()->addSeconds($offset) : null,
            'validated_by' => $status === SalesActivityStatus::VALIDATED ? $spv->id : null,
        ]);
    }

    /** @return array{Company, User, User, Team, Product, Product} */
    private function scenario(): array
    {
        $company = Company::factory()->create();
        $spv = User::factory()->spv()->create(['company_id' => $company->id]);
        $sales = User::factory()->sales()->create(['company_id' => $company->id]);
        $team = Team::factory()->create(['company_id' => $company->id, 'supervisor_id' => $spv->id]);
        TeamMember::factory()->create([
            'company_id' => $company->id,
            'team_id' => $team->id,
            'user_id' => $sales->id,
        ]);
        Subscription::factory()->trialing()->create(['company_id' => $company->id]);
        $productA = Product::factory()->create(['company_id' => $company->id]);
        $productB = Product::factory()->create(['company_id' => $company->id]);

        return [$company, $spv, $sales, $team, $productA, $productB];
    }
}
