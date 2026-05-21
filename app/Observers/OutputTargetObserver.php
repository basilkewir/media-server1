<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\OutputTarget;
use Illuminate\Support\Facades\Log;

class OutputTargetObserver
{
    public function created(OutputTarget $target): void
    {
        Log::info('Output target created', [
            'target_id' => $target->id,
            'channel_id' => $target->channel_id,
            'protocol' => $target->output_protocol,
        ]);
    }

    public function updated(OutputTarget $target): void
    {
        if ($target->isDirty('status')) {
            Log::info('Output target status changed', [
                'target_id' => $target->id,
                'channel_id' => $target->channel_id,
                'old_status' => $target->getOriginal('status'),
                'new_status' => $target->status,
            ]);
        }
    }

    public function deleted(OutputTarget $target): void
    {
        Log::info('Output target deleted', [
            'target_id' => $target->id,
            'channel_id' => $target->channel_id,
        ]);
    }
}
