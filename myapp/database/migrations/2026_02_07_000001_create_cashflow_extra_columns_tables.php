<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cashflow_extra_columns')) {
            Schema::create('cashflow_extra_columns', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('cashflow_entry_extra_values')) {
            Schema::create('cashflow_entry_extra_values', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cashflow_entry_id')->constrained('cashflow_entries')->cascadeOnDelete();
                $table->foreignId('cashflow_extra_column_id')->constrained('cashflow_extra_columns')->cascadeOnDelete();
                $table->bigInteger('value_minor')->default(0);
                $table->timestamps();
                $table->unique(['cashflow_entry_id', 'cashflow_extra_column_id'], 'cf_entry_extra_entry_col_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cashflow_entry_extra_values');
        Schema::dropIfExists('cashflow_extra_columns');
    }
};
