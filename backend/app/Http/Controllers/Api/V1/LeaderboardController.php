<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Company;
use App\Services\Reporting\LeaderboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaderboardController extends ApiController
{
    public function __invoke(Request $request, LeaderboardService $service): JsonResponse
    {
        $request->validate([
            'type' => ['sometimes', 'in:weekly,monthly'],
            'scope' => ['sometimes', 'in:company,team'],
            'team_id' => ['nullable', 'uuid'],
            'period' => ['nullable', 'string', 'max:10'],
        ]);
        $company = Company::query()->findOrFail($request->user()->company_id);
        $result = $service->get(
            $company,
            $request->user(),
            (string) $request->input('type', 'monthly'),
            $request->input('period'),
            (string) $request->input('scope', 'company'),
            $request->input('team_id'),
        );

        return $this->data($result['items'], meta: ['period' => $result['period']]);
    }
}
