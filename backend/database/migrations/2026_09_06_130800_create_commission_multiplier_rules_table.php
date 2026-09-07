<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_multiplier_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('commission_setting_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('min_sa');
            $table->unsignedInteger('max_sa')->nullable();
            $table->decimal('multiplier_value', 9, 4)->default(1);
            $table->timestampsTz();

            $table->index(['commission_setting_id', 'min_sa', 'max_sa'], 'commission_multiplier_range_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_multiplier_rules');
    }
};
