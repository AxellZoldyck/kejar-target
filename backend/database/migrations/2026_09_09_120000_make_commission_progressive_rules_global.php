<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $legacyRules = DB::table('commission_progressive_rules')
            ->orderBy('commission_setting_id')
            ->orderBy('sequence_number')
            ->get();

        $ambiguousSetting = $legacyRules
            ->groupBy('commission_setting_id')
            ->first(fn ($rules) => $rules->pluck('product_id')->unique()->count() > 1);

        if ($ambiguousSetting) {
            throw new RuntimeException(
                'Aturan progresif lama memakai lebih dari satu produk dalam satu versi. '
                .'Tentukan rentang SA global secara eksplisit sebelum menjalankan migrasi.'
            );
        }

        Schema::create('commission_progressive_rules_global', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('commission_setting_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('min_sa');
            $table->unsignedInteger('max_sa')->nullable();
            $table->unsignedBigInteger('incentive_amount');
            $table->timestampsTz();

            $table->unique(
                ['commission_setting_id', 'min_sa'],
                'commission_progressive_range_start_unique'
            );
            $table->index(
                ['company_id', 'commission_setting_id'],
                'commission_progressive_company_setting_idx'
            );
        });

        foreach ($legacyRules as $rule) {
            DB::table('commission_progressive_rules_global')->insert([
                'id' => $rule->id,
                'company_id' => $rule->company_id,
                'commission_setting_id' => $rule->commission_setting_id,
                'min_sa' => $rule->sequence_number,
                'max_sa' => $rule->sequence_number,
                'incentive_amount' => $rule->incentive_amount,
                'created_at' => $rule->created_at,
                'updated_at' => $rule->updated_at,
            ]);
        }

        Schema::drop('commission_progressive_rules');
        Schema::rename('commission_progressive_rules_global', 'commission_progressive_rules');
    }

    public function down(): void
    {
        if (DB::table('commission_progressive_rules')->exists()) {
            throw new RuntimeException(
                'Migrasi aturan progresif global tidak dapat dibatalkan setelah aturan rentang tersimpan.'
            );
        }

        Schema::create('commission_progressive_rules_legacy', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('commission_setting_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('sequence_number');
            $table->unsignedBigInteger('incentive_amount');
            $table->timestampsTz();

            $table->unique(
                ['commission_setting_id', 'product_id', 'sequence_number'],
                'commission_progressive_sequence_unique'
            );
            $table->index(['company_id', 'product_id'], 'commission_progressive_product_idx');
        });

        Schema::drop('commission_progressive_rules');
        Schema::rename('commission_progressive_rules_legacy', 'commission_progressive_rules');
    }
};
