<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_report_rows', function (Blueprint $table) {
            $table->boolean('below_total')->default(false)->after('row_order');
        });
    }

    public function down(): void
    {
        Schema::table('company_report_rows', function (Blueprint $table) {
            $table->dropColumn('below_total');
        });
    }
};
