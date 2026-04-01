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
        Schema::create('project_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId("company_id")->constrained("solar_companies")->onDelete("cascade");
            $table->foreignId("employee_id")->nullable()->constrained("employees")->nullOnDelete();
            $table->morphs("taskable");
            $table->boolean("task_accepted")->default(false);
            $table->string("rejected_reason")->nullable();
            $table->dateTime("accepted_at")->nullable();
            $table->dateTime("rejected_at")->nullable();
            $table->enum("task_type", ["installation", "metal_base", "blacksmith_workshop", "delivery","maintenance"])->default("installation");
            $table->foreignId("delivery_id")->nullable()->constrained("deliveries")->nullOnDelete();
            $table->decimal("task_fee", 8, 2)->default(0);
            $table->boolean("manager_payed")->default(false);
            $table->dateTime("manager_payed_at")->nullable();
            $table->enum("task_status", ["pending", "in_progress", "completed"])->default("pending");
            $table->string("task_images")->nullable();
            $table->boolean("client_recieve_task")->default(false);
            $table->text("employee_notes")->nullable();
            $table->text("manager_notes")->nullable();
            $table->integer("num_assistants")->default(0);
            $table->string("assistant_names")->nullable();

            $table->decimal("client_additional_cost_amount", 8, 2)->default(0)->nullable();
            $table->decimal("client_additional_entitlement_amount", 8, 2)->default(0)->nullable();
            $table->enum("payment_status", ["pending", "client_paid","manager_paid"])->default("pending");
            $table->enum("payment_method", ["bank_transfer", "cash"])->default("bank_transfer")->nullable();
            $table->boolean("payment_received")->default(false);
            $table->dateTime("sheduled_at")->nullable();
            $table->dateTime("started_at")->nullable();
            $table->dateTime("completed_at")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_tasks');
    }
};
