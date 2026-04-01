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
        Schema::create('proccess_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId("report_id")->constrained("reports")->onDelete("cascade");
            $table->foreignId("admin_id")->constrained("system_admins")->onDelete("cascade");
            $table->enum("proccess_method",["warning","block","compensation","fine","nothing"])->default("warning");
            $table->enum("block_type",["hour","day","week"])->nullable();
            $table->integer("block_duaration_value")->nullable();
            $table->decimal("compensation_amount", 10, 2)->nullable();
            $table->decimal("fine_amount", 10, 2)->nullable();
            $table->text("notes")->nullable();
            $table->dateTime("proccess_datetime")->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proccess_reports');
    }
};
