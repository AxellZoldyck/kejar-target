<?php

namespace Tests\Feature\Auth;

use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Subscription;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_company_spv_initial_team_and_three_day_trial_atomically(): void
    {
        $response = $this->withHeaders($this->spaHeaders())->postJson('/api/v1/auth/register', [
            'company_name' => 'PT Kejar Maju',
            'name' => 'Siti Supervisor',
            'email' => 'SPV@Example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'timezone' => 'Asia/Jakarta',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.user.email', 'spv@example.test')
            ->assertJsonPath('data.user.role', UserRole::SPV->value)
            ->assertJsonPath('data.company.name', 'PT Kejar Maju')
            ->assertJsonPath('data.subscription.plan_code', 'starter')
            ->assertJsonPath('data.subscription.status', SubscriptionStatus::TRIALING->value)
            ->assertJsonPath('data.subscription.can_mutate', true);

        $company = Company::query()->sole();
        $user = User::query()->sole();
        $team = Team::query()->sole();
        $subscription = Subscription::query()->sole();

        $this->assertSame($company->getKey(), $user->company_id);
        $this->assertSame(UserRole::SPV, $user->role);
        $this->assertSame($user->getKey(), $team->supervisor_id);
        $this->assertSame('Tim Utama', $team->name);
        $this->assertSame(SubscriptionStatus::TRIALING, $subscription->status);
        $this->assertTrue($subscription->trial_ends_at->betweenIncluded(
            now()->addDays(3)->subMinute(),
            now()->addDays(3)->addMinute(),
        ));
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertDatabaseHas('audit_logs', [
            'company_id' => $company->getKey(),
            'actor_id' => $user->getKey(),
            'event' => 'company.registered',
        ]);
    }

    public function test_registration_validation_is_structured_and_rolls_back_everything(): void
    {
        $this->withHeaders($this->spaHeaders())->postJson('/api/v1/auth/register', [
            'company_name' => '',
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
        ])->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['company_name', 'name', 'email', 'password']);

        $this->assertDatabaseCount('companies', 0);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('teams', 0);
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_spa_can_login_read_account_and_logout_without_an_api_token(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->spv()->for($company)->create([
            'email' => 'spv@example.test',
            'password' => Hash::make('password123'),
        ]);
        Subscription::factory()->trialing()->for($company)->create();

        $this->withHeaders($this->spaHeaders())->postJson('/api/v1/auth/login', [
            'email' => 'SPV@example.test',
            'password' => 'password123',
        ])->assertOk()
            ->assertJsonPath('data.user.id', $user->getKey())
            ->assertJsonMissingPath('data.token');

        $this->assertAuthenticatedAs($user, 'web');

        $this->withHeaders($this->spaHeaders())->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->getKey())
            ->assertJsonPath('data.company.id', $company->getKey())
            ->assertJsonPath('data.permissions.can_mutate', true);

        $this->withHeaders($this->spaHeaders())->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('data.message', 'Logout berhasil.');

        $this->assertGuest('web');
        $this->assertSame(1, AuditLog::query()->where('event', 'auth.login')->count());
        $this->assertSame(1, AuditLog::query()->where('event', 'auth.logout')->count());
    }

    public function test_invalid_credentials_and_inactive_accounts_are_rejected(): void
    {
        $company = Company::factory()->create();
        User::factory()->spv()->inactive()->for($company)->create([
            'email' => 'inactive@example.test',
            'password' => Hash::make('password123'),
        ]);

        $this->withHeaders($this->spaHeaders())->postJson('/api/v1/auth/login', [
            'email' => 'missing@example.test',
            'password' => 'password123',
        ])->assertUnprocessable()
            ->assertJsonPath('code', 'INVALID_CREDENTIALS');

        $this->withHeaders($this->spaHeaders())->postJson('/api/v1/auth/login', [
            'email' => 'inactive@example.test',
            'password' => 'password123',
        ])->assertForbidden()
            ->assertJsonPath('code', 'ACCOUNT_INACTIVE');

        $this->assertGuest('web');
    }

    public function test_account_endpoint_requires_authentication(): void
    {
        $this->withHeaders($this->spaHeaders())->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    /** @return array<string, string> */
    private function spaHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Origin' => 'http://localhost:3000',
            'Referer' => 'http://localhost:3000/',
        ];
    }
}
