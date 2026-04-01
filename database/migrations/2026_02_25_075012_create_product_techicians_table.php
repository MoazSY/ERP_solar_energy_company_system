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
        Schema::create('product_techicians', function (Blueprint $table) {
            $table->id();
            $table->foreignId("technician_id")->constrained("employees")->onDelete("cascade");
            $table->foreignId("inventory_manager_id")->nullable()->constrained("employees")->nullOnDelete();
            $table->foreignId("task_id")->nullable()->constrained("project_tasks")->nullOnDelete();
            $table->foreignId("item_id")->nullable()->constrained("items")->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_techicians');
    }
};
