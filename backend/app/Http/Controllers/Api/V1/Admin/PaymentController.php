<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['sometimes', 'in:pending,paid,failed,refunded,cancelled'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ]);
        $payments = Payment::query()
            ->with(['company:id,name,slug', 'subscription:id,plan_code,status'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', (string) $request->input('status')))
            ->latest()
            ->paginate(min(100, max(1, $request->integer('per_page', 20))));

        return $this->paginated($payments, $payments->getCollection()->map(fn ($payment) => [
            'id' => $payment->id,
            'company' => [
                'id' => $payment->company->id,
                'name' => $payment->company->name,
            ],
            'subscription' => [
                'id' => $payment->subscription->id,
                'plan_code' => $payment->subscription->plan_code,
            ],
            'amount' => (int) $payment->amount,
            'status' => $payment->status->value,
            'provider_reference' => $payment->provider_reference,
            'paid_at' => $payment->paid_at?->toISOString(),
            'created_at' => $payment->created_at?->toISOString(),
        ])->all());
    }
}
