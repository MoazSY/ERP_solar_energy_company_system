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
        Schema::create('solar_companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId("solar_company_manager_id")->nullable()->constrained("solar_company_managers")->nullOnDelete();
            $table->string("company_name");
            $table->string("company_logo")->nullable();
            $table->string("commerical_register_number");
            $table->longText("company_description")->nullable();
            $table->string("company_email")->unique()->nullable();
            $table->string("company_phone")->unique()->nullable();
            $table->string("tax_number")->unique()->nullable();
            $table->enum("company_status", ["active", "inactive","pending"])->default("pending");
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
        Schema::dropIfExists('solar_companies');
    }
};
