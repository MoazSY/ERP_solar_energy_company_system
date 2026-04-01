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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId("promotion_plan_id")->constrained("promotion_plans")->onDelete("cascade");
            $table->foreignId("admin_id")->constrained("system_admins")->onDelete("cascade");
            $table->morphs("promotable");
            $table->dateTime("start_date");
            $table->dateTime("end_date");
            $table->enum("status", ["active", "inactive","stop"])->default("inactive");
            $table->string("banar_image")->nullable();
            $table->text("promotion_text")->nullable();
            $table->integer("impressions_count")->default(0);
            $table->integer("clicks_count")->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
