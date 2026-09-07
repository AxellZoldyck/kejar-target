<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Admin\UpdateCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanyController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search' => ['sometimes', 'string', 'max:160'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ]);
        $companies = Company::query()
            ->withCount('users')
            ->with('currentSubscription')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], (string) $request->input('search')).'%';
                $query->where('name', 'like', $term);
            })
            ->latest()
            ->paginate(min(100, max(1, $request->integer('per_page', 20))));

        return $this->paginated($companies, $companies->getCollection()->map(fn (Company $company) => [
            ...(new CompanyResource($company))->resolve(),
            'users_count' => (int) $company->users_count,
            'subscription' => $company->currentSubscription ? [
                'id' => $company->currentSubscription->id,
                'plan_code' => $company->currentSubscription->plan_code,
                'status' => $company->currentSubscription->status->value,
                'trial_ends_at' => $company->currentSubscription->trial_ends_at?->toISOString(),
                'ends_at' => $company->currentSubscription->ends_at?->toISOString(),
            ] : null,
        ])->all());
    }

    public function show(string $company): JsonResponse
    {
        $model = Company::query()
            ->withCount(['users', 'teams', 'products', 'salesActivities'])
            ->with(['currentSubscription', 'users:id,company_id,name,email,role,is_active'])
            ->findOrFail($company);

        return $this->data([
            ...(new CompanyResource($model))->resolve(),
            'counts' => [
                'users' => (int) $model->users_count,
                'teams' => (int) $model->teams_count,
                'products' => (int) $model->products_count,
                'sales_activities' => (int) $model->sales_activities_count,
            ],
            'subscription' => $model->currentSubscription?->toArray(),
            'users' => $model->users->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'is_active' => $user->is_active,
            ])->values(),
        ]);
    }

    public function update(
        UpdateCompanyRequest $request,
        string $company,
        AuditLogger $audit,
    ): JsonResponse {
        $model = DB::transaction(function () use ($request, $company, $audit): Company {
            $model = Company::query()->lockForUpdate()->findOrFail($company);
            $before = $model->only(['name', 'status']);
            $model->update($request->validated());
            $audit->record(
                event: 'admin.company.updated',
                actor: $request->user(),
                auditable: $model,
                before: $before,
                after: $model->only(['name', 'status']),
            );

            return $model;
        });

        return $this->data((new CompanyResource($model->refresh()))->resolve());
    }
}
