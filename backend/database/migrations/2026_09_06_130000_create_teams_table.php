<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->foreignUuid('supervisor_id')->constrained('users')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['company_id', 'name'], 'teams_company_name_unique');
            $table->index(['company_id', 'supervisor_id'], 'teams_company_supervisor_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
