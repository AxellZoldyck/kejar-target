<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Organization\StoreTeamRequest;
use App\Http\Requests\Organization\UpdateTeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ]);
        $teams = Team::query()
            ->where('company_id', $request->user()->company_id)
            ->with('supervisor:id,name,email')
            ->withCount('activeMembers')
            ->orderBy('name')
            ->paginate(min(100, max(1, $request->integer('per_page', 20))));

        return $this->paginated($teams, TeamResource::collection($teams->getCollection())->resolve());
    }

    public function store(StoreTeamRequest $request): JsonResponse
    {
        $team = Team::query()->create([
            ...$request->safe()->only(['name', 'is_active']),
            'company_id' => $request->user()->company_id,
            'supervisor_id' => $request->validated('supervisor_id', $request->user()->id),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->data((new TeamResource($team->load('supervisor')->loadCount('activeMembers')))->resolve(), 201);
    }

    public function show(Request $request, string $team): JsonResponse
    {
        $model = $this->findTeam($request, $team);

        return $this->data((new TeamResource($model->load('supervisor')->loadCount('activeMembers')))->resolve());
    }

    public function update(UpdateTeamRequest $request, string $team): JsonResponse
    {
        $model = $this->findTeam($request, $team);
        $model->update($request->safe()->only(['name', 'supervisor_id', 'is_active']));

        return $this->data((new TeamResource($model->refresh()->load('supervisor')->loadCount('activeMembers')))->resolve());
    }

    private function findTeam(Request $request, string $id): Team
    {
        return Team::query()
            ->where('company_id', $request->user()->company_id)
            ->findOrFail($id);
    }
}
