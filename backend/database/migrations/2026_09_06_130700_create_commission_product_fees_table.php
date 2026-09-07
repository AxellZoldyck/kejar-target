<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_product_fees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('commission_setting_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('fee_amount');
            $table->timestampsTz();

            $table->unique(['commission_setting_id', 'product_id'], 'commission_product_fees_rule_unique');
            $table->index(['company_id', 'product_id'], 'commission_product_fees_product_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_product_fees');
    }
};
