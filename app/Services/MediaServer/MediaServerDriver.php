<?php

namespace App\Services\MediaServer;

use App\Models\Channel;

interface MediaServerDriver
{
    /**
     * Start ingesting a stream. Must produce HLS at:
     * storage/streams/{slug}/playlist.m3u8
     */
    public function startIngest(Channel $channel, string $sourceUrl, bool $loop = false): void;

    public function stopIngest(Channel $channel): void;

    public function isRunning(Channel $channel): bool;

    /**
     * Push channel stream to an outbound URL.
     * Returns an opaque handle stored in output target metadata.
     */
    public function startOutput(Channel $channel, string $destUrl, array $options = []): string;

    public function stopOutput(Channel $channel, string $handle): void;

    public function getStats(Channel $channel): array;

    public function getName(): string;
}
