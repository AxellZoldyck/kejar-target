<?php

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Company;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends ApiController
{
    public function __invoke(): JsonResponse
    {
        $subscriptionCounts = [];
        foreach (SubscriptionStatus::cases() as $status) {
            $subscriptionCounts[$status->value] = Subscription::query()->where('status', $status->value)->count();
        }

        return $this->data([
            'companies_total' => Company::query()->count(),
            'users_total' => User::query()->count(),
            'subscriptions' => $subscriptionCounts,
            'payments_total' => Payment::query()->count(),
        ]);
    }
}
