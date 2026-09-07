<?php

namespace Tests\Feature\Uat;

use App\Models\Company;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CoreWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_setup_reject_resubmit_validate_and_reporting_flow_is_consistent(): void
    {
        $registration = $this->withHeaders([
            'Origin' => 'http://localhost:3000',
            'Referer' => 'http://localhost:3000/',
        ])->postJson('/api/v1/auth/register', [
            'company_name' => 'UAT Indonesia',
            'team_name' => 'Tim UAT',
            'name' => 'Supervisor UAT',
            'email' => 'spv-uat@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'timezone' => 'Asia/Jakarta',
        ])->assertCreated()
            ->assertJsonPath('data.user.role', 'spv')
            ->assertJsonPath('data.subscription.status', 'trialing');

        $companyId = $registration->json('data.company.id');
        $company = Company::query()->findOrFail($companyId);
        $spv = User::query()->where('email', 'spv-uat@example.test')->firstOrFail();
        $this->flushHeaders();
        $this->actingAs($spv, 'web');

        $teamId = $this->getJson('/api/v1/teams')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Tim UAT')
            ->json('data.0.id');
        $salesId = $this->postJson('/api/v1/sales', [
            'name' => 'Sales UAT',
            'email' => 'sales-uat@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'team_id' => $teamId,
        ])->assertCreated()
            ->assertJsonPath('data.team.id', $teamId)
            ->json('data.id');
        $productId = $this->postJson('/api/v1/products', [
            'name' => 'Produk UAT',
            'code' => 'UAT-01',
            'product_fee_amount' => 50_000,
        ])->assertCreated()->json('data.id');

        $period = now($company->timezone)->format('Y-m');
        $periodStart = now($company->timezone)->startOfMonth()->format('Y-m-d');
        $periodEnd = now($company->timezone)->endOfMonth()->format('Y-m-d');
        $this->postJson('/api/v1/targets', [
            'sales_id' => $salesId,
            'type' => 'monthly',
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'target_value' => 2,
        ])->assertCreated();

        $this->putJson('/api/v1/commission-settings', [
            'multiplier_enabled' => true,
            'progressive_enabled' => true,
            'progressive_overflow_behavior' => 'zero',
        ])->assertOk();
        $this->putJson('/api/v1/commission-multiplier-rules', [
            'rules' => [[
                'min_sa' => 1,
                'max_sa' => 10,
                'multiplier_value' => '2.0000',
            ]],
        ])->assertOk();
        $this->putJson('/api/v1/commission-progressive-rules', [
            'rules' => [[
                'product_id' => $productId,
                'sequence_number' => 1,
                'incentive_amount' => 25_000,
            ]],
        ])->assertOk();

        $sales = User::query()->findOrFail($salesId);
        Sanctum::actingAs($sales);
        $activityId = $this->postJson('/api/v1/sales-activities', [
            'product_id' => $productId,
            'activity_date' => now($company->timezone)->format('Y-m-d'),
            'customer_reference' => 'CUSTOMER-UAT',
        ])->assertCreated()->json('data.id');
        $this->postJson("/api/v1/sales-activities/{$activityId}/submit")
            ->assertOk()->assertJsonPath('data.status', 'pending');

        Sanctum::actingAs($spv);
        $this->postJson("/api/v1/sales-activities/{$activityId}/reject", [
            'reason' => 'Bukti perlu diperjelas.',
        ])->assertOk()->assertJsonPath('data.status', 'rejected');

        Sanctum::actingAs($sales);
        $this->patchJson("/api/v1/sales-activities/{$activityId}", [
            'notes' => 'Bukti dan catatan sudah diperbaiki.',
        ])->assertOk();
        $this->postJson("/api/v1/sales-activities/{$activityId}/submit")
            ->assertOk()->assertJsonPath('data.status', 'pending');

        Sanctum::actingAs($spv);
        $this->postJson("/api/v1/sales-activities/{$activityId}/validate")
            ->assertOk()->assertJsonPath('data.status', 'validated');
        $this->getJson("/api/v1/targets?period={$periodStart}&sales_id={$salesId}")
            ->assertOk()
            ->assertJsonPath('data.0.validated_count', 1)
            ->assertJsonPath('data.0.achievement_percent', 50);
        $this->getJson("/api/v1/leaderboard?period={$period}&type=monthly")
            ->assertOk()
            ->assertJsonPath('data.0.sales_id', $salesId)
            ->assertJsonPath('data.0.validated_count', 1);
        $this->getJson("/api/v1/commissions?period={$period}&sales_id={$salesId}")
            ->assertOk()
            ->assertJsonPath('data.0.product_fee_amount', 50_000)
            ->assertJsonPath('data.0.multiplier_value', '2.0000')
            ->assertJsonPath('data.0.progressive_incentive_amount', 25_000)
            ->assertJsonPath('data.0.total_amount', 125_000);

        $otherCompany = Company::factory()->create();
        $otherSpv = User::factory()->spv()->for($otherCompany)->create();
        Subscription::factory()->trialing()->for($otherCompany)->create();
        Sanctum::actingAs($otherSpv);
        $this->getJson("/api/v1/sales-activities/{$activityId}")->assertNotFound();
    }
}
