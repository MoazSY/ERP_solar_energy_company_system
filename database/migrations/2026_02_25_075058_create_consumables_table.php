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
        Schema::create('consumables', function (Blueprint $table) {
            $table->id();
            $table->foreignId("technician_id")->constrained("employees")->onDelete("cascade");
            $table->foreignId("task_id")->constrained("project_tasks")->onDelete("cascade");
            $table->foreignId("item_id")->constrained("items")->onDelete("cascade");
            $table->float("quantity_consume")->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumables');
    }
};
