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
        Schema::create('component_warranties', function (Blueprint $table) {
            $table->id();
            $table->foreignId("project_warranty_id")->nullable()->constrained("project_warranties")->nullOnDelete();
            $table->foreignId("item_id")->nullable()->constrained("items")->nullOnDelete();
            $table->foreignId("company_id")->nullable()->constrained("solar_companies")->nullOnDelete();
            $table->foreignId("customer_id")->nullable()->constrained("customers")->nullOnDelete();
            $table->string("customer_name")->nullable();
            $table->string("provider_name")->nullable();
            $table->string("component_type")->nullable();
            $table->decimal("warranty_years", 2, 1)->default(0);    
            $table->text("warranty_terms")->nullable();
            $table->string("product_name");
            $table->string("product_serial_number")->nullable();
            $table->enum("warranty_status", ["active", "expired", "void"])->default("active");
            $table->enum("warranty_source", ["manufacturer", "installer"])->default("manufacturer");
            $table->date("start_date")->nullable(); 
            $table->date("end_date")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('component_warranties');
    }
};
