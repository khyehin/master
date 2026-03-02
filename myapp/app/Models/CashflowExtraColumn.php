<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashflowExtraColumn extends Model
{
    protected $fillable = ['name', 'sort_order'];

    public function entryValues(): HasMany
    {
        return $this->hasMany(CashflowEntryExtraValue::class, 'cashflow_extra_column_id');
    }

    public static function ordered(): \Illuminate\Database\Eloquent\Builder
    {
        return static::query()->orderBy('sort_order')->orderBy('id');
    }
}
