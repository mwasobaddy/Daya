<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = [
        'client_id',
        'dcd_id',
        'title',
        'description',
        'budget',
        'county',
        'status',
        'campaign_objective',
        'target_audience',
        'duration',
        'objectives',
        'digital_product_link',
        'explainer_video_url',
        'metadata',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'budget' => 'decimal:2',
            'completed_at' => 'datetime',
        ];
    }

    // Relationships
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function dcd()
    {
        return $this->belongsTo(User::class, 'dcd_id');
    }

    public function scans()
    {
        return $this->hasMany(Scan::class);
    }
}
