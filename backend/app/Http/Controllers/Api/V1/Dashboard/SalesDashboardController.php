<?php

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Company;
use App\Services\Reporting\SalesDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesDashboardController extends ApiController
{
    public function __invoke(Request $request, SalesDashboardService $service): JsonResponse
    {
        $request->validate(['period' => ['nullable', 'date_format:Y-m']]);
        $company = Company::query()->findOrFail($request->user()->company_id);

        return $this->data($service->get($company, $request->user(), $request->input('period')));
    }
}
