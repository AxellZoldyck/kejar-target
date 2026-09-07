<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('team_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('sales_id')->constrained('users')->restrictOnDelete();
            $table->string('period', 7);
            $table->unsignedBigInteger('product_fee_amount')->default(0);
            $table->decimal('multiplier_value', 9, 4)->default(1);
            $table->unsignedBigInteger('progressive_incentive_amount')->default(0);
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->jsonb('formula_snapshot');
            $table->unsignedInteger('calculation_version');
            $table->timestampTz('calculated_at');
            $table->timestampTz('locked_at')->nullable();
            $table->timestampsTz();

            $table->unique(
                ['company_id', 'sales_id', 'period', 'calculation_version'],
                'commissions_calculation_unique'
            );
            $table->index(['company_id', 'team_id', 'period'], 'commissions_team_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
