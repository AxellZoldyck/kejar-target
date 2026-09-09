<?php

namespace App\Http\Controllers\Api\V1\Commission;

use App\Actions\Commission\ReviseCommissionSettingAction;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Commission\ReplaceMultiplierRulesRequest;
use App\Http\Resources\Commission\CommissionSettingResource;
use App\Models\CommissionSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommissionMultiplierRuleController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $setting = $this->current($request);

        return $this->data($setting
            ? (new CommissionSettingResource($setting))->resolve()['multiplier_rules']
            : []);
    }

    public function update(ReplaceMultiplierRulesRequest $request, ReviseCommissionSettingAction $action): JsonResponse
    {
        $setting = $action->execute($request->user(), multipliers: $request->validated('rules'));

        return $this->data((new CommissionSettingResource($setting))->resolve());
    }

    private function current(Request $request): ?CommissionSetting
    {
        return CommissionSetting::query()
            ->where('company_id', $request->user()->company_id)
            ->where('active_slot', 'active')
            ->with(['productFees.product', 'multiplierRules', 'progressiveRules'])
            ->first();
    }
}
