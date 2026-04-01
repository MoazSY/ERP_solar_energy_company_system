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
        Schema::create('promotion_governorates', function (Blueprint $table) {
            $table->id();
            $table->foreignId("promotion_plan_id")->constrained("promotion_plans")->onDelete("cascade");
            $table->foreignId("promotion_id")->nullable()->constrained("promotions")->nullOnDelete();
            $table->foreignId("governorate_id")->constrained("governorates")->onDelete("cascade");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_governorates');
    }
};
