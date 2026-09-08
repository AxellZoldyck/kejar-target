<?php

namespace Tests\Feature\SalesActivity;

use App\Enums\SalesActivityStatus;
use App\Models\Company;
use App\Models\Product;
use App\Models\SalesActivity;
use App\Models\Subscription;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SalesActivityLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_draft_reject_resubmit_validate_lifecycle_is_audited_and_locked(): void
    {
        [$company, $spv, $sales, $team, $product] = $this->scenario();
        Sanctum::actingAs($sales);

        $created = $this->postJson('/api/v1/sales-activities', [
            'product_id' => $product->id,
            'activity_date' => now($company->timezone)->format('Y-m-d'),
            'customer_reference' => 'CUST-001',
            'notes' => 'Kunjungan awal',
        ])->assertCreated()->assertJsonPath('data.status', 'draft');
        $activityId = $created->json('data.id');

        $this->postJson("/api/v1/sales-activities/{$activityId}/submit")
            ->assertOk()
            ->assertJsonPath('data.status', 'pending');
        $this->patchJson("/api/v1/sales-activities/{$activityId}", ['notes' => 'Tidak boleh'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'CONFLICT');

        Sanctum::actingAs($spv);
        $this->postJson("/api/v1/sales-activities/{$activityId}/reject", [])
            ->assertUnprocessable();
        $this->postJson("/api/v1/sales-activities/{$activityId}/reject", ['reason' => 'Bukti belum lengkap'])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.rejection_reason', 'Bukti belum lengkap');

        Sanctum::actingAs($sales);
        $this->patchJson("/api/v1/sales-activities/{$activityId}", ['notes' => 'Bukti diperbaiki'])
            ->assertOk()
            ->assertJsonPath('data.notes', 'Bukti diperbaiki');
        $this->postJson("/api/v1/sales-activities/{$activityId}/submit")
            ->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.rejection_reason', null);

        Sanctum::actingAs($spv);
        $this->postJson("/api/v1/sales-activities/{$activityId}/validate")
            ->assertOk()
            ->assertJsonPath('data.status', 'validated');
        $this->postJson("/api/v1/sales-activities/{$activityId}/validate")
            ->assertStatus(409);

        Sanctum::actingAs($sales);
        $this->deleteJson("/api/v1/sales-activities/{$activityId}")->assertStatus(409);
        $this->getJson("/api/v1/sales-activities/{$activityId}/history")
            ->assertOk()
            ->assertJsonCount(5, 'data');

        $this->assertDatabaseHas('sales_activities', [
            'id' => $activityId,
            'company_id' => $company->id,
            'team_id' => $team->id,
            'status' => SalesActivityStatus::VALIDATED->value,
            'validated_by' => $spv->id,
        ]);
    }

    public function test_other_team_spv_and_other_company_cannot_review_or_read_activity(): void
    {
        [$company, $spv, $sales, $team, $product] = $this->scenario();
        $activity = SalesActivity::factory()->pending()->create([
            'company_id' => $company->id,
            'team_id' => $team->id,
            'sales_id' => $sales->id,
            'product_id' => $product->id,
        ]);
        $otherSpv = User::factory()->spv()->create(['company_id' => $company->id]);
        Team::factory()->create(['company_id' => $company->id, 'supervisor_id' => $otherSpv->id]);
        Sanctum::actingAs($otherSpv);

        $this->postJson("/api/v1/sales-activities/{$activity->id}/validate")->assertNotFound();

        [$companyB, $spvB] = $this->tenantOnly();
        Sanctum::actingAs($spvB);
        $this->getJson("/api/v1/sales-activities/{$activity->id}")->assertNotFound();
        $this->assertNotSame($company->id, $companyB->id);
    }

    public function test_old_or_future_activity_dates_are_rejected(): void
    {
        [$company, , $sales, , $product] = $this->scenario();
        Sanctum::actingAs($sales);

        foreach ([now($company->timezone)->subDays(3), now($company->timezone)->addDay()] as $date) {
            $this->postJson('/api/v1/sales-activities', [
                'product_id' => $product->id,
                'activity_date' => $date->format('Y-m-d'),
                'customer_reference' => 'CUST-WINDOW',
            ])->assertUnprocessable()->assertJsonValidationErrors('activity_date');
        }
    }

    public function test_review_action_rechecks_current_team_supervisor_and_tenant(): void
    {
        [$company, $originalSpv, $sales, $team, $product] = $this->scenario();
        $replacementSpv = User::factory()->spv()->create(['company_id' => $company->id]);
        $activity = SalesActivity::factory()->pending()->create([
            'company_id' => $company->id,
            'team_id' => $team->id,
            'sales_id' => $sales->id,
            'product_id' => $product->id,
        ]);
        $team->update(['supervisor_id' => $replacementSpv->id]);

        $this->expectException(ModelNotFoundException::class);
        app(\App\Actions\SalesActivity\ReviewSalesActivityAction::class)
            ->reject($originalSpv, $activity, 'Tidak lagi berwenang');
    }

    /** @return array{Company, User, User, Team, Product} */
    private function scenario(): array
    {
        [$company, $spv] = $this->tenantOnly();
        $team = Team::factory()->create(['company_id' => $company->id, 'supervisor_id' => $spv->id]);
        $sales = User::factory()->sales()->create(['company_id' => $company->id]);
        TeamMember::factory()->create([
            'company_id' => $company->id,
            'team_id' => $team->id,
            'user_id' => $sales->id,
        ]);
        $product = Product::factory()->create(['company_id' => $company->id]);

        return [$company, $spv, $sales, $team, $product];
    }

    /** @return array{Company, User} */
    private function tenantOnly(): array
    {
        $company = Company::factory()->create();
        $spv = User::factory()->spv()->create(['company_id' => $company->id]);
        Subscription::factory()->trialing()->create(['company_id' => $company->id]);

        return [$company, $spv];
    }
}
