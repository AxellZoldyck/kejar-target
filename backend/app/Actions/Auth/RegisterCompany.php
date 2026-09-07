<?php

namespace App\Actions\Auth;

use App\Enums\CompanyStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Subscription;
use App\Models\Team;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegisterCompany
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array{
     *     company_name: string,
     *     name: string,
     *     email: string,
     *     password: string,
     *     timezone?: string|null,
     *     team_name?: string|null
     * }  $attributes
     * @return array{company: Company, user: User, team: Team, subscription: Subscription}
     */
    public function execute(array $attributes): array
    {
        return DB::transaction(function () use ($attributes): array {
            $now = now();

            $company = Company::query()->create([
                'name' => $attributes['company_name'],
                'slug' => $this->availableSlug($attributes['company_name']),
                'activity_label' => 'SA',
                'timezone' => $attributes['timezone'] ?? 'Asia/Jakarta',
                'status' => CompanyStatus::ACTIVE,
            ]);

            $user = User::query()->create([
                'company_id' => $company->getKey(),
                'name' => $attributes['name'],
                'email' => Str::lower($attributes['email']),
                'password' => Hash::make($attributes['password']),
                'role' => UserRole::SPV,
                'is_active' => true,
            ]);

            $team = Team::query()->create([
                'company_id' => $company->getKey(),
                'name' => $attributes['team_name'] ?? 'Tim Utama',
                'supervisor_id' => $user->getKey(),
                'is_active' => true,
            ]);

            $subscription = Subscription::query()->create([
                'company_id' => $company->getKey(),
                'plan_code' => 'starter',
                'status' => SubscriptionStatus::TRIALING,
                'starts_at' => $now,
                'trial_ends_at' => $now->copy()->addDays(3),
            ]);

            $this->auditLogger->record(
                event: 'company.registered',
                actor: $user,
                auditable: $company,
                after: [
                    'name' => $company->name,
                    'slug' => $company->slug,
                    'plan_code' => $subscription->plan_code,
                    'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
                ],
            );

            return compact('company', 'user', 'team', 'subscription');
        });
    }

    private function availableSlug(string $companyName): string
    {
        $base = Str::slug($companyName) ?: 'company';

        if (! Company::query()->where('slug', $base)->exists()) {
            return $base;
        }

        do {
            $slug = $base.'-'.Str::lower(Str::random(6));
        } while (Company::query()->where('slug', $slug)->exists());

        return $slug;
    }
}
