<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Scan extends Model
{
    protected $fillable = [
        'dcd_id',
        'campaign_id',
        'scanned_at',
        'device_fingerprint',
        'geo',
        'earnings',
    ];

    protected function casts(): array
    {
        return [
            'scanned_at' => 'datetime',
            'geo' => 'array',
            'earnings' => 'decimal:4',
        ];
    }

    // Relationships
    public function dcd()
    {
        return $this->belongsTo(User::class, 'dcd_id');
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
