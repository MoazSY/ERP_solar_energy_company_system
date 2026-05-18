<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->enum('task_type_new', ['installation', 'metal_base', 'blacksmith_workshop', 'delivery', 'maintenance', 'technical_inspection'])->default('installation')->after('task_type');
        });

        DB::statement('UPDATE project_tasks SET task_type_new = task_type');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropColumn('task_type_new');
        });
    }
};
