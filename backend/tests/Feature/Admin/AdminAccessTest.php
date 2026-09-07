<?php

namespace Tests\Feature\Admin;

use App\Models\Company;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_monitor_companies_subscriptions_and_payments(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $company = Company::factory()->create();
        User::factory()->spv()->create(['company_id' => $company->id]);
        $subscription = Subscription::factory()->trialing()->create(['company_id' => $company->id]);
        Payment::factory()->create([
            'company_id' => $company->id,
            'subscription_id' => $subscription->id,
        ]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('data.companies_total', 1)
            ->assertJsonPath('data.users_total', 2)
            ->assertJsonPath('data.subscriptions.trialing', 1)
            ->assertJsonPath('data.payments_total', 1);
        $this->getJson('/api/v1/admin/companies')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/admin/subscriptions')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/admin/payments')->assertOk()->assertJsonCount(1, 'data');
        $this->patchJson('/api/v1/admin/companies/'.$company->id, ['status' => 'inactive'])
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');
    }

    public function test_business_user_cannot_open_admin_routes(): void
    {
        $company = Company::factory()->create();
        $spv = User::factory()->spv()->create(['company_id' => $company->id]);
        Sanctum::actingAs($spv);

        $this->getJson('/api/v1/admin/dashboard')
            ->assertForbidden()
            ->assertJsonPath('code', 'FORBIDDEN');
    }
}
