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
        Schema::create('customer_electrical_device', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('electrical_device_id')->constrained('electrical_devices')->cascadeOnDelete();
            $table->foreignId('request_solar_system_id')->nullable()->constrained('request_solar_systems')->nullOnDelete();
            $table->string('capacity')->nullable();
            $table->string('unit')->nullable();
            $table->enum('usage_time', ['dayly', 'nightly'])->default('dayly');
            $table->text('notes')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_electrical_device_characteristics');
    }
};
