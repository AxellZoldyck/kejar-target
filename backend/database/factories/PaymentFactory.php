<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => (string) Str::uuid(),
            'subscription_id' => Subscription::factory(),
            'amount' => 149_000,
            'status' => PaymentStatus::PENDING,
            'provider_reference' => null,
            'paid_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Payment $payment): void {
            $subscription = Subscription::query()->find($payment->subscription_id);

            if ($subscription) {
                $payment->company_id = $subscription->company_id;
            }
        });
    }

    public function paid(): static
    {
        return $this->state(fn (): array => [
            'status' => PaymentStatus::PAID,
            'provider_reference' => fake()->unique()->uuid(),
            'paid_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => ['status' => PaymentStatus::FAILED]);
    }

    public function refunded(): static
    {
        return $this->state(fn (): array => ['status' => PaymentStatus::REFUNDED]);
    }
}
