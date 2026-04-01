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
        Schema::create('inverters', function (Blueprint $table) {
            $table->id();
            $table->foreignId("product_id")->constrained("products")->onDelete("cascade");
            $table->enum("grid_type", ["on_grid", "off_grid", "hybrid"])->default("off_grid");
            $table->enum("voltage_v", ["12V", "24V", "48V"])->default("12V");
            $table->float("grid_capacity_kw")->default(0);
            $table->float("solar_capacity_kw")->default(0);
            $table->boolean("inverter_open")->default(true);
            $table->float("voltage_open")->default(60);
            $table->decimal("weight_kg", 8, 2)->default(0);
            $table->decimal("warranty_years", 2, 1)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inverters');
    }
};
