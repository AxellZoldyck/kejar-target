<?php

namespace Tests\Feature\Reporting;

use App\Enums\SalesActivityStatus;
use App\Models\Company;
use App\Models\Product;
use App\Models\SalesActivity;
use App\Models\Subscription;
use App\Models\Target;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardLeaderboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_leaderboard_and_dashboards_share_validated_only_counts(): void
    {
        $company = Company::factory()->create();
        $spv = User::factory()->spv()->create(['company_id' => $company->id]);
        $team = Team::factory()->create(['company_id' => $company->id, 'supervisor_id' => $spv->id]);
        $salesOne = $this->salesInTeam($company, $team, 'Sales Satu');
        $salesTwo = $this->salesInTeam($company, $team, 'Sales Dua');
        $product = Product::factory()->create(['company_id' => $company->id]);
        Subscription::factory()->trialing()->create(['company_id' => $company->id]);
        $month = CarbonImmutable::now($company->timezone)->startOfMonth();

        $this->activity($company, $spv, $team, $salesOne, $product, SalesActivityStatus::VALIDATED, 1);
        $this->activity($company, $spv, $team, $salesOne, $product, SalesActivityStatus::VALIDATED, 2);
        $this->activity($company, $spv, $team, $salesOne, $product, SalesActivityStatus::PENDING, 2);
        $this->activity($company, $spv, $team, $salesTwo, $product, SalesActivityStatus::VALIDATED, 3);
        Target::factory()->create([
            'company_id' => $company->id,
            'sales_id' => $salesOne->id,
            'type' => 'monthly',
            'period_start' => $month,
            'period_end' => $month->endOfMonth(),
            'target_value' => 4,
            'created_by' => $spv->id,
        ]);

        Sanctum::actingAs($spv);
        $this->getJson('/api/v1/leaderboard?period='.$month->format('Y-m'))
            ->assertOk()
            ->assertJsonPath('data.0.sales_id', $salesOne->id)
            ->assertJsonPath('data.0.validated_count', 2)
            ->assertJsonPath('data.0.rank', 1)
            ->assertJsonPath('data.1.validated_count', 1);
        $this->getJson('/api/v1/spv/dashboard?period='.$month->format('Y-m'))
            ->assertOk()
            ->assertJsonPath('data.total_sales', 3)
            ->assertJsonPath('data.pending_validation', 1)
            ->assertJsonPath('data.target', 4)
            ->assertJsonPath('data.active_sales', 2);

        Sanctum::actingAs($salesOne);
        $this->getJson('/api/v1/sales/dashboard?period='.$month->format('Y-m'))
            ->assertOk()
            ->assertJsonPath('data.monthly_target.realization', 2)
            ->assertJsonPath('data.monthly_target.achievement_percent', 50)
            ->assertJsonPath('data.activity_counts.validated', 2)
            ->assertJsonPath('data.activity_counts.pending', 1)
            ->assertJsonPath('data.leaderboard_rank', 1);
    }

    private function salesInTeam(Company $company, Team $team, string $name): User
    {
        $sales = User::factory()->sales()->create(['company_id' => $company->id, 'name' => $name]);
        TeamMember::factory()->create([
            'company_id' => $company->id,
            'team_id' => $team->id,
            'user_id' => $sales->id,
        ]);

        return $sales;
    }

    private function activity(
        Company $company,
        User $spv,
        Team $team,
        User $sales,
        Product $product,
        SalesActivityStatus $status,
        int $day,
    ): void {
        SalesActivity::factory()->create([
            'company_id' => $company->id,
            'team_id' => $team->id,
            'sales_id' => $sales->id,
            'product_id' => $product->id,
            'activity_date' => now()->startOfMonth()->addDays($day),
            'status' => $status,
            'submitted_at' => now(),
            'validated_at' => $status === SalesActivityStatus::VALIDATED ? now()->addSeconds($day) : null,
            'validated_by' => $status === SalesActivityStatus::VALIDATED ? $spv->id : null,
        ]);
    }
}
