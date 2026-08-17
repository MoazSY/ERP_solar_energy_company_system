<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        try {
            Schema::table('products', function (Blueprint $table) {
                $table->dropUnique('products_model_number_unique');
            });
        } catch (\Throwable $e) {
            // Index may already be dropped in new environments.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->unique('model_number', 'products_model_number_unique');
        });
    }
};
