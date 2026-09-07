<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Contracts\SubscriptionAccess;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\SubscriptionResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends ApiController
{
    public function show(Request $request, SubscriptionAccess $subscriptions): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->loadMissing('company');

        $subscription = $user->company === null
            ? null
            : ($subscriptions->currentFor($user->company)
                ?? $subscriptions->latestFor($user->company));
        $teams = match (true) {
            $user->isSpv() => $user->supervisedTeams()->where('is_active', true)->get(['id', 'name']),
            $user->isSales() => $user->teams()->where('teams.is_active', true)->wherePivotNull('left_at')->get(['teams.id', 'teams.name']),
            default => collect(),
        };
        $permissions = match (true) {
            $user->isSuperAdmin() => ['admin.view', 'companies.manage'],
            $user->isSpv() => ['teams.manage', 'sales.manage', 'products.manage', 'targets.manage', 'activities.review', 'commissions.manage'],
            default => ['activities.manage-own', 'leaderboard.view', 'commissions.view-own'],
        };

        return $this->data([
            'user' => new UserResource($user),
            'company' => $user->company === null ? null : new CompanyResource($user->company),
            'subscription' => $subscription === null ? null : new SubscriptionResource($subscription),
            'teams' => $teams->map(fn ($team) => ['id' => $team->id, 'name' => $team->name])->values(),
            'permissions' => [
                'can_mutate' => $subscriptions->allowsMutation($subscription),
                'abilities' => $permissions,
            ],
        ]);
    }
}
