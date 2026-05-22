<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
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
}
