<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashflow_column_orders', function (Blueprint $table) {
            $table->id();
            // company_id: 0 for Master (stored as 0), or actual company id
            $table->unsignedBigInteger('company_id')->default(0)->index();
            $table->json('order');
            $table->timestamps();

            $table->unique(['company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashflow_column_orders');
    }
};

