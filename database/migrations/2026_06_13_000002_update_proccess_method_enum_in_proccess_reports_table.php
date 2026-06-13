<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("UPDATE proccess_reports SET proccess_method = 'nothing' WHERE proccess_method = 'compensation'");
        DB::statement("ALTER TABLE proccess_reports MODIFY proccess_method ENUM('warning','fine','suspension','block','nothing') NOT NULL DEFAULT 'warning'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE proccess_reports MODIFY proccess_method ENUM('warning','block','compensation','fine','nothing') NOT NULL DEFAULT 'warning'");
    }
};
