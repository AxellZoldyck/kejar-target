<?php

namespace Database\Seeders;

use App\Enums\ProgressiveOverflowBehavior;
use App\Enums\SalesActivityStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TargetType;
use App\Enums\UserRole;
use App\Models\CommissionMultiplierRule;
use App\Models\CommissionProductFee;
use App\Models\CommissionProgressiveRule;
use App\Models\CommissionSetting;
use App\Models\Company;
use App\Models\Product;
use App\Models\SalesActivity;
use App\Models\SalesActivityStatusHistory;
use App\Models\Subscription;
use App\Models\Target;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $company = Company::query()->updateOrCreate(
                ['slug' => 'demo-kejar-target'],
                [
                    'name' => 'Demo Kejar Target',
                    'activity_label' => 'SA',
                    'timezone' => 'Asia/Jakarta',
                    'status' => 'active',
                ],
            );

            $superAdmin = User::query()->updateOrCreate(
                ['email' => 'admin@kejartarget.test'],
                [
                    'company_id' => null,
                    'name' => 'Super Admin Demo',
                    'password' => Hash::make('password'),
                    'role' => UserRole::SUPER_ADMIN,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );

            $spv = $this->upsertUser($company, 'spv@demo.test', 'SPV Demo', UserRole::SPV);
            $salesOne = $this->upsertUser($company, 'sales1@demo.test', 'Sales Demo Satu', UserRole::SALES);
            $salesTwo = $this->upsertUser($company, 'sales2@demo.test', 'Sales Demo Dua', UserRole::SALES);

            $team = Team::query()->updateOrCreate(
                ['company_id' => $company->id, 'name' => 'Tim Utama'],
                ['supervisor_id' => $spv->id, 'is_active' => true],
            );

            foreach ([$salesOne, $salesTwo] as $sales) {
                TeamMember::query()->updateOrCreate(
                    ['company_id' => $company->id, 'user_id' => $sales->id, 'active_slot' => 'active'],
                    ['team_id' => $team->id, 'joined_at' => now(), 'left_at' => null],
                );
            }

            $productOne = $this->upsertProduct($company, 'Paket Starter', 'STARTER', 50_000);
            $productTwo = $this->upsertProduct($company, 'Paket Pro', 'PRO', 100_000);

            Subscription::query()->updateOrCreate(
                ['company_id' => $company->id, 'running_slot' => 'running'],
                [
                    'plan_code' => 'starter',
                    'status' => SubscriptionStatus::TRIALING,
                    'starts_at' => now(),
                    'trial_ends_at' => now()->addDays(3),
                    'ends_at' => null,
                ],
            );

            $localNow = CarbonImmutable::now($company->timezone);
            $this->upsertTarget($company, $salesOne, $spv, TargetType::WEEKLY, $localNow, 5);
            $this->upsertTarget($company, $salesOne, $spv, TargetType::MONTHLY, $localNow, 20);
            $this->upsertTarget($company, $salesTwo, $spv, TargetType::WEEKLY, $localNow, 4);
            $this->upsertTarget($company, $salesTwo, $spv, TargetType::MONTHLY, $localNow, 16);

            $setting = CommissionSetting::query()->updateOrCreate(
                ['company_id' => $company->id, 'version' => 1],
                [
                    'multiplier_enabled' => true,
                    'progressive_enabled' => true,
                    'progressive_overflow_behavior' => ProgressiveOverflowBehavior::ZERO,
                    'effective_from' => $localNow->startOfMonth()->utc(),
                    'effective_until' => null,
                    'is_active' => true,
                ],
            );

            foreach ([$productOne, $productTwo] as $product) {
                CommissionProductFee::query()->updateOrCreate(
                    ['commission_setting_id' => $setting->id, 'product_id' => $product->id],
                    ['company_id' => $company->id, 'fee_amount' => $product->product_fee_amount],
                );
            }

            foreach ([[1, 4, '1.0000'], [5, 9, '1.2500'], [10, null, '1.5000']] as [$min, $max, $value]) {
                CommissionMultiplierRule::query()->updateOrCreate(
                    ['commission_setting_id' => $setting->id, 'min_sa' => $min],
                    [
                        'company_id' => $company->id,
                        'max_sa' => $max,
                        'multiplier_value' => $value,
                    ],
                );
            }

            foreach ([1 => 50_000, 2 => 50_000, 3 => 100_000] as $sequence => $amount) {
                CommissionProgressiveRule::query()->updateOrCreate(
                    [
                        'commission_setting_id' => $setting->id,
                        'product_id' => $productOne->id,
                        'sequence_number' => $sequence,
                    ],
                    ['company_id' => $company->id, 'incentive_amount' => $amount],
                );
            }

            $validated = $this->upsertActivity(
                $company,
                $team,
                $salesOne,
                $productOne,
                'DEMO-VALIDATED-001',
                SalesActivityStatus::VALIDATED,
                $spv,
            );
            $pending = $this->upsertActivity(
                $company,
                $team,
                $salesTwo,
                $productTwo,
                'DEMO-PENDING-001',
                SalesActivityStatus::PENDING,
                $spv,
            );

            $this->seedHistory($validated, $salesOne, $spv);
            $this->seedHistory($pending, $salesTwo, $spv);

            // Keep static analyzers from treating the intentionally seeded admin as unused.
            $superAdmin->getKey();
        });
    }

    private function upsertUser(Company $company, string $email, string $name, UserRole $role): User
    {
        return User::query()->updateOrCreate(
            ['email' => $email],
            [
                'company_id' => $company->id,
                'name' => $name,
                'password' => Hash::make('password'),
                'role' => $role,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
    }

    private function upsertProduct(Company $company, string $name, string $code, int $fee): Product
    {
        return Product::query()->updateOrCreate(
            ['company_id' => $company->id, 'code' => $code],
            ['name' => $name, 'product_fee_amount' => $fee, 'is_active' => true],
        );
    }

    private function upsertTarget(
        Company $company,
        User $sales,
        User $spv,
        TargetType $type,
        CarbonImmutable $now,
        int $value,
    ): void {
        $start = $type === TargetType::WEEKLY ? $now->startOfWeek() : $now->startOfMonth();
        $end = $type === TargetType::WEEKLY ? $now->endOfWeek() : $now->endOfMonth();

        Target::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'sales_id' => $sales->id,
                'type' => $type,
                'period_start' => $start->toDateString(),
            ],
            [
                'period_end' => $end->toDateString(),
                'target_value' => $value,
                'created_by' => $spv->id,
                'updated_by' => $spv->id,
            ],
        );
    }

    private function upsertActivity(
        Company $company,
        Team $team,
        User $sales,
        Product $product,
        string $reference,
        SalesActivityStatus $status,
        User $spv,
    ): SalesActivity {
        return SalesActivity::query()->updateOrCreate(
            ['company_id' => $company->id, 'customer_reference' => $reference],
            [
                'team_id' => $team->id,
                'sales_id' => $sales->id,
                'product_id' => $product->id,
                'activity_date' => today()->toDateString(),
                'notes' => 'Data demonstrasi lokal.',
                'status' => $status,
                'submitted_at' => now()->subMinutes(10),
                'validated_at' => $status === SalesActivityStatus::VALIDATED ? now()->subMinutes(5) : null,
                'validated_by' => $status === SalesActivityStatus::VALIDATED ? $spv->id : null,
                'rejection_reason' => null,
            ],
        );
    }

    private function seedHistory(SalesActivity $activity, User $sales, User $spv): void
    {
        SalesActivityStatusHistory::query()->firstOrCreate(
            ['sales_activity_id' => $activity->id, 'to_status' => SalesActivityStatus::PENDING],
            [
                'company_id' => $activity->company_id,
                'from_status' => SalesActivityStatus::DRAFT,
                'actor_id' => $sales->id,
                'created_at' => $activity->submitted_at,
            ],
        );

        if ($activity->status === SalesActivityStatus::VALIDATED) {
            SalesActivityStatusHistory::query()->firstOrCreate(
                ['sales_activity_id' => $activity->id, 'to_status' => SalesActivityStatus::VALIDATED],
                [
                    'company_id' => $activity->company_id,
                    'from_status' => SalesActivityStatus::PENDING,
                    'actor_id' => $spv->id,
                    'created_at' => $activity->validated_at,
                ],
            );
        }
    }
}
