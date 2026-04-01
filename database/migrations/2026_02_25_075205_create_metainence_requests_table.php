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
        Schema::create('metainence_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId("company_id")->constrained("solar_companies")->onDelete("cascade");
            $table->foreignId("customer_id")->nullable()->constrained("customers")->nullOnDelete();
            $table->string("customer_name")->nullable();
            $table->string("customer_phone")->nullable();
            $table->enum("metainence_type",["preventive","corrective","warranty","upgrade"])->default("preventive");
            $table->enum("issue_category",["inverter","solar_panel","battery","fullsystem","other"])->default("other");
            $table->enum("priority",["low","medium","high"])->default("medium");
            $table->text("issue_description")->nullable();
            $table->boolean("manager_approval")->default(false);
            $table->string("manager_notes")->nullable();
            $table->enum("metainence_status",["pending","in_progress","completed","cancelled"])->default("pending");
            $table->dateTime("metainence_scheduled_at")->nullable();
            $table->string("system_sn")->nullable();
            $table->string("warranty_number")->nullable();
            $table->string("image_state")->nullable();
            $table->decimal("estimated_cost", 8, 2)->default(0);
            $table->text("problem_name")->nullable();
            $table->text("problem_cause")->nullable();
            $table->boolean("is_paid")->default(false);
            $table->enum("payment_method",["cash","card","bank_transfer"])->default("cash");
            $table->enum("currency",["USD","SY"])->default("SY");
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metainence_requests');
    }
};
