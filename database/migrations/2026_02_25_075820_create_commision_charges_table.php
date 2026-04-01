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
        Schema::create('commision_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId("admin_id")->constrained("system_admins")->onDelete("cascade");
            $table->foreignId("commision_police_id")->nullable()->constrained("commision_polices")->nullOnDelete();
            $table->morphs("target_table");
            $table->foreignId("invoice_id")->nullable()->constrained("purchase_invoices")->nullOnDelete();
            $table->decimal("sales_amount", 10, 2)->default(0);
            $table->decimal("commision_amount", 10, 2)->default(0);
            $table->dateTime("paid_at")->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commision_charges');
    }
};
