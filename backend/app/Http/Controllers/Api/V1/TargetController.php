<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Organization\SaveTargetAction;
use App\Enums\SalesActivityStatus;
use App\Enums\UserRole;
use App\Http\Requests\Organization\StoreTargetRequest;
use App\Http\Requests\Organization\UpdateTargetRequest;
use App\Http\Resources\TargetResource;
use App\Models\SalesActivity;
use App\Models\Target;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TargetController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'sales_id' => ['sometimes', 'uuid'],
            'type' => ['sometimes', 'in:weekly,monthly'],
            'period' => ['sometimes', 'date_format:Y-m-d'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ]);
        $user = $request->user();
        $targets = Target::query()
            ->where('company_id', $user->company_id)
            ->when($user->role === UserRole::SALES, fn ($query) => $query->where('sales_id', $user->id))
            ->when($request->filled('sales_id') && $user->role === UserRole::SPV,
                fn ($query) => $query->where('sales_id', (string) $request->input('sales_id')))
            ->when($request->filled('type'), fn ($query) => $query->where('type', (string) $request->input('type')))
            ->when($request->filled('period'), fn ($query) => $query
                ->whereDate('period_start', '<=', (string) $request->input('period'))
                ->whereDate('period_end', '>=', (string) $request->input('period')))
            ->with('sales:id,name')
            ->orderByDesc('period_start')
            ->paginate(min(100, max(1, $request->integer('per_page', 20))));

        $targets->getCollection()->each(function (Target $target): void {
            $target->validated_count = SalesActivity::query()
                ->where('company_id', $target->company_id)
                ->where('sales_id', $target->sales_id)
                ->where('status', SalesActivityStatus::VALIDATED->value)
                ->whereBetween('activity_date', [$target->period_start, $target->period_end])
                ->count();
        });

        return $this->paginated($targets, TargetResource::collection($targets->getCollection())->resolve());
    }

    public function store(StoreTargetRequest $request, SaveTargetAction $action): JsonResponse
    {
        $target = $action->create($request->user(), $request->validated());

        return $this->data((new TargetResource($target->load('sales')))->resolve(), 201);
    }

    public function update(UpdateTargetRequest $request, string $target, SaveTargetAction $action): JsonResponse
    {
        $model = Target::query()
            ->where('company_id', $request->user()->company_id)
            ->findOrFail($target);
        $model = $action->update($request->user(), $model, $request->integer('target_value'));

        return $this->data((new TargetResource($model->load('sales')))->resolve());
    }
}
