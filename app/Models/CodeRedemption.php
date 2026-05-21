<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodeRedemption extends Model
{
    use HasFactory;

    protected $table = 'code_redemptions';

    protected $fillable = [
        'access_code_id',
        'ip_address',
        'user_agent',
        'redeemed_at',
        'expires_at',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'redeemed_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'metadata' => 'json',
    ];

    public function accessCode(): BelongsTo
    {
        return $this->belongsTo(AccessCode::class);
    }

    /**
     * Check if this redemption is still active (not expired).
     */
    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Deactivate this redemption.
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }
}
