<?php

namespace App\Http\Controllers\Api\V1\Commission;

use App\Enums\UserRole;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Commission\CommissionResource;
use App\Models\Commission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommissionController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'period' => ['sometimes', 'date_format:Y-m'],
            'sales_id' => ['sometimes', 'uuid'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ]);
        $commissions = $this->visibleQuery($request)
            ->when($request->filled('period'), fn ($query) => $query->where('period', (string) $request->input('period')))
            ->when($request->filled('sales_id') && $request->user()->role === UserRole::SPV,
                fn ($query) => $query->where('sales_id', (string) $request->input('sales_id')))
            ->with(['sales:id,name', 'team:id,name'])
            ->latest('period')
            ->latest('calculated_at')
            ->paginate(min(100, max(1, $request->integer('per_page', 20))));

        return $this->paginated(
            $commissions,
            CommissionResource::collection($commissions->getCollection())->resolve(),
        );
    }

    public function show(Request $request, string $commission): JsonResponse
    {
        $model = $this->visibleQuery($request)
            ->with(['sales:id,name', 'team:id,name'])
            ->findOrFail($commission);

        return $this->data((new CommissionResource($model))->resolve());
    }

    private function visibleQuery(Request $request): Builder
    {
        return Commission::query()
            ->where('company_id', $request->user()->company_id)
            ->when($request->user()->role === UserRole::SALES,
                fn ($query) => $query->where('sales_id', $request->user()->id));
    }
}
