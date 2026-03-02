<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Global account categories for cashflow (same for all companies).
     */
    public function up(): void
    {
        Schema::create('cashflow_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 64)->nullable()->unique();
            $table->string('type', 16)->default('outflow'); // inflow | outflow
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashflow_categories');
    }
};
