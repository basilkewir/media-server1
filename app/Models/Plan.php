<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'tier',
        'storage_quota_bytes', 'max_channels', 'max_vod_files',
        'max_upload_bytes', 'features', 'price_cents', 'currency',
        'billing_interval', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'features'           => 'json',
        'storage_quota_bytes'=> 'integer',
        'max_channels'       => 'integer',
        'max_vod_files'      => 'integer',
        'max_upload_bytes'   => 'integer',
        'price_cents'        => 'integer',
        'is_active'          => 'boolean',
        'sort_order'         => 'integer',
    ];

    const TIER_FREE       = 'free';
    const TIER_BASIC      = 'basic';
    const TIER_PRO        = 'pro';
    const TIER_ENTERPRISE = 'enterprise';

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class)->where('is_active', true);
    }

    public function hasFeature(string $feature): bool
    {
        $features = $this->features ?? [];
        return in_array($feature, $features, true);
    }

    public function formattedPrice(): string
    {
        if ($this->price_cents === 0) {
            return 'Free';
        }
        return number_format($this->price_cents / 100, 2) . ' ' . $this->currency
            . '/' . $this->billing_interval;
    }

    public function formattedQuota(): string
    {
        $bytes = $this->storage_quota_bytes;
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 1) . ' GB';
        return round($bytes / 1048576) . ' MB';
    }

    public static function tiers(): array
    {
        return [
            self::TIER_FREE       => 'Free',
            self::TIER_BASIC      => 'Basic',
            self::TIER_PRO        => 'Pro',
            self::TIER_ENTERPRISE => 'Enterprise',
        ];
    }
}
