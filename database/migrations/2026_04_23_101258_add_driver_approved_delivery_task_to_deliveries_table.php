<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
    Schema::table('deliveries', function (Blueprint $table) {
        $table->enum('driver_approved_delivery_task', ['pending', 'approve', 'reject'])
            ->default('pending')
            ->after('driver_id');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    Schema::table('deliveries', function (Blueprint $table) {
        $table->dropColumn('driver_approved_delivery_task');
    });
    }
};
