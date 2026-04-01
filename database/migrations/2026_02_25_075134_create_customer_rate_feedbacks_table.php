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
        Schema::create('customer_rate_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId("customer_id")->constrained("customers")->onDelete("cascade");
            $table->foreignId("task_id")->constrained("project_tasks")->onDelete("cascade");
            $table->float("rate")->default(0);
            $table->text("feedback")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_rate_feedbacks');
    }
};
