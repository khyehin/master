<?php

namespace Database\Seeders;

use App\Models\CashflowCategory;
use Illuminate\Database\Seeder;

class CashflowCategorySeeder extends Seeder
{
    /**
     * Default categories matching Excel-style report (inflow/outflow).
     */
    public function run(): void
    {
        $defaults = [
            ['name' => 'Deposit', 'type' => 'inflow', 'sort_order' => 10],
            ['name' => 'Manual Bank Deposit', 'type' => 'inflow', 'sort_order' => 20],
            ['name' => 'Bonus Free Credit', 'type' => 'inflow', 'sort_order' => 30],
            ['name' => 'Withdrawal', 'type' => 'outflow', 'sort_order' => 100],
            ['name' => 'Manual Bank Withdrawal', 'type' => 'outflow', 'sort_order' => 110],
            ['name' => 'Upline Fee', 'type' => 'outflow', 'sort_order' => 120],
            ['name' => 'Fpay Fee', 'type' => 'outflow', 'sort_order' => 130],
            ['name' => 'Luxepay Fee', 'type' => 'outflow', 'sort_order' => 140],
            ['name' => 'Salary', 'type' => 'outflow', 'sort_order' => 150],
            ['name' => 'Ads Market', 'type' => 'outflow', 'sort_order' => 160],
            ['name' => 'Ads FB', 'type' => 'outflow', 'sort_order' => 170],
            ['name' => 'System', 'type' => 'outflow', 'sort_order' => 180],
            ['name' => 'Phone Card', 'type' => 'outflow', 'sort_order' => 190],
            ['name' => 'Idol Domain', 'type' => 'outflow', 'sort_order' => 200],
            ['name' => 'Remote Desktop', 'type' => 'outflow', 'sort_order' => 210],
            ['name' => 'Bank Rental', 'type' => 'outflow', 'sort_order' => 220],
            ['name' => 'Live Chat', 'type' => 'outflow', 'sort_order' => 230],
            ['name' => 'Top Up PG', 'type' => 'outflow', 'sort_order' => 240],
            ['name' => 'Other', 'type' => 'outflow', 'sort_order' => 999],
        ];

        foreach ($defaults as $i => $row) {
            CashflowCategory::firstOrCreate(
                ['name' => $row['name']],
                ['type' => $row['type'], 'sort_order' => $row['sort_order']]
            );
        }
    }
}
