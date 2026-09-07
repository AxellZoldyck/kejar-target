<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('code', 64);
            $table->unsignedBigInteger('product_fee_amount')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['company_id', 'code'], 'products_company_code_unique');
            $table->index(['company_id', 'is_active'], 'products_company_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
