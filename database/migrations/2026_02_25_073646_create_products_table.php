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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->morphs("entity_type");
            $table->string("product_name");
            $table->enum("product_type", ["solar_panel", "inverter", "battery", "accessory"])->default("solar_panel");
            $table->string("product_brand")->nullable();
            $table->string("model_number")->nullable();
            $table->integer("quentity")->default(1);
            $table->decimal("price", 8, 2)->default(0);
            $table->enum("disscount_type", ["percentage", "fixed"])->nullable();
            $table->decimal("disscount_value", 8, 2)->default(0);
            $table->enum("currency", ["USD", "SY"])->default("USD");
            $table->date("manufacture_date")->nullable();
            $table->string("product_image")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
