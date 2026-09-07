<?php

namespace Tests\Feature\Tenant;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class TenantAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_endpoint_always_resolves_tenant_from_authenticated_user(): void
    {
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $companyB = Company::factory()->create(['name' => 'Company B']);
        $spvA = User::factory()->spv()->for($companyA)->create();
        Subscription::factory()->trialing()->for($companyA)->create();

        $this->actingAs($spvA)
            ->getJson('/api/v1/company?company_id='.$companyB->getKey())
            ->assertOk()
            ->assertJsonPath('data.id', $companyA->getKey())
            ->assertJsonPath('data.name', 'Company A');

        $this->assertFalse(Gate::forUser($spvA)->allows('view', $companyB));
        $this->assertFalse(Gate::forUser($spvA)->allows('update', $companyB));
    }

    public function test_spv_can_only_update_own_company_and_client_cannot_override_company_id(): void
    {
        $companyA = Company::factory()->create(['activity_label' => 'SA']);
        $companyB = Company::factory()->create(['activity_label' => 'Other']);
        $spvA = User::factory()->spv()->for($companyA)->create();
        Subscription::factory()->trialing()->for($companyA)->create();

        $this->actingAs($spvA)->patchJson('/api/v1/company', [
            'activity_label' => 'Deal',
        ])->assertOk()
            ->assertJsonPath('data.id', $companyA->getKey())
            ->assertJsonPath('data.activity_label', 'Deal');

        $this->assertSame('Deal', $companyA->refresh()->activity_label);
        $this->assertSame('Other', $companyB->refresh()->activity_label);

        $this->actingAs($spvA)->patchJson('/api/v1/company', [
            'company_id' => $companyB->getKey(),
            'activity_label' => 'Hijacked',
        ])->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors('company_id');

        $this->assertSame('Deal', $companyA->refresh()->activity_label);
        $this->assertSame('Other', $companyB->refresh()->activity_label);
    }

    public function test_sales_inactive_user_and_inactive_company_are_denied_protected_tenant_access(): void
    {
        $company = Company::factory()->create();
        $sales = User::factory()->sales()->for($company)->create();

        $this->actingAs($sales)->getJson('/api/v1/company')
            ->assertForbidden()
            ->assertJsonPath('code', 'FORBIDDEN');

        $inactiveUser = User::factory()->spv()->inactive()->for($company)->create();

        $this->actingAs($inactiveUser)->getJson('/api/v1/company')
            ->assertForbidden()
            ->assertJsonPath('code', 'ACCOUNT_INACTIVE');

        $inactiveCompany = Company::factory()->inactive()->create();
        $inactiveCompanySpv = User::factory()->spv()->for($inactiveCompany)->create();

        $this->actingAs($inactiveCompanySpv)->getJson('/api/v1/company')
            ->assertForbidden()
            ->assertJsonPath('code', 'COMPANY_INACTIVE');
    }

    public function test_super_admin_without_company_can_use_account_endpoint_but_not_tenant_routes(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.user.role', UserRole::SUPER_ADMIN->value)
            ->assertJsonPath('data.company', null);

        $this->actingAs($admin)->getJson('/api/v1/company')
            ->assertForbidden()
            ->assertJsonPath('code', 'TENANT_REQUIRED');
    }
}
