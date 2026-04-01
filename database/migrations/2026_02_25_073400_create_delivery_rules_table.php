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
        Schema::create('delivery_rules', function (Blueprint $table) {
            $table->id();
            $table->morphs("entity_type");
            $table->string("rule_name")->nullable();
            $table->foreignId("governorate_id")->nullable()->constrained("governorates")->nullOnDelete();
            $table->foreignId("area_id")->nullable()->constrained("areas")->nullOnDelete();
            $table->decimal("delivery_fee", 8, 2)->default(0);
            $table->decimal("price_per_km", 8, 2)->default(0);
            $table->integer("max_weight_kg")->default(0);
            $table->decimal("price_per_extra_kg", 8, 2)->default(0);
            $table->enum("currency", ["USD", "SY"])->default("SY");
            $table->boolean("is_active")->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_rules');
    }
};
