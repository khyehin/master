<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'base_currency',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function cashflowEntries()
    {
        return $this->hasMany(CashflowEntry::class);
    }

    public function cashflowAdjustments()
    {
        return $this->hasMany(CashflowAdjustment::class);
    }
}

