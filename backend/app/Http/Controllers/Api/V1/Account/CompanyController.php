<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Contracts\CurrentTenant;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Company\UpdateCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CompanyController extends ApiController
{
    public function show(CurrentTenant $tenant): JsonResponse
    {
        /** @var Company $company */
        $company = $tenant->company();
        Gate::authorize('view', $company);

        return $this->data(new CompanyResource($company));
    }

    public function update(
        UpdateCompanyRequest $request,
        CurrentTenant $tenant,
        AuditLogger $auditLogger,
    ): JsonResponse {
        /** @var Company $company */
        $company = $tenant->company();
        Gate::authorize('update', $company);

        /** @var User $actor */
        $actor = $request->user();

        DB::transaction(function () use ($company, $request, $actor, $auditLogger): void {
            $before = $company->only(['name', 'activity_label', 'timezone']);

            $company->fill($request->validated());
            $company->save();

            $auditLogger->record(
                event: 'company.updated',
                actor: $actor,
                auditable: $company,
                before: $before,
                after: $company->only(['name', 'activity_label', 'timezone']),
            );
        });

        return $this->data(new CompanyResource($company->refresh()));
    }
}
