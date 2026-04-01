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
        Schema::create('installation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId("company_id")->constrained("solar_companies")->onDelete("cascade");
            $table->string("rule_name")->nullable();
            $table->enum("system_type", ["on_grid", "off_grid", "hybrid"])->default("off_grid");
            $table->decimal("installation_fee", 8, 2)->default(0);
            $table->decimal("price_per_kw", 8, 2)->default(0);
            $table->decimal("price_per_panal", 8, 2)->default(0);
            $table->text("general_terms")->nullable();
            $table->enum("currency", ["USD", "SY"])->default("SY");
            $table->boolean("is_active")->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installation_rules');
    }
};
