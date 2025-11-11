<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $fillable = [
        'code',
        'name',
        'county_label',
        'subcounty_label',
    ];

    public function counties(): HasMany
    {
        return $this->hasMany(County::class);
    }
}
