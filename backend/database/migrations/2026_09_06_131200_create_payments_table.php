<?php

use App\Enums\PaymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('subscription_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('status', 32)->default(PaymentStatus::PENDING->value);
            $table->string('provider_reference')->nullable()->unique();
            $table->timestampTz('paid_at')->nullable();
            $table->timestampsTz();

            $table->index(['company_id', 'status', 'created_at'], 'payments_company_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
