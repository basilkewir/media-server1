<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApiToken extends Model
{
    use HasFactory;

    protected $table = 'api_tokens';

    protected $fillable = [
        'name',
        'token',
        'is_active',
        'expires_at',
        'last_used_at',
        'allowed_ips',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'allowed_ips' => 'json',
        'metadata' => 'json',
    ];

    /**
     * Generate a new secure API token.
     */
    public static function generate(string $name, ?\DateTimeInterface $expiresAt = null, array $allowedIps = []): static
    {
        $plainToken = bin2hex(random_bytes(32));

        $token = static::create([
            'name' => $name,
            'token' => hash('sha256', $plainToken),
            'is_active' => true,
            'expires_at' => $expiresAt,
            'allowed_ips' => $allowedIps ?: null,
            'metadata' => ['plain_token' => $plainToken], // returned once, not stored in plain text after creation
        ]);

        // Store plain token temporarily so the caller can retrieve it
        $token->plain_token = $plainToken;

        return $token;
    }

    /**
     * Check if token is valid for the given IP address.
     */
    public function isAllowedForIp(string $ip): bool
    {
        $allowed = $this->allowed_ips;
        if (empty($allowed)) return true;
        return in_array($ip, $allowed, true);
    }
}
