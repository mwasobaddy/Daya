<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ward extends Model
{
    protected $fillable = [
        'subcounty_id',
        'name',
        'code',
    ];

    public function subcounty()
    {
        return $this->belongsTo(Subcounty::class);
    }
}
