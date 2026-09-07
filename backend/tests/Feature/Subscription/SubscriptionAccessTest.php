<?php

namespace Tests\Feature\Subscription;

use App\Enums\SubscriptionStatus;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_trialing_and_active_subscriptions_allow_business_mutations(): void
    {
        $trialCompany = Company::factory()->create();
        $trialSpv = User::factory()->spv()->for($trialCompany)->create();
        Subscription::factory()->trialing()->for($trialCompany)->create();

        $this->actingAs($trialSpv)->patchJson('/api/v1/company', [
            'activity_label' => 'Closing',
        ])->assertOk();

        $activeCompany = Company::factory()->create();
        $activeSpv = User::factory()->spv()->for($activeCompany)->create();
        Subscription::factory()->active()->for($activeCompany)->create();

        $this->actingAs($activeSpv)->patchJson('/api/v1/company', [
            'activity_label' => 'Aktivitas',
        ])->assertOk();
    }

    public function test_expired_cancelled_past_due_and_stale_trials_are_read_only(): void
    {
        $states = [
            'expired' => fn ($factory) => $factory->expired(),
            'cancelled' => fn ($factory) => $factory->cancelled(),
            'past_due' => fn ($factory) => $factory->pastDue(),
            'stale_trial' => fn ($factory) => $factory->trialing()->state([
                'trial_ends_at' => now()->subMinute(),
            ]),
        ];

        foreach ($states as $label => $state) {
            $company = Company::factory()->create();
            $spv = User::factory()->spv()->for($company)->create();
            $state(Subscription::factory())->for($company)->create();

            $this->actingAs($spv)->patchJson('/api/v1/company', [
                'activity_label' => 'Blocked '.$label,
            ])->assertForbidden()
                ->assertJsonPath('code', 'SUBSCRIPTION_READ_ONLY');

            $this->actingAs($spv)->getJson('/api/v1/subscription')
                ->assertOk();
        }
    }

    public function test_subscription_endpoint_reports_effective_mutation_access(): void
    {
        $company = Company::factory()->create();
        $spv = User::factory()->spv()->for($company)->create();
        $subscription = Subscription::factory()->trialing()->for($company)->create();

        $this->actingAs($spv)->getJson('/api/v1/subscription')
            ->assertOk()
            ->assertJsonPath('data.id', $subscription->getKey())
            ->assertJsonPath('data.status', SubscriptionStatus::TRIALING->value)
            ->assertJsonPath('data.can_mutate', true);
    }

    public function test_expired_subscription_can_still_read_organization_data_but_cannot_change_it(): void
    {
        $company = Company::factory()->create();
        $spv = User::factory()->spv()->for($company)->create();
        $team = Team::factory()->for($company)->create(['supervisor_id' => $spv->id]);
        User::factory()->sales()->for($company)->create();
        Subscription::factory()->expired()->for($company)->create();

        $this->actingAs($spv)->getJson('/api/v1/teams')
            ->assertOk()
            ->assertJsonPath('data.0.id', $team->id);
        $this->actingAs($spv)->getJson('/api/v1/sales')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($spv)->postJson('/api/v1/teams', ['name' => 'Blocked'])
            ->assertForbidden()
            ->assertJsonPath('code', 'SUBSCRIPTION_READ_ONLY');
    }

    public function test_checkout_placeholder_never_creates_payment_or_activates_subscription(): void
    {
        $company = Company::factory()->create();
        $spv = User::factory()->spv()->for($company)->create();
        $subscription = Subscription::factory()->expired()->for($company)->create();

        $this->actingAs($spv)->postJson('/api/v1/subscription/checkout', [
            'plan_code' => 'starter',
        ])->assertStatus(202)
            ->assertJsonPath('data.mode', 'manual')
            ->assertJsonPath('data.status', 'action_required')
            ->assertJsonPath('data.provider', null)
            ->assertJsonPath('data.checkout_url', null);

        $this->assertDatabaseCount('payments', 0);
        $this->assertSame(SubscriptionStatus::EXPIRED, $subscription->refresh()->status);
        $this->assertSame(1, AuditLog::query()
            ->where('company_id', $company->getKey())
            ->where('event', 'subscription.checkout_requested')
            ->count());
        $this->assertSame(0, Payment::query()->count());
    }

    public function test_sales_cannot_view_or_request_subscription_checkout(): void
    {
        $company = Company::factory()->create();
        $sales = User::factory()->sales()->for($company)->create();
        Subscription::factory()->trialing()->for($company)->create();

        $this->actingAs($sales)->getJson('/api/v1/subscription')
            ->assertForbidden()
            ->assertJsonPath('code', 'FORBIDDEN');

        $this->actingAs($sales)->postJson('/api/v1/subscription/checkout', [
            'plan_code' => 'starter',
        ])->assertForbidden()
            ->assertJsonPath('code', 'FORBIDDEN');
    }
}
