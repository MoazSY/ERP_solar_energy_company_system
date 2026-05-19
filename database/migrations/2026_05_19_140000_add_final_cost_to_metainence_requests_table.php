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
        Schema::table('metainence_requests', function (Blueprint $table) {
            $table->decimal('final_cost', 8, 2)->default(0)->after('estimated_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('metainence_requests', function (Blueprint $table) {
            if (Schema::hasColumn('metainence_requests', 'final_cost')) {
                $table->dropColumn('final_cost');
            }
        });
    }
};
