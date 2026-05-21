<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StreamEvent extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stream_events';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'channel_id',
        'event_type',
        'message',
        'severity',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'metadata' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const SEVERITY_INFO = 'info';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_ERROR = 'error';
    const SEVERITY_CRITICAL = 'critical';

    const EVENT_STREAM_STARTED = 'stream_started';
    const EVENT_STREAM_STOPPED = 'stream_stopped';
    const EVENT_STREAM_FAILED = 'stream_failed';
    const EVENT_VOD_FALLBACK = 'vod_fallback';
    const EVENT_FALLBACK_RECOVERED  = 'fallback_recovered';
    const EVENT_HEALTH_CHECK_FAILED = 'health_check_failed';
    const EVENT_HEALTH_CHECK_PASSED = 'health_check_passed';

    /**
     * Get the channel that owns the event.
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * Scope to get only error events.
     */
    public function scopeErrors($query)
    {
        return $query->whereIn('severity', ['error', 'critical']);
    }

    /**
     * Scope to get recent events.
     */
    public function scopeRecent($query, $minutes = 60)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }
}
