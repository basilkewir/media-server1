<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RelayServer extends Model
{
    use HasFactory;

    protected $table = 'relay_servers';

    protected $fillable = [
        'name',
        'hostname',
        'port',
        'username',
        'password',
        'server_type',
        'is_active',
        'max_listeners',
        'location',
        'bandwidth_kbps',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get all relay broadcasts for this server.
     */
    public function broadcasts()
    {
        return $this->hasMany(RelayBroadcast::class);
    }

    /**
     * Check if server is online and accessible.
     */
    public function isOnline(): bool
    {
        return @fsockopen($this->hostname, $this->port, $errno, $errstr, 5) !== false;
    }

    /**
     * Get total active listeners across all broadcasts.
     */
    public function getTotalListeners(): int
    {
        return $this->broadcasts()
            ->where('is_active', true)
            ->sum('listeners');
    }
}
