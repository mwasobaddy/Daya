<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subcounty extends Model
{
    protected $fillable = [
        'county_id',
        'name',
    ];

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }
}
