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
        Schema::create('subscribe_polices', function (Blueprint $table) {
            $table->id();
            $table->foreignId("admin_id")->constrained("system_admins")->onDelete("cascade");
            $table->string("name");
            $table->text("description")->nullable();
            $table->enum("apply_to",["company","agency"])->default("company");
            $table->decimal("subscription_fee", 10, 2)->default(0);
            $table->enum("currency",["USD","SY"])->default("SY");
            $table->integer("duration_value")->default(0);
            $table->enum("duration_type",["day","month","year"])->default("month");
            $table->boolean("is_active")->default(true);
            $table->boolean("is_trial_granted")->default(false);
            $table->integer("trial_duration_value")->nullable();
            $table->enum("trial_duration_type",["day","month","year"])->nullable();
            $table->integer("priority")->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscribe_polices');
    }
};
