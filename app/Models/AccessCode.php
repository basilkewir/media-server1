<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccessCode extends Model
{
    use HasFactory;

    protected $table = 'access_codes';

    protected $fillable = [
        'channel_id',
        'code',
        'type',
        'duration_days',
        'expires_at',
        'max_uses',
        'uses_count',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'metadata' => 'json',
    ];

    const TYPE_LIBRARY_ONLY = 'library_only';
    const TYPE_FULL_ACCESS  = 'full_access';
    const TYPE_PREMIUM      = 'premium';
    const TYPE_VOD_MANAGER  = 'vod_manager';

    public function channel(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(CodeRedemption::class);
    }

    public function activeRedemptions(): HasMany
    {
        return $this->hasMany(CodeRedemption::class)->where('is_active', true);
    }

    /**
     * Check if the code is currently valid (active, not expired, uses remaining).
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses > 0 && $this->uses_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Increment usage count.
     */
    public function incrementUsage(): void
    {
        $this->increment('uses_count');
    }

    /**
     * Generate a batch of secure random access codes.
     *
     * @return array<int, static>
     */
    public static function generateBatch(
        string $type,
        int $durationDays,
        int $quantity,
        ?\DateTimeInterface $expiresAt = null,
        int $maxUses = 1,
        int $codeLength = 12,
        ?int $channelId = null,
    ): array {
        $codes = [];

        for ($i = 0; $i < $quantity; $i++) {
            $plainCode = self::generateRandomCode($codeLength);

            $codes[] = static::create([
                'channel_id'    => $channelId,
                'code'          => $plainCode,
                'type'          => $type,
                'duration_days' => $durationDays,
                'expires_at'    => $expiresAt,
                'max_uses'      => $maxUses,
                'uses_count'    => 0,
                'is_active'     => true,
            ]);
        }

        return $codes;
    }

    /**
     * Generate a cryptographically secure random code.
     */
    public static function generateRandomCode(int $length = 12): string
    {
        $length = max(8, $length);
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $maxIndex = strlen($chars) - 1;
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, $maxIndex)];
        }

        // Add hyphens for readability: XXXX-XXXX-XXXX
        if ($length >= 12) {
            $code = implode('-', str_split($code, 4));
        }

        return $code;
    }

    /**
     * Find a valid code by its plain text value.
     */
    public static function findValid(string $code): ?static
    {
        $record = static::where('code', $code)->first();

        if (!$record || !$record->isValid()) {
            return null;
        }

        return $record;
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_LIBRARY_ONLY => 'Library Only',
            self::TYPE_FULL_ACCESS  => 'Full Access',
            self::TYPE_PREMIUM      => 'Premium',
            self::TYPE_VOD_MANAGER  => 'VOD Manager',
            default                 => 'Unknown',
        };
    }
}
