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
        Schema::create('input_output_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId("company_id")->constrained("solar_companies")->onDelete("cascade");
            $table->enum("request_type", ["input", "output"])->default("input");
            $table->foreignId("inventory_manager_id")->nullable()->constrained("employees")->nullOnDelete();
            $table->foreignId("order_id")->nullable()->constrained("order_lists")->nullOnDelete();
            $table->enum("status", ["pending", "ready", "problem"])->default("pending");
            $table->dateTime("request_datetime")->nullable();
            $table->dateTime("ready_datetime")->nullable();
            $table->string("notes")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('input_output_requests');
    }
};
