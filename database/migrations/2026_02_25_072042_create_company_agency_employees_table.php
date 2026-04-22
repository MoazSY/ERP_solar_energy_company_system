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
        Schema::create('company_agency_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId("employee_id")->constrained("employees")->onDelete("cascade");
            $table->morphs("entity_type");
            $table->enum("role", ["install_technician", "metal_base_technician", "blacksmith_workshop", "driver","inventory_manager"]);
            $table->enum("salary_type", ["fixed", "rate"])->default("fixed");
            $table->enum("currency", ["USD","SY"])->default("SY")->nullable();
            $table->enum("work_type", ["full_time", "task_based"])->default("full_time");
            $table->enum("payment_method", ["bank_transfer", "cash"])->default("bank_transfer")->nullable();
            $table->enum("payment_frequency", ["daily", "weekly", "monthly","after_task"])->default("monthly")->nullable();
            $table->decimal("salary_rate", 5, 2)->default(0)->nullable();
            $table->decimal("salary_amount", 8, 2)->default(0)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_agency_employees');
    }
};
