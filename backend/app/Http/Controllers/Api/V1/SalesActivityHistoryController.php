<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Resources\SalesActivityStatusHistoryResource;
use App\Models\SalesActivity;
use App\Models\SalesActivityStatusHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesActivityHistoryController extends ApiController
{
    public function __invoke(Request $request, string $activity): JsonResponse
    {
        $user = $request->user();
        $visible = SalesActivity::query()
            ->where('company_id', $user->company_id)
            ->when($user->role === UserRole::SALES, fn ($query) => $query->where('sales_id', $user->id))
            ->when($user->role === UserRole::SPV, fn ($query) => $query->whereHas(
                'team',
                fn ($team) => $team->where('supervisor_id', $user->id),
            ))
            ->findOrFail($activity);

        $history = SalesActivityStatusHistory::query()
            ->where('company_id', $user->company_id)
            ->where('sales_activity_id', $visible->id)
            ->with('actor:id,name')
            ->oldest('created_at')
            ->get();

        return $this->data(SalesActivityStatusHistoryResource::collection($history)->resolve());
    }
}
