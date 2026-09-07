<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('team_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('user_id')->constrained()->restrictOnDelete();
            $table->timestampTz('joined_at')->useCurrent();
            $table->timestampTz('left_at')->nullable();
            // Portable partial-unique guard: active rows use "active", historical rows use null.
            $table->string('active_slot', 16)->nullable()->default('active');
            $table->timestampsTz();

            $table->unique(['company_id', 'user_id', 'active_slot'], 'team_members_one_active_unique');
            $table->index(['company_id', 'team_id', 'left_at'], 'team_members_team_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
