<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class VodFile extends Model
{
    protected $fillable = [
        'channel_id', 'title', 'filename', 'original_name',
        'mime_type', 'size_bytes', 'duration_seconds', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'size_bytes'       => 'integer',
        'duration_seconds' => 'integer',
        'sort_order'       => 'integer',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function url(): string
    {
        return Storage::disk('vod')->url($this->filename);
    }

    public function path(): string
    {
        return Storage::disk('vod')->path($this->filename);
    }

    public function formattedSize(): string
    {
        $bytes = $this->size_bytes;
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576)    return round($bytes / 1048576, 2) . ' MB';
        return round($bytes / 1024, 2) . ' KB';
    }

    public function formattedDuration(): string
    {
        if (!$this->duration_seconds) return '—';
        return gmdate('H:i:s', $this->duration_seconds);
    }
}
