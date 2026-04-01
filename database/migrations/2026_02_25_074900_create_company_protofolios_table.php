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
        Schema::create('company_protofolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId("company_id")->constrained("solar_companies")->onDelete("cascade");
            $table->string("project_name");
            $table->string("title");
            $table->longText("description");
            $table->enum("project_status", ["completed", "in_progress", "pending"])->default("completed");
            $table->enum("project_type", ["residential", "commercial","industrial"])->default("residential");
            $table->string("location")->nullable();
            $table->enum("project_size", ["small", "medium", "large"])->default("small");
            $table->enum("system_type", ["grid_tied", "off_grid", "hybrid"])->default("off_grid");
            $table->decimal("capacity_kw", 8, 2)->default(0)->nullable();
            $table->decimal("total_cost", 10, 2)->default(0)->nullable();
            $table->date("installation_date")->nullable();
            $table->string("project_cover_image")->nullable();
            $table->json("project_images")->nullable();
            $table->json("project_videos")->nullable();
            $table->enum("customer_satisfaction", [1, 2, 3, 4, 5])->default(5)->nullable();
            $table->boolean("is_featured")->default(false);
            $table->foreignId("project_task_id")->nullable()->constrained("project_tasks")->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_protofolios');
    }
};
