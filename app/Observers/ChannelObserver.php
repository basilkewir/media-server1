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
        $this->flushChannelCache($channel->slug);
    }

    public function updated(Channel $channel): void
    {
        Log::info('Channel updated', ['channel_id' => $channel->id, 'slug' => $channel->slug]);
        $this->flushChannelCache($channel->slug);
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

        $this->flushChannelCache($channel->slug);
    }

    private function flushChannelCache(string $slug): void
    {
        Cache::forget("channel:{$slug}");
        Cache::forget('channels:all');
        if (method_exists(Cache::getStore(), 'tags')) {
            Cache::tags(['channels'])->flush();
        }
    }
}
