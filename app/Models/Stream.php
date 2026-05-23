<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stream extends Model
{
    use HasFactory;

    protected $table = 'streams';

    protected $fillable = [
        'channel_id', 'status', 'stream_type',
        'source_url', 'input_protocol',
        'started_at', 'ended_at',
        'bitrate_kbps', 'resolution', 'viewers', 'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
        'metadata'   => 'json',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function statistics(): HasMany
    {
        return $this->hasMany(StreamStatistic::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && is_null($this->ended_at);
    }

    public function isFallback(): bool
    {
        return $this->status === 'fallback';
    }

    public function getDuration(): int
    {
        if (!$this->started_at) return 0;
        return (int) ($this->ended_at ?? now())->diffInSeconds($this->started_at);
    }

    public function getUptimePercentage(): float
    {
        $total = $this->statistics()->count();
        if ($total === 0) return 100.0;
        return round($this->statistics()->where('is_healthy', true)->count() / $total * 100, 2);
    }
}
