<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'storage_used_bytes',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'password'           => 'hashed',
            'is_active'          => 'boolean',
            'storage_used_bytes' => 'integer',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin' && $this->is_active;
    }

    public function isManager(): bool
    {
        return $this->role === 'manager' && $this->is_active;
    }

    public function managedChannels(): BelongsToMany
    {
        return $this->belongsToMany(Channel::class, 'channel_user');
    }

    public function canManageChannel(Channel $channel): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->isManager() && $this->managedChannels()->where('channels.id', $channel->id)->exists();
    }

    public function manageableChannels()
    {
        if ($this->isAdmin()) {
            return Channel::query();
        }

        return $this->managedChannels();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->latest('starts_at')
            ->first();
    }

    public function activePlan(): ?Plan
    {
        return $this->activeSubscription()?->plan;
    }

    public function getStorageQuotaBytes(): int
    {
        return $this->activePlan()?->storage_quota_bytes ?? 0;
    }

    public function getStorageUsedBytes(): int
    {
        return (int) $this->storage_used_bytes;
    }

    public function getRemainingStorageBytes(): int
    {
        return max(0, $this->getStorageQuotaBytes() - $this->getStorageUsedBytes());
    }

    public function hasFeature(string $feature): bool
    {
        return $this->activePlan()?->hasFeature($feature) ?? false;
    }
}
