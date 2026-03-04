<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashflowColumnOrder extends Model
{
    protected $fillable = [
        'company_id',
        'order',
    ];

    protected $casts = [
        'order' => 'array',
    ];
}

