<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiToken extends Model
{
    protected $table = 'api_tokens';

    protected $fillable = [
        'name', 'token', 'abilities', 'last_used_at', 'expires_at', 'is_active',
    ];

    protected $hidden = ['token'];

    protected $casts = [
        'abilities'    => 'json',
        'last_used_at' => 'datetime',
        'expires_at'   => 'datetime',
        'is_active'    => 'boolean',
    ];

    public function isValid(): bool
    {
        if (!$this->is_active) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        return true;
    }

    public function can(string $ability): bool
    {
        if (empty($this->abilities)) return true; // no restrictions = full access
        return in_array($ability, $this->abilities) || in_array('*', $this->abilities);
    }

    public static function findByToken(string $token): ?self
    {
        return static::where('token', hash('sha256', $token))
            ->where('is_active', true)
            ->first();
    }

    public static function generate(string $name): static
    {
        $plain = bin2hex(random_bytes(32));
        $token = static::create([
            'name'  => $name,
            'token' => hash('sha256', $plain),
        ]);
        $token->plain_token = $plain;
        return $token;
    }
}
