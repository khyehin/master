<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Per-company monthly report grid: 4 sections, rows with label + 12 month amounts.
     */
    public function up(): void
    {
        Schema::create('company_report_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('section'); // 1=main, 2=nett, 3=nett2, 4=open_capital
            $table->unsignedInteger('row_order')->default(0);
            $table->string('label')->nullable(); // description
            $table->decimal('m1', 15, 2)->nullable();
            $table->decimal('m2', 15, 2)->nullable();
            $table->decimal('m3', 15, 2)->nullable();
            $table->decimal('m4', 15, 2)->nullable();
            $table->decimal('m5', 15, 2)->nullable();
            $table->decimal('m6', 15, 2)->nullable();
            $table->decimal('m7', 15, 2)->nullable();
            $table->decimal('m8', 15, 2)->nullable();
            $table->decimal('m9', 15, 2)->nullable();
            $table->decimal('m10', 15, 2)->nullable();
            $table->decimal('m11', 15, 2)->nullable();
            $table->decimal('m12', 15, 2)->nullable();
            $table->timestamps();
            $table->index(['company_id', 'year', 'section', 'row_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_report_rows');
    }
};
