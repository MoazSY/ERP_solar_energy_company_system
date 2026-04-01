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
        Schema::create('specific_disscounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId("product_id")->constrained("products")->onDelete("cascade");
            $table->morphs("entity_type");
            $table->morphs("discount_type");
            $table->decimal("discount_amount", 8, 2)->default(0);
            $table->enum("disscount_type", ["percentage", "fixed"])->default("fixed");
            $table->enum("currency", ["USD", "SY"])->default("SY");
            $table->enum("product_type", ["solar_panel", "inverter", "battery", "accessory"])->default("solar_panel");
            $table->string("product_brand")->nullable();
            $table->boolean("disscount_active")->default(true);
            $table->integer("quentity_condition")->default(0);
            $table->boolean("public")->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('specific_disscounts');
    }
};
