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
        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId("agency_manager_id")->nullable()->constrained("agency_managers")->nullOnDelete();
            $table->string("agency_name");
            $table->string("agency_logo")->nullable();
            $table->string("commerical_register_number");
            $table->longText("agency_description")->nullable();
            $table->string("agency_email")->unique()->nullable();
            $table->string("agency_phone")->unique()->nullable();
            $table->string("tax_number")->unique()->nullable();
            $table->enum("agency_status", ["active", "inactive","pending"])->default("pending");
            $table->dateTime("verified_at")->nullable();
            $table->time("working_hours_start")->nullable();
            $table->time("working_hours_end")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agencies');
    }
};
