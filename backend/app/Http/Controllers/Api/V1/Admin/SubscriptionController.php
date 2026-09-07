<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['sometimes', 'in:trialing,active,past_due,expired,cancelled'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ]);
        $subscriptions = Subscription::query()
            ->with('company:id,name,slug')
            ->when($request->filled('status'), fn ($query) => $query->where('status', (string) $request->input('status')))
            ->latest()
            ->paginate(min(100, max(1, $request->integer('per_page', 20))));

        return $this->paginated($subscriptions, $subscriptions->getCollection()->map(fn ($subscription) => [
            ...(new SubscriptionResource($subscription))->resolve(),
            'company' => [
                'id' => $subscription->company->id,
                'name' => $subscription->company->name,
                'slug' => $subscription->company->slug,
            ],
        ])->all());
    }
}
