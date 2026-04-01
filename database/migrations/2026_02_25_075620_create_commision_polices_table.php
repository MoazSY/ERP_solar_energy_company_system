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
        Schema::create('commision_polices', function (Blueprint $table) {
            $table->id();
            $table->foreignId("admin_id")->constrained("system_admins")->onDelete("cascade");
            $table->string("policy_name");
            $table->text("description")->nullable();
            $table->enum("target_type",["app_sales","inner_sales","installation","maintenance","delivery","public"])->default("app_sales");
            $table->enum("applies_to",["company","agency"])->default("company");
            $table->enum("commision_type",["percentage","fixed"])->default("percentage");
            $table->float("commision_value")->default(0);
            $table->boolean("is_active")->default(true);
            $table->date("start_date")->nullable();
            $table->date("end_date")->nullable();
            $table->integer("priority")->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commision_polices');
    }
};
