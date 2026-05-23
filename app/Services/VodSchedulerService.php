<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Channel;
use App\Models\VodSchedule;
use App\Models\VodFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VodSchedulerService
{
    /**
     * Get the VOD files that should be playing right now based on schedule.
     * Returns the scheduled playlist if active, otherwise falls back to default ordering.
     */
    public function getActiveVodPlaylist(Channel $channel): array
    {
        $now = now();

        $activeSchedule = VodSchedule::where('channel_id', $channel->id)
            ->where('is_active', true)
            ->where('play_at', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
            })
            ->orderBy('play_at')
            ->first();

        if ($activeSchedule && $activeSchedule->override_default_playlist) {
            $vodFile = $activeSchedule->vodFile;
            if ($vodFile && $vodFile->is_active) {
                return [$vodFile];
            }
        }

        return $channel->vodFiles()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->all();
    }

    /**
     * Check and update the schedule for all active channels.
     * Called periodically from the stream:monitor command.
     */
    public function processSchedules(): void
    {
        $channels = Channel::where('is_active', true)->get();

        foreach ($channels as $channel) {
            $this->processChannelSchedule($channel);
        }
    }

    /**
     * If a schedule has changed, regenerate the VOD playlist.
     */
    public function processChannelSchedule(Channel $channel): void
    {
        $scheduledFiles = $this->getActiveVodPlaylist($channel);

        if (empty($scheduledFiles)) {
            return;
        }

        $vodController = app(\App\Http\Controllers\Admin\VodController::class);
        $vodController->generatePlaylist($channel);

        Log::debug('VOD schedule processed', [
            'channel' => $channel->slug,
            'files'   => count($scheduledFiles),
        ]);
    }

    /**
     * Get upcoming schedule entries for a channel.
     */
    public function getUpcoming(Channel $channel, int $limit = 10): array
    {
        return VodSchedule::where('channel_id', $channel->id)
            ->where('is_active', true)
            ->where('play_at', '>', now())
            ->with('vodFile')
            ->orderBy('play_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
