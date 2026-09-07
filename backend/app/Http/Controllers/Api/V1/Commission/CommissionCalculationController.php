<?php

namespace App\Http\Controllers\Api\V1\Commission;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Commission\CalculateCommissionRequest;
use App\Http\Resources\Commission\CommissionResource;
use App\Models\Company;
use App\Models\User;
use App\Services\CommissionService;
use Illuminate\Http\JsonResponse;

class CommissionCalculationController extends ApiController
{
    public function __invoke(CalculateCommissionRequest $request, CommissionService $service): JsonResponse
    {
        $company = Company::query()->findOrFail($request->user()->company_id);
        $sales = User::query()
            ->where('company_id', $company->id)
            ->findOrFail($request->validated('sales_id'));
        $commission = $service->calculate($company, $sales, $request->validated('period'), $request->user());

        return $this->data((new CommissionResource($commission))->resolve());
    }
}
