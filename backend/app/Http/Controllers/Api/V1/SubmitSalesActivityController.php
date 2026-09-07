<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\SalesActivity\SubmitSalesActivityAction;
use App\Http\Resources\SalesActivityResource;
use App\Models\SalesActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubmitSalesActivityController extends ApiController
{
    public function __invoke(Request $request, string $activity, SubmitSalesActivityAction $action): JsonResponse
    {
        $model = SalesActivity::query()
            ->where('company_id', $request->user()->company_id)
            ->where('sales_id', $request->user()->id)
            ->findOrFail($activity);

        $model = $action->execute($request->user(), $model);

        return $this->data((new SalesActivityResource($model->load(['team', 'sales', 'product'])))->resolve());
    }
}
