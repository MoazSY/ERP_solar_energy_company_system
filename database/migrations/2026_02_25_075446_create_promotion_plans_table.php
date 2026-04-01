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
        Schema::create('promotion_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId("admin_id")->constrained("system_admins")->onDelete("cascade");
            $table->string("plan_name");
            $table->text("plan_description")->nullable();
            $table->decimal("plan_price", 10, 2)->default(0);
            $table->enum("currency",["USD","SY"])->default("SY");
            $table->integer("duration_days")->default(0);
            $table->enum("priority_level",["basic","featured","premium","sponsored"])->default("basic");
            $table->integer("priority_value")->default(0)->nullable();
            $table->boolean("allows_banar")->default(false);
            $table->boolean("is_active")->default(true);
            $table->date("start_date")->nullable();
            $table->date("end_date")->nullable();
            $table->integer("max_promotions")->default(0)->nullable();
            $table->float("max_daily_promotion_period")->default(0)->nullable();
            $table->float("promotion_part")->default(0)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_plans');
    }
};
