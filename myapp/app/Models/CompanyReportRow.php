<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyReportRow extends Model
{
    protected $fillable = [
        'company_id',
        'year',
        'section',
        'row_order',
        'label',
        'm1', 'm2', 'm3', 'm4', 'm5', 'm6',
        'm7', 'm8', 'm9', 'm10', 'm11', 'm12',
    ];

    protected $casts = [
        'm1' => 'decimal:2',
        'm2' => 'decimal:2',
        'm3' => 'decimal:2',
        'm4' => 'decimal:2',
        'm5' => 'decimal:2',
        'm6' => 'decimal:2',
        'm7' => 'decimal:2',
        'm8' => 'decimal:2',
        'm9' => 'decimal:2',
        'm10' => 'decimal:2',
        'm11' => 'decimal:2',
        'm12' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Month keys 1..12 */
    public static function monthKeys(): array
    {
        return ['m1', 'm2', 'm3', 'm4', 'm5', 'm6', 'm7', 'm8', 'm9', 'm10', 'm11', 'm12'];
    }

    /** Get amount for month 1-12 */
    public function getMonth(int $month): ?float
    {
        if ($month < 1 || $month > 12) {
            return null;
        }
        $key = 'm' . $month;
        $v = $this->{$key};
        return $v !== null ? (float) $v : null;
    }

    /** Sum of all 12 months */
    public function getTotal(): float
    {
        $sum = 0;
        foreach (self::monthKeys() as $k) {
            $v = $this->{$k};
            if ($v !== null) {
                $sum += (float) $v;
            }
        }
        return $sum;
    }
}
