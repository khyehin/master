<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashflow_entries', function (Blueprint $table) {
            $table->bigInteger('affin_minor')->nullable()->default(0)->after('base_amount_minor');
            $table->bigInteger('xe_minor')->nullable()->default(0)->after('affin_minor');
            $table->bigInteger('usdt_minor')->nullable()->default(0)->after('xe_minor');
        });
    }

    public function down(): void
    {
        Schema::table('cashflow_entries', function (Blueprint $table) {
            $table->dropColumn(['affin_minor', 'xe_minor', 'usdt_minor']);
        });
    }
};
