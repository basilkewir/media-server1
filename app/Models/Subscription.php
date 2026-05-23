<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'plan_id',
        'starts_at', 'ends_at',
        'storage_used_bytes',
        'is_active', 'auto_renew',
        'payment_status', 'payment_method',
        'metadata',
    ];

    protected $casts = [
        'starts_at'          => 'datetime',
        'ends_at'            => 'datetime',
        'storage_used_bytes' => 'integer',
        'is_active'          => 'boolean',
        'auto_renew'         => 'boolean',
        'metadata'           => 'json',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isExpired(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    public function isCurrentlyActive(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    public function storageUsedFormatted(): string
    {
        $bytes = $this->storage_used_bytes;
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576)    return round($bytes / 1048576, 2) . ' MB';
        return round($bytes / 1024, 2) . ' KB';
    }

    public function remainingQuota(): int
    {
        $plan = $this->plan;
        if (!$plan) return 0;
        return max(0, $plan->storage_quota_bytes - $this->storage_used_bytes);
    }

    public function quotaPct(): float
    {
        $plan = $this->plan;
        if (!$plan || $plan->storage_quota_bytes <= 0) return 0;
        return min(100, round(($this->storage_used_bytes / $plan->storage_quota_bytes) * 100, 1));
    }
}
