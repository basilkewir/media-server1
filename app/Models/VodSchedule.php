<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VodSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'vod_file_id', 'channel_id',
        'title',
        'play_at', 'end_at',
        'is_repeating', 'repeat_days',
        'override_default_playlist',
        'is_active', 'metadata',
    ];

    protected $casts = [
        'play_at'                 => 'datetime',
        'end_at'                  => 'datetime',
        'is_repeating'            => 'boolean',
        'repeat_days'             => 'json',
        'override_default_playlist' => 'boolean',
        'is_active'               => 'boolean',
        'metadata'                => 'json',
    ];

    public function vodFile(): BelongsTo
    {
        return $this->belongsTo(VodFile::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function isPlayingNow(): bool
    {
        if (!$this->is_active) return false;
        $now = now();

        if ($this->is_repeating && $this->repeat_days) {
            $dayOfWeek = (int) $now->format('N'); // 1=Mon
            if (!in_array($dayOfWeek, $this->repeat_days, true)) {
                return false;
            }
        }

        if ($this->end_at && $now->gt($this->end_at)) {
            return false;
        }

        return $now->gte($this->play_at)
            && (is_null($this->end_at) || $now->lte($this->end_at));
    }

    public function dayNames(): string
    {
        if (!$this->repeat_days) return '—';
        $dayMap = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
        return implode(', ', array_map(fn($d) => $dayMap[$d] ?? '', $this->repeat_days));
    }
}
