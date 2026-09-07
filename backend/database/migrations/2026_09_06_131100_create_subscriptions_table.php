<?php

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->restrictOnDelete();
            $table->string('plan_code', 64)->default('starter');
            $table->string('status', 32)->default(SubscriptionStatus::TRIALING->value);
            $table->timestampTz('trial_ends_at')->nullable();
            $table->timestampTz('starts_at')->nullable();
            $table->timestampTz('ends_at')->nullable();
            $table->string('provider', 64)->nullable();
            $table->string('provider_subscription_id')->nullable();
            // trialing, active, and past_due rows use "running"; terminal rows use null.
            $table->string('running_slot', 16)->nullable()->default('running');
            $table->timestampsTz();

            $table->unique(['company_id', 'running_slot'], 'subscriptions_one_running_unique');
            $table->unique(['provider', 'provider_subscription_id'], 'subscriptions_provider_reference_unique');
            $table->index(['company_id', 'status', 'ends_at'], 'subscriptions_company_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
