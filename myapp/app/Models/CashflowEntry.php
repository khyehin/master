<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashflowEntry extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'user_id',
        'entry_date',
        'category',
        'currency',
        'amount_minor',
        'fx_rate_to_base',
        'base_amount_minor',
        'deposit_minor',
        'withdrawal_minor',
        'affin_minor',
        'xe_minor',
        'usdt_minor',
        'description',
    ];

    protected $casts = [
        'entry_date' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function extraValues(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CashflowEntryExtraValue::class);
    }

    /** Get value_minor for an extra column id (from extraValues relationship). */
    public function getExtraValueMinor(int $columnId): int
    {
        $v = $this->extraValues->firstWhere('cashflow_extra_column_id', $columnId);
        return $v ? (int) $v->value_minor : 0;
    }
}

