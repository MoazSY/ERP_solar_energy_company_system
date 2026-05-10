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
        Schema::table('request_solar_systems', function (Blueprint $table) {
            $table->text('additional_details')->nullable()->after('back_base_height_m');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_solar_systems', function (Blueprint $table) {
            $table->dropColumn('additional_details');
        });
    }
};
