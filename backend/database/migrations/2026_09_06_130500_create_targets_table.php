<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('targets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('sales_id')->constrained('users')->restrictOnDelete();
            $table->string('type', 16);
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedInteger('target_value');
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['company_id', 'sales_id', 'type', 'period_start'], 'targets_period_unique');
            $table->index(['company_id', 'sales_id', 'period_start', 'period_end'], 'targets_sales_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('targets');
    }
};
