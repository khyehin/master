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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('base_currency', 3)->default('USD');
            $table->timestamps();
        });

        Schema::create('company_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['company_id', 'user_id']);
        });

        Schema::create('cashflow_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('entry_date');
            $table->string('category');
            $table->string('currency', 3);
            $table->bigInteger('amount_minor');
            $table->decimal('fx_rate_to_base', 18, 8);
            $table->bigInteger('base_amount_minor');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'entry_date', 'category']);
        });

        Schema::create('cashflow_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->string('category');
            $table->string('currency', 3);
            $table->bigInteger('amount_minor');
            $table->decimal('fx_rate_to_base', 18, 8);
            $table->bigInteger('base_amount_minor');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'year', 'month', 'category']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();
            $table->index(['event_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('cashflow_adjustments');
        Schema::dropIfExists('cashflow_entries');
        Schema::dropIfExists('company_user');
        Schema::dropIfExists('companies');
    }
};

