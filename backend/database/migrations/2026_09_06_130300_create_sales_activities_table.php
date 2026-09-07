<?php

use App\Enums\SalesActivityStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('team_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('sales_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('product_id')->constrained()->restrictOnDelete();
            $table->date('activity_date');
            $table->string('customer_reference');
            $table->text('notes')->nullable();
            $table->string('evidence_path')->nullable();
            $table->string('status', 32)->default(SalesActivityStatus::DRAFT->value);
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampTz('validated_at')->nullable();
            $table->foreignUuid('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['company_id', 'status', 'activity_date'], 'sales_activities_company_status_date_idx');
            $table->index(['company_id', 'team_id', 'status'], 'sales_activities_validation_queue_idx');
            $table->index(['company_id', 'sales_id', 'activity_date'], 'sales_activities_sales_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_activities');
    }
};
