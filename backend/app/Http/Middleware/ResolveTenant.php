<?php

namespace App\Http\Middleware;

use App\Contracts\CurrentTenant;
use App\Enums\CompanyStatus;
use App\Exceptions\ApiException;
use BackedEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function __construct(private readonly CurrentTenant $tenant) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            throw new ApiException('Unauthenticated.', 'UNAUTHENTICATED', 401);
        }

        $company = $user->company;

        if ($company === null || $user->company_id === null) {
            throw new ApiException('Akun ini tidak terhubung ke perusahaan.', 'TENANT_REQUIRED', 403);
        }

        $status = $company->status;
        $statusValue = $status instanceof BackedEnum ? $status->value : $status;

        if ($statusValue !== CompanyStatus::ACTIVE->value) {
            throw new ApiException('Perusahaan ini tidak aktif.', 'COMPANY_INACTIVE', 403);
        }

        $this->tenant->setCompany($company);
        $request->attributes->set('tenant', $company);
        $request->attributes->set('tenant_id', $company->getKey());

        return $next($request);
    }
}
