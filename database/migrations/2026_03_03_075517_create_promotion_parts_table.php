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
        Schema::create('promotion_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId("promotion_plan_id")->constrained("promotion_plans")->onDelete("cascade");
            $table->foreignId("promotion_id")->nullable()->constrained("promotions")->nullOnDelete();
            $table->morphs("promotable");
            $table->float("start_period")->default(0);
            $table->float("end_period")->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_parts');
    }
};
