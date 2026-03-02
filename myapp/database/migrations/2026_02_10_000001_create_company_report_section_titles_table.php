<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Per-company per-year custom section title (e.g. "Part 1" → "Main expenses").
     */
    public function up(): void
    {
        Schema::create('company_report_section_titles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('section');
            $table->string('title')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'year', 'section']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_report_section_titles');
    }
};
