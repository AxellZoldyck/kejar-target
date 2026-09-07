<?php

namespace Tests\Feature\Target;

use App\Enums\SalesActivityStatus;
use App\Models\Company;
use App\Models\Product;
use App\Models\SalesActivity;
use App\Models\Subscription;
use App\Models\Team;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TargetProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_target_progress_counts_only_validated_activities_in_period(): void
    {
        $company = Company::factory()->create();
        $spv = User::factory()->spv()->create(['company_id' => $company->id]);
        $sales = User::factory()->sales()->create(['company_id' => $company->id]);
        $team = Team::factory()->create(['company_id' => $company->id, 'supervisor_id' => $spv->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);
        Subscription::factory()->trialing()->create(['company_id' => $company->id]);
        $month = CarbonImmutable::now($company->timezone)->startOfMonth();

        foreach ([SalesActivityStatus::VALIDATED, SalesActivityStatus::VALIDATED, SalesActivityStatus::PENDING] as $status) {
            SalesActivity::factory()->create([
                'company_id' => $company->id,
                'team_id' => $team->id,
                'sales_id' => $sales->id,
                'product_id' => $product->id,
                'activity_date' => $month->addDay(),
                'status' => $status,
                'validated_at' => $status === SalesActivityStatus::VALIDATED ? now() : null,
                'validated_by' => $status === SalesActivityStatus::VALIDATED ? $spv->id : null,
            ]);
        }
        SalesActivity::factory()->validated()->create([
            'company_id' => $company->id,
            'team_id' => $team->id,
            'sales_id' => $sales->id,
            'product_id' => $product->id,
            'activity_date' => $month->subDay(),
            'validated_by' => $spv->id,
        ]);

        Sanctum::actingAs($spv);
        $this->postJson('/api/v1/targets', [
            'sales_id' => $sales->id,
            'type' => 'monthly',
            'period_start' => $month->format('Y-m-d'),
            'period_end' => $month->endOfMonth()->format('Y-m-d'),
            'target_value' => 4,
        ])->assertCreated();

        $this->getJson('/api/v1/targets?period='.$month->format('Y-m-d').'&sales_id='.$sales->id)
            ->assertOk()
            ->assertJsonPath('data.0.validated_count', 2)
            ->assertJsonPath('data.0.achievement_percent', 50);
    }

    public function test_zero_target_is_reported_as_not_configured_without_percentage(): void
    {
        $company = Company::factory()->create();
        $spv = User::factory()->spv()->create(['company_id' => $company->id]);
        $sales = User::factory()->sales()->create(['company_id' => $company->id]);
        Subscription::factory()->trialing()->create(['company_id' => $company->id]);
        $start = CarbonImmutable::now($company->timezone)->startOfWeek();
        Sanctum::actingAs($spv);

        $this->postJson('/api/v1/targets', [
            'sales_id' => $sales->id,
            'type' => 'weekly',
            'period_start' => $start->format('Y-m-d'),
            'period_end' => $start->addDays(6)->format('Y-m-d'),
            'target_value' => 0,
        ])->assertCreated();

        $this->getJson('/api/v1/targets?sales_id='.$sales->id)
            ->assertOk()
            ->assertJsonPath('data.0.configured', false)
            ->assertJsonPath('data.0.achievement_percent', null);
    }
}
