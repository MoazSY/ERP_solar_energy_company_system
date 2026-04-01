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
        Schema::create('custom_subscribe', function (Blueprint $table) {
            $table->id();
            $table->foreignId("subscribe_policy_id")->constrained("subscribe_polices")->onDelete("cascade");
            $table->morphs("subscribeable");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_subscribe_policies');
    }
};
