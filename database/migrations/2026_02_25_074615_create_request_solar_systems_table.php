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
        Schema::create('request_solar_systems', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('company_id')->nullable()->constrained('solar_companies')->nullOnDelete();
            $table->decimal('requested_capacity_kw', 8, 2)->default(0)->nullable();
            $table->decimal('dayly_consumption_kwh', 8, 2)->default(0)->nullable();
            $table->decimal('nightly_consumption_kwh', 8, 2)->default(0)->nullable();
            $table->enum('system_type', ['on_grid', 'off_grid', 'hybrid'])->default('off_grid');
            $table->string('invertar_type')->nullable();
            $table->string('inverter_brand')->nullable();
            $table->string('battery_type')->nullable();
            $table->string('battery_brand')->nullable();
            $table->string('solar_panel_type')->nullable();
            $table->string('solar_panel_brand')->nullable();
            $table->decimal('inverter_capacity_kw', 8, 2)->default(0)->nullable();
            $table->decimal('solar_panel_capacity_kw', 8, 2)->default(0)->nullable();
            $table->integer('solar_panel_number')->default(0)->nullable();
            $table->decimal('battery_capacity_kwh', 8, 2)->default(0)->nullable();
            $table->integer('battery_number')->default(0)->nullable();
            $table->enum('inverter_voltage_v', ['12V', '24V', '48V'])->default('12V');
            $table->enum('battery_voltage_v', ['12V', '24V', '48V'])->default('12V');
            $table->enum('expected_budget', ['low', 'medium', 'high'])->default('medium');
            $table->enum('metal_base_type', ['installation', 'blacksmith_workshop'])->default('installation');
            $table->decimal('front_base_height_m', 8, 2)->default(0)->nullable();
            $table->decimal('back_base_height_m', 8, 2)->default(0)->nullable();
            $table->string('surface_image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_solar_systems');
    }
};
