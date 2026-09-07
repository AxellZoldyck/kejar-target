<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\SalesActivity\ReviewSalesActivityAction;
use App\Http\Resources\SalesActivityResource;
use App\Models\SalesActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ValidateSalesActivityController extends ApiController
{
    public function __invoke(Request $request, string $activity, ReviewSalesActivityAction $action): JsonResponse
    {
        $model = SalesActivity::query()
            ->where('company_id', $request->user()->company_id)
            ->whereHas('team', fn ($query) => $query->where('supervisor_id', $request->user()->id))
            ->findOrFail($activity);

        $model = $action->validate($request->user(), $model);

        return $this->data((new SalesActivityResource($model->load(['team', 'sales', 'product'])))->resolve());
    }
}
