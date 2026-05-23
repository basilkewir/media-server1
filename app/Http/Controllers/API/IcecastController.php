<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Icecast\SetMaxListenersRequest;
use App\Models\Channel;
use App\Services\AudioRelayService;
use App\Services\IcecastService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IcecastController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected IcecastService $icecastService,
        protected AudioRelayService $audioRelay,
    ) {}

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
            $this->audioRelay->stopAudioRelay($channel);
            $this->icecastService->disconnectStream($channel);
            $channel->update(['is_icecast_enabled' => false]);

            return $this->success(message: 'Icecast disabled for channel successfully.');
        } catch (\Exception $e) {
            report($e);
            return $this->serverError('Failed to disable Icecast: ' . $e->getMessage());
        }
    }

    // ─── Audio Relay API ──────────────────────────────────────────────────────

    public function startAudioRelay(Request $request, Channel $channel): JsonResponse
    {
        try {
            if ($request->has('audio_relay_target_url')) {
                $channel->audio_relay_target_url = $request->input('audio_relay_target_url');
            }
            if ($request->has('audio_source_url')) {
                $channel->audio_source_url = $request->input('audio_source_url');
            }
            if ($request->has('audio_relay_playlist_url')) {
                $channel->audio_relay_playlist_url = $request->input('audio_relay_playlist_url');
            }
            if ($request->has('bitrate_kbps')) {
                $channel->bitrate_kbps = (int) $request->input('bitrate_kbps');
            }
            $channel->audio_fallback_enabled = $request->boolean('audio_fallback_enabled', false);
            $channel->audio_relay_enabled = true;
            $channel->save();

            $pid = $this->audioRelay->startAudioRelay($channel);

            if ($pid) {
                return $this->success(
                    data: ['pid' => $pid, 'channel_id' => $channel->id],
                    message: 'Audio relay started.'
                );
            }

            return $this->serverError('Could not start audio relay. Check source/target URLs.');
        } catch (\Exception $e) {
            report($e);
            return $this->serverError('Failed to start audio relay: ' . $e->getMessage());
        }
    }

    public function stopAudioRelay(Channel $channel): JsonResponse
    {
        try {
            $this->audioRelay->stopAudioRelay($channel);
            $channel->update(['audio_relay_enabled' => false]);

            return $this->success(message: 'Audio relay stopped.');
        } catch (\Exception $e) {
            report($e);
            return $this->serverError('Failed to stop audio relay: ' . $e->getMessage());
        }
    }

    public function audioRelayStatus(Channel $channel): JsonResponse
    {
        try {
            $info = $this->audioRelay->getAudioRelayInfo($channel);

            return $this->success(data: $info, message: 'Audio relay status retrieved.');
        } catch (\Exception $e) {
            report($e);
            return $this->serverError('Failed to get audio relay status: ' . $e->getMessage());
        }
    }

    public function forwardToServer(Request $request, Channel $channel): JsonResponse
    {
        $request->validate([
            'relay_server_id' => 'required|exists:relay_servers,id',
            'mode'            => 'required|in:video,audio',
        ]);

        try {
            $server = \App\Models\RelayServer::findOrFail($request->input('relay_server_id'));

            if ($request->input('mode') === 'audio') {
                $pid = $this->audioRelay->relayAudioToServer($channel, $server);
            } else {
                $pid = $this->audioRelay->forwardStreamToServer($channel, $server);
            }

            if ($pid) {
                return $this->success(
                    data: ['pid' => $pid, 'server' => $server->name],
                    message: "Stream forwarded to {$server->name}."
                );
            }

            return $this->serverError('No source available. Start the channel stream first.');
        } catch (\Exception $e) {
            report($e);
            return $this->serverError('Forward failed: ' . $e->getMessage());
        }
    }
}
