<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutputTargetLog extends Model
{
    protected $table = 'output_target_logs';

    protected $fillable = [
        'output_target_id', 'level', 'event', 'message', 'context',
    ];

    protected $casts = ['context' => 'json'];

    public function outputTarget(): BelongsTo
    {
        return $this->belongsTo(OutputTarget::class);
    }
}
