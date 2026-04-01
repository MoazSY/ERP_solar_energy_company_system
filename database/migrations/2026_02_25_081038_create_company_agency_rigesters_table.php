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
        Schema::create('company_agency_rigesters', function (Blueprint $table) {
            $table->id();
            $table->foreignId("admin_id")->constrained("system_admins")->onDelete("cascade");
            $table->morphs("registerable");
            $table->enum("status", ["pending", "approved", "rejected"])->default("pending");
            $table->text("rejection_reason")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company__agency_rigesters');
    }
};
