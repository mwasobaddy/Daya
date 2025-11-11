<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'action',
        'resource_type',
        'resource_id',
        'token',
        'metadata',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    /**
     * Check if the action is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the action has been used
     */
    public function isUsed(): bool
    {
        return !is_null($this->used_at);
    }

    /**
     * Check if the action is valid
     */
    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->isUsed();
    }
}