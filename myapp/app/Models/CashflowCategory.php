<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashflowCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'sort_order',
    ];

    public function scopeInflow($query)
    {
        return $query->where('type', 'inflow');
    }

    public function scopeOutflow($query)
    {
        return $query->where('type', 'outflow');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
