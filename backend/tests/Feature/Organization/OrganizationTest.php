<?php

namespace Tests\Feature\Organization;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_spv_can_manage_team_sales_membership_and_products_in_own_company(): void
    {
        [$company, $spv, $team] = $this->tenant();
        Sanctum::actingAs($spv);

        $this->postJson('/api/v1/teams', ['name' => 'Tim Dua'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Tim Dua');

        $salesResponse = $this->postJson('/api/v1/sales', [
            'name' => 'Sales Satu',
            'email' => 'sales@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'team_id' => $team->id,
        ])->assertCreated()->assertJsonPath('data.team.id', $team->id);

        $salesId = $salesResponse->json('data.id');
        $this->assertDatabaseHas('users', [
            'id' => $salesId,
            'company_id' => $company->id,
            'role' => UserRole::SALES->value,
        ]);
        $this->assertDatabaseHas('team_members', [
            'team_id' => $team->id,
            'user_id' => $salesId,
            'active_slot' => 'active',
        ]);

        $this->postJson('/api/v1/products', [
            'name' => 'Paket Starter',
            'code' => 'STARTER',
            'product_fee_amount' => 50_000,
        ])->assertCreated()->assertJsonPath('data.product_fee_amount', 50_000);

        $this->getJson('/api/v1/sales')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/products')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_tenant_resources_and_foreign_ids_cannot_cross_companies(): void
    {
        [$companyA, $spvA, $teamA] = $this->tenant();
        [$companyB, , $teamB] = $this->tenant();
        $productB = Product::factory()->create(['company_id' => $companyB->id]);
        $salesB = User::factory()->sales()->create(['company_id' => $companyB->id]);
        Sanctum::actingAs($spvA);

        $this->getJson('/api/v1/teams/'.$teamB->id)->assertNotFound();
        $this->patchJson('/api/v1/products/'.$productB->id, ['name' => 'Bocor'])
            ->assertNotFound();
        $this->postJson('/api/v1/teams/'.$teamA->id.'/members', ['sales_id' => $salesB->id])
            ->assertUnprocessable();

        $this->assertDatabaseHas('products', ['id' => $productB->id, 'company_id' => $companyB->id]);
        $this->assertDatabaseMissing('team_members', ['team_id' => $teamA->id, 'user_id' => $salesB->id]);
        $this->assertNotSame($companyA->id, $companyB->id);
    }

    public function test_team_name_must_be_unique_within_company_but_can_repeat_across_companies(): void
    {
        [$companyA, $spvA, $teamA] = $this->tenant();
        [$companyB, $spvB] = $this->tenant();

        Sanctum::actingAs($spvA);
        $this->postJson('/api/v1/teams', ['name' => $teamA->name])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors('name');

        $otherTeam = Team::factory()->create([
            'company_id' => $companyA->id,
            'supervisor_id' => $spvA->id,
            'name' => 'Nama Lain',
        ]);
        $this->patchJson('/api/v1/teams/'.$otherTeam->id, ['name' => $teamA->name])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
        $this->patchJson('/api/v1/teams/'.$teamA->id, ['name' => $teamA->name])
            ->assertOk();

        Sanctum::actingAs($spvB);
        $this->postJson('/api/v1/teams', ['name' => $teamA->name])
            ->assertCreated();

        $this->assertSame(2, Team::query()->where('name', $teamA->name)->count());
        $this->assertNotSame($companyA->id, $companyB->id);
    }

    public function test_reassignment_keeps_exactly_one_active_membership_and_preserves_history(): void
    {
        [$company, $spv, $firstTeam] = $this->tenant();
        $secondTeam = Team::factory()->create([
            'company_id' => $company->id,
            'supervisor_id' => $spv->id,
        ]);
        $sales = User::factory()->sales()->create(['company_id' => $company->id]);
        Sanctum::actingAs($spv);

        $this->postJson('/api/v1/teams/'.$firstTeam->id.'/members', ['sales_id' => $sales->id])
            ->assertCreated();
        $this->postJson('/api/v1/teams/'.$secondTeam->id.'/members', ['sales_id' => $sales->id])
            ->assertCreated();

        $memberships = TeamMember::query()
            ->where('company_id', $company->id)
            ->where('user_id', $sales->id)
            ->oldest('joined_at')
            ->get();

        $this->assertCount(2, $memberships);
        $this->assertNotNull($memberships->first()->left_at);
        $this->assertNull($memberships->first()->active_slot);
        $this->assertSame($secondTeam->id, $memberships->last()->team_id);
        $this->assertSame('active', $memberships->last()->active_slot);
    }

    /** @return array{Company, User, Team} */
    private function tenant(): array
    {
        $company = Company::factory()->create();
        $spv = User::factory()->spv()->create(['company_id' => $company->id]);
        $team = Team::factory()->create([
            'company_id' => $company->id,
            'supervisor_id' => $spv->id,
        ]);
        Subscription::factory()->trialing()->create(['company_id' => $company->id]);

        return [$company, $spv, $team];
    }
}
