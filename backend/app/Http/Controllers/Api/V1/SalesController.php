<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Organization\AssignTeamMemberAction;
use App\Actions\Organization\CreateSalesAction;
use App\Enums\UserRole;
use App\Http\Requests\Organization\StoreSalesRequest;
use App\Http\Requests\Organization\UpdateSalesRequest;
use App\Http\Resources\SalesUserResource;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SalesController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search' => ['sometimes', 'string', 'max:120'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ]);
        $sales = User::query()
            ->where('company_id', $request->user()->company_id)
            ->where('role', UserRole::SALES->value)
            ->when($request->filled('search'), fn ($query) => $query->where(function ($nested) use ($request) {
                $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $request->string('search')).'%';
                $nested->where('name', 'like', $term)->orWhere('email', 'like', $term);
            }))
            ->with('activeTeamMembership.team:id,name')
            ->orderBy('name')
            ->paginate(min(100, max(1, $request->integer('per_page', 20))));

        return $this->paginated($sales, SalesUserResource::collection($sales->getCollection())->resolve());
    }

    public function store(StoreSalesRequest $request, CreateSalesAction $action): JsonResponse
    {
        $sales = $action->execute($request->user(), $request->validated());

        return $this->data((new SalesUserResource($sales->load('activeTeamMembership.team')))->resolve(), 201);
    }

    public function show(Request $request, string $sales): JsonResponse
    {
        return $this->data((new SalesUserResource(
            $this->findSales($request, $sales)->load('activeTeamMembership.team'),
        ))->resolve());
    }

    public function update(
        UpdateSalesRequest $request,
        string $sales,
        AssignTeamMemberAction $assignMember,
    ): JsonResponse {
        $model = $this->findSales($request, $sales);

        DB::transaction(function () use ($request, $model, $assignMember): void {
            $model->update(Arr::whereNotNull($request->safe()->only([
                'name', 'email', 'password', 'is_active',
            ])));

            if ($request->exists('team_id')) {
                $teamId = $request->validated('team_id');
                if ($teamId) {
                    $team = Team::query()
                        ->where('company_id', $request->user()->company_id)
                        ->findOrFail($teamId);
                    $assignMember->execute($team, $model, $request->user());
                } else {
                    $assignMember->unassign($model, $request->user());
                }
            }
        });

        return $this->data((new SalesUserResource($model->refresh()->load('activeTeamMembership.team')))->resolve());
    }

    private function findSales(Request $request, string $id): User
    {
        return User::query()
            ->where('company_id', $request->user()->company_id)
            ->where('role', UserRole::SALES->value)
            ->findOrFail($id);
    }
}
