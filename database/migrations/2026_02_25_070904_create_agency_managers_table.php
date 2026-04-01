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
        Schema::create('agency_managers', function (Blueprint $table) {
            $table->id();
            $table->string("first_name");
            $table->string("last_name");
            $table->date("date_of_birth")->nullable();
            $table->string("phoneNumber")->unique();
            $table->string("image")->nullable();
            $table->string("identification_image")->nullable();
            $table->longText("about_him")->nullable();
            $table->string("email")->unique();
            $table->string("password")->nullable();
            $table->string("account_number")->unique()->nullable();
            $table->boolean('Activate_Account')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_managers');
    }
};
