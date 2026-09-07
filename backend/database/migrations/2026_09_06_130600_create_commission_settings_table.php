<?php

use App\Enums\ProgressiveOverflowBehavior;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->boolean('multiplier_enabled')->default(false);
            $table->boolean('progressive_enabled')->default(false);
            $table->string('progressive_overflow_behavior', 24)
                ->default(ProgressiveOverflowBehavior::ZERO->value);
            $table->timestampTz('effective_from');
            $table->timestampTz('effective_until')->nullable();
            $table->boolean('is_active')->default(true);
            // Portable partial-unique guard for the one active setting per company.
            $table->string('active_slot', 16)->nullable()->default('active');
            $table->timestampsTz();

            $table->unique(['company_id', 'version'], 'commission_settings_version_unique');
            $table->unique(['company_id', 'active_slot'], 'commission_settings_one_active_unique');
            $table->index(['company_id', 'effective_from', 'effective_until'], 'commission_settings_effective_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_settings');
    }
};
