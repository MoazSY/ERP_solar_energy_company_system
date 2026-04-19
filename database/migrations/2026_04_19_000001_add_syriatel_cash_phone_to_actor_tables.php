<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('system_admins', function (Blueprint $table) {
            $table->string('syriatel_cash_phone')->nullable()->unique()->after('account_number');
        });

        Schema::table('solar_company_managers', function (Blueprint $table) {
            $table->string('syriatel_cash_phone')->nullable()->unique()->after('account_number');
        });

        Schema::table('agency_managers', function (Blueprint $table) {
            $table->string('syriatel_cash_phone')->nullable()->unique()->after('account_number');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->string('syriatel_cash_phone')->nullable()->unique()->after('account_number');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('syriatel_cash_phone')->nullable()->unique()->after('account_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_admins', function (Blueprint $table) {
            $table->dropUnique(['syriatel_cash_phone']);
            $table->dropColumn('syriatel_cash_phone');
        });

        Schema::table('solar_company_managers', function (Blueprint $table) {
            $table->dropUnique(['syriatel_cash_phone']);
            $table->dropColumn('syriatel_cash_phone');
        });

        Schema::table('agency_managers', function (Blueprint $table) {
            $table->dropUnique(['syriatel_cash_phone']);
            $table->dropColumn('syriatel_cash_phone');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['syriatel_cash_phone']);
            $table->dropColumn('syriatel_cash_phone');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['syriatel_cash_phone']);
            $table->dropColumn('syriatel_cash_phone');
        });
    }
};
