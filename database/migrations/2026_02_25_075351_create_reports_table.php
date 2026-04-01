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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId("customer_id")->constrained("customers")->onDelete("cascade");
            $table->foreignId("company_id")->constrained("solar_companies")->onDelete("cascade");
            $table->foreignId("admin_id")->constrained("system_admins")->onDelete("cascade");
            $table->enum("report_type",["Service_Complaint","Contractual_Issue","Workmanship_Issue","Project_Delay","Financial_Dispute"])->default("Service_Complaint");
            $table->string("report_subject");
            $table->text("report_content")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
