<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE component_warranties MODIFY warranty_source ENUM('manufacturer', 'installer', 'both') NOT NULL DEFAULT 'manufacturer'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE component_warranties SET warranty_source = 'manufacturer' WHERE warranty_source = 'both'");
        DB::statement("ALTER TABLE component_warranties MODIFY warranty_source ENUM('manufacturer', 'installer') NOT NULL DEFAULT 'manufacturer'");
    }
};
