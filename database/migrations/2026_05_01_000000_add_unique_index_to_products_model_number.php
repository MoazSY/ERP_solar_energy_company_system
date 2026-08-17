<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Intentionally left blank: unique constraint is now managed elsewhere.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback action required because up() does not modify the schema.
    }
};
