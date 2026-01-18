<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = [
        'client_id',
        'dcd_id',
        'title',
        'budget',
        'cost_per_click',
        'spent_amount',
        'campaign_credit',
        'max_scans',
        'total_scans',
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
            'budget' => 'decimal:4',  // Allow up to 4 decimal places for precise budget values
            'cost_per_click' => 'decimal:4',
            'spent_amount' => 'decimal:4',
            'campaign_credit' => 'decimal:4',
            'max_scans' => 'integer',
            'total_scans' => 'integer',
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

    /**
     * Check if campaign has reached its scan limit
     */
    public function hasReachedScanLimit(): bool
    {
        return $this->max_scans > 0 && $this->total_scans >= $this->max_scans;
    }

    /**
     * Check if campaign has exhausted its budget
     */
    public function hasExhaustedBudget(): bool
    {
        return $this->spent_amount >= $this->budget;
    }

    /**
     * Get remaining budget
     */
    public function getRemainingBudget(): float
    {
        return max(0, (float)$this->budget - (float)$this->spent_amount);
    }

    /**
     * Get remaining scans allowed
     */
    public function getRemainingScans(): int
    {
        if ($this->max_scans <= 0) {
            return 0;
        }
        return max(0, $this->max_scans - $this->total_scans);
    }

    /**
     * Check if campaign can accept more scans
     */
    public function canAcceptScans(): bool
    {
        if ($this->status === 'completed') {
            return false;
        }

        if ($this->hasReachedScanLimit() || $this->hasExhaustedBudget()) {
            return false;
        }

        // Check if campaign credit is exhausted
        if ($this->campaign_credit <= 0) {
            return false;
        }

        return true;
    }

    /**
     * Get remaining campaign credit
     */
    public function getRemainingCredit(): float
    {
        return max(0, (float)$this->campaign_credit);
    }
}
