<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RelayBroadcastLog extends Model
{
    use HasFactory;

    protected $table = 'relay_broadcast_logs';

    protected $fillable = [
        'relay_broadcast_id',
        'event_type',
        'message',
        'listeners_count',
        'bitrate_kbps',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the relay broadcast.
     */
    public function relayBroadcast()
    {
        return $this->belongsTo(RelayBroadcast::class);
    }
}
