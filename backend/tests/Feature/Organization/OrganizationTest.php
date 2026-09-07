<?php

namespace Tests\Feature\Organization;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\Team;
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
