<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stream\ProbeRequest;
use App\Http\Requests\Stream\RecoverRequest;
use App\Http\Requests\Stream\StartRequest;
use App\Http\Requests\Stream\StopRequest;
use App\Http\Resources\StreamResource;
use App\Http\Resources\StreamStatisticResource;
use App\Models\Channel;
use App\Services\ProtocolDetector;
use App\Services\StreamingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StreamController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected StreamingService $streamingService,
        protected ProtocolDetector $protocol
    ) {}

    public function start(StartRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $channel = Channel::findOrFail($validated['channel_id']);
        $detectedProto = $this->protocol->label($validated['push_url']);

        try {
            if (isset($validated['rtmp_push_url'])) {
                $channel->update(['rtmp_push_url' => $validated['rtmp_push_url']]);
            }

            $stream = $this->streamingService->startStream($channel, $validated['push_url']);

            return $this->success(
                data: new StreamResource($stream),
                message: "Stream started ({$detectedProto})",
                statusCode: 201,
                meta: [
                    'input_protocol' => $detectedProto,
                    'hls_url' => url("/streams/{$channel->slug}/playlist.m3u8"),
                    'player_url' => url("/play/{$channel->slug}"),
                ]
            );
        } catch (\Exception $e) {
            report($e);
            return $this->serverError('Failed to start stream: ' . $e->getMessage(), 'STREAM_START_FAILED');
        }
    }

    public function stop(StopRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $channel = Channel::findOrFail($validated['channel_id']);

        try {
            $this->streamingService->stopStream($channel);
            return $this->success(message: 'Stream stopped successfully.');
        } catch (\Exception $e) {
            report($e);
            return $this->serverError('Failed to stop stream: ' . $e->getMessage(), 'STREAM_STOP_FAILED');
        }
    }

    public function status(Channel $channel): JsonResponse
    {
        return $this->success(
            data: $this->streamingService->getStreamStatus($channel),
            message: 'Stream status retrieved successfully.'
        );
    }

    public function fallback(Request $request, Channel $channel): JsonResponse
    {
        try {
            $stream = $this->streamingService->switchToVODFallback($channel);

            if (!$stream) {
                return $this->error(
                    message: 'No VOD playlist configured for this channel.',
                    statusCode: 400,
                    errorCode: 'NO_VOD_CONFIGURED'
                );
            }

            return $this->success(
                data: new StreamResource($stream),
                message: 'Switched to VOD fallback successfully.',
                meta: [
                    'input_protocol' => $this->protocol->label($channel->vod_playlist_url),
                    'hls_url' => url("/streams/{$channel->slug}/playlist.m3u8"),
                ]
            );
        } catch (\Exception $e) {
            report($e);
            return $this->serverError('Failed to switch to VOD fallback: ' . $e->getMessage(), 'VOD_FALLBACK_FAILED');
        }
    }

    public function recover(RecoverRequest $request, Channel $channel): JsonResponse
    {
        $validated = $request->validated();

        try {
            $stream = $this->streamingService->recoverFromFallback($channel, $validated['push_url']);

            return $this->success(
                data: new StreamResource($stream),
                message: 'Recovered to live stream successfully.',
                meta: [
                    'input_protocol' => $this->protocol->label($validated['push_url']),
                ]
            );
        } catch (\Exception $e) {
            report($e);
            return $this->serverError('Failed to recover from fallback: ' . $e->getMessage(), 'RECOVER_FAILED');
        }
    }

    public function setRtmpPush(Request $request, Channel $channel): JsonResponse
    {
        $validated = $request->validate([
            'rtmp_push_url' => 'nullable|string|stream_url|max:2048',
        ]);

        $channel->update(['rtmp_push_url' => $validated['rtmp_push_url']]);

        return $this->success(
            message: $validated['rtmp_push_url'] ? 'RTMP push target set.' : 'RTMP push target cleared.',
            data: ['rtmp_push_url' => $validated['rtmp_push_url']]
        );
    }

    public function probe(ProbeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $proto = $this->protocol->detect($validated['url']);
        $reachable = $this->protocol->isReachable($validated['url']);

        return $this->success(
            data: [
                'url' => $validated['url'],
                'protocol' => strtoupper($proto),
                'reachable' => $reachable,
            ],
            message: 'URL probed successfully.'
        );
    }

    public function statistics(Channel $channel): JsonResponse
    {
        $stream = $channel->activeStream();

        if (!$stream) {
            return $this->notFound('No active stream found for this channel.');
        }

        return $this->success(
            data: [
                'stream_id' => $stream->id,
                'channel_id' => $channel->id,
                'input_protocol' => $stream->input_protocol,
                'duration_seconds' => $stream->getDuration(),
                'uptime_percentage' => $stream->getUptimePercentage(),
                'statistics' => StreamStatisticResource::collection(
                    $stream->statistics()->latest()->limit(100)->get()
                ),
            ],
            message: 'Stream statistics retrieved successfully.'
        );
    }

    public function recent(Request $request, Channel $channel): JsonResponse
    {
        $streams = $channel->streams()
            ->latest('started_at')
            ->limit($request->integer('limit', 10))
            ->get();

        return $this->success(
            data: StreamResource::collection($streams),
            message: 'Recent streams retrieved successfully.'
        );
    }
}
