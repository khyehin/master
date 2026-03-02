<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashflow_entries', function (Blueprint $table) {
            $table->bigInteger('deposit_minor')->nullable()->after('base_amount_minor');
            $table->bigInteger('withdrawal_minor')->nullable()->after('deposit_minor');
            $table->index(['entry_date']);
        });

        // Backfill legacy rows so existing Deposit/Withdrawal values still display.
        // Only fill when both new columns are still NULL (so future "total-only" rows can remain blank).
        DB::table('cashflow_entries')
            ->whereNull('deposit_minor')
            ->whereNull('withdrawal_minor')
            ->update([
                'deposit_minor' => DB::raw('CASE WHEN amount_minor >= 0 THEN amount_minor ELSE 0 END'),
                'withdrawal_minor' => DB::raw('CASE WHEN amount_minor < 0 THEN ABS(amount_minor) ELSE 0 END'),
            ]);
    }

    public function down(): void
    {
        Schema::table('cashflow_entries', function (Blueprint $table) {
            $table->dropColumn(['deposit_minor', 'withdrawal_minor']);
        });
    }
};

