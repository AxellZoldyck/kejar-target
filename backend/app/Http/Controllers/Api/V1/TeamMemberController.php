<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Organization\AssignTeamMemberAction;
use App\Enums\UserRole;
use App\Http\Requests\Organization\AssignTeamMemberRequest;
use App\Http\Resources\SalesUserResource;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class TeamMemberController extends ApiController
{
    public function store(
        AssignTeamMemberRequest $request,
        string $team,
        AssignTeamMemberAction $action,
    ): JsonResponse {
        $teamModel = Team::query()
            ->where('company_id', $request->user()->company_id)
            ->where('is_active', true)
            ->findOrFail($team);
        $sales = User::query()
            ->where('company_id', $request->user()->company_id)
            ->where('role', UserRole::SALES->value)
            ->findOrFail($request->validated('sales_id'));

        $action->execute($teamModel, $sales, $request->user());

        return $this->data((new SalesUserResource($sales->refresh()->load('activeTeamMembership.team')))->resolve(), 201);
    }
}
