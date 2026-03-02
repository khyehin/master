<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Master Cashflow entries use company_id = NULL; company cashflow uses company_id = company.id.
     */
    public function up(): void
    {
        Schema::table('cashflow_entries', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });
        Schema::table('cashflow_entries', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->change();
        });
        Schema::table('cashflow_entries', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cashflow_entries', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });
        Schema::table('cashflow_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable(false)->change();
        });
        Schema::table('cashflow_entries', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
        });
    }
};
