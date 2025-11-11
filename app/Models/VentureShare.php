<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VentureShare extends Model
{
    protected $fillable = [
        'user_id',
        'kedds_amount',
        'kedws_amount',
        'reason',
        'allocated_at',
    ];

    protected function casts(): array
    {
        return [
            'allocated_at' => 'datetime',
            'kedds_amount' => 'decimal:4',
            'kedws_amount' => 'decimal:4',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
