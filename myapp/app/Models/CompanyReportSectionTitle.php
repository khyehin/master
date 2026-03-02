<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyReportSectionTitle extends Model
{
    protected $fillable = ['company_id', 'year', 'section', 'title'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
