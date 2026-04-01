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
        Schema::create('solar_panals', function (Blueprint $table) {
            $table->id();
            $table->foreignId("product_id")->constrained("products")->onDelete("cascade");
            $table->enum("capacity_kw", ["250w", "300w", "350w", "400w","580w","620w"])->default("580w");
            $table->float("basbar_number")->default(16);
            $table->boolean("is_half_cell")->default(false);
            $table->boolean("is_bifacial")->default(false);
            $table->decimal("warranty_years", 2, 1)->default(0);
            $table->decimal("weight_kg", 8, 2)->default(0);
            $table->float("length_m")->default(0);
            $table->float("width_m")->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solar_panals');
    }
};
