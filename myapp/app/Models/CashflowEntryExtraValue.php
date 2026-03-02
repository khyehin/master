<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashflowEntryExtraValue extends Model
{
    protected $fillable = ['cashflow_entry_id', 'cashflow_extra_column_id', 'value_minor'];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(CashflowEntry::class, 'cashflow_entry_id');
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(CashflowExtraColumn::class, 'cashflow_extra_column_id');
    }
}
