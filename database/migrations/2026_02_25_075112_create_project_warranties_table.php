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
        Schema::create('project_warranties', function (Blueprint $table) {
            $table->id();
            $table->foreignId("invoice_id")->constrained("purchase_invoices")->onDelete("cascade");
            $table->foreignId("customer_id")->nullable()->constrained("customers")->nullOnDelete();
            $table->string("customer_name")->nullable();
            $table->foreignId("company_id")->nullable()->constrained("solar_companies")->nullOnDelete();
            $table->string("provider_name")->nullable();
            $table->enum("warranty_status", ["active", "expired", "void"])->default("active");
            $table->string("warranty_number")->unique()->nullable();
            $table->string("project_serial_number")->nullable();
            $table->text("warranty_terms")->nullable();
            $table->date("start_date")->nullable();
            $table->date("end_date")->nullable();
            $table->decimal("installation_warranty_years", 2, 1)->default(0);



            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_warranties');
    }
};
