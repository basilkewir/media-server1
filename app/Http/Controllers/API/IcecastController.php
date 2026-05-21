<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Icecast\SetMaxListenersRequest;
use App\Models\Channel;
use App\Services\IcecastService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class IcecastController extends Controller
{
    use ApiResponse;

    public function __construct(protected IcecastService $icecastService) {}

    public function create(Channel $channel): JsonResponse
    {
        try {
            $result = $this->icecastService->createIcecastStream($channel);

            if (!$result['success']) {
                return $this->serverError(
                    $result['error'] ?? 'Unknown error',
                    'ICECAST_CREATE_FAILED'
                );
            }

            return $this->success(
                data: [
                    'channel_id' => $channel->id,
                    'mount_point' => $result['mount_point'],
                    'stream_url' => $result['stream_url'],
                    'push_url' => "icecast://{$result['mount_point']}",
                ],
                message: 'Icecast stream created successfully.',
                statusCode: 201
            );
        } catch (\Exception $e) {
            report($e);
            return $this->serverError('Failed to create Icecast stream: ' . $e->getMessage(), 'ICECAST_CREATE_FAILED');
        }
    }

    public function getStreamUrl(Channel $channel): JsonResponse
    {
        try {
            $streamUrl = $this->icecastService->getStreamUrl($channel);
            $mountPoint = $this->icecastService->getMountPoint($channel);

            if (!$mountPoint) {
                return $this->notFound('Icecast not configured for this channel.');
            }

            return $this->success(
                data: [
                    'stream_url' => $streamUrl,
                    'mount_point' => $mountPoint,
                ],
                message: 'Icecast stream URL retrieved successfully.'
            );
        } catch (\Exception $e) {
            report($e);
            return $this->serverError('Failed to get Icecast stream URL: ' . $e->getMessage());
        }
    }

    public function getStats(Channel $channel): JsonResponse
    {
        try {
            $stats = $this->icecastService->getStreamStats($channel);

            return $this->success(
                data: [
                    'channel_id' => $channel->id,
                    'listeners' => $stats['listeners'] ?? 0,
                    'bitrate_kbps' => $stats['bitrate'] ?? 0,
                    'connected' => $stats['connected'] ?? false,
                    'title' => $stats['title'] ?? $channel->name,
                ],
                message: 'Icecast stats retrieved successfully.'
            );
        } catch (\Exception $e) {
            report($e);
            return $this->serverError('Failed to get Icecast stats: ' . $e->getMessage());
        }
    }

    public function disconnect(Channel $channel): JsonResponse
    {
        try {
            $this->icecastService->disconnectStream($channel);

            return $this->success(message: 'Icecast stream disconnected successfully.');
        } catch (\Exception $e) {
            report($e);
            return $this->serverError('Failed to disconnect Icecast stream: ' . $e->getMessage());
        }
    }

    public function setMaxListeners(SetMaxListenersRequest $request, Channel $channel): JsonResponse
    {
        try {
            $this->icecastService->setMaxListeners($channel, $request->validated()['max_listeners']);

            return $this->success(
                data: ['max_listeners' => $request->validated()['max_listeners']],
                message: 'Max listeners updated successfully.'
            );
        } catch (\Exception $e) {
            report($e);
            return $this->serverError('Failed to set max listeners: ' . $e->getMessage());
        }
    }

    public function enable(Channel $channel): JsonResponse
    {
        try {
            $channel->update(['is_icecast_enabled' => true]);
            $this->icecastService->createIcecastStream($channel);

            return $this->success(
                data: ['id' => $channel->id, 'is_icecast_enabled' => true],
                message: 'Icecast enabled for channel successfully.'
            );
        } catch (\Exception $e) {
            report($e);
            return $this->serverError('Failed to enable Icecast: ' . $e->getMessage());
        }
    }

    public function disable(Channel $channel): JsonResponse
    {
        try {
            $this->icecastService->disconnectStream($channel);
            $channel->update(['is_icecast_enabled' => false]);

            return $this->success(message: 'Icecast disabled for channel successfully.');
        } catch (\Exception $e) {
            report($e);
            return $this->serverError('Failed to disable Icecast: ' . $e->getMessage());
        }
    }
}
