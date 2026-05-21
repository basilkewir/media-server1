<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Channel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ChannelObserver
{
    public function created(Channel $channel): void
    {
        Log::info('Channel created', ['channel_id' => $channel->id, 'slug' => $channel->slug]);
        Cache::tags(['channels'])->flush();
    }

    public function updated(Channel $channel): void
    {
        Log::info('Channel updated', ['channel_id' => $channel->id, 'slug' => $channel->slug]);
        Cache::forget("channel:{$channel->slug}");
        Cache::tags(['channels'])->flush();
    }

    public function deleted(Channel $channel): void
    {
        Log::info('Channel deleted', ['channel_id' => $channel->id, 'slug' => $channel->slug]);

        // Clean up stream files
        $dir = storage_path("streams/{$channel->slug}");
        if (is_dir($dir)) {
            array_map('unlink', glob("{$dir}/*") ?: []);
            @rmdir($dir);
        }

        Cache::forget("channel:{$channel->slug}");
        Cache::tags(['channels'])->flush();
    }
}
