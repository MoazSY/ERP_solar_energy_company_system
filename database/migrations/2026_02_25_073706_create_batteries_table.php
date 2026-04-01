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
        Schema::create('batteries', function (Blueprint $table) {
            $table->id();
            $table->foreignId("product_id")->constrained("products")->onDelete("cascade");
            $table->enum("battery_type", ["lithium_ion", "lead_acid", "nickel_cadmium"])->default("lithium_ion");
            $table->decimal("capacity_kwh", 8, 2)->default(0);
            $table->enum("voltage_v", ["12V", "24V", "48V"])->default("12V");
            $table->integer("cycle_life")->default(0);
            $table->decimal("warranty_years", 2, 1)->default(0);
            $table->decimal("weight_kg", 8, 2)->default(0);
            $table->enum("Amperage_Ah", ["100Ah", "200Ah", "300Ah"])->default("100Ah");
            $table->enum("celles_type",["new","renewed"])->default("new");
            $table->string("celles_name")->nullable();



            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batteries');
    }
};
